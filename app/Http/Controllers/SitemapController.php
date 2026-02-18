<?php

namespace App\Http\Controllers;

use App\Services\PartyStatsService;
use Illuminate\Http\Response;

class SitemapController
{
    public function __invoke(): Response
    {
        $staticRoutes = [
            route('dashboard'),
            route('initiatives.index'),
            route('parties.index'),
            route('about'),
        ];

        $partyRoutes = collect(PartyStatsService::MAIN_PARTIES)
            ->map(fn (string $acronym): string => route('parties.show', strtolower($acronym)));

        $urls = collect($staticRoutes)->merge($partyRoutes);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= '  <url><loc>' . htmlspecialchars($url) . '</loc></url>' . "\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'text/xml']);
    }
}
