# Changelog

All notable changes to this package are documented here.
Format: [Keep a Changelog](https://keepachangelog.com/en/1.1.0/); versions follow [SemVer](https://semver.org/).

## [Unreleased] – 2.0.0

### Fixed
- **The profile edit page uses the full content width** like every other page.

### Changed
- **The profile edit page is a form schema** (`organisation-profile`, rebuilt by `organisation:seed`): sections About / Contact / Social links / Office bearers (a repeater; rows keep their id) / Logo and public page, with the logo upload. It posts JSON to `PUT /organisations/{uid}/profile` (a plain form post still redirects).

### Fixed
- **Users and Teams were hard to reach:** the Organisations list linked the name to the new profile instead of the organisation page (Overview / Users / Teams). The name opens the organisation again, and **Profile** is a fourth tab on its pages.

### Added
- **Organisation context:** the organisation the user works in, kept in the session (`Support\OrganisationContext`).
  - **Header switcher** (registered as a `header-widgets` service): the current organisation; a menu to switch when there is more than one; super-admins can also choose "All organisations".
  - Only organisations the user belongs to or administers (all, for super-admin); a stale choice is ignored. Someone in exactly one organisation is always in it.
  - Routes: `POST /organisations/context` (switch), `DELETE /organisations/context` (all organisations), `GET /organisations/mine` (the current organisation's profile, else the list).
  - Sidebar: **My organisation**.
  - Other modules default to it: `app(OrganisationContext::class)->current()`.
- **Organisation profile** (`/organisations/{uid}/profile`):
  - details: logo, tagline, about, founded year, affiliation / board, registration number, email, phone, website, address, and Facebook / Instagram / LinkedIn / X / YouTube links;
  - **office bearers** (title, name, email, phone; each one shown on the public page or not);
  - members see it; **organisation admins edit their own profile**; super-admin edits all. Creating and deleting organisations stay super-admin only.
- **Public page** (`/o/{slug}`), switched on per organisation in the profile: the details and the public office bearers, never their contact details. The slug comes from the name (`ttvians-alumni`, then `-2` if taken).
- **Logo** stored privately (`local` disk, `organisation/logos/{uid}`) and served by `/organisations/{uid}/logo`: to those who may see the profile, or to anyone while the public page is on. No `storage:link` needed.
- **`OrganisationAccess`**: who administers which organisation. Super-admin: all. Otherwise Track B's `TenantAuthorization` (explicit `organisation_role_assignments`) when that table exists; until then, a user with the `admin` role administers the organisations they belong to (logged once as a warning). Members (`model_has_organisations`) may view.
- Migration `2026_11_25_000001_add_organisation_profiles`: `organisations.slug` (unique, nullable), `organisation_metas.meta_value` widened to text, table `organisation_office_bearers`.
- The organisations list links to the profile; the Show page has a Profile button.
- **Organisation access scope for the entity API** (`Support\OrganisationScopeResolver`, tagged `entity-scope-resolvers`). Foundation's entity API refuses any table with an `organisation_id` column until a resolver says which organisations the user may use ("No access-scope resolver is registered for the 'organisation_id' column"). Now: super-admin gets every organisation; others get the organisations they belong to; guests none.

### Housekeeping
- Added Pint/Larastan, test CI, `.gitattributes`, `SECURITY.md`, `CONTRIBUTING.md`, `UPGRADE.md` and composer metadata.

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
