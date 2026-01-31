<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Add default API secret key header for all requests
        $this->withHeaders([
            'X-SECRET-KEY' => env('API_SECRET_KEY'),
        ]);
    }
}
