# Contributing to AppFoundry

Thanks for considering a contribution. AppFoundry is intentionally small enough that contributors can understand the foundation before changing it.

## Find something to work on

Good entry points:

- [good first issues](https://github.com/ymuft/appfoundry/issues?q=is%3Aissue+is%3Aopen+label%3A%22good+first+issue%22)
- [help wanted](https://github.com/ymuft/appfoundry/issues?q=is%3Aissue+is%3Aopen+label%3A%22help+wanted%22)
- [all open issues](https://github.com/ymuft/appfoundry/issues)

If an issue is unassigned, leave a short comment before starting so duplicate work is less likely.

For larger features or architectural changes, open or join an issue first so the design can be discussed before implementation.

## Local setup

```bash
git clone https://github.com/YOUR-USERNAME/appfoundry.git
cd appfoundry
cp .env.example .env
composer install
php scripts/migrate.php
composer test
```

Docker is also supported through `docker compose`.

## Create a branch

Use a short, descriptive branch name, for example:

```bash
git checkout -b fix/session-expiration
git checkout -b feat/audit-viewer
```

Keep a pull request focused on one behavior or issue whenever possible.

## Before opening a pull request

Run:

```bash
composer validate --strict
APP_ENV=local php scripts/check-config.php
composer lint
composer test
docker build -t appfoundry:test .
```

For security-sensitive logic, add or update tests. UI screenshots are useful, but are not a substitute for tests.

## Pull request expectations

A useful PR description should explain:

- what problem it solves
- the approach used
- relevant security implications
- how the change was tested
- the issue it closes, when applicable

Please avoid unrelated refactors in the same pull request.

## Project constraints

- keep configuration explicit
- keep authorization enforcement server-side
- avoid runtime dependencies unless they remove more complexity than they add
- do not weaken a security default for convenience without documenting the tradeoff
- preserve SQLite as the low-friction default unless the issue explicitly changes that direction
- keep the foundation understandable without requiring a large framework

## AI-assisted contributions

AI-assisted development is welcome, but contributors are expected to understand and review the code they submit. Please mention substantial AI assistance in the pull request description. Bulk or speculative PRs that have not been tested or reviewed by the submitter may be closed.

## Security reports

Do not open a public issue for a suspected vulnerability. Follow [SECURITY.md](SECURITY.md) instead.
