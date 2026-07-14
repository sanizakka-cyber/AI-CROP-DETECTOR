# MSAS Laravel System — Quick Start Guide

## Prerequisites
1. Install **XAMPP** → https://www.apachefriends.org (choose PHP 8.2+)
2. Install **Composer** → https://getcomposer.org/Composer-Setup.exe
3. After installing, open a NEW terminal and verify:
   ```
   php --version
   composer --version
   ```

---

## Setup Steps (Run Once)

```bash
# 1. Create the Laravel project
composer create-project laravel/laravel msas-system
cd msas-system

# 2. Install Laravel Breeze (auth scaffolding)
composer require laravel/breeze --dev
php artisan breeze:install blade

# 3. Install additional packages
composer require barryvdh/laravel-dompdf maatwebsite/excel

# 4. Copy ALL files from the laravel/ folder in this repository into msas-system/

# 5. Configure .env
DB_DATABASE=msas_db
DB_USERNAME=root
DB_PASSWORD=

# 6. Run migrations (order matters)
php artisan migrate

# 7. Seed base data (roles, sample animals, finances)
php artisan db:seed --class=DatabaseSeeder

# 8. Seed the RBAC permission catalogue (100 permissions → 6 role mappings)
php artisan db:seed --class=RolePermissionSeeder

# 9. Register PermissionMiddleware in app/Http/Kernel.php:
#    Add to $routeMiddleware:
#      'permission' => \App\Http\Middleware\PermissionMiddleware::class,

# 10. Start the server
php artisan serve
```

---

## Dev Seed Accounts

Passwords for dev seed accounts are defined in `.env.seed` (never in version control).
After running `DatabaseSeeder`, log in with the emails below and the passwords set in `.env.seed`.

| Role             | Email                | Login method         |
|------------------|----------------------|----------------------|
| CEO              | ceo@msas.ng          | Email + password     |
| Admin            | admin@msas.ng        | Email + password     |
| Vet              | vet@msas.ng          | Email + password     |
| Agronomist       | agro@msas.ng         | Email + password     |
| Farmer           | farmer@msas.ng       | Phone + password     |
| Agro-Dealer      | dealer@msas.ng       | Email + password     |
| Extension Officer| ext@msas.ng          | Email + password     |
| HR               | hr@msas.ng           | Email + password     |
| Finance          | finance@msas.ng      | Email + password     |

> **Change all dev passwords in `.env.seed` before running on any shared or staging server.**

---

## QA Test Accounts

To create one test account per role with cryptographically-random passwords:

```bash
php artisan db:seed --class=QAAccountsSeeder
```

Passwords are printed **once** to the terminal and saved to `storage/qa-credentials-<timestamp>.txt`.

**Required actions after running the QA seeder:**
1. Copy credentials into your team password manager (1Password, Bitwarden, etc.)
2. Delete `storage/qa-credentials-*.txt` immediately
3. Share credentials **only** through the password manager — never via chat, email, or tickets

---

## RBAC — Applying Permissions to Routes

Use the `permission` middleware in `routes/web.php` or `routes/api.php`:

```php
// Single permission
Route::get('/admin/users', [AdminController::class, 'index'])
    ->middleware(['auth', 'permission:user:list_all']);

// Multiple (chain them)
Route::post('/consultations/{id}/prescribe', [ConsultationController::class, 'prescribe'])
    ->middleware(['auth', 'permission:consultation:write_prescription']);

// Role-level guard (existing, unchanged)
Route::get('/ceo/dashboard', [CEOController::class, 'index'])
    ->middleware(['auth', 'role:ceo,admin']);
```

**CEO always passes every `permission:` check automatically** — no DB rows are needed for the CEO role.

**Invalidate the permission cache** after a role change:

```php
use App\Http\Middleware\PermissionMiddleware;

PermissionMiddleware::clearRoleCache($user->role);
```

---

## Pre-Production Checklist

Run through every item before going live:

- [ ] All dev seed passwords changed from defaults (`password`, `farmer123`)
- [ ] `DatabaseSeeder` re-run with strong passwords stored in `.env.seed`
- [ ] `QAAccountsSeeder` accounts deleted: `User::where('is_test_account', true)->forceDelete()`
- [ ] `storage/qa-credentials-*.txt` files deleted from server disk
- [ ] `APP_DEBUG=false` in `.env`
- [ ] `APP_ENV=production` in `.env`
- [ ] HTTPS certificate configured (TLS 1.2+)
- [ ] CSRF middleware active (default in Laravel)
- [ ] Rate limiting verified (`throttle:api` on all API routes)
- [ ] Database backups enabled
- [ ] Error logging routed to a monitoring service (Sentry, Bugsnag, etc.)
- [ ] `PermissionMiddleware` registered in `Kernel.php`
- [ ] `RolePermissionSeeder` run on production database
- [ ] Permission cache warm after deploy: `php artisan cache:clear`
- [ ] Confirm no test data visible in any user-facing screen
- [ ] Audit log table (`audit_logs`) is indexed on `user_id` and `created_at`

---

## Folder Map (Key Files Added)

```
laravel/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── CEOController.php          — CEO/Admin dashboard
│   │   └── Middleware/
│   │       ├── RoleMiddleware.php         — Role-level guard
│   │       └── PermissionMiddleware.php   — Granular action-level RBAC ✨
│   └── Models/
│       ├── Permission.php                 — Permission model ✨
│       └── RolePermission.php             — Role↔Permission pivot ✨
└── database/
    ├── migrations/
    │   ├── add_role_to_users.php
    │   ├── create_core_tables.php
    │   ├── create_operations_tables.php
    │   └── 2026_06_16_create_permissions_tables.php  ✨
    └── seeders/
        ├── DatabaseSeeder.php             — Dev accounts & sample data
        ├── RolePermissionSeeder.php       — 100 permissions, 6 roles ✨
        └── QAAccountsSeeder.php           — Secure QA accounts ✨
```
