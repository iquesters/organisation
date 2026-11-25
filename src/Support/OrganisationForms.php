<?php

namespace Iquesters\Organisation\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Iquesters\Organisation\Models\OrganisationUserRole;

/**
 * The organisation module's form schemas (user-interface form engine). The
 * module owns them: organisation:seed rebuilds them.
 */
class OrganisationForms
{
    public const DETAILS = 'organisation-details';

    public const PERSON_ADD = 'organisation-person-add';

    public const PERSON_ROLE = 'organisation-person-role';

    public const PERSON_REMOVE = 'organisation-person-remove';

    public const TEAM = 'organisation-team-details';

    public static function syncAll(): void
    {
        self::upsert(self::DETAILS, 'Organisation', self::details());
        self::upsert(self::PERSON_ADD, 'Add a person to an organisation', self::personAdd());
        self::upsert(self::PERSON_ROLE, 'Role in the organisation', self::personRole());
        self::upsert(self::PERSON_REMOVE, 'Remove from the organisation', self::personRemove());
        self::upsert(self::TEAM, 'Team', self::team());
    }

    public static function roleOptions(): array
    {
        return array_map(fn ($role) => ['value' => $role, 'label' => OrganisationUserRole::label($role)], OrganisationUserRole::ROLES);
    }

    /** Name, description, status. Super-admins create and edit; everyone else sees it. */
    public static function details(): array
    {
        return [
            'defaultFieldSize' => 'auto',
            'submitButtonLabel' => 'Save',
            'modes' => [
                'create' => ['endpoint' => '/organisations', 'method' => 'POST'],
                'edit' => ['endpoint' => '/organisations/{uid}', 'method' => 'PUT', 'allowDelete' => false],
                'view' => ['allowDelete' => false],
            ],
            'fields' => [
                ['id' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'maxLength' => 255],
                ['id' => 'status', 'label' => 'Status', 'type' => 'select', 'required' => true, 'options' => [
                    ['value' => 'active', 'label' => 'Active'],
                    ['value' => 'inactive', 'label' => 'Inactive (hidden from members)'],
                ]],
                ['id' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rows' => 3, 'maxLength' => 2000],
            ],
        ];
    }

    /** Add someone by email: their account, or a new one when the email isn't registered. */
    public static function personAdd(): array
    {
        return [
            'defaultFieldSize' => 'auto',
            'submitButtonLabel' => 'Add',
            'modes' => [
                'create' => ['endpoint' => '/organisation-people', 'method' => 'POST'],
            ],
            'fields' => [
                ['id' => 'organisation_id', 'label' => 'Organisation', 'type' => 'select', 'required' => true, 'optionsFrom' => ['url' => '/organisation-people/options/organisations']],
                ['id' => 'role', 'label' => 'Role', 'type' => 'select', 'required' => true, 'options' => self::roleOptions()],
                ['id' => 'user_uid', 'label' => 'Existing account', 'type' => 'select', 'optionsFrom' => ['url' => '/organisation-people/options/users'],
                    'helperText' => 'Pick someone who has signed up, or leave it and type their email below.'],
                ['id' => 'email', 'label' => 'Or their email', 'type' => 'email', 'maxLength' => 255,
                    'helperText' => 'If nobody has signed up with this email, a new account is made (give the name); they sign in with Google or "Forgot password".'],
                ['id' => 'name', 'label' => 'Name (for a new account)', 'type' => 'text', 'maxLength' => 255],
            ],
        ];
    }

    public static function personRole(): array
    {
        return [
            'endpoint' => '/organisation-people/{uid}',
            'method' => 'PUT',
            'submitButtonLabel' => 'Save role',
            'allowCancel' => false,
            'defaultFieldSize' => 12,
            'modes' => ['edit' => ['allowCancel' => false, 'allowDelete' => false], 'view' => ['allowDelete' => false]],
            'fields' => [
                ['id' => 'role', 'label' => 'Role in this organisation', 'type' => 'select', 'required' => true, 'options' => self::roleOptions(),
                    'helperText' => 'Organisation admins manage this organisation only (members, applications, events). Elsewhere they are what their role there says.'],
            ],
        ];
    }

    public static function personRemove(): array
    {
        return [
            'endpoint' => '/organisation-people/{uid}',
            'method' => 'DELETE',
            'submitButtonLabel' => 'Remove from this organisation',
            'allowCancel' => false,
            'modes' => ['edit' => ['allowCancel' => false, 'allowDelete' => false]],
            // A confirmation, so the form isn't empty and nobody is removed by one click.
            'fields' => [
                ['id' => 'confirm', 'label' => 'Yes, take this person out of the organisation (their account stays)', 'type' => 'checkbox', 'required' => true],
            ],
        ];
    }

    public static function team(): array
    {
        return [
            'defaultFieldSize' => 'auto',
            'submitButtonLabel' => 'Save',
            'modes' => [
                'create' => ['endpoint' => '/organisation-teams', 'method' => 'POST'],
                'edit' => ['endpoint' => '/organisation-teams/{uid}', 'method' => 'PUT', 'allowDelete' => false],
                'view' => ['allowDelete' => false],
            ],
            'fields' => [
                ['id' => 'organisation_id', 'label' => 'Organisation', 'type' => 'select', 'required' => true, 'optionsFrom' => ['url' => '/organisation-people/options/organisations']],
                ['id' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'maxLength' => 255],
                ['id' => 'status', 'label' => 'Status', 'type' => 'select', 'required' => true, 'options' => [
                    ['value' => 'active', 'label' => 'Active'],
                    ['value' => 'inactive', 'label' => 'Inactive'],
                ]],
                ['id' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rows' => 2, 'maxLength' => 2000],
                ['id' => 'user_uids', 'label' => 'Members of the team', 'type' => 'checkbox', 'optionsFrom' => ['url' => '/organisation-teams/options/people?team={uid}'],
                    'helperText' => 'People of the organisation. Save the team first to choose them.'],
            ],
        ];
    }

    private static function upsert(string $slug, string $name, array $schema): void
    {
        if (! Schema::hasTable('form_schemas')) {
            return;
        }

        $existing = DB::table('form_schemas')->where('slug', $slug)->first(['uid', 'created_at']);
        DB::table('form_schemas')->updateOrInsert(['slug' => $slug], [
            'uid' => $existing->uid ?? (string) Str::ulid(),
            'name' => $name,
            'description' => $name.' (organisation:seed rebuilds it)',
            'schema' => json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'status' => 'active',
            'created_at' => $existing->created_at ?? now(),
            'updated_at' => now(),
        ]);
    }
}
