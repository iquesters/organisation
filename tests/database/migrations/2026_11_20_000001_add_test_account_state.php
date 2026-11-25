<?php

// Disposable account lifecycle fixture; not a deployed Laravel migration.
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('status')->default('active');
            $t->string('uid', 26)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['status', 'uid']));
    }
};
