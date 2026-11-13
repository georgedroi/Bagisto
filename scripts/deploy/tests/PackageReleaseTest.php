<?php

use Illuminate\Filesystem\Filesystem;

/**
 * Execute fixture tools without interpolating shell arguments.
 */
function releaseFixtureCommand(array $command, string $directory): array
{
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $directory);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    return [proc_close($process), $stdout, $stderr];
}

/**
 * Commit only the named fixture paths.
 */
function commitReleaseFixture(object $fixture, array $paths): void
{
    [$status] = releaseFixtureCommand(['git', 'add', '--', ...$paths], $fixture->project);
    expect($status)->toBe(0);
    [$status] = releaseFixtureCommand(['git', 'commit', '-qm', 'Prepare release fixture'], $fixture->project);
    expect($status)->toBe(0);
}

/**
 * Create an isolated repository with the release inputs.
 */
function releasePackageFixture(): object
{
    $root = sys_get_temp_dir().'/msp-package-test-'.bin2hex(random_bytes(8));
    $fixture = (object) ['root' => $root, 'project' => $root.'/project', 'archive' => $root.'/release.tar.gz'];
    mkdir($fixture->project, 0700, true);
    $files = [
        '.gitignore' => "config/ignored-runtime.php\n",
        'app/Version.php' => '<?php return "reviewed";',
        'app/old.php.bak' => 'fixture backup',
        'bootstrap/app.php' => '<?php return null;',
        'bootstrap/cache/config.php' => '<?php return [];',
        'config/app.php' => '<?php return [];',
        'config/fixture.key' => 'fixture only',
        'config/.env.fixture' => 'fixture only',
        'database/seeders/ManPleasureQaProductSeeder.php' => '<?php',
        'database/migrations/reviewed.php' => '<?php',
        'lang/en/messages.php' => '<?php return [];',
        'packages/Webkul/Shop/package-lock.json' => '{}',
        'packages/Webkul/Shop/tests/FixtureTest.php' => '<?php',
        'public/build/manifest.json' => '{}',
        'public/themes/shop/default/build/manifest.json' => '{}',
        'public/themes/admin/default/build/manifest.json' => '{}',
        'public/dev.hot' => 'fixture only',
        'resources/view.blade.php' => '<p>fixture</p>',
        'routes/web.php' => '<?php',
        'artisan' => '<?php',
        'composer.json' => '{}',
        'composer.lock' => '{}',
        'package.json' => '{}',
        'vite.config.js' => '',
        'scripts/deploy/package-release.sh' => file_get_contents(dirname(__DIR__).'/package-release.sh'),
        'scripts/deploy/package-admin-update.sh' => file_get_contents(dirname(__DIR__).'/package-admin-update.sh'),
        'app/Http/Controllers/Admin/SystemUpdateController.php' => '<?php',
        'routes/manpleasure-updates.php' => '<?php',
        'docker/production/mansecretpleasure/fixture.service' => '[Unit]',
        'release.json' => '{"release_id":"fixture-1","version":"1","commit":"fixture"}',
    ];
    foreach ($files as $name => $contents) {
        $path = $fixture->project.'/'.$name;
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0700, true);
        }
        file_put_contents($path, $contents);
    }
    foreach ([['git', 'init', '-qb', 'main'], ['git', 'config', 'user.email', 'fixture@example.invalid'], ['git', 'config', 'user.name', 'Release fixture']] as $command) {
        [$status] = releaseFixtureCommand($command, $fixture->project);
        expect($status)->toBe(0);
    }
    commitReleaseFixture($fixture, array_keys($files));

    return $fixture;
}

/**
 * Run the packager against a disposable repository.
 */
function packageReleaseFixture(object $fixture, ?string $archive = null): array
{
    return releaseFixtureCommand(['bash', 'scripts/deploy/package-release.sh', $archive ?? $fixture->archive], $fixture->project);
}

beforeEach(function () {
    $this->fixture = releasePackageFixture();
});

afterEach(function () {
    $root = realpath($this->fixture->root);
    if ($root !== false && dirname($root) === sys_get_temp_dir() && str_starts_with(basename($root), 'msp-package-test-')) {
        (new Filesystem)->deleteDirectory($root);
    }
});

it('refuses packaging from a branch other than main', function () {
    releaseFixtureCommand(['git', 'switch', '-qc', 'work'], $this->fixture->project);
    [$status] = packageReleaseFixture($this->fixture);
    expect($status)->not->toBe(0)->and(file_exists($this->fixture->archive))->toBeFalse();
});

it('refuses uncommitted tracked changes', function () {
    file_put_contents($this->fixture->project.'/app/Version.php', '<?php return "unreviewed";');
    [$status] = packageReleaseFixture($this->fixture);
    expect($status)->not->toBe(0)->and(file_exists($this->fixture->archive))->toBeFalse();
});

it('refuses untracked source files', function () {
    file_put_contents($this->fixture->project.'/app/untracked.php', '<?php');
    [$status] = packageReleaseFixture($this->fixture);
    expect($status)->not->toBe(0)->and(file_exists($this->fixture->archive))->toBeFalse();
});

