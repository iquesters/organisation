<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Organisation profiles: a public address (slug), longer meta values (about,
     * address), and office bearers (President, Secretary…).
     */
    public function up(): void
    {
        if (! Schema::hasColumn('organisations', 'slug')) {
            Schema::table('organisations', function (Blueprint $table) {
                // The public page's address: /o/{slug}. Set when the profile is saved.
                $table->string('slug', 100)->nullable()->unique()->after('name');
            });
        }

        // Profile texts (about, address) are longer than 255 characters.
        Schema::table('organisation_metas', function (Blueprint $table) {
            $table->text('meta_value')->nullable()->change();
        });

        if (! Schema::hasTable('organisation_office_bearers')) {
            Schema::create('organisation_office_bearers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('organisation_id');
                $table->string('title', 100);
                $table->string('name');
                // Shown to members only, never on the public page.
                $table->string('email')->nullable();
                $table->string('phone', 32)->nullable();
                $table->boolean('is_public')->default(true);
                $table->unsignedInteger('sort')->default(0);
                $table->timestamps();

                $table->index(['organisation_id', 'sort']);
                $table->foreign('organisation_id')->references('id')->on('organisations')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('organisation_office_bearers');

        if (Schema::hasColumn('organisations', 'slug')) {
            Schema::table('organisations', function (Blueprint $table) {
                $table->dropUnique(['slug']);
                $table->dropColumn('slug');
            });
        }
        // meta_value stays text: shortening it could cut existing values.
    }
};
