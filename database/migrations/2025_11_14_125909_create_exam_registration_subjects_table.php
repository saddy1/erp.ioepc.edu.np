<?php

// database/migrations/2025_11_14_000004_create_exam_registration_subjects_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('exam_registration_subjects', function (Blueprint $t) {
      $t->id();
      $t->foreignId('exam_registration_id')->constrained('exam_registrations')->cascadeOnDelete();
      $t->foreignId('faculty_id')->constrained('faculties')->cascadeOnDelete(); // dept
      $t->foreignId('faculty_semester_subject_id')->nullable()->constrained('faculty_semester_subjects')->nullOnDelete();
      $t->string('subject_code', 80);
      $t->boolean('th_taking')->default(false);
      $t->boolean('p_taking')->default(false);
      $t->timestamps();

      $t->unique(['exam_registration_id','subject_code'], 'uniq_reg_subject');
      $t->index(['faculty_id','subject_code']);
    });
  }
  public function down(): void { Schema::dropIfExists('exam_registration_subjects'); }
};
