<?php

use Illuminate\Filesystem\Filesystem;

/**
 * Run a command with an explicit environment and no shell interpolation.
 */
function provisionFixtureCommand(array $command, string $directory, array $environment): array
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
function provisionFixtureStub(string $path, string $body): void
{
    file_put_contents($path, "#!/usr/bin/env bash\n".$body);
    chmod($path, 0755);
}

/**
 * Build an empty deployment tree, command stubs and a copy of provision-database.sh pointed at it.
 */
function provisionDatabaseFixture(): object
{
    $root = sys_get_temp_dir().'/msp-provision-test-'.bin2hex(random_bytes(8));
    $fixture = (object) ['root' => $root, 'base' => $root.'/base', 'bin' => $root.'/bin', 'environment' => $root.'/reviewed.env'];

    foreach ([$fixture->bin, $fixture->base.'/shared'] as $directory) {
        mkdir($directory, 0700, true);
    }

    file_put_contents($fixture->environment, "APP_NAME=Fixture\nAPP_KEY=base64:".base64_encode(random_bytes(32))."\n");

    $script = file_get_contents(dirname(__DIR__).'/provision-database.sh');
    $pointed = str_replace('base=/var/www/mansecretpleasure', 'base='.$fixture->base, $script, $count);
    expect($count)->toBe(1);
    file_put_contents($root.'/provision-database.sh', $pointed);

    provisionFixtureStub($fixture->bin.'/id', 'if [ "${1:-}" = -u ]; then echo 0; else exec /usr/bin/id "$@"; fi'."\n");
    provisionFixtureStub($fixture->bin.'/mysql', 'case "$*" in *--execute*) echo 0 ;; *) cat > /dev/null ;; esac'."\n");
    provisionFixtureStub($fixture->bin.'/install', 'for last; do :; done'."\n".'cp "${@: -2:1}" "$last" && chmod 640 "$last"'."\n");
    provisionFixtureStub($fixture->bin.'/chown', 'exit 0'."\n");

    return $fixture;
}

/**
 * Run the pointed copy of provision-database.sh with the stubs first on PATH.
 */
function runProvisionFixture(object $fixture): array
{
    return provisionFixtureCommand(['bash', $fixture->root.'/provision-database.sh', $fixture->environment], $fixture->root, [
        'PATH' => $fixture->bin.':'.getenv('PATH'),
        'HOME' => $fixture->root,
    ]);
}

beforeEach(function () {
    $this->fixture = provisionDatabaseFixture();
});

afterEach(function () {
    $root = realpath($this->fixture->root);
    if ($root !== false && dirname($root) === sys_get_temp_dir() && str_starts_with(basename($root), 'msp-provision-test-')) {
        (new Filesystem)->deleteDirectory($root);
    }
});

it('writes a private MySQL options file whose client credentials match the environment', function () {
    [$status, , $stderr] = runProvisionFixture($this->fixture);
    expect($status)->toBe(0, $stderr);
    $optionsFile = $this->fixture->base.'/shared/.mysql.cnf';
    expect(sprintf('%04o', fileperms($optionsFile) & 0777))->toBe('0600');
    $options = parse_ini_file($optionsFile, true, INI_SCANNER_RAW);
    $environment = parse_ini_file($this->fixture->base.'/shared/.env', false, INI_SCANNER_RAW);
    expect($options['client'] ?? [])->toMatchArray(['host' => '127.0.0.1', 'user' => 'mansecretpleasure', 'password' => $environment['DB_PASSWORD']]);
});

it('keeps the default database out of every option group mysqldump reads', function () {
    [$status, , $stderr] = runProvisionFixture($this->fixture);
    expect($status)->toBe(0, $stderr);
    $options = parse_ini_file($this->fixture->base.'/shared/.mysql.cnf', true, INI_SCANNER_RAW);
    foreach (['client', 'mysqldump'] as $group) {
        expect($options[$group] ?? [])->not->toHaveKey('database');
    }
    expect($options['mysql']['database'] ?? null)->toBe('mansecretpleasure');
});
