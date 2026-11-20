<?php

use Illuminate\Filesystem\Filesystem;

/**
 * Run a command with an explicit environment and no shell interpolation.
 */
function rollbackFixtureCommand(array $command, string $directory, array $environment): array
{
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $directory, $environment);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    return [proc_close($process), $stdout, $stderr];
}

/**
 * Write an executable stub.
 */
function rollbackFixtureStub(string $path, string $body): void
{
    file_put_contents($path, "#!/usr/bin/env bash\n".$body);
    chmod($path, 0755);
}

/**
 * Build a disposable deployment tree, command stubs and a copy of rollback-release.sh pointed at it.
 *
 * The php8.3 stub rebuilds the caches with Laravel's own Filesystem::replace(), which chmods every file it writes to
 * 0777 minus the umask; that is how the real cache commands produce files the www-data group cannot read.
 */
function rollbackReleaseFixture(): object
{
    $root = sys_get_temp_dir().'/msp-rollback-test-'.bin2hex(random_bytes(8));
    $fixture = (object) ['root' => $root, 'base' => $root.'/base', 'bin' => $root.'/bin', 'log' => $root.'/calls.log'];
    $fixture->target = $fixture->base.'/releases/previous';
    $fixture->framework = $fixture->base.'/shared/storage/framework';

    foreach ([$fixture->bin, $fixture->target.'/bootstrap/cache', $fixture->base.'/releases/current-release', $fixture->framework.'/views', $fixture->framework.'/cache/data'] as $directory) {
        mkdir($directory, 0770, true);
    }

    foreach ([$fixture->target.'/artisan', $fixture->base.'/releases/current-release/artisan'] as $artisan) {
        file_put_contents($artisan, '<?php');
    }

    file_put_contents($fixture->target.'/bootstrap/cache/packages.php', '<?php return [];');
    file_put_contents($fixture->framework.'/views/stale.php', '<?php');
    symlink($fixture->base.'/releases/current-release', $fixture->base.'/current');

    $script = file_get_contents(dirname(__DIR__).'/rollback-release.sh');
    $pointed = str_replace('base=/var/www/mansecretpleasure', 'base='.$fixture->base, $script, $count);
    expect($count)->toBe(1);
    file_put_contents($root.'/rollback-release.sh', $pointed);

    $artisan = <<<'PHP'
<?php
require getenv('ROLLBACK_FIXTURE_AUTOLOAD');
$files = new Illuminate\Filesystem\Filesystem;
$release = dirname($argv[1]);
$cache = $release.'/bootstrap/cache';
$views = getenv('ROLLBACK_FIXTURE_FRAMEWORK').'/views';
$command = $argv[2] ?? '';
if ($command === 'optimize:clear') {
    foreach (array_merge(glob($cache.'/*.php'), glob($views.'/*.php')) as $file) {
        unlink($file);
    }
}
if (is_dir($cache) && ! is_file($cache.'/packages.php')) {
    $files->replace($cache.'/packages.php', '<?php return [];');
    $files->replace($cache.'/services.php', '<?php return [];');
}
$outputs = ['config:cache' => $cache.'/config.php', 'route:cache' => $cache.'/routes-v7.php', 'view:cache' => $views.'/compiled.php'];
if (isset($outputs[$command])) {
    $files->replace($outputs[$command], '<?php return [];');
}
PHP;
    file_put_contents($root.'/artisan-stub.php', $artisan);

    $log = escapeshellarg($fixture->log);
    $unreadable = 'find '.escapeshellarg($fixture->target.'/bootstrap/cache').' '.escapeshellarg($fixture->framework).' -type f ! -perm -g=r | wc -l';
    rollbackFixtureStub($fixture->bin.'/id', 'if [ "${1:-}" = -u ]; then echo 0; else exec /usr/bin/id "$@"; fi'."\n");
    rollbackFixtureStub($fixture->bin.'/runuser', 'echo "runuser $*" >> '.$log."\n".'shift 3'."\n".'exec "$@"'."\n");
    rollbackFixtureStub($fixture->bin.'/chown', 'echo "chown $*" >> '.$log."\n");
    rollbackFixtureStub($fixture->bin.'/systemctl', 'echo "systemctl $*" >> '.$log."\n".'if [ "$1" = reload ]; then echo "unreadable-at-reload $('.$unreadable.')" >> '.$log.'; fi'."\n");
    rollbackFixtureStub($fixture->bin.'/php8.3', 'exec php '.escapeshellarg($root.'/artisan-stub.php').' "$@"'."\n");

    return $fixture;
}

