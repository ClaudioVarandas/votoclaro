<?php

namespace Tests\Feature;

use Tests\TestCase;

class AboutPageTest extends TestCase
{
    public function test_about_page_returns_successful_response(): void
    {
        $response = $this->get(route('about'));

        $response->assertStatus(200);
        $response->assertViewIs('pages.about');
    }

    public function test_about_page_displays_section_headings(): void
    {
        $response = $this->get(route('about'));

        $response->assertSee(__('ui.about.what_is_title'));
        $response->assertSee(__('ui.about.parliament_title'));
        $response->assertSee(__('ui.about.reading_data_title'));
        $response->assertSee(__('ui.about.data_source_title'));
        $response->assertSee(__('ui.about.contact_title'));
    }
}
