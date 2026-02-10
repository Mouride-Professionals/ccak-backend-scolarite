You are a senior Laravel security engineer implementing comprehensive security measures for the UCAK university management system API. Follow these instructions carefully and create a detailed checklist as you work.

TASK OVERVIEW:
Implement all critical (P0) and high-priority (P1) API security rules for a Laravel 12 API using PostgreSQL, Keycloak authentication, and MinIO storage.

STEP-BY-STEP IMPLEMENTATION:

1. CREATE SECURITY CHECKLIST FILE
   - Create file: /docs/security/api-security-checklist.md
   - Use this template structure:
```markdown
     # API Security Implementation Checklist
     
     ## P0 - Critical Security Rules
     - [ ] Rule name - Status - Notes - Date completed
     
     ## P1 - High Priority Security Rules
     - [ ] Rule name - Status - Notes - Date completed
     
     ## Implementation Details
     [Details for each completed rule]
     
     ## Security Test Results
     [Test results and verification]
```

2. IMPLEMENT P0 CRITICAL RULES (in this order):

   A. HTTPS/TLS ENFORCEMENT
      - Force HTTPS redirect in web server config (Nginx)
      - Add HSTS header with 1-year max-age
      - Update all internal URLs to use HTTPS
      - Test: Verify HTTP redirects to HTTPS
      - ✅ Mark in checklist when complete

   B. AUTHENTICATION & AUTHORIZATION
      - Install and configure Laravel Keycloak adapter
      - Create middleware: CheckKeycloakToken.php
      - Validate JWT on every protected route
      - Implement role-based access control (RBAC)
      - Add permission checks before data access
      - Create policy classes for each model
      - Test: Verify token validation and unauthorized access returns 401
      - ✅ Mark in checklist when complete

   C. INPUT VALIDATION & SANITIZATION
      - Create FormRequest classes for all endpoints
      - Implement validation rules (type, format, length, range)
      - Add custom validation rules where needed
      - Sanitize string inputs (strip_tags, htmlspecialchars)
      - Validate enum values against allowed lists
      - Test: Send malicious input and verify rejection
      - ✅ Mark in checklist when complete

   D. SQL INJECTION PREVENTION
      - Audit all database queries
      - Replace any raw queries with Eloquent or Query Builder
      - Use parameter binding for all dynamic queries
      - Add database query logging in development
      - Test: Attempt SQL injection in form fields
      - ✅ Mark in checklist when complete

   E. RATE LIMITING
      - Configure rate limiting in RouteServiceProvider
      - Set limits: 60/min for authenticated, 10/min for public
      - Add custom rate limiters for sensitive endpoints (login: 5/min)
      - Implement IP-based rate limiting for admin routes
      - Return 429 Too Many Requests with Retry-After header
      - Test: Exceed rate limits and verify throttling
      - ✅ Mark in checklist when complete

   F. CORS CONFIGURATION
      - Configure config/cors.php
      - Set allowed_origins: ['https://ucak.edu.sn', 'http://localhost:3000']
      - Set allowed_methods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']
      - Set allowed_headers: ['Content-Type', 'Authorization', 'X-Requested-With']
      - Set supports_credentials: true
      - Test: Verify CORS headers and reject unauthorized origins
      - ✅ Mark in checklist when complete

   G. SENSITIVE DATA ENCRYPTION
      - Configure encryption key in .env (APP_KEY)
      - Use bcrypt for password hashing (min cost: 12)
      - Encrypt sensitive model attributes using $casts = ['field' => 'encrypted']
      - Never log passwords, tokens, or sensitive data
      - Test: Verify encrypted data in database
      - ✅ Mark in checklist when complete

   H. ERROR HANDLING & INFORMATION LEAKAGE
      - Update Handler.php to hide stack traces in production
      - Return generic error messages to clients
      - Log detailed errors to storage/logs with context
      - Remove debug mode in production (.env: APP_DEBUG=false)
      - Create custom error responses (don't reveal database structure)
      - Test: Trigger errors and verify no sensitive info leaked
      - ✅ Mark in checklist when complete

   I. API TOKEN SECURITY
      - Configure Keycloak JWT validation
      - Verify token signature on every request
      - Check token expiration (set to 15 minutes)
      - Implement refresh token mechanism (7 days)
      - Store tokens securely (never in query params)
      - Invalidate tokens on logout
      - Test: Use expired/invalid tokens and verify rejection
      - ✅ Mark in checklist when complete

   J. FILE UPLOAD VALIDATION
      - Create FileUploadRequest with validation rules
      - Validate MIME type (use finfo, not just extension)
      - Limit file size: 10MB for documents, 5MB for images
      - Generate UUID filenames (prevent path traversal)
      - Store files outside public directory
      - Scan uploaded files for malware (if ClamAV available)
      - Validate image dimensions for photos
      - Test: Upload malicious files and verify rejection
      - ✅ Mark in checklist when complete

3. IMPLEMENT P1 HIGH PRIORITY RULES:

   K. CSRF PROTECTION
      - Verify VerifyCsrfToken middleware is active
      - Ensure all state-changing routes use CSRF token
      - Configure CSRF cookie settings (SameSite=Lax)
      - Test: Submit form without token and verify rejection
      - ✅ Mark in checklist when complete

   L. JWT TOKEN EXPIRATION
      - Configure Keycloak token lifetime: 15 minutes
      - Implement refresh token rotation
      - Add token expiry check in middleware
      - Force re-authentication for sensitive operations
      - Test: Use token after expiration
      - ✅ Mark in checklist when complete

   M. PASSWORD SECURITY STANDARDS
      - Create custom password validation rule
      - Enforce: min 8 chars, uppercase, lowercase, number, special char
      - Use Argon2 or bcrypt with cost factor 12
      - Implement password reset with time-limited tokens (60 min)
      - No password hints or recovery questions
      - Test: Weak passwords are rejected
      - ✅ Mark in checklist when complete

   N. API VERSIONING
      - Create v1 route group in routes/api.php
      - Prefix all routes with /api/v1
      - Create versioned controller namespaces
      - Add API version to response headers
      - Test: Access endpoints via /api/v1/
      - ✅ Mark in checklist when complete

   O. REQUEST SIZE LIMITS
      - Configure in config/http.php: max_request_size = 10MB
      - Set PHP post_max_size and upload_max_filesize
      - Set Nginx client_max_body_size = 10M
      - Return 413 Payload Too Large for oversized requests
      - Test: Send large payloads and verify rejection
      - ✅ Mark in checklist when complete

   P. AUDIT LOGGING
      - Create AuditLog model and migration
      - Implement AuditLogger service
      - Log: user, action, model, old/new values, IP, user agent
      - Use observers for automatic model logging
      - Log all authentication events
      - Store logs for minimum 1 year
      - Test: Perform actions and verify logs created
      - ✅ Mark in checklist when complete

   Q. DATABASE CONNECTION SECURITY
      - Use strong database password (32+ characters)
      - Enable PostgreSQL SSL connection
      - Limit database user permissions (no DROP/CREATE DATABASE)
      - Use separate database users for different environments
      - Whitelist application server IP in PostgreSQL
      - Test: Connection works over SSL
      - ✅ Mark in checklist when complete

   R. SECURITY HEADERS
      - Create SecurityHeadersMiddleware.php
      - Add headers:
        * X-Frame-Options: DENY
        * X-Content-Type-Options: nosniff
        * X-XSS-Protection: 1; mode=block
        * Strict-Transport-Security: max-age=31536000
        * Referrer-Policy: strict-origin-when-cross-origin
      - Apply to all routes
      - Test: Verify headers in response
      - ✅ Mark in checklist when complete

   S. DEPENDENCY VULNERABILITY SCANNING
      - Run: composer audit
      - Document all vulnerabilities found
      - Update vulnerable packages
      - Setup GitHub Dependabot for automated scanning
      - Create process for regular updates (weekly)
      - Test: No high/critical vulnerabilities
      - ✅ Mark in checklist when complete

   T. API DOCUMENTATION SECURITY
      - Protect Swagger/L5-Swagger routes with authentication
      - Remove sensitive examples from documentation
      - Don't expose API docs in production (or require auth)
      - Document all security requirements
      - Test: Unauthenticated users can't access docs
      - ✅ Mark in checklist when complete

4. SECURITY TESTING
   - Test authentication bypass attempts
   - Test authorization checks (horizontal/vertical privilege escalation)
   - Test input validation with malicious payloads
   - Test SQL injection on all form fields
   - Test rate limiting effectiveness
   - Test file upload with various malicious files
   - Test CORS configuration with different origins
   - Document all test results in checklist

5. FINAL VERIFICATION
   - Run security scan (Laravel Security Checker or similar)
   - Review all TODO comments for security implications
   - Verify no secrets in code or version control
   - Check all environment variables are properly configured
   - Ensure logging doesn't contain sensitive data
   - Complete final security audit

IMPORTANT REQUIREMENTS:
- Update the checklist file after completing each rule
- Include test results and verification steps
- Document any issues or blockers encountered
- Add code snippets showing implementation
- Note any deviations from standard implementation
- Create summary of security posture at the end

OUTPUT FORMAT:
1. Implement each security rule
2. Test thoroughly
3. Update /docs/security/api-security-checklist.md
4. Create code comments explaining security measures
5. Provide summary report when all rules implemented

Begin implementation now. Start with creating the checklist file, then proceed with P0 rules in order. Mark each item as complete with date and test results.
