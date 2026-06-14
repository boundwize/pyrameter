# Contributing

Contributions are welcome via Pull Requests on [GitHub](https://github.com/boundwize/pyrameter).

## Setup

Fork the repository on GitHub, then clone your fork:

```bash
git clone https://github.com/<your-username>/pyrameter.git
cd pyrameter
git remote add upstream https://github.com/boundwize/pyrameter.git
composer install
```

## Tooling

| Command | Description |
|---|---|
| `composer test` | Run the test suite |
| `composer cs-check` | Check coding standard |
| `composer cs-fix` | Fix coding standard violations |
| `composer phpstan` | Run static analysis |
| `composer rector` | Check for Rector suggestions (dry-run) |
| `composer structarmed` | Run structarmed in this repository itself |

All checks must pass before a PR will be merged. CI runs against PHP 8.2, 8.3, and 8.4 on Linux, macOS, and Windows.
