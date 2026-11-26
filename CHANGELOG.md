# Changelog

All notable changes to this package are documented here.
Format: [Keep a Changelog](https://keepachangelog.com/en/1.1.0/); versions follow [SemVer](https://semver.org/).

## [Unreleased]

### Fixed
- **`OrganisationPolicies::url()`** reads the organisation's uid when it was loaded without it, instead of failing to build the link.
 – 2.0.0

### Added (where a visitor came from)
- **`OrganisationContext::rememberVisit()` / `cameFrom()`**: the organisation whose public page (or public event) a visitor came through, kept in the session before they sign in. Other modules preselect it (community's membership page). The organisation's public page remembers it.

### Changed (public page address)
- **The public page is at `/o/{uid}`**, which never changes (a rename doesn't break shared links); an old address by name (`/o/{slug}`) redirects there (301). The *Public address* field left the profile form; the page needs only *Public page* switched on.

### Changed (test round 3)
- **All Organisations is super-admin only** (menu item, list and its data). Org-admins and members open their own organisation with **My organisation**; an old link to an organisation shows its profile. The list's record shows only the details (no Profile / Public page links). Without an organisation, My organisation goes to the dashboard. Existing installs: `organisation:seed` takes `view-organisations` from the `admin` role.
- **No organisation tabs:** the sidebar is the menu and the header's organisation switcher picks the organisation. The tab bar is gone from the profile page, and the Organisations record shows the details, then plain **Profile** and **Public page** links. The old tab addresses still redirect to the filtered tables.

### Fixed (test round 2)
- **The organisation record has its tabs again:** Overview, People and roles, Teams, Profile (and Public page), above its details.
- **Edit an organisation:** super-admins, and now its org-admins (name and description; the status stays super-admin only). The record says who may edit (`_can`).
- **People and roles:** a person's record shows who they are, then **Role** and **Remove** (with a confirmation box) for whoever may change them; your own row says why you can't. **Add person** can pick an **existing account** (super-admins; org-admins type the email).
- **Profile:** a **Links** box with the public page's address (**Open**, **Copy link**) or "the public page is off", and the **Terms and Privacy** links (the organisation's own, or the platform's) with Copy link. New field **Public address** (`/o/{address}`, unique), so a renamed organisation can change it. The public page's footer links the Terms and Privacy.
- **The module for everyone in an organisation:** `admin` (org-admins), `member` and `user` get the Organisation module; members see My organisation, org-admins also All Organisations (theirs), People and roles, Teams.
- **Sidebar:** All Organisations, People and roles and Teams open their tables directly, so the item is highlighted on the list page (the old addresses redirect).

### Changed (organisation admins per organisation)
- **Org-admin is a role per organisation** (new table `organisation_user_roles`: organisation, user, role `member` | `org-admin`, status, audit). Someone administers only the organisations where their role is org-admin; an org-admin of one school who joins another as a member is **only a member** there until their role there is changed. The global `admin` role no longer grants any organisation: it only opens the admin menus, and follows the per-organisation roles automatically (`OrganisationRoles::syncMenuRole`).
- The table extends the membership row: a composite foreign key to `model_has_organisations` (organisation, user, user type) cascading on delete, so the membership is stored once and a role can't outlive it. Memberships made without a role get one as member (`OrganisationRoles::heal`).
- **Existing installs keep their behaviour:** the migration gives every user already in an organisation a row: org-admin when they had the global `admin` role (the old rule), else member.
- `OrganisationAccess` reads only this table (Track B's `organisation_role_assignments` and the global-admin fallback are no longer used); community, event and commerce follow.

### Changed (every list is the generic table, every form a form schema)
- **Organisations** (`organisation-table`, rows from `/organisations/data`): everyone sees theirs with their role; super-admins see all and create them (`organisation-details`). The record shows the details and links to Profile, People and roles, Teams and the public page. Organisations are made inactive, not deleted.
- **People and roles** (`organisation-people-table`, `/organisation-people/data`, `?organisation={uid}`; new sidebar item, permission `manage-organisation-people`): super-admins and the organisation's org-admins **add people** (an existing account by email, or a new account with a name: they sign in with Google or "Forgot password"), **change their role**, and **remove them** from the organisation. Deleting an account stays with user management. Nobody can change or remove themselves (no lock-out).
- **Teams** (`organisation-team-table`, `/organisation-teams/data`; form `organisation-team-details` with the team's people as checkboxes, people of the organisation only).
- The old pages (`/organisations/{uid}/show`, `/users`, `/teams`, `/create`, `/edit`) redirect to the tables. The hand-written views and `OrganisationUserController` are gone.

### Added (terms and privacy per organisation)
- Profile section **Terms and privacy:** a link or the text for each, and a version. The text has its own public page (`/organisations/{uid}/terms`, `/organisations/{uid}/privacy`); without either, the platform's pages apply (`OrganisationPolicies`).

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
