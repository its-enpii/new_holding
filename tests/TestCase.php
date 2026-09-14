<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;

abstract class TestCase extends BaseTestCase
{
    /**
     * Point the dedicated `sso` cache store at an in-memory array store so a
     * test run never depends on the shared Redis token bus being reachable.
     * Tests that exercise the real bus must not call this helper.
     */
    protected function useInMemorySsoStore(): void
    {
        config()->set('cache.stores.sso', ['driver' => 'array']);

        Cache::purge('sso');
        Cache::store('sso')->flush();
    }
}
