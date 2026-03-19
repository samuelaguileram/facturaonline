# Changelog

All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog and this project follows Semantic Versioning.

## [Unreleased]

### Added
- Dedicated services for CFDI general, Carta Porte, payments, retentions, catalogs, clients, migrations, series and products.
- PHPUnit test suite covering the SDK public surface.
- PHPStan and PHP-CS-Fixer configuration.
- GitHub Actions CI workflow.

### Changed
- Package minimum PHP version updated to 8.1 to match language features already used by the SDK.
- Carta Porte, payment and retention operations split into dedicated services while keeping compatibility aliases.

