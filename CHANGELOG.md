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
- On `composer install/update` the app's `User` model gets the `HasOrganisations` trait automatically.

### Changed
- Admin check uses foundation's `EnsureAdmin::roles()` (DB setting) instead of a config lookup.
- Removed the unused `api_conf` setting (module APIs are declared in composer.json now).
- Docs no longer list organisation/team API endpoints that never existed.
- Logging uses foundation's `Loggable`.
- Real dependencies declared; Laravel 13 allowed.

### Fixed
- Database: `organisation_metas` LONGTEXT and unique keys, audit FKs.

### Security
- Organisation management restricted to admins; membership checked.
