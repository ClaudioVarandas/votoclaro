<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Sleep;

abstract class TestCase extends BaseTestCase
{
    /**
     * A download the Parliament refuses is tried again after a pause: no test waits for it.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Sleep::fake();
    }
}
