# API Security Implementation Checklist

## P0 - Critical Security Rules
- [x] HTTPS/TLS enforcement - Nginx HSTS + `FORCE_HTTPS` app-level scheme forcing - 2026-01-18
- [x] Authentication & authorization - Keycloak JWT guard (`auth:api`) + BasePolicy/PermissionService + Spatie roles - 2026-01-18
- [x] Input validation & sanitization - FormRequests + `SanitizeInput` middleware (strip_tags + htmlspecialchars) - 2026-01-18, **fixed: middleware registered globally** - 2026-02-10
- [x] SQL injection prevention - Eloquent/Query Builder only; parameter binding; optional query logging (`DB_QUERY_LOG`) - 2026-01-18
- [x] Rate limiting - `api` 60/min auth, 10/min guest; `login` 5/min; `admin` 30/min - 2026-01-18
- [x] CORS configuration - explicit methods/headers/origins; `supports_credentials: true`; `max_age: 3600`; env-configurable origins (`CORS_ALLOWED_ORIGINS`) - **fixed** 2026-02-10
- [x] Sensitive data encryption - `Notification.metadata` uses `encrypted:array`; passwords use `hashed` cast - **fixed: encrypted cast applied** 2026-02-10
- [x] Error handling & info leakage - specific exception handlers + **generic Throwable catch** in production (hides stack traces) - **fixed** 2026-02-10
- [x] API token security - Keycloak guard validates JWT signature + expiration - 2026-01-18
- [x] File upload validation - `StoreDocumentRequest` enforces MIME types (pdf,doc,docx,jpg,jpeg,png,webp) + 10MB max + `DocumentValidationService` - **fixed: MIME+size at request level** 2026-02-10

## P1 - High Priority Security Rules
- [x] CSRF protection - `VerifyCsrfToken` for web routes; API uses stateless JWT (no CSRF needed) - 2026-01-18
- [x] JWT token expiration - Keycloak manages token lifetime (15m access, 7d refresh); guard validates `exp` claim - 2026-01-18
- [ ] Password security standards - deferred: password management fully handled by Keycloak - 2026-01-18
- [x] API versioning - `apiPrefix: 'api/v1'` + `ApiVersionHeader` middleware sets `X-Api-Version` - 2026-01-18, **fixed: middleware registered** 2026-02-10
- [x] Request size limits - Nginx `client_max_body_size 10M` + PHP `upload_max_filesize=10M` / `post_max_size=12M` + `RequestSizeLimiter` middleware (10MB) - **fixed: all layers aligned** 2026-02-10
- [x] Audit logging - `audit_logs` table + `AuditObserver` for all core models + `AuditLogger` service with **sensitive field filtering** - **fixed** 2026-02-10
- [x] Database connection security - PostgreSQL `sslmode` is env-configurable via `DB_SSLMODE` (default: `prefer`) - **fixed** 2026-02-10
- [x] Security headers - `SecurityHeadersMiddleware` sets X-Frame-Options, X-Content-Type-Options, X-XSS-Protection, Referrer-Policy, HSTS + Nginx adds same headers - **fixed: middleware registered + nginx hardened** 2026-02-10
- [x] Dependency vulnerability scanning - `.github/dependabot.yml` for Composer, npm, GitHub Actions (weekly) - 2026-01-18
- [x] API documentation security - Scramble requires auth by default (`SCRAMBLE_REQUIRE_AUTH=true`); Gate restricts to ADMIN role - **fixed: default changed to true** 2026-02-10

## Implementation Details

### Middleware Registration (bootstrap/app.php)
**Global middleware** (all requests):
- `HandleCors` - CORS preflight handling
- `SecurityHeadersMiddleware` - security response headers
- `ApiVersionHeader` - `X-Api-Version` response header

**API group middleware** (prepended):
- `SanitizeInput` - strips HTML tags, escapes special chars
- `RequestSizeLimiter` - rejects payloads > 10MB with 413

**Aliases:**
- `role` -> Spatie `RoleMiddleware`
- `permission` -> Spatie `PermissionMiddleware`
- `role_or_permission` -> Spatie `RoleOrPermissionMiddleware`