it('packages reviewed source and excludes ignored runtime files and private or test members', function () {
    file_put_contents($this->fixture->project.'/config/ignored-runtime.php', '<?php return "unreviewed";');
    [$status] = packageReleaseFixture($this->fixture);
    expect($status)->toBe(0);
    [$status, $listing] = releaseFixtureCommand(['tar', '-tzf', $this->fixture->archive], $this->fixture->project);
    expect($status)->toBe(0);
    $names = explode("\n", trim($listing));
    expect($names)->toContain('app/Version.php', 'release.json', 'public/themes/admin/default/build/manifest.json');
    foreach (['app/old.php.bak', 'config/fixture.key', 'config/.env.fixture', 'config/ignored-runtime.php', 'bootstrap/cache/config.php', 'public/dev.hot', 'packages/Webkul/Shop/tests/FixtureTest.php', 'database/seeders/ManPleasureQaProductSeeder.php'] as $excluded) {
        expect($names)->not->toContain($excluded);
    }
});

it('refuses a public admin directory even when it is empty', function () {
    mkdir($this->fixture->project.'/public/admin');
    [$status] = packageReleaseFixture($this->fixture);
    expect($status)->not->toBe(0)->and(file_exists($this->fixture->archive))->toBeFalse();
});

it('refuses tracked symbolic links before staging', function () {
    symlink('/tmp', $this->fixture->project.'/app/link');
    commitReleaseFixture($this->fixture, ['app/link']);
    [$status] = packageReleaseFixture($this->fixture);
    expect($status)->not->toBe(0)->and(file_exists($this->fixture->archive))->toBeFalse();
});

it('refuses symbolic links when the tracked source exceeds a pipe buffer', function () {
    $paths = [];
    for ($index = 0; $index < 1000; $index++) {
        $path = 'app/'.str_repeat('x', 120).$index.'.php';
        file_put_contents($this->fixture->project.'/'.$path, '<?php');
        $paths[] = $path;
    }
    symlink('/tmp', $this->fixture->project.'/app/0link');
    commitReleaseFixture($this->fixture, [...$paths, 'app/0link']);
    [$status] = packageReleaseFixture($this->fixture);
    expect($status)->not->toBe(0)->and(file_exists($this->fixture->archive))->toBeFalse();
});

it('refuses an archive inside the source checkout', function () {
    [$status] = packageReleaseFixture($this->fixture, $this->fixture->project.'/release.tar.gz');
    expect($status)->not->toBe(0)->and(file_exists($this->fixture->project.'/release.tar.gz'))->toBeFalse();
});

it('refuses a release without committed root assets', function () {
    unlink($this->fixture->project.'/public/build/manifest.json');
    commitReleaseFixture($this->fixture, ['public/build/manifest.json']);
    [$status] = packageReleaseFixture($this->fixture);
    expect($status)->not->toBe(0)->and(file_exists($this->fixture->archive))->toBeFalse();
});

it('preserves an existing archive', function () {
    file_put_contents($this->fixture->archive, 'preserve');
    [$status] = packageReleaseFixture($this->fixture);
    expect($status)->not->toBe(0)->and(file_get_contents($this->fixture->archive))->toBe('preserve');
});

it('produces the same archive for the same reviewed commit', function () {
    [$first] = packageReleaseFixture($this->fixture);
    [$second] = packageReleaseFixture($this->fixture, $this->fixture->root.'/second.tar.gz');
    expect($first)->toBe(0)->and($second)->toBe(0);
    expect(hash_file('sha256', $this->fixture->archive))->toBe(hash_file('sha256', $this->fixture->root.'/second.tar.gz'));
});

it('refuses updater metadata that does not match the requested release without changing source', function () {
    $metadata = file_get_contents($this->fixture->project.'/release.json');
    [$status] = releaseFixtureCommand(['bash', 'scripts/deploy/package-admin-update.sh', 'msp-2026.11.13.1', $this->fixture->root.'/update'], $this->fixture->project);
    expect($status)->not->toBe(0);
    expect(file_get_contents($this->fixture->project.'/release.json'))->toBe($metadata);
    expect(file_exists($this->fixture->root.'/update/manpleasure-release.json'))->toBeFalse();
});

it('builds an updater manifest from committed metadata and leaves the checkout clean', function () {
    [$status, $commit] = releaseFixtureCommand(['git', 'rev-parse', 'HEAD'], $this->fixture->project);
    expect($status)->toBe(0);
    $metadata = json_encode(['schema' => 1, 'updater_protocol' => 1, 'project' => 'mansecretpleasure', 'release_id' => 'msp-2026.11.13.1', 'version' => '2026.11.13.1', 'commit' => trim($commit)]);
    file_put_contents($this->fixture->project.'/release.json', $metadata);
    commitReleaseFixture($this->fixture, ['release.json']);
    [$status] = releaseFixtureCommand(['bash', 'scripts/deploy/package-admin-update.sh', 'msp-2026.11.13.1', $this->fixture->root.'/update'], $this->fixture->project);
    expect($status)->toBe(0);
    $manifest = json_decode(file_get_contents($this->fixture->root.'/update/manpleasure-release.json'), true);
    expect($manifest['archive']['sha256'])->toBe(hash_file('sha256', $this->fixture->root.'/update/msp-2026.11.13.1.tar.gz'));
    expect(file_get_contents($this->fixture->project.'/release.json'))->toBe($metadata);
    [$status, $changes] = releaseFixtureCommand(['git', 'status', '--porcelain'], $this->fixture->project);
    expect($status)->toBe(0)->and(trim($changes))->toBe('');
});
