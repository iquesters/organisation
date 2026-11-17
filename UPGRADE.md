# Upgrading iquesters/organisation

## 1.x → 2.0

Upgrade together with foundation, user-interface and user-management; follow foundation's
`UPGRADE.md` first (requirements, migrations, admin roles).

### Breaking changes
- `config/organisation.php` and `ORGANISATION_LAYOUT` are removed: the layout is the DB setting
  `organisation_layout`. Remove a published copy. See `docs/developer/configuration.md`.
- Managing organisations and teams requires an admin role (foundation `admin_roles`); members can
  only view organisations they belong to.
- `App\Models\User` gets the `HasOrganisations` trait automatically on `composer update`
  (`package:discover`).
