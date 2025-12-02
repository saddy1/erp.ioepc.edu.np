<?php
// database/migrations/2025_12_01_000004_create_attendances_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $t) {
            $t->id();

            $t->foreignId('routine_id')
              ->constrained('routines')
              ->cascadeOnDelete();

            $t->foreignId('student_id')
              ->constrained('students')
              ->cascadeOnDelete();

            $t->foreignId('teacher_id')
              ->constrained('teachers')
              ->cascadeOnDelete();

            $t->date('date');                      // actual class date
            $t->enum('status', ['P', 'A']);        // Present / Absent

            $t->timestamps();

            $t->unique(['routine_id', 'student_id', 'date'], 'att_unique_row');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
