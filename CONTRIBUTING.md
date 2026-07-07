# Contributing

Thanks for helping improve the Minimax SDK.

## Questions & ideas

For questions, ideas, or to show what you built, start a thread in
[Discussions](https://github.com/Nejcc/minimax-sdk/discussions/1) rather than
opening an issue. Issues are for confirmed bugs and concrete feature requests.

## Reporting bugs

Open an issue with the bug template. Please include your PHP and Laravel
versions, the localization (SI/HR/RS), and a minimal reproduction. Never paste
real credentials, client secrets, or tokens — redact them.

## Security

Do **not** open a public issue for vulnerabilities. Report them privately — see
[SECURITY.md](SECURITY.md).

## Development

```bash
git clone git@github.com:Nejcc/minimax-sdk.git
cd minimax-sdk
composer install
composer test
```

The suite runs against fake mode, so no Minimax credentials are needed. It keeps
**100% line coverage** — add or update tests for any change and keep it there:

```bash
XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-text --coverage-filter=src
```

## Pull requests

- Branch off `master`, keep the PR focused on one thing.
- Match the existing style: `declare(strict_types=1)`, typed signatures,
  `final` classes, PHPDoc array shapes. Run **Pint** before pushing:
  ```bash
  vendor/bin/pint
  ```
- Add tests and make sure the whole suite is green.
- Write a clear description of what changed and why.

## Conventions

- Resources return plain arrays using Minimax's own field names — don't wrap
  them in DTOs.
- Anything touching auth, request building, or the token cache is a security
  boundary — be conservative and add a test for the failure path.
