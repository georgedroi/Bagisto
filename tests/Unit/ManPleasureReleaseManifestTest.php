<?php

use App\Services\Updates\ReleaseManifest;

/**
 * Build independent release fixtures without booting Laravel or connecting to a database.
 */
function manpleasureReleaseFixture(): array
{
    $hash = str_repeat('a', 64);
    $manifest = [
        'schema' => 1,
        'updater_protocol' => 1,
        'project' => 'mansecretpleasure',
        'release_id' => 'msp-2026.10.17.1',
        'version' => '2026.10.17.1',
        'commit' => str_repeat('b', 40),
        'database_policy' => 'unchanged',
        'archive' => ['name' => 'msp-2026.10.17.1.tar.gz', 'sha256' => $hash],
    ];
    $release = [
        'id' => 100,
        'tag_name' => $manifest['release_id'],
        'draft' => false,
        'prerelease' => false,
        'body' => '<script>untrusted notes</script>',
        'assets' => [['id' => 200, 'name' => $manifest['archive']['name'], 'size' => 100, 'digest' => 'sha256:'.$hash]],
    ];

    return [$manifest, $release];
}

it('binds a stable project manifest to one exact archive asset', function () {
    [$manifest, $release] = manpleasureReleaseFixture();
    $result = ReleaseManifest::validate($manifest, $release);

    expect($result['api_id'])->toBe(100)
        ->and($result['asset_id'])->toBe(200)
        ->and($result['sha256'])->toBe($manifest['archive']['sha256'])
        ->and($result['notes'])->toBe($release['body']);
});

it('rejects manifests outside the supported deployment protocol', function (string $field, mixed $value) {
    [$manifest, $release] = manpleasureReleaseFixture();
    $manifest[$field] = $value;

    expect(fn () => ReleaseManifest::validate($manifest, $release))->toThrow(RuntimeException::class);
})->with([
    ['schema', 2], ['updater_protocol', 2], ['project', 'bagisto'],
    ['release_id', '../escape'], ['version', '2026.10.18.1'],
    ['commit', 'not-a-commit'], ['database_policy', 'migrate'],
]);

it('rejects draft and prerelease packages', function (string $field) {
    [$manifest, $release] = manpleasureReleaseFixture();
    $release[$field] = true;

    expect(fn () => ReleaseManifest::validate($manifest, $release))->toThrow(RuntimeException::class);
})->with(['draft', 'prerelease']);

it('rejects missing duplicate oversized or mismatched archive assets', function (string $mutation) {
    [$manifest, $release] = manpleasureReleaseFixture();

    match ($mutation) {
        'missing' => $release['assets'] = [],
        'duplicate' => $release['assets'][] = $release['assets'][0],
        'oversized' => $release['assets'][0]['size'] = 1073741825,
        'digest' => $release['assets'][0]['digest'] = 'sha256:'.str_repeat('c', 64),
        'non-integer' => $release['assets'][0]['size'] = '100',
    };

    expect(fn () => ReleaseManifest::validate($manifest, $release))->toThrow(RuntimeException::class);
})->with(['missing', 'duplicate', 'oversized', 'digest', 'non-integer']);
