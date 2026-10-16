<?php

use App\Http\Middleware\RequireUpdateOwner;
use App\Services\Updates\DeploymentOperations;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->previousContainer = Container::getInstance();
    $this->directory = sys_get_temp_dir().'/manpleasure-update-test-'.bin2hex(random_bytes(8));
    mkdir($this->directory, 0700, true);
    mkdir($this->directory.'/requests', 0700);
    mkdir($this->directory.'/public', 0700);
    $this->application = new Application($this->directory);
    $this->application->instance('env', 'production');
    $this->application->instance('config', new Repository([
        'app' => ['url' => 'https://mansecretpleasure.com'],
        'manpleasure-updates' => [
            'repository' => 'georgedroi/Bagisto',
            'deploy_enabled' => true,
            'request_directory' => $this->directory.'/requests',
            'status_directory' => $this->directory.'/public',
        ],
    ]));
    $this->application->instance('translator', new class
    {
        public function get($key, $replace = [], $locale = null): string
        {
            return $key;
        }
    });
    file_put_contents($this->directory.'/public/heartbeat.json', json_encode([
        'protocol' => 1, 'repository' => 'georgedroi/Bagisto', 'timestamp' => time(), 'active' => null, 'rollback' => true,
    ]));
    $this->operations = new DeploymentOperations;
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->directory);
    Container::setInstance($this->previousContainer);
    Mockery::close();
});

it('keeps deployment unavailable in local and testing environments', function (string $environment) {
    $this->application->instance('env', $environment);

    expect($this->operations->capabilities())->toBe(['ready' => false, 'busy' => false, 'rollback' => false]);
    expect(fn () => $this->operations->enqueue('install', ['api_id' => 12, 'sha256' => str_repeat('a', 64)], 1))->toThrow(RuntimeException::class);
    expect(glob($this->directory.'/requests/*'))->toBe([]);
})->with(['local', 'testing']);

it('requires an enabled exact production origin and a fresh matching worker', function (string $mutation) {
    $heartbeat = json_decode(file_get_contents($this->directory.'/public/heartbeat.json'), true);

    match ($mutation) {
        'origin' => config(['app.url' => 'https://another.example']),
        'disabled' => config(['manpleasure-updates.deploy_enabled' => false]),
        'stale' => $heartbeat['timestamp'] = time() - 100,
        'repository' => $heartbeat['repository'] = 'another/project',
        'protocol' => $heartbeat['protocol'] = 2,
    };

    file_put_contents($this->directory.'/public/heartbeat.json', json_encode($heartbeat));

    expect($this->operations->capabilities()['ready'])->toBeFalse();
})->with(['origin', 'disabled', 'stale', 'repository', 'protocol']);

it('writes one private bounded request instead of executing commands', function () {
    $id = $this->operations->enqueue('install', ['api_id' => 12, 'sha256' => str_repeat('a', 64)], 1);
    $path = $this->directory.'/requests/'.$id.'.json';
    $request = json_decode(file_get_contents($path), true);

    expect($request['action'])->toBe('install')
        ->and($request['release_api_id'])->toBe(12)
        ->and($request['actor'])->toBe(1)
        ->and($request['expected_current'])->toBe(basename($this->directory))
        ->and(fileperms($path) & 0777)->toBe(0600)
        ->and(glob($this->directory.'/requests/*.pending'))->toBe([]);
});

it('rejects arbitrary actions and missing rollback history', function () {
    expect(fn () => $this->operations->enqueue('/bin/sh', null, 1))->toThrow(RuntimeException::class);
    file_put_contents($this->directory.'/public/heartbeat.json', json_encode([
        'protocol' => 1, 'repository' => 'georgedroi/Bagisto', 'timestamp' => time(), 'active' => null, 'rollback' => false,
    ]));

    expect(fn () => $this->operations->enqueue('rollback', null, 1))->toThrow(RuntimeException::class);
});

it('does not expose unrecognized worker fields or phases', function () {
    $id = 'd1d671bd-1460-4311-a12d-9f092c70101b';
    file_put_contents($this->directory.'/public/'.$id.'.json', json_encode([
        'phase' => '/bin/sh', 'command' => 'private', 'token' => 'secret',
    ]));
    $status = $this->operations->status($id);

    expect($status['phase'])->toBe('queued')
        ->and($status['terminal'])->toBeFalse()
        ->and(array_keys($status))->toBe(['operation', 'phase', 'message', 'terminal']);
});

it('restricts routes to configured active full-access owners', function (string $scenario, bool $allowed) {
    config(['manpleasure-updates.owner_ids' => [1]]);
    $admin = Mockery::mock(Authenticatable::class);
    $admin->id = 1;
    $admin->status = true;
    $admin->role = (object) ['permission_type' => 'all'];

    match ($scenario) {
        'guest' => $admin = null,
        'inactive' => $admin->status = false,
        'limited' => $admin->role->permission_type = 'custom',
        'different-owner' => $admin->id = 2,
        default => null,
    };

    $guard = Mockery::mock(Guard::class);
    $guard->shouldReceive('user')->once()->andReturn($admin);
    $factory = Mockery::mock(AuthFactory::class);
    $factory->shouldReceive('guard')->with('admin')->once()->andReturn($guard);
    $this->application->instance('auth', $factory);
    $middleware = new RequireUpdateOwner;
    $request = Request::create('/admin/settings/system-updates');
    $next = fn () => new Response('allowed');

    if ($allowed) {
        expect($middleware->handle($request, $next)->getContent())->toBe('allowed');
    } else {
        expect(fn () => $middleware->handle($request, $next))->toThrow(HttpException::class);
    }
})->with([
    ['owner', true], ['guest', false], ['inactive', false], ['limited', false], ['different-owner', false],
]);
