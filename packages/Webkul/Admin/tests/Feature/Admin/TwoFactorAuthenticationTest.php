<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PragmaRX\Google2FA\Google2FA;
use Webkul\Admin\Mail\Admin\BackupCodesNotification;
use Webkul\User\Repositories\AdminRepository;

beforeEach(function () {
    $this->admin = $this->loginAsAdmin();

    $this->google2fa = new Google2FA;

    Mail::fake();
});

describe('two factor authentication setup endpoint', function () {
    it('allows an authenticated admin to access the 2FA setup endpoint', function () {
        $response = $this->postJson(route('admin.two_factor.setup'));
        $response->assertStatus(200)
            ->assertJsonStructure(['qrCodeSvg', 'qrCodeUrl']);
    });

    it('denies unauthenticated users from accessing 2FA setup', function () {
        auth('admin')->logout();
        $response = $this->postJson(route('admin.two_factor.setup'));
        $response->assertRedirect(route('admin.session.create'));
    });

    it('generates a secret key for a new admin on setup', function () {
        expect($this->admin->two_factor_secret)->toBeNull();
        $response = $this->postJson(route('admin.two_factor.setup'));
        $this->admin = app(AdminRepository::class)->findOrFail($this->admin->id);

        expect($this->admin->two_factor_secret)->not()->toBeNull();
    });

    it('returns the existing secret if the admin has already configured 2FA', function () {
        $originalSecret = $this->google2fa->generateSecretKey();

        $this->admin = app(AdminRepository::class)->update([
            'two_factor_secret' => encrypt($originalSecret),
        ], $this->admin->id);
        auth('admin')->setUser($this->admin);
        $response = $this->postJson(route('admin.two_factor.setup'));
        $response->assertStatus(200);

        $this->admin = app(AdminRepository::class)->findOrFail($this->admin->id);

        expect(decrypt($this->admin->two_factor_secret))->toBe($originalSecret);
    });

    it('does not expose the existing secret to a session that has not passed verification', function () {
        $this->admin = app(AdminRepository::class)->update([
            'two_factor_secret' => encrypt($this->google2fa->generateSecretKey()),
            'two_factor_enabled' => true,
            'two_factor_verified_at' => now(),
        ], $this->admin->id);
        auth('admin')->setUser($this->admin);
        $response = $this->postJson(route('admin.two_factor.setup'));
        $response->assertStatus(401);
    });
});

describe('two factor authentication enable endpoint', function () {
    beforeEach(function () {
        $this->secret = $this->google2fa->generateSecretKey();

        $this->admin = app(AdminRepository::class)->update([
            'two_factor_secret' => encrypt($this->secret),
        ], $this->admin->id);
        auth('admin')->setUser($this->admin);
    });

    it('allows an admin to enable 2FA with a valid code', function () {
        $validCode = $this->google2fa->getCurrentOtp($this->secret);
        $response = $this->post(route('admin.two_factor.enable'), [
            'code' => $validCode,
        ]);
        $response->assertOk()
            ->assertJsonStructure(['message', 'backup_codes']);

        $this->admin = app(AdminRepository::class)->findOrFail($this->admin->id);

        expect($this->admin->two_factor_enabled)->toBeTrue();
        expect($this->admin->two_factor_verified_at)->not()->toBeNull();

        Mail::assertQueued(BackupCodesNotification::class, fn ($mail) => $mail->hasTo($this->admin->email));
    });

    it('prevents an admin from enabling 2FA with an invalid code', function () {
        $response = $this->post(route('admin.two_factor.enable'), [
            'code' => '123456',
        ]);
        $response->assertStatus(422);

        $this->admin = app(AdminRepository::class)->findOrFail($this->admin->id);

        expect($this->admin->two_factor_enabled)->toBeFalse();
        expect($this->admin->two_factor_verified_at)->toBeNull();
        Mail::assertNotSent(BackupCodesNotification::class);
    });

    it('requires a 6-digit code to enable 2FA', function () {
        $response = $this->post(route('admin.two_factor.enable'), [
            'code' => '123',
        ]);
        $response->assertSessionHasErrors('code');
    });

    it('requires the code parameter when enabling 2FA', function () {
        $response = $this->post(route('admin.two_factor.enable'), []);
        $response->assertSessionHasErrors('code');
    });

    it('sets a session flag when 2FA is successfully enabled', function () {
        $validCode = $this->google2fa->getCurrentOtp($this->secret);
        $this->post(route('admin.two_factor.enable'), [
            'code' => $validCode,
        ]);
        expect(session('two_factor_passed'))->toBeTrue();
    });
});

