# GreenHumble Routes Normalization & Legacy PHP Cleanup

## Current State (Before Cleanup)

### Duplicate Entry Points
- `/index.php` → redirects to `/api/index.php` (LEGACY)
- `/admin/index.php` → includes `/api/admin/index.php` (LEGACY ALIAS)
- `/flush.php` → OPcache purger (DUPLICATED AT `/api/flush.php`)

### API Routes (Canonical)
- `/api/index.php` → Public home page / scan form
- `/api/audit` → POST/GET audits (JSON)
- `/api/state` → GET/POST app state sync
- `/api/auth/login` → POST authentication
- `/api/admin/auth.php` → REST auth endpoint
- `/api/admin/config.php` → Configuration management
- `/api/admin/ai_console.php` → IA settings
- `/api/admin/users.php` → User management (HTML form)
- `/api/admin/teams.php` → Team management (HTML form)
- `/api/admin/index.php` → Admin dashboard (HTML)

## Normalized Routes (After Cleanup)

### Remove Legacy Aliases
- ✗ DELETE `/index.php` (use `/api/index.php` instead)
- ✗ DELETE `/admin/index.php` (use `/api/admin/index.php` instead)
- ✗ DELETE `/flush.php` (use `/api/flush.php` instead)

### Canonical URL Structure
```
/ (root)
├── /api/
│   ├── index.php         [GET] Public scan form + home
│   ├── audit             [GET/POST] Audit REST endpoint
│   ├── state             [GET/POST] State sync
│   ├── login.php         [GET/POST] Login form/API
│   ├── auth/
│   │   └── login         [POST] REST login endpoint
│   ├── admin/
│   │   ├── index.php     [GET] Admin dashboard
│   │   ├── auth.php      [GET/POST/DELETE] Session/token auth
│   │   ├── config.php    [GET/POST/PUT/DELETE] Config management
│   │   ├── ai_console.php [GET/POST/PUT/DELETE] IA settings
│   │   ├── users.php     [GET/POST] User management
│   │   ├── teams.php     [GET/POST] Team management
│   │   ├── user_manager.php [GET/POST/PUT/DELETE] User CRUD
│   │   ├── team_manager.php [GET/POST/PUT/DELETE] Team CRUD
│   │   ├── save_state.php [POST] State save (protected)
│   │   ├── flush.php     [GET/POST] OPcache purger
│   │   ├── console.php   [GET/POST] Admin console
│   │   └── ultimate-access.php [GET/POST] Emergency access
│   ├── scans/
│   │   └── create.php    [POST] Create new scan
│   └── flush.php         [GET/POST] OPcache purger (canonical)
├── /admin/               [DEPRECATED - DELETE]
└── /index.php            [DEPRECATED - DELETE]
```

## Migration Plan

### Phase 1: Add Protection to All Admin Endpoints
✓ `/api/admin/save_state.php`
✓ `/api/admin/config.php`
✓ `/api/admin/ai_console.php`
□ `/api/admin/users.php`
□ `/api/admin/teams.php`
□ `/api/admin/user_manager.php`
□ `/api/admin/team_manager.php`
□ `/api/admin/console.php`

### Phase 2: Redirect Legacy Aliases
Create redirect files that send 301/302 to canonical routes:

```php
// /index.php (legacy)
<?php
http_response_code(301);
header('Location: /api/index.php');
exit;
```

```php
// /admin/index.php (legacy)
<?php
http_response_code(301);
header('Location: /api/admin/index.php');
exit;
```

```php
// /flush.php (legacy)
<?php
http_response_code(301);
header('Location: /api/flush.php');
exit;
```

### Phase 3: Update Frontend Links
- Audit form: `/api/index.php`
- Admin: `/api/admin/index.php`
- Login: `/api/login.php` or `/api/auth/login`
- Flush: `/api/flush.php`

### Phase 4: Update .htaccess Rewrites

**Current (with legacy routing):**
```apache
# Redirect legacy entry points
RewriteRule ^index\.php$ /api/index.php [L]
RewriteRule ^admin/index\.php$ /api/admin/index.php [L]
```

**Optimized (canonical routing):**
```apache
# Canonical API routing
RewriteRule ^api/(.*)$ /api/$1 [L]

# Legacy redirects (eventually remove)
RewriteRule ^index\.php$ /api/index.php [L,R=301]
RewriteRule ^admin/index\.php$ /api/admin/index.php [L,R=301]
RewriteRule ^flush\.php$ /api/flush.php [L,R=301]
```

### Phase 5: Delete Deprecated Files
After 1-2 deployment cycles with redirects in place:

```bash
rm /index.php
rm /admin/index.php
rm /flush.php
rmdir /admin/ (if empty)
```

## Files to Protect Before Cleanup

### Admin Endpoints (All Need Auth + CSRF)
- [ ] `/api/admin/config.php` ✓
- [ ] `/api/admin/ai_console.php` ✓
- [ ] `/api/admin/users.php`
- [ ] `/api/admin/teams.php`
- [ ] `/api/admin/user_manager.php`
- [ ] `/api/admin/team_manager.php`
- [ ] `/api/admin/console.php`
- [ ] `/api/admin/index.php`
- [ ] `/api/admin/save_state.php` ✓

### Protected Mutations
- [ ] `/api/state.php` (POST)
- [ ] `/api/audit.php` (POST for admin-triggered audits)
- [ ] `/api/auth/login` (POST)

## Security Headers to Add

```php
// In api/admin/* endpoints
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
```

## Testing & Validation

### Run Validation Suite
```bash
php tests/validation-endpoints.php
```

### Expected Output
```
SUMMARY: 15/15 tests passed
✓ ALL VALIDATION TESTS PASSED
```

### Manual Testing
```bash
# Test public scan (no auth)
curl -X GET https://yourdomain/api/index.php

# Test admin access (no auth) → should fail
curl -X POST https://yourdomain/api/admin/config.php \
  -H "Content-Type: application/json" \
  -d '{"nom":"test","valeur":"value"}'
# Expected: 403 (Accès admin requis)

# Test admin access (with auth + CSRF) → should succeed
curl -X POST https://yourdomain/api/admin/config.php \
  -H "Origin: https://yourdomain" \
  -H "X-CSRF-Token: <token>" \
  -H "Authorization: Bearer <admin_token>" \
  -d '{"_csrf_token":"<token>","nom":"test","valeur":"value"}'
# Expected: 201 (Élément de configuration ajouté)
```

## Deployment Timeline

- **Sprint 1:** Protect all admin endpoints with auth/CSRF ✓
- **Sprint 2:** Add redirects for legacy routes
- **Sprint 3:** Update frontend links & test in staging
- **Sprint 4:** Monitor logs for legacy route hits
- **Sprint 5:** Remove deprecated files

## Rollback Plan

If issues occur:
1. Revert to previous branch
2. Check server logs for auth failures
3. Verify environment variables (CSRF tokens, API keys)
4. Test locally before re-deploying
