<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('student_roles', function (Blueprint $t) {
            // Match students.year / students.semester (tiny ints)
            $t->unsignedTinyInteger('year')
              ->nullable()
              ->after('section_id');

            $t->unsignedTinyInteger('semester')
              ->nullable()
              ->after('year');

            // optional: if you already added batch earlier, keep it; otherwise ignore

            // Helpful index for lookups
            $t->index(['section_id', 'year', 'semester', 'role'], 'sr_sec_year_sem_role_idx');
        });
    }

    public function down(): void
    {
        Schema::table('student_roles', function (Blueprint $t) {
            $t->dropIndex('sr_sec_year_sem_role_idx');
            $t->dropColumn(['year', 'semester']);
        });
    }
};