describe('two factor authentication disable', function () {
    beforeEach(function () {
        $this->admin = app(AdminRepository::class)->update([
            'two_factor_secret' => encrypt($this->google2fa->generateSecretKey()),
            'two_factor_enabled' => true,
            'two_factor_backup_codes' => ['123456', '789012'],
            'two_factor_verified_at' => now(),
        ], $this->admin->id);
        auth('admin')->setUser($this->admin);
    });

    it('admin can disable 2FA', function () {
        $response = $this->withSession(['two_factor_passed' => true])
            ->post(route('admin.two_factor.disable'));
        $response->assertStatus(200)
            ->assertJson([
                'message' => trans('admin::app.account.messages.disabled-success'),
            ]);
        $this->admin = app(AdminRepository::class)->findOrFail($this->admin->id);

        expect($this->admin->two_factor_secret)->toBeNull();
        expect($this->admin->two_factor_enabled)->toBeFalse();
        expect($this->admin->two_factor_backup_codes)->toBeNull();
        expect($this->admin->two_factor_verified_at)->toBeNull();
    });

    it('unauthenticated user cannot disable 2FA', function () {
        auth('admin')->logout();
        $response = $this->post(route('admin.two_factor.disable'));
        $response->assertRedirect(route('admin.session.create'));
    });

    it('prevents a session that has not passed verification from disabling 2FA', function () {
        $response = $this->post(route('admin.two_factor.disable'));
        $response->assertRedirect(route('admin.two_factor.verify.form'));

        $this->admin = app(AdminRepository::class)->findOrFail($this->admin->id);

        expect($this->admin->two_factor_enabled)->toBeTrue();
        expect($this->admin->two_factor_secret)->not()->toBeNull();
    });
});

describe('two factor authentication login verification', function () {
    beforeEach(function () {
        $this->secret = $this->google2fa->generateSecretKey();

        $this->admin = app(AdminRepository::class)->update([
            'two_factor_secret' => encrypt($this->secret),
            'two_factor_enabled' => true,
            'two_factor_backup_codes' => [Hash::make('670089'), Hash::make('569097')],
            'two_factor_verified_at' => now(),
        ], $this->admin->id);
        auth('admin')->setUser($this->admin);
    });

    it('admin can verify with valid TOTP code', function () {
        $validCode = $this->google2fa->getCurrentOtp($this->secret);
        $response = $this->post(route('admin.two_factor.verify.store'), [
            'code' => $validCode,
        ]);
        $response->assertRedirect(route('admin.dashboard.index'));
        expect(session('two_factor_passed'))->toBeTrue();
    });

    it('admin can verify with valid backup code', function () {
        $response = $this->post(route('admin.two_factor.verify.store'), [
            'code' => '670089',
        ]);
        $response->assertRedirect(route('admin.dashboard.index'));
        expect(session('two_factor_passed'))->toBeTrue();

        $this->admin = app(AdminRepository::class)->findOrFail($this->admin->id);
        $remaining = $this->admin->two_factor_backup_codes;

        expect(collect($remaining)->contains(fn ($hash) => Hash::check('670089', $hash)))->toBeFalse();
        expect(collect($remaining)->contains(fn ($hash) => Hash::check('569097', $hash)))->toBeTrue();
    });

    it('verification fails with invalid code', function () {
        $response = $this->post(route('admin.two_factor.verify.store'), [
            'code' => '999999',
        ]);
        $response->assertRedirect()
            ->assertSessionHasErrors('code');

        expect(session('two_factor_passed'))->toBeNull();
    });

    it('verification requires 6-digit code', function () {
        $response = $this->post(route('admin.two_factor.verify.store'), [
            'code' => '123',
        ]);
        $response->assertSessionHasErrors('code');
    });

    it('verification requires code parameter', function () {
        $response = $this->post(route('admin.two_factor.verify.store'), []);
        $response->assertSessionHasErrors('code');
    });

    it('backup code can only be used once', function () {
        $this->post(route('admin.two_factor.verify.store'), [
            'code' => '670089',
        ]);

        $response = $this->post(route('admin.two_factor.verify.store'), [
            'code' => '670089',
        ]);
        $response->assertRedirect()
            ->assertSessionHasErrors('code');
    });
});

