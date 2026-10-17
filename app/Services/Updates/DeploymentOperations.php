<?php

namespace App\Services\Updates;

use Illuminate\Support\Str;
use RuntimeException;

class DeploymentOperations
{
    /**
     * Describe the installed source without exposing paths or private configuration.
     */
    public function installed(): array
    {
        $metadata = $this->readJson(base_path('release.json'));

        return [
            'release_id' => basename(realpath(base_path()) ?: base_path()),
            'version' => ($metadata['project'] ?? '') === 'mansecretpleasure' && is_string($metadata['version'] ?? null)
                ? $metadata['version'] : ($this->isProduction() ? basename(base_path()) : trans('admin::system-updates.local')),
        ];
    }

    /**
     * Require a fresh heartbeat from the separately installed server worker.
     */
    public function capabilities(): array
    {
        $heartbeat = $this->readJson(config('manpleasure-updates.status_directory').'/heartbeat.json');
        $ready = $this->isProduction() && config('manpleasure-updates.deploy_enabled')
            && ($heartbeat['protocol'] ?? null) === 1
            && ($heartbeat['repository'] ?? null) === config('manpleasure-updates.repository')
            && is_int($heartbeat['timestamp'] ?? null) && abs(time() - $heartbeat['timestamp']) < 90
            && is_dir(config('manpleasure-updates.request_directory')) && is_writable(config('manpleasure-updates.request_directory'));

        return [
            'ready' => (bool) $ready,
            'busy' => (bool) ($ready && ! empty($heartbeat['active'])),
            'rollback' => (bool) ($ready && ! empty($heartbeat['rollback'])),
        ];
    }

    /**
     * Submit a bounded request; web requests never execute deployment commands.
     */
    public function enqueue(string $action, ?array $candidate, int $actor): string
    {
        $capabilities = $this->capabilities();

        if (! $capabilities['ready'] || $capabilities['busy'] || ! in_array($action, ['install', 'rollback'], true)
            || ($action === 'rollback' && ! $capabilities['rollback'])) {
            throw new RuntimeException('unavailable');
        }

        $operation = (string) Str::uuid();
        $request = [
            'id' => $operation,
            'action' => $action,
            'actor' => $actor,
            'created_at' => time(),
            'expected_current' => $this->installed()['release_id'],
        ];

        if ($action === 'install') {
            $request['release_api_id'] = $candidate['api_id'];
            $request['sha256'] = $candidate['sha256'];
        }

        $directory = config('manpleasure-updates.request_directory');
        $temporary = $directory.'/'.$operation.'.pending';
        $handle = @fopen($temporary, 'xb');

        if ($handle === false) {
            throw new RuntimeException('unavailable');
        }

        try {
            chmod($temporary, 0600);
            $data = json_encode($request, JSON_THROW_ON_ERROR);

            if (fwrite($handle, $data) !== strlen($data)) {
                throw new RuntimeException('unavailable');
            }
        } catch (\Throwable $error) {
            @unlink($temporary);

            throw $error;
        } finally {
            fclose($handle);
        }

        if (! rename($temporary, $directory.'/'.$operation.'.json')) {
            @unlink($temporary);

            throw new RuntimeException('unavailable');
        }

        return $operation;
    }

    /**
     * Return only public operation fields from a fixed worker-owned status directory.
     */
    public function status(string $operation): array
    {
        if (! Str::isUuid($operation)) {
            throw new RuntimeException('invalid-operation');
        }

        $record = $this->readJson(config('manpleasure-updates.status_directory').'/'.$operation.'.json');
        $allowed = ['queued', 'downloading', 'staging', 'activating', 'verifying', 'completed', 'failed', 'rolled-back'];
        $phase = in_array($record['phase'] ?? null, $allowed, true) ? $record['phase'] : 'queued';

        return [
            'operation' => $operation,
            'phase' => $phase,
            'message' => trans('admin::system-updates.'.(($record['code'] ?? '') === 'database-review' ? 'database-review' : $phase)),
            'terminal' => in_array($phase, ['completed', 'failed', 'rolled-back'], true),
        ];
    }

    /**
     * Read small JSON metadata files without following filesystem links.
     */
    protected function readJson(string $path): array
    {
        if (is_link($path) || ! is_file($path) || filesize($path) > 65536) {
            return [];
        }

        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data) ? $data : [];
    }

    /**
     * Keep deployment disabled in local and testing environments regardless of flags.
     */
    protected function isProduction(): bool
    {
        return app()->environment('production') && config('app.url') === 'https://mansecretpleasure.com';
    }
}
