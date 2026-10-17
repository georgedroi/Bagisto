<?php

namespace App\Services\Updates;

use RuntimeException;

class ReleaseManifest
{
    /**
     * Validate a project release and bind its archive to GitHub's asset inventory.
     */
    public static function validate(array $manifest, array $release): array
    {
        $id = $manifest['release_id'] ?? null;
        $version = $manifest['version'] ?? null;
        $archive = $manifest['archive'] ?? [];

        if (($manifest['schema'] ?? null) !== 1 || ($manifest['updater_protocol'] ?? null) !== 1 || ($manifest['project'] ?? null) !== 'mansecretpleasure'
            || ! is_string($id) || ! preg_match('/^msp-\d{4}\.\d{1,2}\.\d{1,2}\.\d{1,4}$/D', $id)
            || $id !== ($release['tag_name'] ?? null) || $version !== substr($id, 4)
            || ($release['draft'] ?? null) !== false || ($release['prerelease'] ?? null) !== false
            || ! is_string($manifest['commit'] ?? null) || ! preg_match('/^[a-f0-9]{40}$/D', $manifest['commit'])
            || ($manifest['database_policy'] ?? null) !== 'unchanged'
            || ! is_array($archive) || ($archive['name'] ?? null) !== $id.'.tar.gz'
            || ! is_string($archive['sha256'] ?? null) || ! preg_match('/^[a-f0-9]{64}$/D', $archive['sha256'])) {
            throw new RuntimeException('invalid-release');
        }

        $asset = null;

        foreach ($release['assets'] ?? [] as $candidate) {
            if (($candidate['name'] ?? null) === $archive['name']) {
                if ($asset !== null) {
                    throw new RuntimeException('invalid-release');
                }

                $asset = $candidate;
            }
        }

        if (! is_array($asset) || ! is_int($asset['id'] ?? null) || $asset['id'] < 1
            || ! is_int($release['id'] ?? null) || $release['id'] < 1
            || ! is_int($asset['size'] ?? null) || $asset['size'] < 1 || $asset['size'] > 1073741824
            || (isset($asset['digest']) && $asset['digest'] !== 'sha256:'.$archive['sha256'])) {
            throw new RuntimeException('invalid-release');
        }

        return [
            'api_id' => $release['id'],
            'release_id' => $id,
            'version' => $version,
            'sha256' => $archive['sha256'],
            'asset_id' => $asset['id'],
            'notes' => mb_substr((string) ($release['body'] ?? ''), 0, 20000),
        ];
    }
}
