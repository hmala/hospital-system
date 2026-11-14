---
name: laravel-enterprise
description: High-scale Laravel architecture, queue management (Horizon), Caching strategies (Redis), Eloquent optimization, Octane, Action Classes, DTOs, and high-concurrency scaling (3,000+ active users).
---

# Enterprise Laravel High-Scale Architecture Skill

Guidelines, design patterns, and performance optimizations for large-scale Laravel applications (3,000+ to 100,000+ concurrent users).

## 1. Clean Architecture Patterns
- **Action Classes**: Keep Controllers ultra-thin (1-3 lines per method). Delegate business logic to dedicated Action classes (`App\Actions\Orders\ProcessOrderAction`).
- **Data Transfer Objects (DTOs)**: Pass strongly typed DTOs instead of raw arrays (`spatie/laravel-data` or native readonly DTO classes).
- **Form Requests**: Enforce input validation and authorization in custom Form Request classes.
- **API Resources**: Use `JsonResource` and `ResourceCollection` for response transformation. Never expose raw Eloquent models.

## 2. High-Scale Eloquent & Database Optimization
- **Prevent N+1 Queries**: Always eager load relationships (`User::with(['posts', 'profile'])->get()`). Enable `Model::preventLazyLoading(!app()->isProduction())`.
- **Cursor Pagination**: Use `cursorPaginate(50)` instead of `paginate()` or `offset()` for large tables to avoid slow SQL offset scans.
- **Lazy Collections**: Process multi-gigabyte datasets using `Model::cursor()` or `chunkById(1000)` to prevent memory exhaustion.
- **Read/Write Replicas**: Configure `database.php` read/write connections to offload SELECT queries to replica databases.

## 3. Asynchronous Queues & Caching
- **Redis & Laravel Horizon**: Offload heavy tasks (emails, notifications, exports, webhooks, third-party APIs) to async jobs using Redis + Horizon.
- **Job Retries & Dead Letter**: Set explicit `$tries`, `$backoff`, and `$timeout`. Use `failed()` method for alerting.
- **Redis Cache Tagging**: Cache heavy queries (`Cache::tags(['users'])->remember(...)`). Flush granularly on model events (`saved`/`deleted`).

## 4. Application Acceleration (Octane & Security)
- **Laravel Octane**: Serve application using FrankenPHP or Swoole for high-throughput zero-boot response times.
- **Rate Limiting**: Protect high-frequency endpoints (`RateLimiter::for('api', ...)`).
- **Strict Typing**: Enforce `declare(strict_types=1);` across all application classes.
