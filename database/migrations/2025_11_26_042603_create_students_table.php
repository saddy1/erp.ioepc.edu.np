<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('students', function (Blueprint $t) {
            $t->id();

            $t->string('symbol_no', 50)->unique(); // campus roll / symbol
            $t->string('name', 191);

            $t->foreignId('faculty_id')
                ->constrained('faculties')
                ->cascadeOnDelete();

            $t->foreignId('section_id')
                ->nullable()
                ->constrained('sections')
                ->nullOnDelete();

            // Batch the user selects while importing (can be year like 2081 or code like 077/078)
            $t->string('batch', 30)->nullable();

            // Academic position
            $t->unsignedTinyInteger('year')->default(1);  // 1..4
            $t->unsignedTinyInteger('part')->default(1);  // 1 or 2
            $t->unsignedTinyInteger('semester')->default(1); // 1..8 (derived from year + part)

            // Extra fields from import
            $t->string('contact', 30)->nullable();
            $t->string('email', 120)->nullable();
            $t->string('dob')->nullable();
            $t->string('father_name', 191)->nullable();
            $t->string('mother_name', 191)->nullable();
            $t->string('gender', 10)->nullable();

            $t->string('municipality', 120)->nullable(); // municipal/vdc
            $t->string('ward', 10)->nullable();
            $t->string('district', 120)->nullable();

            $t->timestamps();

            $t->index(['faculty_id', 'batch', 'semester'], 'students_fac_batch_sem_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
