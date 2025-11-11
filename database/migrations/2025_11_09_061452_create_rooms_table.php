<?php

// database/migrations/2025_11_09_000003_create_rooms_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up() {
    Schema::create('rooms', function (Blueprint $t) {
      $t->id();
      $t->string('room_no')->unique();
      $t->unsignedInteger('total_benches')->default(0); // optional summary
      $t->timestamps();
    });
  }
  public function down() { Schema::dropIfExists('rooms'); }
};
