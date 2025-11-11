<?php

// database/migrations/2025_11_09_000009_create_invigilator_assignments_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up() {
    Schema::create('invigilator_assignments', function (Blueprint $t) {
      $t->id();
      $t->foreignId('exam_id')->constrained()->cascadeOnDelete();
      $t->foreignId('room_id')->constrained()->cascadeOnDelete();
      $t->foreignId('invigilator_id')->constrained()->cascadeOnDelete();
      $t->timestamps();
      $t->unique(['exam_id','room_id','invigilator_id']);
    });
  }
  public function down() { Schema::dropIfExists('invigilator_assignments'); }
};
