<?php

namespace App\Services\Updates;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class ReleaseCatalog
{
    /**
     * Locate the newest published project release without selecting upstream updates.
     */
    public function latest(): ?array
    {
        $repository = (string) config('manpleasure-updates.repository');

        if (! preg_match('~^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$~D', $repository)) {
            throw new RuntimeException('invalid-repository');
        }

        $releases = $this->get('https://api.github.com/repos/'.$repository.'/releases?per_page=100');
        $items = json_decode($releases, true, 32, JSON_THROW_ON_ERROR);

        if (! is_array($items) || ! array_is_list($items)) {
            throw new RuntimeException('invalid-release-list');
        }

        usort($items, fn (array $a, array $b): int => strcmp((string) ($b['published_at'] ?? ''), (string) ($a['published_at'] ?? '')));

        foreach ($items as $release) {
            if (! empty($release['draft']) || ! empty($release['prerelease']) || ! str_starts_with((string) ($release['tag_name'] ?? ''), 'msp-')) {
                continue;
            }

            foreach ($release['assets'] ?? [] as $asset) {
                if (($asset['name'] ?? '') !== 'manpleasure-release.json') {
                    continue;
                }

                if (! is_int($asset['id'] ?? null) || $asset['id'] < 1 || ($asset['size'] ?? 0) < 1 || $asset['size'] > 65536) {
                    throw new RuntimeException('invalid-manifest-asset');
                }

                $body = $this->get('https://api.github.com/repos/'.$repository.'/releases/assets/'.$asset['id'], true);

                return ReleaseManifest::validate(json_decode($body, true, 32, JSON_THROW_ON_ERROR), $release);
            }
        }

        return null;
    }

    /**
     * Follow only GitHub asset redirects and never forward credentials to the CDN.
     */
    protected function get(string $url, bool $asset = false): string
    {
        for ($attempt = 0; $attempt < 4; $attempt++) {
            $parts = parse_url($url);
            $host = $parts['host'] ?? '';

            if (($parts['scheme'] ?? '') !== 'https' || isset($parts['user']) || isset($parts['pass'])
                || (isset($parts['port']) && $parts['port'] !== 443)
                || ! in_array($host, ['api.github.com', 'github.com', 'release-assets.githubusercontent.com', 'objects.githubusercontent.com', 'github-releases.githubusercontent.com'], true)
                || ($host === 'github.com' && ! str_starts_with($parts['path'] ?? '', '/'.config('manpleasure-updates.repository').'/releases/download/'))) {
                throw new RuntimeException('invalid-download-host');
            }

            $request = Http::withoutRedirecting()->connectTimeout(5)->timeout(15)->withHeaders([
                'Accept' => $asset ? 'application/octet-stream' : 'application/vnd.github+json',
                'User-Agent' => 'ManPleasure-Admin-Updater/1',
            ]);

            if ($host === 'api.github.com' && filled(config('manpleasure-updates.token'))) {
                $request = $request->withToken(config('manpleasure-updates.token'));
            }

            $response = $request->get($url);

            if ($asset && in_array($response->status(), [301, 302, 303, 307, 308], true)) {
                $url = (string) $response->header('Location');

                continue;
            }

            if (! $response->successful() || strlen($response->body()) > ($asset ? 65536 : 4194304)) {
                throw new RuntimeException('release-request-failed');
            }

            return $response->body();
        }

        throw new RuntimeException('redirect-limit');
    }
}
