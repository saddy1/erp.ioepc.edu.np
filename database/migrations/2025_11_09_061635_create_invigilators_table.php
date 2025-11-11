<?php

// database/migrations/2025_11_09_000004_create_invigilators_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up() {
    Schema::create('invigilators', function (Blueprint $t) {
      $t->id();
      $t->string('name');
      $t->enum('type', ['teacher','staff']);
      $t->foreignId('faculty_id')->nullable()->constrained()->nullOnDelete();
      $t->timestamps();
    });
  }
  public function down() { Schema::dropIfExists('invigilators'); }
};
