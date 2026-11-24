
<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Throwable;

class CacheDiagnostics
{
    public function inspect(
        string $key,
        string $store = 'default'
    ): array {
        try {
            $cache = Cache::store($store);

            $exists = $cache->has($key);

            return [
                'store' => $store,
                'key' => $key,
                'available' => true,
                'exists' => $exists,
                'value' => $exists ? $cache->get($key) : null,
            ];
        } catch (Throwable $exception) {
            return [
                'store' => $store,
                'key' => $key,
                'available' => false,
                'exists' => false,
                'value' => null,
                'error' => $exception->getMessage(),
            ];
        }
    }

    public function testWrite(
        string $key,
        string $store = 'default'
    ): bool {
        $cache = Cache::store($store);

        $value = 'cache-diagnostic-' . uniqid();

        $cache->put($key, $value, 60);

        return $cache->get($key) === $value;
    }
}