### CORS Configuration (config/cors.php)
- Explicit methods: GET, POST, PUT, PATCH, DELETE, OPTIONS
- Explicit headers: Content-Type, Authorization, X-Requested-With, Accept, Origin
- Origins configurable via `CORS_ALLOWED_ORIGINS` env var
- Preflight cache: 1 hour (`max_age: 3600`)
- Credentials: enabled

### Nginx Hardening (docker/nginx/default.conf)
- `client_max_body_size 10M`
- `server_tokens off` (hide Nginx version)
- Security headers (HSTS, X-Frame-Options, X-Content-Type-Options, X-XSS-Protection, Referrer-Policy)
- Block access to sensitive file extensions (.env, .log, .sql, .bak, etc.)
- Block access to hidden files (except .well-known)

### PHP Hardening (docker/php/local.ini)
- `upload_max_filesize=10M` / `post_max_size=12M` (aligned with Nginx + middleware)
- `expose_php=Off` (hide PHP version in headers)

### Error Handling (bootstrap/app.php)
- `UnauthorizedException` -> 403 with message
- `TokenException` -> 401 (strips Keycloak prefix)
- `HttpException` -> appropriate status code
- `AuthenticationException` -> 401
- **Generic `Throwable`** -> 500 "An internal error occurred." (production only; debug mode shows full error)

### Audit Logging
- `AuditObserver` on all core models (created/updated/deleted)
- `AuditLogger` service filters sensitive fields: password, remember_token, token, secret, api_key, two_factor_secret, two_factor_recovery_codes
- Logs: user_id, action, auditable_type/id, old/new values, IP address, user agent

### File Upload Security
- Request-level validation: MIME type whitelist + 10MB max size
- Service-level validation: additional type/size/dimension checks
- UUID filenames via Spatie Media Library (prevents path traversal)
- Storage on Minio/S3 (outside public directory)

## Fixes Applied (2026-02-10)

| # | Issue | Severity | Fix |
|---|-------|----------|-----|
| 1 | Security middleware not registered in bootstrap/app.php | **Critical** | Registered SanitizeInput, RequestSizeLimiter (API group), SecurityHeadersMiddleware, ApiVersionHeader (global) |
| 2 | CORS uses wildcard `*` for methods/headers | **High** | Replaced with explicit allowed lists; added env-configurable origins |
| 3 | `supports_credentials: false` in CORS | **High** | Changed to `true` for JWT cookie support |
| 4 | Nginx missing security config | **High** | Added client_max_body_size, server_tokens off, security headers, sensitive file blocking |
| 5 | No generic 500 error handler | **High** | Added Throwable catch that hides details in production |
| 6 | Notification.metadata not encrypted | **Medium** | Changed cast from `array` to `encrypted:array` |
| 7 | File upload missing MIME/size validation at request level | **Medium** | Added mimes + max rules to StoreDocumentRequest |
| 8 | PostgreSQL sslmode not env-configurable | **Medium** | Changed to `env('DB_SSLMODE', 'prefer')` |
| 9 | Scramble defaults to no auth | **Medium** | Changed default to `SCRAMBLE_REQUIRE_AUTH=true` |
| 10 | Audit logs could contain password hashes | **Medium** | Added sensitive field filtering in AuditLogger |
| 11 | PHP ini upload limits misaligned (64M vs 10M policy) | **Low** | Aligned to 10M/12M + added expose_php=Off |

## Security Posture Summary
- All P0 critical rules are now fully implemented and verified
- All P1 high-priority rules are implemented (except password standards, managed by Keycloak)
- Defense-in-depth: security controls at Nginx, PHP, middleware, and application layers
- Keycloak manages token lifetimes and password policies externally
- Follow-up recommendations:
  - Run `composer audit` in CI pipeline
  - Enforce `DB_SSLMODE=require` in production
  - Set `SCRAMBLE_REQUIRE_AUTH=true` and `FORCE_HTTPS=true` in production .env
  - Consider Content-Security-Policy header for any web-facing pages
  - Consider adding `Permissions-Policy` header
