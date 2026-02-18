<?php

namespace Tests\Feature;

use Tests\TestCase;

class AccessibilityTest extends TestCase
{
    public function test_skip_link_is_present(): void
    {
        $response = $this->get(route('about'));

        $response->assertSee('href="#main-content"', false);
        $response->assertSee(__('ui.skip_to_content'));
    }

    public function test_html_has_lang_attribute(): void
    {
        $response = $this->get(route('about'));

        $response->assertSee('lang="pt"', false);
    }

    public function test_main_element_has_id(): void
    {
        $response = $this->get(route('about'));

        $response->assertSee('id="main-content"', false);
    }
}
