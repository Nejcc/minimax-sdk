# Changelog

All notable changes to `nejcc/minimax-sdk` are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

- Nothing yet.

## [0.1.2] - 2026-07-07

### Security

- Reject API paths containing `..` (path-traversal guard) before any request is sent.
- Refuse to follow a `Location` header to a different host, so the Bearer token is never re-sent off-host on a `201 Created`.
- Restrict the MCP `list-resource` / `find-record` tools to slugs in the configured resource registry.

### Added

- `CONTRIBUTING.md`, an issue-template `config.yml` (links to Discussions and private security reporting), and `FUNDING.yml`.

## [0.1.1] - 2026-07-07

### Added

- `auto_invoice` and `auto_invoice_queue` config toggles for host-app auto-invoicing (queued or inline), with the documented `minimax.invoice.*` hook names.

## [0.1.0] - 2026-07-07

### Added

- Initial release.
- OAuth2 password-grant `Client` with leeway-aware token caching (scoped per localization).
- Fluent, org-scoped resources — customers, items, orders, invoices, and the code lists — sharing a common CRUD surface (`all`, `find`, `byCode`, `create`, `update`, `delete`), plus a generic `resource($slug)` escape hatch.
- Issued-invoice helpers: `action()`, `issue()`, `pdf()`.
- Fake mode with canned fixtures for offline development.
- Local-only admin UI: dashboard, live diagnostics, resource browser.
- MCP server `minimax` with read-only tools: `list-organisations`, `list-resource`, `find-record`.
- 100% line coverage and a `SECURITY.md` disclosure policy.

[Unreleased]: https://github.com/Nejcc/minimax-sdk/compare/v0.1.2...HEAD
[0.1.2]: https://github.com/Nejcc/minimax-sdk/compare/v0.1.1...v0.1.2
[0.1.1]: https://github.com/Nejcc/minimax-sdk/compare/v0.1.0...v0.1.1
[0.1.0]: https://github.com/Nejcc/minimax-sdk/releases/tag/v0.1.0
