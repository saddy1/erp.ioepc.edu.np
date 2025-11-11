<?php
// database/migrations/2025_11_09_000008_create_seat_assignments_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up() {
    Schema::create('seat_assignments', function (Blueprint $t) {
      $t->id();
      $t->foreignId('exam_id')->constrained()->cascadeOnDelete();
      $t->foreignId('room_id')->constrained()->cascadeOnDelete();
      $t->unsignedTinyInteger('col'); // 1..3
      $t->unsignedInteger('row');     // 1..rows_in_that_col
      $t->enum('side', ['L','R']);    // left or right seat on the bench
      $t->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
      $t->timestamps();
      $t->unique(['exam_id','room_id','col','row','side']);
    });
  }
  public function down() { Schema::dropIfExists('seat_assignments'); }
};
