<?php

// database/migrations/2025_11_10_000002_create_routine_subjects_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(){
    Schema::create('routine_subjects', function(Blueprint $t){
      $t->id();
      $t->foreignId('routine_slot_id')->constrained('routine_slots')->cascadeOnDelete();
      $t->foreignId('faculty_id')->constrained('faculties')->cascadeOnDelete();
$t->unsignedTinyInteger('batch')->default(1); // 1 = new, 2 = old
      $t->string('subject_code'); // e.g., EX 501 / SH 501 ...
      $t->timestamps();
      $t->unique(['routine_slot_id','faculty_id']);
      $t->index(['faculty_id']);
    });
  }
  public function down(){ Schema::dropIfExists('routine_subjects'); }
};

