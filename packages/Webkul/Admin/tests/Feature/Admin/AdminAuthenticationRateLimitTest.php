<?php

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use Webkul\User\Repositories\AdminRepository;
use Webkul\User\Repositories\RoleRepository;

beforeEach(function () {
    Mail::fake();

    $this->withoutMiddleware(ValidateCsrfToken::class);
    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10']);
    $this->freezeTime();
});

afterEach(function () {
    TrustProxies::flushState();
});

/**
 * Create a synthetic administrator with a known password inside the test transaction.
 */
function rateLimitAdmin(array $attributes = []): array
{
    $identity = Str::uuid()->toString();
    $password = Str::random(40);
    $role = app(RoleRepository::class)->create([
        'name' => 'Rate limit '.$identity,
        'description' => 'Synthetic rate limit fixture',
        'permission_type' => 'custom',
        'permissions' => ['dashboard'],
    ]);
    $admin = app(AdminRepository::class)->create([
        'name' => 'Rate limit account',
        'email' => $identity.'@example.test',
        'password' => bcrypt($password),
        'role_id' => $role->id,
        'status' => 1,
        ...$attributes,
    ]);

    return [$admin, $password];
}

/**
 * Create a synthetic administrator who must pass a second factor after the password.
 */
function rateLimitTwoFactorAdmin(): array
{
    $secret = two_factor_authentication()->generateSecretKey();
    [$admin, $password] = rateLimitAdmin(['two_factor_secret' => encrypt($secret), 'two_factor_enabled' => true]);

    return [$admin, $password, $secret];
}

/**
 * A six-digit code that the given secret does not accept now.
 */
function rateLimitWrongCode(string $secret): string
{
    $valid = (new Google2FA)->getCurrentOtp($secret);

    return $valid === '000000' ? '111111' : '000000';
}

it('lets an administrator who mistyped the password four times still sign in and pass the second factor', function () {
    [$admin, $password, $secret] = rateLimitTwoFactorAdmin();

    foreach (range(1, 4) as $attempt) {
        $this->post(route('admin.session.store'), ['email' => $admin->email, 'password' => 'wrong-'.$attempt])
            ->assertRedirect();
    }

    $this->post(route('admin.session.store'), ['email' => $admin->email, 'password' => $password])
        ->assertRedirect(route('admin.dashboard.index'));

    $this->post(route('admin.two_factor.verify.store'), ['code' => (new Google2FA)->getCurrentOtp($secret)])
        ->assertRedirect(route('admin.dashboard.index'));

    expect(auth('admin')->id())->toBe($admin->id)
        ->and(session('two_factor_passed'))->toBeTrue();

    $this->get(route('admin.account.edit'))->assertOk();
});

it('refuses repeated wrong passwords with 429, even when the right password follows', function () {
    [$admin, $password] = rateLimitAdmin();

    foreach (range(1, 5) as $attempt) {
        $this->post(route('admin.session.store'), ['email' => $admin->email, 'password' => 'wrong-'.$attempt])
            ->assertRedirect();
    }

    $this->post(route('admin.session.store'), ['email' => $admin->email, 'password' => 'wrong-6'])
        ->assertTooManyRequests()
        ->assertHeader('Retry-After');

    $this->post(route('admin.session.store'), ['email' => Str::upper(' '.$admin->email.' '), 'password' => $password])
        ->assertTooManyRequests();

    expect(auth('admin')->check())->toBeFalse();
});

it('refuses repeated wrong second-factor codes with 429, even when the right code follows', function () {
    [$admin, $password, $secret] = rateLimitTwoFactorAdmin();

    $this->post(route('admin.session.store'), ['email' => $admin->email, 'password' => $password])->assertRedirect();

    foreach (range(1, 5) as $attempt) {
        $this->post(route('admin.two_factor.verify.store'), ['code' => rateLimitWrongCode($secret)])
            ->assertSessionHasErrors('code');
    }

    $this->post(route('admin.two_factor.verify.store'), ['code' => (new Google2FA)->getCurrentOtp($secret)])
        ->assertTooManyRequests()
        ->assertHeader('Retry-After');

    expect(session('two_factor_passed'))->toBeNull();

    $this->get(route('admin.account.edit'))->assertRedirect(route('admin.two_factor.verify.form'));
});

