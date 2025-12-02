<?php

// database/migrations/2025_12_01_000002_create_student_roles_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_roles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            $t->foreignId('section_id')
                ->constrained('sections')
                ->cascadeOnDelete();

            $t->enum('role', ['CR', 'VCR']);     // Class rep / Vice class rep

            $t->timestamps();

            // 1 CR & 1 VCR per section
            $t->unique(['section_id', 'role'], 'section_role_uq');

            // one role per student (cannot be CR in multiple sections)
            $t->unique('student_id', 'student_role_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_roles');
    }
};
