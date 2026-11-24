# Laravel Cache Diagnostics

A practical troubleshooting guide for diagnosing and resolving Laravel caching problems in development and production environments.

This repository covers cache configuration, driver compatibility, stale data, cache invalidation, expiration, Redis and database caching, cache tags, atomic locks, and performance issues.

## Table of Contents

- [Introduction](#introduction)
- [How Laravel Cache Works](#how-laravel-cache-works)
- [Common Cache Problems](#common-cache-problems)
- [Cache Configuration Diagnostics](#cache-configuration-diagnostics)
- [Cache Driver Diagnostics](#cache-driver-diagnostics)
- [Cache Miss and Stale Data](#cache-miss-and-stale-data)
- [Cache Invalidation Problems](#cache-invalidation-problems)
- [Expiration and TTL Diagnostics](#expiration-and-ttl-diagnostics)
- [Cache Tags Compatibility](#cache-tags-compatibility)
- [Redis Cache Diagnostics](#redis-cache-diagnostics)
- [Database Cache Diagnostics](#database-cache-diagnostics)
- [Atomic Locks and Race Conditions](#atomic-locks-and-race-conditions)
- [Cache Prefix and Key Conflicts](#cache-prefix-and-key-conflicts)
- [Cache Performance Diagnostics](#cache-performance-diagnostics)
- [Cache Failover](#cache-failover)
- [Useful Artisan Commands](#useful-artisan-commands)
- [Code Examples](#code-examples)
- [Testing Cache Behavior](#testing-cache-behavior)
- [Production Debugging Checklist](#production-debugging-checklist)
- [References](#references)

## Introduction

Laravel provides a unified cache API that supports multiple storage drivers.

Caching reduces repeated database queries, expensive calculations, and external service requests.

Common caching use cases include:

- Storing frequently accessed database results.
- Caching API responses.
- Storing application settings.
- Reducing repeated queries.
- Managing temporary application data.
- Preventing simultaneous execution of sensitive operations.

Incorrect cache configuration can cause stale data, unexpected cache misses, inconsistent application behavior, or production performance problems.

This repository provides a systematic approach to diagnosing these issues.

## How Laravel Cache Works

A typical cache workflow:

1. Application requests a cache key.
2. Laravel checks the selected cache store.
3. If the key exists, Laravel returns the cached value.
4. If the key does not exist, the application retrieves or calculates the data.
5. Laravel stores the result with an expiration time.
6. Future requests reuse the cached result until it expires or is invalidated.

### Cache Flow

```text
Application Request
        |
        v
   Cache Lookup
        |
        +------ Cache Hit ------> Return Cached Data
        |
        +------ Cache Miss
                    |
                    v
              Fetch Data
                    |
                    v
               Store Data
                    |
                    v
               Return Data
```

## Common Cache Problems

| Problem | Possible Cause |
|---|---|
| Cache always misses | Incorrect store or key |
| Old data appears | Cache not invalidated |
| Cache expires too early | Incorrect TTL |
| Cache never expires | Missing expiration |
| Redis connection fails | Incorrect Redis configuration |
| Database cache fails | Missing cache table |
| Cache tags do not work | Unsupported cache driver |
| Multiple requests execute together | Missing atomic lock |
| Different applications share keys | Prefix conflict |
| Cache works locally but not in production | Environment configuration mismatch |

## Cache Configuration Diagnostics

Laravel cache configuration is managed through `config/cache.php`.

The default cache store is controlled by the `CACHE_STORE` environment variable in current Laravel application configurations.

Example:

```env
CACHE_STORE=database
```

For Redis:

```env
CACHE_STORE=redis
```

For file-based caching:

```env
CACHE_STORE=file
```

### Diagnostic Steps

1. Check the configured cache store.
2. Verify the selected driver is available.
3. Check the backend connection.
4. Review the cache prefix.
5. Verify environment configuration.
6. Check whether configuration is cached.

Useful commands:

```bash
php artisan config:show cache
php artisan about
php artisan config:clear
```

Important: Do not use `env()` directly throughout application code. Read environment values through configuration files and access them using `config()`.

## Cache Driver Diagnostics

Laravel supports several cache backends.

| Driver | Storage | Common Use |
|---|---|---|
| `array` | In-memory array | Automated tests |
| `file` | Local filesystem | Simple applications |
| `database` | Database table | Database-backed caching |
| `redis` | Redis server | High-performance caching |
| `memcached` | Memcached server | Distributed caching |
| `dynamodb` | AWS DynamoDB | Distributed cloud caching |

### File Driver

Common problems:

- Incorrect storage permissions.
- Missing cache directory.
- Disk space limitations.
- Different servers using separate local storage.

Check the storage directory permissions and available disk space.

### Database Driver

Common problems:

- Missing cache table.
- Database connection errors.
- Incorrect database permissions.
- Slow database queries.

### Redis Driver

Common problems:

- Redis server unavailable.
- Incorrect host or port.
- Authentication errors.
- Network connectivity problems.
- Different Redis databases or prefixes.

### Diagnostic Checklist

- Verify the active cache driver.
- Confirm backend availability.
- Check required PHP extensions or packages.
- Verify connection credentials.
- Test cache read and write operations.

## Cache Miss and Stale Data

A cache miss happens when the requested key does not exist in the selected cache store.

A stale cache problem occurs when the application returns an outdated cached value.

### Common Causes

- Different cache keys are used for storing and retrieving data.
- Cache expiration is too short.
- Cache is not invalidated after database updates.
- Application uses an unexpected cache store.
- Cache prefix differs between environments.
- Multiple servers do not share the same cache backend.

### Using Cache::remember()

```php
use Illuminate\Support\Facades\Cache;

$products = Cache::remember(
    'products:featured',
    300,
    function () {
        return Product::where('featured', true)->get();
    }
);
```

This retrieves the cached value or executes the callback and stores its result for 300 seconds.

### Diagnostic Steps

1. Identify the exact cache key.
2. Verify the active cache store.
3. Check whether the key exists.
4. Compare the cached data with the database.
5. Inspect the expiration time.
6. Verify cache invalidation after updates.

## Cache Invalidation Problems

Cache invalidation means removing or refreshing cached data when the original data changes.

For example, updating a product without clearing its cached record may cause users to see old product information.

### Forget a Cache Key

```php
Cache::forget('products:featured');
```

### Clear a Store

```php
Cache::store('redis')->flush();
```

Warning: Flushing a cache store can remove entries belonging to other parts of an application or other applications sharing that store. Prefer targeted key invalidation when possible.

### Recommended Approach

- Use consistent cache key naming.
- Invalidate related keys after database updates.
- Keep cache expiration appropriate.
- Avoid unnecessary global cache flushing.
- Test update and delete operations.

A practical invalidation example is available in `examples/CacheInvalidation.php`.

## Expiration and TTL Diagnostics

TTL means Time To Live. It determines how long a cached item remains available.

Example:

```php
Cache::put('user:profile:10', $user, 600);
```

The value is stored for 600 seconds.

### Common TTL Problems

- Expiration is shorter than expected.
- Values are stored indefinitely.
- Different code paths use different expiration times.
- Cached data is not refreshed after expiration.
- Developers confuse seconds with minutes.

### Diagnostic Checklist

- Check the expiration value.
- Confirm the unit is seconds when using integer durations.
- Review all cache write locations.
- Check whether the value is overwritten.
- Verify expiration behavior in the selected driver.

Laravel also supports date and time objects for expiration.

## Cache Tags Compatibility

Cache tags allow related cached values to be grouped and invalidated together.

Example:

```php
Cache::tags(['products', 'catalog'])
    ->put('featured', $products, 600);
```

Retrieve the value using the same ordered tags:

```php
$products = Cache::tags(['products', 'catalog'])
    ->get('featured');
```

Clear tagged values:

```php
Cache::tags(['products', 'catalog'])->flush();
```

### Important Driver Compatibility

Laravel cache tags are not supported by the following drivers:

- File
- Database
- DynamoDB
- Storage

Using tags with an unsupported driver can cause runtime errors.

### Diagnostic Checklist

- Check the active cache driver.
- Confirm that the driver supports tags.
- Use the same ordered tag list when retrieving values.
- Verify tag invalidation logic.
- Test tag operations against the actual backend.

## Redis Cache Diagnostics

Redis is commonly used for shared and high-performance caching.

### Example Configuration

```env
CACHE_STORE=redis

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
```

### Test Redis Connectivity

```bash
redis-cli ping
```

Expected response:

```text
PONG
```

### Common Problems

- Redis service is stopped.
- Incorrect hostname or port.
- Authentication failure.
- Redis connection timeout.
- Incorrect database selection.
- Different environments use different Redis instances.

### Diagnostic Checklist

- Verify Redis service status.
- Test network connectivity.
- Check authentication.
- Confirm Redis database configuration.
- Check application logs.
- Verify cache prefix and connection settings.

## Database Cache Diagnostics

The database cache driver requires a cache table.

If the required migration is missing, create it:

```bash
php artisan make:cache-table
php artisan migrate
```

### Common Problems

- Cache table does not exist.
- Migration was not executed.
- Database connection is unavailable.
- Database permissions are insufficient.
- Cache table queries are slow.

### Diagnostic Checklist

- Confirm the cache table exists.
- Check migration status.
- Verify database connectivity.
- Check table permissions.
- Inspect database query performance.

## Atomic Locks and Race Conditions

Atomic locks help prevent multiple processes from performing the same operation simultaneously.

Example use cases:

- Prevent duplicate payment processing.
- Avoid concurrent report generation.
- Prevent overlapping imports.
- Protect shared resources.

### Basic Lock Example

```php
use Illuminate\Support\Facades\Cache;

$lock = Cache::lock('order-processing:1001', 60);

if ($lock->get()) {
    try {
        // Perform the protected operation.
    } finally {
        $lock->release();
    }
}
```

### Common Problems

- Lock is not released.
- Lock expires while processing is still running.
- Different servers use separate cache stores.
- Lock name is inconsistent.
- Multiple workers cannot access the same central cache.

### Important Considerations

- Use a shared cache backend across application servers.
- Set lock duration according to operation time.
- Release locks safely.
- Handle lock acquisition failures.
- Avoid assuming a lock replaces database transactions or idempotency.

See `examples/CacheLockExample.php` for a complete example.

## Cache Prefix and Key Conflicts

Cache prefixes help separate keys belonging to different applications or environments.

Example:

```env
CACHE_PREFIX=myapp_production
```

### Common Problems

- Multiple applications share the same prefix.
- Development and production use the same Redis database and prefix.
- Cache keys are not consistently named.
- A prefix changes but old keys remain.

### Recommended Key Naming

```text
products:featured
products:category:10
users:profile:25
settings:application
```

Use a consistent naming convention and avoid unnecessarily broad key names.

## Cache Performance Diagnostics

Caching should reduce application workload, not create additional complexity.

### Common Performance Problems

- Too many small cache operations.
- Large serialized values.
- Repeated cache misses.
- Slow cache backend.
- Unnecessary global flush operations.
- Excessive database fallback queries.

### Diagnostic Steps

1. Identify frequently executed queries.
2. Measure cache hit and miss behavior.
3. Inspect response times.
4. Check Redis or database latency.
5. Review cache payload sizes.
6. Verify expiration and invalidation strategy.

Laravel supports cache events such as cache hits, misses, writes, and failures. These can help monitor cache behavior.

## Cache Failover

Laravel supports cache failover configurations that can use another store when the primary store is unavailable.

This can improve resilience, but the fallback behavior must be understood.

### Things to Verify

- Primary store availability.
- Fallback store configuration.
- Consistency between stores.
- Expected behavior during recovery.
- Whether temporary fallback data is still valid after the primary store returns.

Do not assume a fallback store automatically synchronizes cached values between backends.

## Useful Artisan Commands

| Command | Purpose |
|---|---|
| `php artisan cache:clear` | Clear the default application cache |
| `php artisan cache:clear --store=redis` | Clear a selected cache store |
| `php artisan config:show cache` | Inspect cache configuration |
| `php artisan config:clear` | Clear cached configuration |
| `php artisan config:cache` | Rebuild configuration cache |
| `php artisan make:cache-table` | Generate database cache migration |
| `php artisan migrate` | Run database migrations |

Command availability may depend on the Laravel version.

## Code Examples

This repository includes separate PHP files for practical cache diagnostics.

| File | Purpose |
|---|---|
| `examples/CacheDiagnostics.php` | Inspect cache read, write, and expiration behavior |
| `examples/CacheLockExample.php` | Demonstrate atomic lock usage |
| `examples/CacheInvalidation.php` | Demonstrate targeted cache invalidation |
| `tests/CacheDiagnosticsTest.php` | Test basic cache behavior |

These are example files, not a complete standalone Laravel package. Adapt namespaces and application-specific logic before using them.

## Testing Cache Behavior

Laravel provides cache testing support through its testing environment and cache facade.

Test important scenarios:

- Cache key creation.
- Cache retrieval.
- Cache expiration.
- Cache invalidation.
- Lock acquisition.
- Lock release.
- Backend connection failures.

Use the `array` cache driver for isolated tests where appropriate. It is not a substitute for integration testing against Redis or a database cache backend.

The example test file is available at `tests/CacheDiagnosticsTest.php`.

## Production Debugging Checklist

### Configuration

- [ ] Verify `CACHE_STORE`.
- [ ] Check `config/cache.php`.
- [ ] Confirm environment configuration.
- [ ] Verify cache prefix.
- [ ] Check cached configuration.

### Cache Storage

- [ ] Verify backend availability.
- [ ] Check database cache table.
- [ ] Confirm Redis connectivity.
- [ ] Verify filesystem permissions.
- [ ] Check required PHP extensions.

### Cache Behavior

- [ ] Verify cache key consistency.
- [ ] Check TTL values.
- [ ] Inspect stale data.
- [ ] Review invalidation logic.
- [ ] Confirm driver compatibility with tags.

### Concurrency

- [ ] Review atomic lock usage.
- [ ] Verify shared cache backend.
- [ ] Check lock expiration.
- [ ] Handle lock acquisition failures.
- [ ] Protect sensitive operations against duplicate execution.

### Performance

- [ ] Measure cache hit and miss behavior.
- [ ] Inspect slow cache operations.
- [ ] Review payload sizes.
- [ ] Check backend resource usage.
- [ ] Avoid unnecessary global cache flushing.

## References

- [Laravel Cache Documentation](https://laravel.com/docs/13.x/cache)
- [Laravel Cache Configuration](https://laravel.com/docs/13.x/cache#configuration)
- [Laravel Cache Usage](https://laravel.com/docs/13.x/cache#cache-usage)
- [Laravel Cache Tags](https://laravel.com/docs/13.x/cache#cache-tags)
- [Laravel Atomic Locks](https://laravel.com/docs/13.x/cache#atomic-locks)
- [Laravel Configuration](https://laravel.com/docs/13.x/configuration)

## License

This project is licensed under the MIT License.
