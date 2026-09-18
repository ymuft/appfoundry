# Contributing

AppFoundry is meant to remain understandable enough that someone can audit the foundation before building on it.

## Before opening a PR

- keep changes focused on one behavior
- add or update tests for security-sensitive logic
- avoid introducing runtime dependencies unless they clearly remove more complexity than they add
- keep configuration explicit
- do not weaken a security default for convenience without documenting the tradeoff

Run:

```bash
composer validate --strict
composer lint
composer test
```

## Pull requests

Explain the problem, the chosen approach, security implications, and how you tested it. Screenshots are useful for UI changes but are not a substitute for tests.

For substantial changes, open an issue first so the design can be discussed before implementation.
