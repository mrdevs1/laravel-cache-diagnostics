
<?php

namespace App\Support;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;

class CacheLockExample
{
    public function execute(
        string $resource,
        callable $callback
    ): mixed {
        $lock = Cache::lock(
            'resource-lock:' . $resource,
            60
        );

        try {
            $lock->block(5);

            return $callback();
        } catch (LockTimeoutException $exception) {
            report($exception);

            return null;
        } finally {
            $lock->release();
        }
    }
}
