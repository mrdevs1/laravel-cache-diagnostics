
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheDiagnosticsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cache.default' => 'array',
        ]);
    }

    public function test_cache_value_can_be_stored_and_retrieved(): void
    {
        Cache::put('diagnostics:test', 'working', 60);

        $this->assertSame(
            'working',
            Cache::get('diagnostics:test')
        );
    }

    public function test_cache_key_can_be_invalidated(): void
    {
        Cache::put('diagnostics:remove', 'temporary', 60);

        Cache::forget('diagnostics:remove');

        $this->assertFalse(
            Cache::has('diagnostics:remove')
        );
    }

    public function test_cache_key_can_be_checked(): void
    {
        Cache::put('diagnostics:exists', 'yes', 60);

        $this->assertTrue(
            Cache::has('diagnostics:exists')
        );
    }
}
