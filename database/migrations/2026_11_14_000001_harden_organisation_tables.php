<?php

use Illuminate\Database\Migrations\Migration;
use Iquesters\Foundation\Database\SchemaHardening;

/**
 * Platform review D1, D2, D7:
 * - organisation_metas.meta_value VARCHAR(255) -> LONGTEXT
 * - unique (ref_parent, meta_key) on meta tables
 * - created_by / updated_by as nullable foreign keys to users
 */
return new class extends Migration
{
    private array $metaTables = ['organisation_metas', 'team_metas'];

    private array $auditTables = ['organisations', 'organisation_metas', 'teams', 'team_metas'];

    public function up(): void
    {
        SchemaHardening::longText('organisation_metas', 'meta_value');

        foreach ($this->metaTables as $table) {
            SchemaHardening::uniqueMetaKeys($table);
        }

        foreach ($this->auditTables as $table) {
            SchemaHardening::auditForeignKeys($table);
        }
    }

    public function down(): void
    {
        foreach ($this->auditTables as $table) {
            SchemaHardening::dropAuditForeignKeys($table);
        }

        foreach ($this->metaTables as $table) {
            SchemaHardening::dropUniqueMetaKeys($table);
        }
    }
};