it('refuses repeated wrong codes when enabling the second factor with 429', function () {
    $secret = two_factor_authentication()->generateSecretKey();
    [$admin] = rateLimitAdmin(['two_factor_secret' => encrypt($secret)]);

    $this->actingAs($admin, 'admin');

    foreach (range(1, 5) as $attempt) {
        $this->postJson(route('admin.two_factor.enable'), ['code' => rateLimitWrongCode($secret)])
            ->assertUnprocessable();
    }

    $this->postJson(route('admin.two_factor.enable'), ['code' => (new Google2FA)->getCurrentOtp($secret)])
        ->assertTooManyRequests();

    expect(app(AdminRepository::class)->findOrFail($admin->id)->two_factor_enabled)->toBeFalsy();
});

it('does not let one locked account lock another account signing in from the same address', function () {
    [$locked] = rateLimitAdmin();
    [$other, $password] = rateLimitAdmin();

    foreach (range(1, 6) as $attempt) {
        $response = $this->post(route('admin.session.store'), ['email' => $locked->email, 'password' => 'wrong-'.$attempt]);
    }

    $response->assertTooManyRequests();

    $this->post(route('admin.session.store'), ['email' => $other->email, 'password' => $password])
        ->assertRedirect(route('admin.dashboard.index'));

    expect(auth('admin')->id())->toBe($other->id);
});

it('does not let one account locked at the second factor lock another account at the second factor', function () {
    [$first, $firstPassword, $firstSecret] = rateLimitTwoFactorAdmin();
    [$second, $secondPassword, $secondSecret] = rateLimitTwoFactorAdmin();

    $this->post(route('admin.session.store'), ['email' => $first->email, 'password' => $firstPassword])->assertRedirect();

    foreach (range(1, 6) as $attempt) {
        $response = $this->post(route('admin.two_factor.verify.store'), ['code' => rateLimitWrongCode($firstSecret)]);
    }

    $response->assertTooManyRequests();

    $this->post(route('admin.session.store'), ['email' => $second->email, 'password' => $secondPassword])->assertRedirect();

    expect(auth('admin')->id())->toBe($second->id);

    $this->post(route('admin.two_factor.verify.store'), ['code' => (new Google2FA)->getCurrentOtp($secondSecret)])
        ->assertRedirect(route('admin.dashboard.index'));

    expect(session('two_factor_passed'))->toBeTrue();
});

it('caps sign-in attempts from one address across many accounts', function () {
    foreach (range(1, 20) as $attempt) {
        $this->post(route('admin.session.store'), ['email' => 'unknown-'.$attempt.'@example.test', 'password' => 'wrong'])
            ->assertRedirect();
    }

    $this->post(route('admin.session.store'), ['email' => 'unknown-21@example.test', 'password' => 'wrong'])
        ->assertTooManyRequests();
});

it('ignores a spoofed X-Forwarded-For header from an untrusted client', function () {
    [$admin] = rateLimitAdmin();

    foreach (range(1, 5) as $attempt) {
        $this->withHeader('X-Forwarded-For', '198.51.100.'.$attempt)
            ->post(route('admin.session.store'), ['email' => $admin->email, 'password' => 'wrong-'.$attempt])
            ->assertRedirect();
    }

    $this->withHeader('X-Forwarded-For', '198.51.100.99')
        ->post(route('admin.session.store'), ['email' => $admin->email, 'password' => 'wrong-6'])
        ->assertTooManyRequests();

    foreach (range(6, 20) as $attempt) {
        $this->withHeader('X-Forwarded-For', '198.51.100.'.$attempt)
            ->post(route('admin.session.store'), ['email' => 'spoof-'.$attempt.'@example.test', 'password' => 'wrong'])
            ->assertRedirect();
    }

    $this->withHeader('X-Forwarded-For', '198.51.100.21')
        ->post(route('admin.session.store'), ['email' => 'spoof-21@example.test', 'password' => 'wrong'])
        ->assertTooManyRequests();
});

