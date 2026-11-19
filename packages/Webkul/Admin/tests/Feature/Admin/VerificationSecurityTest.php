<?php

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use Webkul\Core\Repositories\CurrencyRepository;
use Webkul\Core\Repositories\ExchangeRateRepository;
use Webkul\User\Http\Middleware\Bouncer;
use Webkul\User\Repositories\AdminRepository;
use Webkul\User\Repositories\RoleRepository;

beforeEach(function () {
    $connection = config('database.default');

    if (
        $connection !== 'mysql'
        || config('database.connections.'.$connection.'.database') !== 'bagisto_admin_audit_tests_20261115_02'
        || storage_path() !== '/tmp/msp-admin-audit-tests/storage'
    ) {
        throw new RuntimeException('Verification security regressions require the isolated audit test wrapper');
    }

    Http::preventStrayRequests();
    Mail::fake();
    Notification::fake();

    $this->withoutMiddleware(ValidateCsrfToken::class);
});

/**
 * Create a synthetic account using repositories inside the test transaction.
 */
function verificationAccount(array $permissions = ['dashboard'], array $attributes = [], string $permissionType = 'custom'): array
{
    $identity = Str::uuid()->toString();
    $password = Str::random(40);
    $role = app(RoleRepository::class)->create([
        'name' => 'Verification '.$identity,
        'description' => 'Synthetic transactional security fixture',
        'permission_type' => $permissionType,
        'permissions' => $permissions,
    ]);
    $admin = app(AdminRepository::class)->create([
        'name' => 'Verification account',
        'email' => $identity.'@example.test',
        'password' => bcrypt($password),
        'role_id' => $role->id,
        'status' => 1,
        ...$attributes,
    ]);

    return [$admin, $password, $role];
}

it('omits second-factor authentication material from manageable user JSON', function () {
    [$manager] = verificationAccount(['dashboard', 'settings.users.edit']);
    [$target] = verificationAccount(['dashboard'], [
        'two_factor_secret' => encrypt(Str::random(32)),
        'two_factor_enabled' => true,
        'two_factor_backup_codes' => [bcrypt(Str::random(32))],
    ]);

    $this->actingAs($manager, 'admin');

    $response = $this->getJson(route('admin.settings.users.edit', $target->id))->assertOk();
    $keys = array_keys($response->json('user'));

    foreach (['two_factor_secret', 'two_factor_backup_codes', 'password', 'api_token', 'remember_token'] as $secretKey) {
        expect($keys)->not->toContain($secretKey);
    }

    expect($keys)->toContain('id', 'name', 'email', 'role_id', 'status');
});

it('refuses anonymous second-factor verification before processing the code', function (array $input) {
    $response = $this->postJson(route('admin.two_factor.verify.store'), $input);

    expect($response->status())->toBeIn([302, 401, 403]);

    if ($response->status() === 302) {
        $response->assertRedirect(route('admin.session.create'));
    }
})->with([
    'valid shape' => [['code' => '123456']],
    'missing code' => [[]],
    'malformed code' => [['code' => 'invalid']],
]);

it('throttles repeated failed logins and permits a valid login after the retry window', function () {
    [$admin, $password] = verificationAccount(['dashboard']);
    $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.61']);
    $this->freezeTime();

    foreach (range(1, 6) as $attempt) {
        $response = $this->post(route('admin.session.store'), [
            'email' => $admin->email,
            'password' => 'incorrect-synthetic-password',
        ]);

        expect(auth('admin')->check())->toBeFalse();
    }

    $response->assertTooManyRequests()->assertHeader('Retry-After');

    $this->travel(((int) $response->headers->get('Retry-After')) + 1)->seconds();

    $this->post(route('admin.session.store'), [
        'email' => $admin->email,
        'password' => $password,
    ])->assertRedirect(route('admin.dashboard.index'));

    expect(auth('admin')->id())->toBe($admin->id);
});

