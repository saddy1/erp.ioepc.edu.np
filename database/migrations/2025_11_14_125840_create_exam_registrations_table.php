<?php

// database/migrations/2025_11_14_000003_create_exam_registrations_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('exam_registrations', function (Blueprint $t) {
      $t->id();
      $t->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
      $t->foreignId('student_id')->constrained('students')->cascadeOnDelete();
      $t->foreignId('faculty_id')->nullable()->constrained('faculties')->nullOnDelete(); // dept/program
      $t->unsignedTinyInteger('semester');
      $t->unsignedTinyInteger('batch'); // 1=new, 2=old
      $t->string('exam_roll_no', 50)->nullable();
      $t->string('token_no', 50)->nullable();
      $t->integer('amount')->default(0);
      $t->timestamps();

      $t->unique(['exam_id','student_id'], 'uniq_exam_student');
      $t->index(['exam_id','faculty_id','semester','batch']);
    });
  }
  public function down(): void { Schema::dropIfExists('exam_registrations'); }
};
