# Track B — Organisation lifecycle remains platform-only

The existing ensure.admin group now admits only active platform super-admins via Foundation. The show controller explicitly checks only super-admin for cross-membership access, never global admin. Organisation creation, edits, deletion and unconfirmed user/team delegation stay platform-only. A global admin with unrelated membership cannot read or administer another tenant. No organisation or role data is changed by this PR.

## Validation

6 MariaDB regression tests pass (41 assertions), including global-admin create/read/update denial. Only a disposable account-state fixture migration is added under tests/; existing production migrations are unchanged.

## Review and release gates

This branch is for review only. [Architecture and API contract](https://github.com/iquesters/chatbot-util/blob/feature/knorai-rbac-account-management/docs/security/TRACK_B_SECURITY_FOUNDATION.md), [complete source inventory](https://github.com/iquesters/chatbot-util/blob/feature/knorai-rbac-account-management/docs/security/LARAVEL_MIGRATION_INVENTORY.md), and [test evidence](https://github.com/iquesters/chatbot-util/blob/feature/knorai-rbac-account-management/docs/security/TEST_EVIDENCE.md).

Track C (chatbot-util #137) owns additive schema/revision IDs. No new Alembic head, historical SQL edit, live migration, production role grant, merge or deployment is included. Coordinate schema verification, tenant assignments, all Util caller service credentials and the host dependency lock before release. Do not merge schema-dependent changes before the reviewed C contract is agreed. PHP remains transitional; final backend parity moves to Python.
