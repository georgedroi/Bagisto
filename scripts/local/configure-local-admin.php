<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;
use Webkul\User\Repositories\AdminRepository;

require __DIR__.'/../../vendor/autoload.php';

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (! $app->environment('local') || config('database.connections.mysql.database') !== 'bagisto_local') {
    throw new RuntimeException('This helper is limited to the dedicated local Bagisto installation.');
}

$credentials = json_decode(file_get_contents($app->basePath('.local/admin-credentials.json')), true, flags: JSON_THROW_ON_ERROR);

if (! filter_var($credentials['email'] ?? '', FILTER_VALIDATE_EMAIL) || strlen($credentials['password'] ?? '') < 32 || empty($credentials['name'])) {
    throw new RuntimeException('The local administrator credentials are incomplete.');
}

$repository = $app->make(AdminRepository::class);

if ($repository->count() !== 1 || ! $repository->find(1)) {
    throw new RuntimeException('Use this helper only immediately after installing a fresh store.');
}

$repository->update([
    'name' => $credentials['name'],
    'email' => $credentials['email'],
    'password' => Hash::make($credentials['password']),
], 1);

if (! Hash::check($credentials['password'], $repository->find(1)->password)) {
    throw new RuntimeException('Local administrator password verification failed.');
}

fwrite(STDOUT, "Local administrator configured. Read .local/admin-credentials.json on your computer to sign in.\n");
