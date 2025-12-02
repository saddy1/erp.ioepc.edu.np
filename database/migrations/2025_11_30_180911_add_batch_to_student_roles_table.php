<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('student_roles', function (Blueprint $t) {
            // match students.batch: string(30), nullable
            $t->string('batch', 30)
              ->nullable()
              ->after('section_id');

            // optional index to speed lookup
            $t->index(['section_id', 'batch', 'role'], 'sr_sec_batch_role_idx');
        });
    }

    public function down(): void
    {
        Schema::table('student_roles', function (Blueprint $t) {
            $t->dropIndex('sr_sec_batch_role_idx');
            $t->dropColumn('batch');
        });
    }
};
