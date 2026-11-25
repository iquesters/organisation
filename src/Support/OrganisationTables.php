<?php

namespace Iquesters\Organisation\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Iquesters\Organisation\Http\Controllers\OrganisationController;
use Iquesters\Organisation\Http\Controllers\OrganisationPeopleController;
use Iquesters\Organisation\Http\Controllers\OrganisationTeamController;

/**
 * The organisation module's lists, all on the generic table with rows from the
 * module (data_url, limited to what the user may see): organisations, people
 * and their roles, teams. organisation:seed keeps the data source, create form
 * and record sections up to date; admins may change columns and options.
 */
class OrganisationTables
{
    public static function syncAll(): void
    {
        self::sync(OrganisationController::TABLE, 'Organisations', 'Organisations you belong to (all of them for super-admins)', self::organisations());
        self::sync(OrganisationPeopleController::TABLE, 'People and roles', 'People of the organisations you manage, and their role there', self::people());
        self::sync(OrganisationTeamController::TABLE, 'Teams', 'Teams of the organisations you manage', self::teams());
    }

    public static function organisations(): array
    {
        return [
            'entity' => 'organisations',
            'data_url' => '/organisations/data',
            'create' => ['form' => OrganisationForms::DETAILS, 'label' => 'New organisation', 'permission' => 'create-organisations'],
            // The organisation's tabs first, then its details (Edit for super-admins and its org-admins).
            'record' => [
                ['html' => '/organisations/{uid}/links'],
                ['form' => OrganisationForms::DETAILS, 'mode' => 'view', 'title' => 'Organisation'],
            ],
            'dt-options' => [
                'columns' => [
                    ['data' => 'name', 'title' => 'Organisation', 'visible' => true, 'link' => true, 'form-schema-uid' => OrganisationForms::DETAILS],
                    ['data' => 'my_role', 'title' => 'Your role', 'visible' => true],
                    ['data' => 'people', 'title' => 'People', 'visible' => true],
                    ['data' => 'status_label', 'title' => 'Status', 'visible' => true],
                ],
                'options' => ['pageLength' => 25, 'order' => [], 'responsive' => true],
            ],
            'default_view_mode' => 'inbox',
        ];
    }

    public static function people(): array
    {
        return [
            'entity' => 'organisation_user_roles',
            'data_url' => '/organisation-people/data',
            'create' => ['form' => OrganisationForms::PERSON_ADD, 'label' => 'Add person', 'permission' => 'manage-organisation-people'],
            // Who they are, then (for whoever may manage them: not themselves) the role and removing them.
            'record' => [
                ['html' => '/organisation-people/{uid}/summary'],
                ['form' => OrganisationForms::PERSON_ROLE, 'mode' => 'edit', 'title' => 'Role', 'when' => ['can_manage' => '1']],
                ['form' => OrganisationForms::PERSON_REMOVE, 'mode' => 'edit', 'title' => 'Remove from the organisation', 'when' => ['can_manage' => '1']],
            ],
            'dt-options' => [
                'columns' => [
                    ['data' => 'name', 'title' => 'Name', 'visible' => true, 'link' => true, 'form-schema-uid' => OrganisationForms::PERSON_ROLE],
                    ['data' => 'email', 'title' => 'Email', 'visible' => true],
                    ['data' => 'organisation', 'title' => 'Organisation', 'visible' => true],
                    ['data' => 'role_label', 'title' => 'Role', 'visible' => true],
                    ['data' => 'since', 'title' => 'Since', 'visible' => true],
                ],
                'options' => ['pageLength' => 25, 'order' => [], 'responsive' => true],
            ],
            'default_view_mode' => 'inbox',
        ];
    }

    public static function teams(): array
    {
        return [
            'entity' => 'teams',
            'data_url' => '/organisation-teams/data',
            'create' => ['form' => OrganisationForms::TEAM, 'label' => 'New team', 'permission' => 'manage-organisation-people'],
            'record' => [
                ['form' => OrganisationForms::TEAM, 'mode' => 'edit', 'title' => 'Team'],
            ],
            'dt-options' => [
                'columns' => [
                    ['data' => 'name', 'title' => 'Team', 'visible' => true, 'link' => true, 'form-schema-uid' => OrganisationForms::TEAM],
                    ['data' => 'organisation', 'title' => 'Organisation', 'visible' => true],
                    ['data' => 'people', 'title' => 'People', 'visible' => true],
                    ['data' => 'status_label', 'title' => 'Status', 'visible' => true],
                ],
                'options' => ['pageLength' => 25, 'order' => [], 'responsive' => true],
            ],
            'default_view_mode' => 'inbox',
        ];
    }

    private static function sync(string $slug, string $name, string $description, array $schema): void
    {
        if (! Schema::hasTable('table_schemas')) {
            return;
        }

        $existing = DB::table('table_schemas')->where('slug', $slug)->first(['uid', 'schema', 'created_at']);
        if ($existing) {
            // Admins may change columns and options; the data source, create form and record sections are the module's.
            $stored = json_decode((string) $existing->schema, true) ?: [];
            $schema = array_merge($stored, array_intersect_key($schema, array_flip(['entity', 'data_url', 'create', 'record'])));
        }

        DB::table('table_schemas')->updateOrInsert(['slug' => $slug], [
            'uid' => $existing->uid ?? (string) Str::ulid(),
            'name' => $name,
            'description' => $description,
            'schema' => json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'status' => 'active',
            'created_at' => $existing->created_at ?? now(),
            'updated_at' => now(),
        ]);
    }
}
