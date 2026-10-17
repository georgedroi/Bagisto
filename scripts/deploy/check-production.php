<?php

use Illuminate\Contracts\Console\Kernel;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\User\Repositories\AdminRepository;

require __DIR__.'/../../vendor/autoload.php';

$app = require __DIR__.'/../../bootstrap/app.php';

try {
    $app->make(Kernel::class)->bootstrap();

    $checks = [
        'production environment' => $app->environment('production'),
        'debug disabled' => config('app.debug') === false,
        'HTTPS application URL' => config('app.url') === 'https://mansecretpleasure.com',
        'application key configured' => filled(config('app.key')),
        'secure session cookie' => config('session.secure') === true,
        'database session driver' => config('session.driver') === 'database',
        'database queue driver' => config('queue.default') === 'database',
        'queue retry window exceeds worker timeout' => (int) config('queue.connections.database.retry_after') > 60,
        'local MySQL application database' => config('database.default') === 'mysql'
            && config('database.connections.mysql.database') === 'mansecretpleasure'
            && config('database.connections.mysql.username') === 'mansecretpleasure',
        'existing administrator records' => app(AdminRepository::class)->count() > 0,
        'existing channel records' => app(ChannelRepository::class)->count() > 0,
        'QA products absent' => app(ProductRepository::class)->findWhere([['sku', 'like', 'MP-QA-%']])->isEmpty(),
        'Shop asset manifest exists' => is_file(public_path('themes/shop/default/build/manifest.json')),
        'Admin asset manifest exists' => is_file(public_path('themes/admin/default/build/manifest.json')),
        'production storage link' => is_link(public_path('storage'))
            && realpath(public_path('storage')) === realpath(storage_path('app/public')),
        'development HMR markers absent' => glob(public_path('*.hot')) === [],
    ];

    foreach (['fpx', 'tngewallet', 'creditdebitcards', 'duitnowqr_auto', 'duitnowqr_manual', 'duitnowqr', 'stripe'] as $method) {
        $checks['unverified payment method disabled: '.$method] = ! core()->getConfigData('sales.payment_methods.'.$method.'.active');
        $class = config('payment_methods.'.$method.'.class');
        $checks['unverified payment method unavailable: '.$method] = is_string($class)
            && ! app($class)->isAvailable();
    }

    foreach ($checks as $name => $passed) {
        fwrite(STDOUT, ($passed ? 'PASS ' : 'FAIL ').$name.PHP_EOL);
    }

    exit(in_array(false, $checks, true) ? 1 : 0);
} catch (Throwable) {
    fwrite(STDERR, 'Production readiness check failed; inspect application configuration privately.'.PHP_EOL);

    exit(1);
}
