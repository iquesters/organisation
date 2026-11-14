# Changelog

All notable changes to this package are documented here.
Format: [Keep a Changelog](https://keepachangelog.com/en/1.1.0/); versions follow [SemVer](https://semver.org/).

## [Unreleased] – 2.0.0

### Breaking
- Requires PHP 8.3, Laravel 12/13 and spatie/laravel-permission 8.
- `config/organisation.php` and `ORGANISATION_LAYOUT` removed; views use the DB setting `organisation_layout`.
  See [docs/developer/configuration.md](docs/developer/configuration.md).
- No longer hard-codes `App\Models\User`.

### Added
- Testbench test suite.

### Changed
- Logging uses foundation's `Loggable`.
- Real dependencies declared; Laravel 13 allowed.

### Fixed
- Database: `organisation_metas` LONGTEXT and unique keys, audit FKs.

### Security
- Organisation management restricted to admins; membership checked.