it('requires a new account to pass its own second factor after a session account switch', function () {
    [$first] = verificationAccount(['dashboard'], [
        'two_factor_secret' => encrypt(two_factor_authentication()->generateSecretKey()),
        'two_factor_enabled' => true,
    ]);
    [$second, $password] = verificationAccount(['dashboard'], [
        'two_factor_secret' => encrypt(two_factor_authentication()->generateSecretKey()),
        'two_factor_enabled' => true,
    ]);

    $this->actingAs($first, 'admin')->withSession(['two_factor_passed' => true]);

    $this->post(route('admin.session.store'), [
        'email' => $second->email,
        'password' => $password,
    ])->assertRedirect();

    expect(auth('admin')->id())->toBe($second->id);

    $this->get(route('admin.account.edit'))->assertRedirect(route('admin.two_factor.verify.form'));

    $this->post(route('admin.two_factor.disable'))->assertRedirect(route('admin.two_factor.verify.form'));

    expect(app(AdminRepository::class)->findOrFail($second->id)->two_factor_enabled)->toBeTrue();
});

it('permits protected access only after the current account verifies its own second factor', function () {
    $secret = two_factor_authentication()->generateSecretKey();
    [$admin, $password] = verificationAccount(['dashboard'], [
        'two_factor_secret' => encrypt($secret),
        'two_factor_enabled' => true,
    ]);

    $this->post(route('admin.session.store'), [
        'email' => $admin->email,
        'password' => $password,
    ])->assertRedirect();

    $this->get(route('admin.account.edit'))->assertRedirect(route('admin.two_factor.verify.form'));

    $this->post(route('admin.two_factor.verify.store'), [
        'code' => (new Google2FA)->getCurrentOtp($secret),
    ])->assertRedirect();

    $this->get(route('admin.account.edit'))->assertOk();
});

it('refuses second-factor setup for an existing disabled session before creating a secret', function () {
    [$admin] = verificationAccount(['dashboard'], ['status' => 0]);

    $this->actingAs($admin, 'admin');

    $response = $this->postJson(route('admin.two_factor.setup'));

    expect($response->status())->toBeIn([302, 401, 403]);
    expect(app(AdminRepository::class)->findOrFail($admin->id)->two_factor_secret)->toBeNull();
});

it('refuses second-factor setup for an existing empty-permission session', function () {
    [$admin] = verificationAccount([]);

    $this->actingAs($admin, 'admin');

    $response = $this->postJson(route('admin.two_factor.setup'));

    expect($response->status())->toBeIn([302, 401, 403]);
    expect(app(AdminRepository::class)->findOrFail($admin->id)->two_factor_secret)->toBeNull();
});

it('keeps the post-login destination on the admin origin after an external admin referer', function (string $entryRoute) {
    [$admin, $password] = verificationAccount(['dashboard']);

    $this->withHeader('Referer', 'https://example.invalid/admin')->get(route($entryRoute))->assertOk();

    $response = $this->post(route('admin.session.store'), [
        'email' => $admin->email,
        'password' => $password,
    ])->assertRedirect();

    $location = $response->headers->get('Location');

    expect(parse_url($location, PHP_URL_HOST))->toBe(parse_url(route('admin.dashboard.index'), PHP_URL_HOST));
    expect(parse_url($location, PHP_URL_PATH))->toStartWith('/admin/');
})->with([
    'login' => ['admin.session.create'],
    'password recovery' => ['admin.forget_password.create'],
]);

it('refuses anonymous direct access to protected admin records and actions', function (string $route, array $parameters, string $method) {
    $this->call($method, route($route, $parameters))->assertRedirect(route('admin.session.create'));
})->with([
    'account' => ['admin.account.edit', [], 'GET'],
    'native products' => ['admin.catalog.products.index', [], 'GET'],
    'product entry' => ['admin.catalog.product_entry.index', [], 'GET'],
    'private receipt' => ['admin.sales.receipts.view', ['id' => 999999], 'GET'],
    'receipt approval' => ['admin.sales.receipts.approve', ['id' => 999999], 'POST'],
    'workflow update' => ['admin.sales.orders.workflow.update', ['orderId' => 999999], 'POST'],
    'private AWB' => ['admin.sales.orders.awb.image', ['orderId' => 999999], 'GET'],
    'media' => ['admin.media.index', [], 'GET'],
    'configuration' => ['admin.configuration.index', [], 'GET'],
    'updater' => ['admin.settings.system_updates.index', [], 'GET'],
]);

