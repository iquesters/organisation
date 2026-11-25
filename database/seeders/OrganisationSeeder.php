<?php

namespace Iquesters\Organisation\Database\Seeders;

use Illuminate\Support\Facades\DB;
use Iquesters\Foundation\Models\Module;
use Iquesters\Organisation\Http\Controllers\OrganisationController;
use Iquesters\Organisation\Http\Controllers\OrganisationPeopleController;
use Iquesters\Organisation\Http\Controllers\OrganisationTeamController;
use Iquesters\Foundation\Database\Seeders\BaseSeeder;
use Iquesters\Organisation\Support\OrganisationForms;
use Iquesters\Organisation\Support\OrganisationProfile;
use Iquesters\Organisation\Support\OrganisationRoles;
use Iquesters\Organisation\Support\OrganisationTables;
use Spatie\Permission\Models\Role;

class OrganisationSeeder extends BaseSeeder
{
    /** People and roles, Teams: super-admins and org-admins (who hold the "admin" menu role). */
    public const MANAGE_PEOPLE_PERMISSION = 'manage-organisation-people';

    protected string $moduleName = 'organisation';

    protected string $description = 'organisation module';

    protected array $metas = [
        'module_icon' => 'fas fa-building',
        'module_sidebar_menu' => [
            [
                'key' => 'mine',
                'icon' => 'fas fa-id-card',
                'label' => 'My organisation',
                // The profile of the organisation the user works in (header switcher).
                'route' => 'organisations.mine',
            ],
            // These three open their tables directly (run() adds the table uid), so the
            // sidebar marks them on the list page. The old routes redirect there too.
            [
                'icon' => 'fas fa-building-columns',
                'label' => 'All Organisations',
                'permission' => 'view-organisations',
                'table' => OrganisationController::TABLE,
            ],
            [
                'key' => 'people',
                'icon' => 'fas fa-users',
                'label' => 'People and roles',
                'permission' => self::MANAGE_PEOPLE_PERMISSION,
                'table' => OrganisationPeopleController::TABLE,
            ],
            [
                'key' => 'teams',
                'icon' => 'fas fa-users-cog',
                'label' => 'Teams',
                'permission' => self::MANAGE_PEOPLE_PERMISSION,
                'table' => OrganisationTeamController::TABLE,
            ],
        ],
    ];

    /**
     * Who sees the Organisation module: everyone in an organisation gets "My organisation";
     * org-admins (the "admin" menu role) also All Organisations (theirs), People and roles, Teams.
     */
    public const MODULE_ROLES = ['super-admin', 'admin', 'member', 'user'];

    /** The tables first: the sidebar items link to them by uid. */
    public function run(): void
    {
        OrganisationForms::syncAll();
        OrganisationTables::syncAll();

        $menu = $this->metas['module_sidebar_menu'];
        foreach ($menu as $i => $item) {
            if (isset($item['table'])) {
                $uid = DB::table('table_schemas')->where('slug', $item['table'])->value('uid');
                unset($menu[$i]['table']);
                if ($uid) {
                    $menu[$i]['default-table-schema-uid'] = $uid;
                }
            }
        }
        $this->metas['module_sidebar_menu'] = $menu;

        parent::run();
    }

    protected array $permissions = [
        'view-organisations',
        'create-organisations',
        'edit-organisations',
        'delete-organisations',
        self::MANAGE_PEOPLE_PERMISSION,
    ];

    protected array $entities = [
        'organisations' => [
            'fields' => [],
            'meta_fields' => [],
            'metas' => [],
        ],
        'teams' => [
            'fields' => [],
            'meta_fields' => [],
            'metas' => [],
        ],
        'model_has_organisations' => [
            'fields' => [],
            'meta_fields' => [],
            'metas' => [],
        ],
        'model_has_teams' => [
            'fields' => [],
            'meta_fields' => [],
            'metas' => [],
        ],
    ];

    /**
     * Implement abstract method from BaseSeeder
     */
    protected function seedCustom(): void
    {
        // The profile's edit form (form schema), rebuilt on every seed.
        OrganisationProfile::syncForm();
        // Organisations, people and roles, teams: form and table schemas (rebuilt on every seed).
        OrganisationForms::syncAll();
        OrganisationTables::syncAll();

        // Org-admins hold the global "admin" role (it only opens the menus; which
        // organisations they manage comes from organisation_user_roles).
        Role::findOrCreate(OrganisationRoles::MENU_ROLE, 'web')->givePermissionTo([self::MANAGE_PEOPLE_PERMISSION, 'view-organisations']);

        // The module for everyone in an organisation; the items above decide what each sees.
        $roles = collect(self::MODULE_ROLES)->map(fn (string $name) => Role::findOrCreate($name, 'web'));
        Module::where('name', $this->moduleName)->first()?->assignRoles($roles->pluck('id')->all());
    }
}
