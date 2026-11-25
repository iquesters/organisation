<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * A user's role in each organisation they belong to: "member" or "org-admin".
 *
 * Normalised as an extension of the membership row: (organisation_id,
 * user_id, user_type) is a foreign key to model_has_organisations'
 * primary key (organisation_id, model_id, model_type), cascading on delete.
 * The membership itself is stored once (model_has_organisations); this
 * table holds only what depends on it (role, status, audit), so a role can't
 * outlive its membership whichever code removes it.
 *
 * Organisation admin rights come only from here: being org-admin of one
 * organisation gives nothing in another, where the person is only a member
 * until their role there is changed.
 *
 * Existing installs keep their behaviour: every user already in an
 * organisation gets a row, as org-admin when they had the global "admin" role
 * (the old rule), else as member.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('organisation_user_roles')) {
            Schema::create('organisation_user_roles', function (Blueprint $table) {
                $table->id();
                $table->string('uid', 26)->unique();
                $table->unsignedBigInteger('organisation_id');
                $table->unsignedBigInteger('user_id');
                // The user model's morph class, as in model_has_organisations.model_type.
                $table->string('user_type');
                $table->string('role', 32)->default('member');
                $table->string('status', 32)->default('active');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                $table->unique(['organisation_id', 'user_id', 'user_type']);
                $table->index(['user_id', 'role', 'status']);
                $table->foreign(['organisation_id', 'user_id', 'user_type'], 'organisation_user_roles_membership_fk')
                    ->references(['organisation_id', 'model_id', 'model_type'])->on('model_has_organisations')
                    ->cascadeOnDelete();
            });
        }

        $this->backfill();
    }

    public function down(): void
    {
        Schema::dropIfExists('organisation_user_roles');
    }

    private function backfill(): void
    {
        if (! Schema::hasTable('model_has_organisations') || ! Schema::hasTable('users')) {
            return;
        }

        $userClass = config('auth.providers.users.model', 'App\\Models\\User');
        $userTypes = array_unique(array_filter([$userClass, (new $userClass)->getMorphClass()]));

        $admins = [];
        if (Schema::hasTable('roles') && Schema::hasTable('model_has_roles')) {
            $admins = DB::table('model_has_roles as mr')
                ->join('roles as r', 'r.id', '=', 'mr.role_id')
                ->where('r.name', 'admin')->where('r.guard_name', 'web')
                ->whereIn('mr.model_type', $userTypes)
                ->pluck('mr.model_id')->map(fn ($id) => (int) $id)->all();
        }

        $existing = DB::table('organisation_user_roles')->get(['organisation_id', 'user_id', 'user_type'])
            ->map(fn ($row) => $row->organisation_id.':'.$row->user_id.':'.$row->user_type)->flip();

        DB::table('model_has_organisations')
            ->whereIn('model_type', $userTypes)
            ->orderBy('organisation_id')
            ->get(['organisation_id', 'model_id', 'model_type'])
            ->each(function ($link) use ($admins, $existing) {
                if ($existing->has($link->organisation_id.':'.$link->model_id.':'.$link->model_type)) {
                    return;
                }
                DB::table('organisation_user_roles')->insert([
                    'uid' => (string) Str::ulid(),
                    'organisation_id' => $link->organisation_id,
                    'user_id' => $link->model_id,
                    'user_type' => $link->model_type,
                    'role' => in_array((int) $link->model_id, $admins, true) ? 'org-admin' : 'member',
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }
};
