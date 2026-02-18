<?php

namespace Tests\Feature;

use Tests\TestCase;

class SitemapTest extends TestCase
{
    public function test_sitemap_returns_successful_response(): void
    {
        $response = $this->get(route('sitemap'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/xml; charset=UTF-8');
    }

    public function test_sitemap_contains_expected_urls(): void
    {
        $response = $this->get(route('sitemap'));

        $response->assertSee(route('dashboard'));
        $response->assertSee(route('initiatives.index'));
        $response->assertSee(route('parties.index'));
        $response->assertSee(route('about'));
        $response->assertSee(route('parties.show', 'ps'));
        $response->assertSee(route('parties.show', 'psd'));
    }
}
