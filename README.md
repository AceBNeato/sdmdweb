# SDMD Equipment Management System

Equipment and maintenance lifecycle tracking for the Systems and Data Management Division (SDMD). Built on Laravel 12 with Vite-powered assets and role-based access control.

---

## 1. Tech Stack

- **Backend:** PHP 8.2, Laravel 12
- **Frontend:** Vite, Vue/Alpine (Blade templates), Tailwind/Bootstrap mix *(see resources/css & resources/js)*
- **Database:** MySQL / MariaDB
- **Runtime:** Node.js 18+, Composer 2+
- **Recommended Dev Environment:** Laragon on Windows (ships with required PHP extensions)

---

## 2. System Requirements

| Component | Minimum Version | Notes |
|-----------|-----------------|-------|
| PHP | 8.2.x | Enable `fileinfo`, `openssl`, `pdo_mysql`, `mbstring`, `curl`, `zip` |
| Composer | 2.6+ | Used for PHP dependency management |
| Node.js / npm | Node 18+, npm 9+ | Required for Vite asset build |
| Database | MySQL 8 / MariaDB 10.5 | Update `.env` with credentials |
| Git | latest | Source control |

> **Windows users:** Laragon includes PHP, MySQL, and Node out of the box. Ensure PHP is added to your PATH before running artisan commands from terminals outside Laragon.

---

## 3. Quick Start (Fresh Install)

```bash
# 1. Clone project
git clone https://github.com/<org>/sdmdweb.git
cd sdmdweb

# 2. Install PHP dependencies
composer install

# 3. Environment + app key
cp .env.example .env
php artisan key:generate

# 4. Configure .env
#   - DB_DATABASE=sdmd
#   - DB_USERNAME=root
#   - DB_PASSWORD=secret
#   - APP_URL=http://sdmd.test (or your domain)

#   - MAIL_MAILER=smtp
#   - MAIL_HOST=smtp.gmail.com
#   - MAIL_PORT=587
#   - MAIL_USERNAME=sdmdweb1@gmail.com
#   - MAIL_PASSWORD=arkhvuwcuevtdqxb
#   - MAIL_ENCRYPTION=null
#   - MAIL_FROM_ADDRESS=sdmdweb1@gmail.com
#   - MAIL_FROM_NAME="SDMD"

# 5. Run migrations + seeders (creates roles, offices, default users)
php artisan migrate --seed

# 6. Link storage for uploaded avatars, QR codes, etc. (REQUIRED)
php artisan storage:link

# 7. Install and compile assets
npm install
npm run build   # production assets
# or npm run dev for hot reload

# 8. Serve application
php artisan serve
# Visit: http://127.0.0.1:8000
```

---

## 4. Seeded Accounts & Roles

| Role | Email | Password |
|------|-------|----------|
| Super Admin | superadmin@sdmd.ph | superadmin123 |
| Super Admin (2) | superadmin2@sdmd.ph | superadmin123 |
| Admin | arthurdalemicaroz@gmail.com | 12345678 |

> Update credentials immediately in production. Extra demo users/roles can be added in `database/seeders`.

---

## 5. Useful Artisan & npm Commands

| Command | Purpose |
|---------|---------|
| `php artisan migrate:fresh --seed` | Reset database and reseed demo data |
| `php artisan storage:link` | **Important:** expose `storage/app/public` via `public/storage` |
| `php artisan queue:work` | Run queued jobs (if using notifications or async tasks) |
| `php artisan optimize:clear` | Clear config, route, view caches |
| `npm run dev` | Watch + hot reload assets |
| `npm run build` | Compile & minify production assets |

### Composer Scripts

- `composer run dev` ⇒ boots Laravel server, queue listener, and Vite concurrently
- `composer run setup` ⇒ full install workflow (composer + key + migrate + npm build)

---

## 6. Environment Tips

- **File Uploads:** uploaded avatars/QR codes live in `storage/app/public`. Missing images usually mean `php artisan storage:link` was skipped.
- **Sessions:** configured via `config/session.php`; adjust timeout/security as needed.
- **Mail & Queues:** set `MAIL_*` + queue driver in `.env` before enabling email alerts or background jobs.
- **Logging:** check `storage/logs/laravel.log` when debugging.

---

## 7. Project Map

```
app/
├── Http/
│   ├── Controllers/        # Admin, Technician, Staff portals
│   ├── Middleware/         # Security headers, activity logging
│   └── Kernel.php          # Middleware registration
├── Models/                 # Equipment, Users, Roles
├── Services/               # PDF generation, etc.

database/
├── migrations/             # Schema definition
└── seeders/                # RBAC, campuses, equipment types

resources/
├── views/                  # Blade templates
├── css/ & js/              # Vite entrypoints
└── lang/                   # Localisation strings

public/
├── storage/ -> ../storage/app/public (symlink)
└── assets/ (generated)
```

---

## 8. Testing & Quality

```bash
# Run automated tests
php artisan test

# Static analysis / formatting (optional but recommended)
./vendor/bin/pint
```

---

## 9. Maintenance Checklist

- Clear caches after env/config changes:
  ```bash
  php artisan optimize:clear
  ```
- Update dependencies regularly:
  ```bash
  composer update
  npm update
  ```
- Back up `.env` and database before deployment.
- Configure HTTPS and trusted proxies when deploying behind load balancers.

---

## 10. License & Attribution

This project is released under the [MIT License](LICENSE).

<div align="center">
  <sub>Maintained by the SDMD development team.</sub>
</div>
