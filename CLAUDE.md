# CLAUDE.md — CCAK Backend Scolarité

## Project Overview
University academic management API (scolarité) built with **Laravel 12 / PHP 8.2**.
API-only backend with Keycloak JWT auth, Spatie roles/permissions, and Minio (S3) file storage.

## Commands
```bash
# Dev
composer dev                    # Start server + queue + logs + vite
php artisan serve               # Server only

# Test
composer test                   # Clear config + run PHPUnit
php artisan test                # PHPUnit directly
php artisan test --filter=Name  # Single test

# Lint / Static Analysis
./vendor/bin/pint               # Laravel Pint (code style)
composer phpstan                # Larastan level 6

# Database
php artisan migrate             # Run migrations
php artisan migrate:fresh --seed # Reset + seed

# Docker
docker compose up -d            # Start services
```

## Architecture
```
app/
├── Http/Controllers/           # Grouped: Academic/, Admin/, Student/, Notification/
│   └── BaseApiController.php   # All controllers extend this (uses ApiResponse trait)
├── Http/Resources/             # API Resources + Collections (JSON:API-style)
├── Http/Requests/              # Form Request validation
├── Models/                     # Eloquent models (UUID primary keys, auditing)
├── Policies/                   # BasePolicy + per-resource policies (PermissionService)
├── Services/                   # Business logic: Authorization/, Documents/, Media/, Notification/
├── Support/                    # ApiResponse trait
└── Observers/                  # AuditObserver
routes/api.php                  # All API routes under /api/v1 (apiPrefix)
bootstrap/app.php               # Middleware + exception handling config
```

## Key Patterns
- **API responses**: Use `ApiResponse` trait (`successResponse`, `errorResponse`, etc.) — consistent `{success, data, message, meta}` envelope
- **Authorization**: `BasePolicy` delegates to `PermissionService`. Policies use `$resource` property for permission strings like `students.view`, `documents.create`
- **Auth**: Keycloak JWT guard (`auth:api`) — no session auth
- **Filtering**: Spatie Query Builder for index endpoints
- **Media**: Spatie Media Library with Minio/S3 storage
- **Auditing**: `owen-it/laravel-auditing` for model audit trails
- **API docs**: Scramble (auto-generated OpenAPI)

## Conventions
- Controllers extend `BaseApiController`
- Policies extend `BasePolicy`
- Use Form Requests for validation (e.g., `StoreDocumentRequest`)
- Use API Resources for response transformation
- Route model binding with UUID where applicable
- Middleware: `role:ADMIN` for admin-only routes, `throttle:admin` for rate limiting
- French comments exist in codebase — maintain language consistency per file

## Testing
- PHPUnit 11, Mockery for mocks
- Tests in `tests/Feature/` and `tests/Unit/`
- Use `php artisan test --filter=ClassName` for targeted runs