describe('two factor authentication backup codes email notifications', function () {
    it('sends backup codes email on successful 2FA enable', function () {
        $secret = $this->google2fa->generateSecretKey();

        $this->admin = app(AdminRepository::class)->update(['two_factor_secret' => encrypt($secret)], $this->admin->id);
        auth('admin')->setUser($this->admin);

        $validCode = $this->google2fa->getCurrentOtp($secret);
        $this->post(route('admin.two_factor.enable'), [
            'code' => $validCode,
        ]);
        $this->admin = app(AdminRepository::class)->findOrFail($this->admin->id);
        Mail::assertQueued(BackupCodesNotification::class, function ($mail) {
            return $mail->hasTo($this->admin->email) && ! empty($this->admin->two_factor_backup_codes);
        });
    });

    it('handles email failure gracefully', function () {
        Mail::shouldReceive('to->send')->andThrow(new Exception('Mail server error'));

        $secret = $this->google2fa->generateSecretKey();

        $this->admin = app(AdminRepository::class)->update(['two_factor_secret' => encrypt($secret)], $this->admin->id);
        auth('admin')->setUser($this->admin);

        $validCode = $this->google2fa->getCurrentOtp($secret);
        $response = $this->post(route('admin.two_factor.enable'), [
            'code' => $validCode,
        ]);
        $response->assertOk()
            ->assertJsonStructure(['message', 'backup_codes']);

        $this->admin = app(AdminRepository::class)->findOrFail($this->admin->id);

        expect($this->admin->two_factor_enabled)->toBeTrue();
    });
});

describe('two factor authentication integration flow', function () {
    it('complete 2FA setup and login flow', function () {
        $setupResponse = $this->postJson(route('admin.two_factor.setup'));
        $setupResponse->assertJsonStructure([
            'qrCodeSvg',
            'qrCodeUrl',
        ]);
        $this->admin = app(AdminRepository::class)->findOrFail($this->admin->id);

        $secret = decrypt($this->admin->two_factor_secret);

        $validCode = $this->google2fa->getCurrentOtp($secret);
        $enableResponse = $this->post(route('admin.two_factor.enable'), [
            'code' => $validCode,
        ]);
        $enableResponse->assertOk()
            ->assertJsonStructure(['message', 'backup_codes']);
        $this->admin = app(AdminRepository::class)->findOrFail($this->admin->id);

        expect($this->admin->two_factor_enabled)->toBeTrue();
        $newValidCode = $this->google2fa->getCurrentOtp($secret);
        $verifyResponse = $this->post(route('admin.two_factor.verify.store'), [
            'code' => $newValidCode,
        ]);
        $verifyResponse->assertRedirect(route('admin.dashboard.index'));

        expect(session('two_factor_passed'))->toBeTrue();
        $disableResponse = $this->post(route('admin.two_factor.disable'));

        $this->admin = app(AdminRepository::class)->findOrFail($this->admin->id);

        expect($this->admin->two_factor_enabled)->toBeFalse();
    });
});
