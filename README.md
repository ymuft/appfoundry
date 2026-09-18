<p align="center">
  <img src="docs/assets/appfoundry-hero.jpg" alt="AppFoundry — secure-by-default foundation for self-hosted internal web applications" width="100%">
</p>

<h1 align="center">AppFoundry</h1>

<p align="center"><strong>Build internal tools without rebuilding authentication, security, and deployment plumbing every time.</strong></p>

<p align="center">
  <a href="https://github.com/ymuft/appfoundry/actions/workflows/ci.yml"><img alt="CI" src="https://github.com/ymuft/appfoundry/actions/workflows/ci.yml/badge.svg"></a>
  <img alt="PHP 8.3+" src="https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white">
  <img alt="Docker" src="https://img.shields.io/badge/Docker-ready-2496ED?logo=docker&logoColor=white">
  <a href="LICENSE"><img alt="MIT License" src="https://img.shields.io/badge/license-MIT-green"></a>
</p>

AppFoundry is a small, framework-free PHP foundation for self-hosted business applications. It gives you the boring but important parts up front — sessions, authorization, CSRF protection, login throttling, audit logging, database setup, Docker, CI, and a minimal admin surface — so a fork can focus on its actual business workflow.

Use it for inventory tools, internal dashboards, small CRMs, maintenance systems, admin portals, help desks, operations tools, or other self-hosted software.

> **Looking to contribute?** Start with the [good first issues](https://github.com/ymuft/appfoundry/issues?q=is%3Aissue+is%3Aopen+label%3A%22good+first+issue%22) or browse the [contribution page](https://github.com/ymuft/appfoundry/contribute).

## Quick start

```bash
git clone https://github.com/ymuft/appfoundry.git
cd appfoundry
cp .env.example .env

docker compose build
docker compose run --rm app php scripts/migrate.php
docker compose run --rm app php scripts/create-admin.php \
  --name="Admin" \
  --email="admin@example.com"
docker compose up -d
```

The admin command prompts for the password without putting it in your shell history. Passwords must contain 12–72 characters.

Open **http://localhost:8080**.

Before exposing an instance to the internet, set `APP_ENV=production`, enable `APP_SECURE_COOKIES=true` behind HTTPS, use a non-default database password, and review your reverse-proxy configuration.

## What is already wired together

| Area | Included |
| --- | --- |
| Authentication | Session-based login, strict-mode session IDs, ID rotation after login |
| Authorization | Built-in `admin`, `manager`, and `user` roles |
| Request security | CSRF tokens, restrictive CSP, frame denial, no-sniff, no-store responses |
| Login abuse | Database-backed per-account and per-IP throttling |
| Passwords | `password_hash()` / `password_verify()` with automatic rehash |
| Audit | Authentication and user-creation events with metadata |
| Database | SQLite by default; MySQL supported |
| Operations | `/health`, Docker image, Docker Compose, production PHP settings |
| Quality | Composer validation, configuration checks, linting, tests, Docker build in CI |

No JavaScript framework is required and there are no runtime package dependencies beyond PHP/PDO.

## How a fork becomes an application

```text
AppFoundry
    │
    ├── keep: auth / sessions / CSRF / RBAC / audit / Docker / CI
    │
    └── replace: example dashboard + domain tables + domain controllers
                         │
                         ▼
            inventory / CRM / help desk / admin tool / ...
```

A typical fork only needs to add its domain tables, controllers, routes, and UI while keeping the foundation underneath.

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

The foundation is deliberately small enough to read and audit without learning a custom framework first.

## Security defaults

| Area | Default |
| --- | --- |
| Password storage | `password_hash()` / `password_verify()` with automatic rehash on login |
| Password input | 12–72 characters to avoid current bcrypt truncation ambiguity |
| Sessions | strict mode, cookies only, ID regeneration after login, HttpOnly, SameSite=Lax |
| CSRF | 256-bit per-session token, rotated after login |
| Login abuse | per-account and per-IP throttling over a 15-minute window |
| Authorization | explicit server-side role checks |
| Browser policy | restrictive CSP, frame denial, no-sniff, no-store dynamic responses |
| Audit | sign-in, sign-out, and user-creation events |
| Secrets | environment file excluded from Git |

AppFoundry is a starter, not a security certification. Every deployment still needs a threat model appropriate to its users, data, network, and reverse-proxy setup.

## Database

SQLite is the zero-configuration default:

```env
DB_DRIVER=sqlite
DB_DATABASE=/var/www/html/storage/app.sqlite
```

MySQL is supported through environment configuration:

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
docs/assets/        repository artwork
.github/workflows/  CI
```

## Contributing

Contributions are welcome. Small, focused pull requests are easier to review and merge.

Good places to start:

- [Disable / re-enable user accounts — #6](https://github.com/ymuft/appfoundry/issues/6)
- [Read-only audit log viewer — #7](https://github.com/ymuft/appfoundry/issues/7)
- [End-to-end HTTP smoke tests — #11](https://github.com/ymuft/appfoundry/issues/11)

More substantial work is tracked under [`help wanted`](https://github.com/ymuft/appfoundry/issues?q=is%3Aissue+is%3Aopen+label%3A%22help+wanted%22), including session expiration, versioned migrations, and trusted-proxy support.

Read [CONTRIBUTING.md](CONTRIBUTING.md) before opening a pull request. Security issues should not be filed publicly; see [SECURITY.md](SECURITY.md).

## Roadmap

The project intentionally starts small. Current directions include PostgreSQL, optional TOTP MFA, permission-based authorization, Redis-backed rate limiting, session expiration, a versioned migration runner, trusted-proxy support, and stronger HTTP-level test coverage.

The roadmap lives in [open issues](https://github.com/ymuft/appfoundry/issues).

## License

MIT