it('keeps the inactive login page builder unreachable, anonymous or signed in', function () {
    $base = '/'.config('app.admin_url').'/appearance/login-page-builder';

    expect(Route::has('admin.appearance.login_page_builder.index'))->toBeFalse();
    $this->get($base)->assertNotFound();
    expect($this->withoutMiddleware(ValidateCsrfToken::class)->post($base.'/publish')->status())->toBeIn([404, 405]);

    [$admin] = verificationAccount(['dashboard']);

    $this->actingAs($admin, 'admin')->get($base)->assertNotFound();
    expect($this->post($base.'/save', ['html' => '', 'css' => ''])->status())->toBeIn([404, 405]);
});

it('refuses every protected named non-shared endpoint for a dashboard-only role', function () {
    $this->withoutMiddleware(ThrottleRequests::class);
    [$admin] = verificationAccount(['dashboard']);
    $this->actingAs($admin, 'admin');
    $acl = acl()->getRoles();
    $checked = 0;
    $unrefused = [];

    foreach (app('router')->getRoutes() as $route) {
        $name = $route->getName();

        if (
            ! $name
            || ! str_starts_with($name, 'admin.')
            || ! in_array('admin', $route->gatherMiddleware(), true)
            || in_array($name, Bouncer::UNRESTRICTED_ROUTES, true)
            || in_array($name, ['admin.two_factor.setup', 'admin.two_factor.verify.form', 'admin.two_factor.verify.store', 'admin.session.destroy'], true)
            || ($acl[$name] ?? null) === 'dashboard'
        ) {
            continue;
        }

        $parameters = [];

        foreach ($route->parameterNames() as $parameter) {
            $pattern = $route->wheres[$parameter] ?? null;
            $parameters[$parameter] = $pattern && ! preg_match('/^'.$pattern.'$/', '999999')
                ? Str::uuid()->toString()
                : 999999;
        }

        $response = $this->call($route->methods()[0], route($name, $parameters));

        if (! in_array($response->status(), [401, 403], true)) {
            $unrefused[$name] = $response->status();
        }

        $checked++;
    }

    expect($unrefused)->toBe([]);
    expect($checked)->toBeGreaterThan(300);
});

it('refuses unnamed catalog sync for a role without catalog access', function () {
    [$admin] = verificationAccount(['dashboard']);
    $this->actingAs($admin, 'admin');

    $this->get('/admin/catalog/sync')->assertUnauthorized();
});

it('refuses updater access to a full administrator outside the configured owner list', function () {
    [$admin] = verificationAccount([], [], 'all');
    config(['manpleasure-updates.owner_ids' => [-1]]);
    $this->actingAs($admin, 'admin');

    $this->get(route('admin.settings.system_updates.index'))->assertForbidden();
});

it('revokes normal protected access for an inactive or empty-permission account', function (array $permissions, int $status) {
    [$admin] = verificationAccount($permissions, ['status' => $status]);
    $this->actingAs($admin, 'admin');

    $this->get(route('admin.account.edit'))->assertRedirect(route('admin.session.create'));
    expect(auth('admin')->check())->toBeFalse();
})->with([
    'inactive' => [['dashboard'], 0],
    'empty permissions' => [[], 1],
]);

it('logs out and clears the passed second factor before subsequent protected requests', function () {
    [$admin] = verificationAccount(['dashboard']);
    $this->actingAs($admin, 'admin')->withSession(['two_factor_passed' => true]);

    $this->delete(route('admin.session.destroy'))->assertRedirect(route('admin.session.create'));

    expect(auth('admin')->check())->toBeFalse();
    expect(session('two_factor_passed'))->toBeNull();
    $this->get(route('admin.account.edit'))->assertRedirect(route('admin.session.create'));
});

it('declares state-changing admin actions only on protected write methods', function (string $route) {
    expect(app('router')->getRoutes()->getByName($route)->methods())->not->toContain('GET');
})->with([
    'order notification read' => ['admin.notification.viewed_notification'],
    'order reorder' => ['admin.sales.orders.reorder'],
    'exchange provider update' => ['admin.settings.exchange_rates.update_rates'],
    'import validate' => ['admin.settings.data_transfer.imports.validate'],
    'import validate chunk' => ['admin.settings.data_transfer.imports.validate_chunk'],
    'import queued validation' => ['admin.settings.data_transfer.imports.validate_queued'],
    'import validation finalization' => ['admin.settings.data_transfer.imports.validate_status'],
    'import image download' => ['admin.settings.data_transfer.imports.download_images'],
    'import queued image download' => ['admin.settings.data_transfer.imports.download_images_queued'],
    'import start' => ['admin.settings.data_transfer.imports.start'],
    'import link' => ['admin.settings.data_transfer.imports.link'],
    'import index' => ['admin.settings.data_transfer.imports.index_data'],
    'second-factor secret creation' => ['admin.two_factor.setup'],
]);

