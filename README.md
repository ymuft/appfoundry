# AppFoundry

**A secure-by-default foundation for small self-hosted internal web applications.**

AppFoundry is intentionally boring infrastructure: authentication, authorization, CSRF protection, login throttling, audit logs, migrations, health checks, Docker, CI, and a small admin surface — already wired together so you can spend your time building the actual application.

Use it as a base for inventory tools, internal dashboards, small CRMs, maintenance systems, admin portals, help desks, or other self-hosted business software.

## What you get

- PHP 8.3 with a small framework-free core
- session-based authentication with strict-mode session IDs and ID rotation after login
- role-based authorization (`admin`, `manager`, `user`)
- CSRF protection on state-changing requests
- database-backed account + IP login throttling
- audit logging for authentication and user creation
- secure response headers and restrictive CSP
- SQLite by default, MySQL supported through environment configuration
- admin user management example
- `/health` JSON endpoint for monitoring
- Docker image and Docker Compose setup
- GitHub Actions for configuration checks, linting, tests, and Docker builds
- no JavaScript framework and no runtime package dependencies beyond PHP/PDO

## Quick start

```bash
cp .env.example .env
docker compose build
docker compose run --rm app php scripts/migrate.php
docker compose run --rm app php scripts/create-admin.php \
  --name="Admin" \
  --email="admin@example.com"
docker compose up -d
```

The admin command prompts for the password without putting it in the command line. Passwords must contain 12-72 characters.

Open `http://localhost:8080`.

> Before exposing an instance to the internet, set `APP_ENV=production`, enable `APP_SECURE_COOKIES=true` behind HTTPS, use a non-default database password, review your reverse-proxy configuration, and replace the example application screens with your own workflow.

## Architecture

```text
Browser
  │
  ▼
public/index.php
  │
  ├── Security headers
  ├── Session bootstrap
  └── Router
       │
       ├── AuthController ── Auth / CSRF / RateLimiter
       ├── UserController ── RBAC / AuditLogger
       ├── HealthController ─ Database probe
       └── DashboardController

                   PDO
                    │
               SQLite / MySQL
```

The project keeps business features separate from the security foundation. Fork it, replace the dashboard, add your domain controllers and tables, and keep the plumbing underneath.

## Security defaults

AppFoundry starts with safer defaults rather than asking each fork to rediscover them:

| Area | Default |
|---|---|
| Password storage | `password_hash()` / `password_verify()` with automatic rehash on login |
| Password input | 12-72 characters to avoid current bcrypt truncation ambiguity |
| Sessions | strict mode, cookies only, ID regeneration after login, HttpOnly, SameSite=Lax |
| CSRF | 256-bit per-session token, rotated after login |
| Login abuse | per-account and per-IP throttling over a 15-minute window |
| Authorization | explicit role checks |
| Browser policy | restrictive CSP, frame denial, no-sniff, no-store dynamic responses |
| Audit | sign-in, sign-out, and user creation events |
| Secrets | environment file excluded from Git |

This is a starter, not a security certification. Review the threat model for your deployment and add controls appropriate to the data and users involved.

## Database

SQLite is the zero-configuration default:

```env
DB_DRIVER=sqlite
DB_DATABASE=/var/www/html/storage/app.sqlite
```

For MySQL:

```env
DB_DRIVER=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=appfoundry
DB_USERNAME=appfoundry
DB_PASSWORD=replace-this
```

Use `migrations/001_init.mysql.sql` when running with MySQL.

## Project layout

```text
public/             web root and static assets
src/Core/           routing, environment, database and response primitives
src/Security/       auth, password policy, CSRF, rate limiting and browser security headers
src/Audit/          audit trail writer
src/Controllers/    example application controllers
views/              server-rendered UI
migrations/         SQLite and MySQL schema
scripts/            migration, configuration and first-admin commands
tests/              dependency-free integration/smoke tests
docker/             web server configuration
.github/workflows/  CI
```

## Extending it

A useful first fork usually does three things:

1. Replace the starter dashboard with the real domain UI.
2. Add domain tables and migrations.
3. Add routes/controllers while keeping authorization checks close to sensitive actions.

Avoid putting authorization only in the UI. Every privileged server-side action should enforce its own role or permission check.

## Roadmap

The initial release deliberately stays small. Good contributions include PostgreSQL support, permission-based authorization, TOTP, recovery flows, Redis-backed rate limiting, structured audit viewers, and first-class reverse-proxy examples.

## Contributing

Small, focused pull requests are preferred. Read [CONTRIBUTING.md](CONTRIBUTING.md) before opening one.

Security issues should not be filed publicly; see [SECURITY.md](SECURITY.md).

## License

MIT