/**
 * Run the pointed copy of rollback-release.sh with the stubs first on PATH.
 */
function runRollbackFixture(object $fixture): array
{
    return rollbackFixtureCommand(['bash', $fixture->root.'/rollback-release.sh', 'previous', '--schema-compatible'], $fixture->root, [
        'PATH' => $fixture->bin.':'.getenv('PATH'),
        'HOME' => $fixture->root,
        'ROLLBACK_FIXTURE_AUTOLOAD' => dirname(__DIR__, 3).'/vendor/autoload.php',
        'ROLLBACK_FIXTURE_FRAMEWORK' => $fixture->framework,
    ]);
}

/**
 * Permission bits of every regular file the web server reads after a rollback.
 */
function rollbackFixtureModes(object $fixture): array
{
    $modes = [];

    foreach ([$fixture->target.'/bootstrap/cache', $fixture->framework] as $directory) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->isFile()) {
                $modes[substr($file->getPathname(), strlen($fixture->base) + 1)] = sprintf('%04o', $file->getPerms() & 0777);
            }
        }
    }

    ksort($modes);

    return $modes;
}

beforeEach(function () {
    $this->fixture = rollbackReleaseFixture();
});

afterEach(function () {
    $root = realpath($this->fixture->root);
    if ($root !== false && dirname($root) === sys_get_temp_dir() && str_starts_with(basename($root), 'msp-rollback-test-')) {
        (new Filesystem)->deleteDirectory($root);
    }
});

it('switches current to the previous release', function () {
    [$status, , $stderr] = runRollbackFixture($this->fixture);
    expect($status)->toBe(0, $stderr)->and(readlink($this->fixture->base.'/current'))->toBe($this->fixture->target);
});

it('leaves every rebuilt cache and compiled view readable and writable by the www-data group', function () {
    [$status, , $stderr] = runRollbackFixture($this->fixture);
    expect($status)->toBe(0, $stderr);
    $modes = rollbackFixtureModes($this->fixture);
    expect(array_keys($modes))->toContain('releases/previous/bootstrap/cache/packages.php', 'releases/previous/bootstrap/cache/services.php', 'releases/previous/bootstrap/cache/config.php', 'shared/storage/framework/views/compiled.php');
    expect(array_unique(array_values($modes)))->toBe(['0660']);
});

it('gives the rebuilt release cache to the www-data group before php-fpm reloads and the queue starts', function () {
    [$status, , $stderr] = runRollbackFixture($this->fixture);
    expect($status)->toBe(0, $stderr);
    $calls = file($this->fixture->log, FILE_IGNORE_NEW_LINES);
    $chown = array_search('chown -R mansecret-deploy:www-data '.$this->fixture->target.'/bootstrap/cache', $calls, true);
    $reload = array_search('systemctl reload php8.3-fpm', $calls, true);
    $start = array_search('systemctl start mansecretpleasure-queue.service mansecretpleasure-scheduler.timer', $calls, true);
    expect($chown)->toBeInt()->and($reload)->toBeInt()->and($start)->toBeInt();
    expect($chown)->toBeLessThan($reload)->and($reload)->toBeLessThan($start);
    expect($calls)->toContain('unreadable-at-reload 0');
});
