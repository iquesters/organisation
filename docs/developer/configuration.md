# Configuration (Developer)

Runtime settings come from the database, read through `ConfProvider::from(Module::ORGANISATION)`.
Defaults are in `src/Config/OrganisationConf.php` and seeded by `php artisan organisation:seed`.
There is no `config/organisation.php` and no `.env` keys.

| Key | Default | Purpose |
|---|---|---|
| `organisation_layout` | `organisation::layouts.package` | Layout the package views extend when user-interface is not installed (set e.g. `layouts.app`) |

When iquesters/user-interface is installed, views use its `userinterface::layouts.app` instead.
