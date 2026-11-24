
<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class CacheInvalidation
{
    public function forgetProduct(int $productId): bool
    {
        return Cache::forget(
            'products:single:' . $productId
        );
    }

    public function forgetFeaturedProducts(): bool
    {
        return Cache::forget('products:featured');
    }

    public function refreshProduct(
        int $productId,
        callable $callback
    ): mixed {
        $key = 'products:single:' . $productId;

        Cache::forget($key);

        $product = $callback();

        Cache::put($key, $product, 600);

        return $product;
    }
}
