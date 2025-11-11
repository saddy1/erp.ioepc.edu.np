<?php

// database/migrations/2025_11_09_000007_create_exam_allowed_pairs_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up() {
    Schema::create('exam_allowed_pairs', function (Blueprint $t) {
      $t->id();
      $t->foreignId('exam_id')->constrained()->cascadeOnDelete();
      $t->foreignId('faculty_a_id')->constrained('faculties')->cascadeOnDelete();
      $t->foreignId('faculty_b_id')->constrained('faculties')->cascadeOnDelete();
      $t->unique(['exam_id','faculty_a_id','faculty_b_id']);
      $t->timestamps();
    });
  }
  public function down() { Schema::dropIfExists('exam_allowed_pairs'); }
};