it('allows a full administrator whose role stores null permissions', function () {
    [$admin, , $role] = verificationAccount([], [], 'all');
    app(RoleRepository::class)->update(['permissions' => null], $role->id);

    expect(app(AdminRepository::class)->findOrFail($admin->id)->hasPermission('catalog.products'))->toBeTrue();
});

it('refuses permission checks when the admin role is missing', function () {
    [$admin] = verificationAccount(['dashboard']);
    $admin->setRelation('role', null);

    expect($admin->hasPermission('dashboard'))->toBeFalse();
});

it('lets a role granted native product creation open the native create page', function () {
    [$admin] = verificationAccount(['dashboard', 'catalog', 'catalog.products', 'catalog.products.create']);
    $this->actingAs($admin, 'admin');

    expect($this->get(route('admin.catalog.products.create'))->status())->not->toBeIn([401, 403]);
});

it('refuses the native product create page to a role without product creation', function () {
    [$admin] = verificationAccount(['dashboard', 'catalog', 'catalog.products']);
    $this->actingAs($admin, 'admin');

    expect($this->get(route('admin.catalog.products.create'))->status())->toBeIn([401, 403]);
});

it('refuses non-positive or unknown-currency exchange rates without persisting them', function (string $rate, bool $knownCurrency) {
    [$admin] = verificationAccount(['dashboard', 'settings', 'settings.exchange_rates', 'settings.exchange_rates.create']);
    $currency = app(CurrencyRepository::class)->create([
        'code' => strtoupper(Str::random(3)),
        'name' => 'Verification currency',
        'symbol' => 'V',
    ]);
    $before = app(ExchangeRateRepository::class)->count();
    $this->actingAs($admin, 'admin');

    $this->postJson(route('admin.settings.exchange_rates.store'), [
        'target_currency' => $knownCurrency ? $currency->id : 999999,
        'rate' => $rate,
    ])->assertUnprocessable();

    expect(app(ExchangeRateRepository::class)->count())->toBe($before);
})->with([
    'zero rate' => ['0', true],
    'negative rate' => ['-1.5', true],
    'unknown currency' => ['1.25', false],
]);

it('accepts one positive exchange rate for a known currency and refuses a duplicate', function () {
    [$admin] = verificationAccount(['dashboard', 'settings', 'settings.exchange_rates', 'settings.exchange_rates.create']);
    $currency = app(CurrencyRepository::class)->create([
        'code' => strtoupper(Str::random(3)),
        'name' => 'Verification currency',
        'symbol' => 'V',
    ]);
    $this->actingAs($admin, 'admin');
    $input = ['target_currency' => $currency->id, 'rate' => '1.25'];

    $this->postJson(route('admin.settings.exchange_rates.store'), $input)->assertOk();
    $this->postJson(route('admin.settings.exchange_rates.store'), $input)->assertUnprocessable();
});

it('refuses invalid admin user status values and a missing password on create', function (array $input) {
    [$manager] = verificationAccount(['dashboard', 'settings', 'settings.users', 'settings.users.create']);
    [, , $grantable] = verificationAccount(['dashboard']);
    $email = Str::uuid()->toString().'@example.test';
    $this->actingAs($manager, 'admin');

    $this->postJson(route('admin.settings.users.store'), [
        'name' => 'Verification created account',
        'email' => $email,
        'role_id' => $grantable->id,
        ...$input,
    ])->assertUnprocessable();

    expect(app(AdminRepository::class)->findOneByField('email', $email))->toBeNull();
})->with([
    'non-boolean status' => [['status' => 'invalid', 'password' => 'Synthetic-pass-123', 'password_confirmation' => 'Synthetic-pass-123']],
    'negative status' => [['status' => -1, 'password' => 'Synthetic-pass-123', 'password_confirmation' => 'Synthetic-pass-123']],
    'missing password' => [['status' => 1]],
]);

