<?php

namespace App\Services;

use Closure;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

/**
 * The Parliament's open-data files run past 100 MB, more than json_decode fits in memory, so
 * they go to a temporary file to be read item by item, and the file is always removed.
 *
 * The site fails now and then (a server error, a dropped connection), so a download is tried a
 * few times before the import gives up.
 */
class ParliamentFileDownloader
{
    private const TIMEOUT_SECONDS = 600;

    private const ATTEMPTS = 3;

    private const SECONDS_BETWEEN_ATTEMPTS = 30;

    /**
     * @template TResult
     *
     * @param  Closure(string $path): TResult  $read
     * @return TResult
     */
    public function withDownload(string $url, Closure $read): mixed
    {
        $path = tempnam(sys_get_temp_dir(), 'ar-download-');

        try {
            $response = Http::sink($path)
                ->timeout(self::TIMEOUT_SECONDS)
                ->retry(self::ATTEMPTS, self::SECONDS_BETWEEN_ATTEMPTS * 1000, throw: false)
                ->get($url);

            if (! $response->successful()) {
                throw new Exception("Failed to fetch {$url} (HTTP {$response->status()}).");
            }

            return $read($path);
        } finally {
            File::delete($path);
        }
    }
}
