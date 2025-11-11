<?php
// database/migrations/2025_11_09_000002_create_students_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up() {
    Schema::create('students', function (Blueprint $t) {
      $t->id();
      $t->string('name');
      $t->string('symbol_no')->unique();
      $t->foreignId('faculty_id')->constrained()->cascadeOnDelete();
      $t->unsignedTinyInteger('semester'); // 1..8 etc.
      $t->timestamps();
    });
  }
  public function down() { Schema::dropIfExists('students'); }
};
