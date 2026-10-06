<?php

namespace App\Services\ICloud;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Talks to Apple's public shared-album endpoints.
 *
 * There is no official iCloud Photos API. Publishing an album produces a link
 * like https://www.icloud.com/sharedalbum/#B0xxxxxxxxx, and the token after the
 * "#" backs a JSON API that the iCloud web client uses. It is undocumented and
 * can change without notice — everything here is best-effort and every failure
 * must surface loudly rather than silently producing an empty album.
 *
 * This class knows the protocol and nothing else: no database access, no file
 * writes. That keeps it trivially fakeable in tests via Http::fake().
 */
class SharedAlbumClient
{
    /**
     * Albums live on numbered partitions. There is no way to know which one
     * from the token alone, so start here and follow Apple's redirect.
     */
    private const DEFAULT_PARTITION = 'p23';

    /**
     * Asset URLs are resolved in batches. Kept modest because the signed URLs
     * expire in roughly an hour and are consumed right after being issued.
     */
    public const ASSET_BATCH_SIZE = 25;

    private const TIMEOUT = 30;

    /**
     * Extract the album token from a full share link, or accept a bare token.
     * Returns null if the input doesn't look like either.
     */
    public static function parseToken(string $input): ?string
    {
        $input = trim($input);

        if (preg_match('~#([A-Za-z0-9_-]+)~', $input, $matches) === 1) {
            return $matches[1];
        }

        return preg_match('~^[A-Za-z0-9_-]{10,}$~', $input) === 1 ? $input : null;
    }

    /**
     * Fetch an album's photo list.
     *
     * Returns null when $knownCtag matches the album's current streamCtag,
     * meaning nothing has changed upstream since the last sync — the common
     * case, and the reason a routine run costs a single request.
     *
     * @return array{stream: array, base: string}|null
     *
     * @throws \Illuminate\Http\Client\RequestException on a non-2xx response
     */
    public function fetchStream(string $token, ?string $knownCtag = null): ?array
    {
        $base = $this->baseUrl(self::DEFAULT_PARTITION, $token);
        $response = $this->request()->post("{$base}/webstream", ['streamCtag' => null]);

        // Apple answers requests for an album on the wrong partition with the
        // host that actually owns it. This is routine, not an error condition.
        $host = data_get($response->json(), 'X-Apple-MMe-Host');

        if (is_string($host) && $host !== '') {
            $base = "https://{$host}/{$token}/sharedstreams";
            $response = $this->request()->post("{$base}/webstream", ['streamCtag' => null]);
        }

        $response->throw();

        $stream = $response->json();

        if (!is_array($stream) || !array_key_exists('photos', $stream)) {
            throw new \RuntimeException('Unexpected webstream response: no photos key.');
        }

        if ($knownCtag !== null && ($stream['streamCtag'] ?? null) === $knownCtag) {
            return null;
        }

        return ['stream' => $stream, 'base' => $base];
    }

    /**
     * Resolve downloadable URLs for a batch of photo guids.
     *
     * The returned URLs are signed and expire in roughly an hour, so callers
     * must download each batch immediately rather than resolving everything
     * upfront and working through it afterwards.
     *
     * @param  list<string>  $guids
     * @return array<string, string>  checksum => absolute URL
     */
    public function fetchAssetUrls(string $base, array $guids): array
    {
        if ($guids === []) {
            return [];
        }

        $response = $this->request()->post("{$base}/webasseturls", [
            'photoGuids' => array_values($guids),
        ]);

        $response->throw();

        $urls = [];

        foreach (data_get($response->json(), 'items', []) as $checksum => $item) {
            $location = $item['url_location'] ?? null;
            $path = $item['url_path'] ?? null;

            if ($location && $path) {
                $urls[$checksum] = "https://{$location}{$path}";
            }
        }

        return $urls;
    }

    /**
     * Pick the highest-resolution derivative Apple offers for a photo.
     *
     * Shared albums are capped at roughly 2048px on the long edge — these are
     * not camera originals. The derivatives map is keyed by height as a string.
     *
     * @return array{checksum: string, width: ?int, height: ?int, fileSize: ?int}|null
     */
    public function largestDerivative(array $photo): ?array
    {
        $best = null;
        $bestHeight = -1;

        foreach ($photo['derivatives'] ?? [] as $key => $derivative) {
            if (empty($derivative['checksum'])) {
                continue;
            }

            // Prefer the reported height, falling back to the map key, which is
            // the height Apple filed the derivative under.
            $height = (int) ($derivative['height'] ?? $key);

            if ($height > $bestHeight) {
                $bestHeight = $height;
                $best = [
                    'checksum' => (string) $derivative['checksum'],
                    'width' => isset($derivative['width']) ? (int) $derivative['width'] : null,
                    'height' => $height ?: null,
                    'fileSize' => isset($derivative['fileSize']) ? (int) $derivative['fileSize'] : null,
                ];
            }
        }

        return $best;
    }

    private function baseUrl(string $partition, string $token): string
    {
        return "https://{$partition}-sharedstreams.icloud.com/{$token}/sharedstreams";
    }

    private function request(): PendingRequest
    {
        return Http::timeout(self::TIMEOUT)
            ->acceptJson()
            ->asJson()
            // Apple rejects requests without a browser-ish agent.
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; PhotoPortfolioSync/1.0)'])
            ->retry(2, 500, throw: false);
    }
}