it('keys sign-in attempts by the forwarded client address only behind a configured trusted proxy', function () {
    TrustProxies::at(['203.0.113.10']);
    [$admin, $password] = rateLimitAdmin();

    foreach (range(1, 6) as $attempt) {
        $response = $this->withHeader('X-Forwarded-For', '198.51.100.1')
            ->post(route('admin.session.store'), ['email' => $admin->email, 'password' => 'wrong-'.$attempt]);
    }

    $response->assertTooManyRequests();

    $this->withHeader('X-Forwarded-For', '198.51.100.2')
        ->post(route('admin.session.store'), ['email' => $admin->email, 'password' => $password])
        ->assertRedirect(route('admin.dashboard.index'));
});

it('permits a correct sign-in and second factor again after the cooldown', function () {
    [$admin, $password, $secret] = rateLimitTwoFactorAdmin();

    foreach (range(1, 6) as $attempt) {
        $response = $this->post(route('admin.session.store'), ['email' => $admin->email, 'password' => 'wrong-'.$attempt]);
    }

    $response->assertTooManyRequests();

    $this->travel(((int) $response->headers->get('Retry-After')) + 1)->seconds();

    $this->post(route('admin.session.store'), ['email' => $admin->email, 'password' => $password])
        ->assertRedirect(route('admin.dashboard.index'));

    foreach (range(1, 6) as $attempt) {
        $response = $this->post(route('admin.two_factor.verify.store'), ['code' => rateLimitWrongCode($secret)]);
    }

    $response->assertTooManyRequests();

    $this->travel(((int) $response->headers->get('Retry-After')) + 1)->seconds();

    $this->post(route('admin.two_factor.verify.store'), ['code' => (new Google2FA)->getCurrentOtp($secret)])
        ->assertRedirect(route('admin.dashboard.index'));

    expect(session('two_factor_passed'))->toBeTrue();
});

it('caps second-factor guesses for one account even when they come from many addresses', function () {
    [$admin, $password, $secret] = rateLimitTwoFactorAdmin();

    $this->post(route('admin.session.store'), ['email' => $admin->email, 'password' => $password])->assertRedirect();

    foreach (range(1, 5) as $address) {
        foreach (range(1, 2) as $attempt) {
            $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.'.$address])
                ->post(route('admin.two_factor.verify.store'), ['code' => rateLimitWrongCode($secret)])
                ->assertSessionHasErrors('code');
        }
    }

    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.99'])
        ->post(route('admin.two_factor.verify.store'), ['code' => (new Google2FA)->getCurrentOtp($secret)])
        ->assertTooManyRequests();

    expect(session('two_factor_passed'))->toBeNull();
});

it('caps second-factor guesses for one account per hour across addresses and minutes', function () {
    [$admin, $password, $secret] = rateLimitTwoFactorAdmin();

    $this->post(route('admin.session.store'), ['email' => $admin->email, 'password' => $password])->assertRedirect();

    foreach (range(1, 3) as $minute) {
        foreach (range(1, 10) as $attempt) {
            $this->withServerVariables(['REMOTE_ADDR' => '198.51.'.$minute.'.'.$attempt])
                ->post(route('admin.two_factor.verify.store'), ['code' => rateLimitWrongCode($secret)])
                ->assertSessionHasErrors('code');
        }

        $this->travel(61)->seconds();
    }

    $response = $this->withServerVariables(['REMOTE_ADDR' => '198.51.9.9'])
        ->post(route('admin.two_factor.verify.store'), ['code' => (new Google2FA)->getCurrentOtp($secret)]);

    $response->assertTooManyRequests();

    expect((int) $response->headers->get('Retry-After'))->toBeGreaterThan(60);
});

it('lets many administrators of one office sign in from the same address within a minute', function () {
    $accounts = array_map(fn () => rateLimitAdmin(), range(1, 15));

    foreach ($accounts as [$admin, $password]) {
        $this->post(route('admin.session.store'), ['email' => $admin->email, 'password' => $password])
            ->assertRedirect(route('admin.dashboard.index'));

        auth('admin')->logout();
    }
});

it('keeps the password step per account and address, so strangers elsewhere cannot lock the owner out', function () {
    [$admin, $password] = rateLimitAdmin();

    foreach (range(1, 10) as $address) {
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.'.$address])
            ->post(route('admin.session.store'), ['email' => $admin->email, 'password' => 'wrong-'.$address])
            ->assertRedirect();
    }

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
        ->post(route('admin.session.store'), ['email' => $admin->email, 'password' => $password])
        ->assertRedirect(route('admin.dashboard.index'));
});
