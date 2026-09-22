# Integrating AppFoundry into an existing PHP application

AppFoundry is still primarily a starter project, not a framework. The preferred path for a new internal tool is to fork or copy the starter. Existing applications can also reuse the foundation without adopting AppFoundry's domain structure.

## What to reuse

The reusable foundation is intentionally small:

- `src/Core/` — bootstrap, paths, routing, configuration, database, response and views;
- `src/Security/` — authentication, CSRF, password policy, rate limiting and browser headers;
- `src/Audit/` — audit logging;
- the base migrations for users, rate limits and audit logs.

Your business/domain code should remain outside those namespaces.

## Explicit application root

AppFoundry no longer assumes that `src/Core/` is always two directories below the application root. Web and console entrypoints should explicitly bootstrap the project root:

```php
use App\Core\Bootstrap;

require __DIR__ . '/vendor/autoload.php';
Bootstrap::web(__DIR__);
```

For CLI commands:

```php
Bootstrap::console(__DIR__);
```

`Bootstrap::web()` configures the application root, loads `.env`, validates production-safe configuration, applies security headers and starts the hardened session. `Bootstrap::console()` configures the root and loads `.env` without starting a web session.

`APP_ROOT` is also supported as a fallback for environments that need to define the root before bootstrap.

## Relative paths

Filesystem paths are resolved through `App\Core\Paths`:

```php
use App\Core\Paths;

$path = Paths::resolve('storage/app.sqlite');
```

Relative paths are anchored to the configured application root. Absolute Unix and Windows paths are left unchanged. This behavior applies to `.env` loading, SQLite database paths and server-rendered views.

That means this is portable between local PHP, Apache and Docker:

```env
DB_DRIVER=sqlite
DB_DATABASE=storage/app.sqlite
```

## Embedding under another namespace tree

If an existing application vendors AppFoundry source under a subdirectory such as `src/AppFoundry/`, preserve AppFoundry's `App\\` namespace with Composer:

```json
{
  "autoload": {
    "psr-4": {
      "YourProduct\\": "src/",
      "App\\": "src/AppFoundry/"
    }
  }
}
```

Then call bootstrap with the host application's real root:

```php
Bootstrap::web(dirname(__DIR__));
```

Views, SQLite files and other relative filesystem paths will resolve from that host root instead of from the physical location of AppFoundry's source files.

## Keep the boundary clear

Treat AppFoundry as web/application infrastructure. Do not put product-specific rules into `App\Core`, `App\Security` or `App\Audit`. A useful dependency direction is:

```text
HTTP/controllers
      ↓
product application layer
      ↓
product domain

AppFoundry supplies the surrounding web/security/database plumbing.
```

This keeps a product's domain portable even if its delivery layer changes later.

## Current packaging status

Embedding source is supported, but AppFoundry is not currently published as a Composer library. This is intentional: the project remains a small, auditable starter rather than a general-purpose framework. If repeated real-world integrations show that package distribution would materially reduce duplication, that can be evaluated separately.
