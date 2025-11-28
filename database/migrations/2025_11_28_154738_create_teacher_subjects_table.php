<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_subjects', function (Blueprint $table) {
            $table->id();

            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();

            // Which department/faculty, semester and batch this mapping is for
            $table->foreignId('faculty_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('semester');         // 1..8
            $table->string('batch', 10);                     // 2080, 2081 etc.

            // Teacher allowed for Theory / Practical (one or both)
            $table->boolean('can_theory')->default(true);
            $table->boolean('can_practical')->default(false);

            $table->timestamps();

            $table->unique(
                ['teacher_id', 'subject_id', 'faculty_id', 'semester', 'batch'],
                'teacher_subject_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_subjects');
    }
};