it('keeps the existing password and an explicit inactive status on admin user update', function () {
    [$manager] = verificationAccount(['dashboard', 'settings', 'settings.users', 'settings.users.edit']);
    [$target, $password, $role] = verificationAccount(['dashboard']);
    $hash = $target->password;
    $this->actingAs($manager, 'admin');

    $this->putJson(route('admin.settings.users.update'), [
        'id' => $target->id,
        'name' => $target->name,
        'email' => $target->email,
        'role_id' => $role->id,
        'password' => '',
        'password_confirmation' => '',
        'status' => 0,
    ])->assertOk();

    $updated = app(AdminRepository::class)->findOrFail($target->id);

    expect($updated->password)->toBe($hash);
    expect((bool) $updated->status)->toBeFalse();
});

it('refuses updater write actions to a dashboard-only role before rate limiting them', function () {
    [$admin] = verificationAccount(['dashboard']);
    $this->actingAs($admin, 'admin');

    foreach (['check', 'install', 'rollback'] as $action) {
        foreach (range(1, 3) as $attempt) {
            expect($this->postJson(route('admin.settings.system_updates.'.$action))->status())->toBeIn([401, 403]);
        }
    }
});

/**
 * Sales invoice, shipment and refund routes with their runtime ACL keys and request methods.
 */
function verificationSalesRoutes(): array
{
    return [
        'invoice index' => ['admin.sales.invoices.index', 'sales.invoices', 'GET', []],
        'invoice view' => ['admin.sales.invoices.view', 'sales.invoices.view', 'GET', ['id' => 999999]],
        'invoice print' => ['admin.sales.invoices.print', 'sales.invoices.view', 'GET', ['id' => 999999]],
        'invoice duplicate email' => ['admin.sales.invoices.send_duplicate_email', 'sales.invoices.view', 'POST', ['id' => 999999]],
        'invoice store' => ['admin.sales.invoices.store', 'sales.invoices.create', 'POST', ['order_id' => 999999]],
        'invoice state mass update' => ['admin.sales.invoices.mass_update.state', 'sales.invoices.update', 'POST', []],
        'shipment index' => ['admin.sales.shipments.index', 'sales.shipments', 'GET', []],
        'shipment view' => ['admin.sales.shipments.view', 'sales.shipments.view', 'GET', ['id' => 999999]],
        'shipment store' => ['admin.sales.shipments.store', 'sales.shipments.create', 'POST', ['order_id' => 999999]],
        'refund index' => ['admin.sales.refunds.index', 'sales.refunds', 'GET', []],
        'refund view' => ['admin.sales.refunds.view', 'sales.refunds.view', 'GET', ['id' => 999999]],
        'refund store' => ['admin.sales.refunds.store', 'sales.refunds.create', 'POST', ['order_id' => 999999]],
        'refund totals' => ['admin.sales.refunds.update_totals', 'sales.refunds.create', 'POST', ['order_id' => 999999]],
        'workspace refunds' => ['admin.seller_workspace.refunds', 'sales.refunds.workspace', 'GET', []],
        'EU withdrawal refunded' => ['admin.sales.eu-withdrawals.mark_refunded', 'sales.eu_withdrawals.mark_refunded', 'POST', ['id' => 999999]],
    ];
}

it('maps each sales document route to its expected ACL key at runtime', function (string $route, string $key) {
    expect(acl()->getRoles()[$route] ?? null)->toBe($key);
})->with(verificationSalesRoutes());

it('redirects anonymous sales document requests to admin login', function (string $route, string $key, string $method, array $parameters) {
    $this->call($method, route($route, $parameters))->assertRedirect(route('admin.session.create'));
})->with(verificationSalesRoutes());

it('refuses a sales document route to a role holding every other sales document permission', function (string $route, string $key, string $method, array $parameters) {
    $others = array_values(array_unique(array_filter(
        array_column(verificationSalesRoutes(), 1),
        fn ($permission) => $permission !== $key
    )));
    [$admin] = verificationAccount(['dashboard', 'sales', ...$others]);
    $this->actingAs($admin, 'admin');

    expect($this->call($method, route($route, $parameters))->status())->toBeIn([401, 403]);
})->with(verificationSalesRoutes());

it('passes ACL for a role holding only the mapped sales document permission', function (string $route, string $key, string $method, array $parameters) {
    [$admin] = verificationAccount(['dashboard', $key]);
    $this->actingAs($admin, 'admin');

    expect($this->call($method, route($route, $parameters))->status())->not->toBeIn([401, 403, 500]);
})->with(verificationSalesRoutes());
