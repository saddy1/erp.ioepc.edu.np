<?php
// database/migrations/2025_11_09_000006_create_exam_rooms_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up() {
    Schema::create('exam_rooms', function (Blueprint $t) {
      $t->id();
      $t->foreignId('exam_id')->constrained()->cascadeOnDelete();
      $t->foreignId('room_id')->constrained()->cascadeOnDelete();
      // bench layout for this exam (3 fixed columns, variable rows each)
      $t->unsignedInteger('rows_col1');
      $t->unsignedInteger('rows_col2');
      $t->unsignedInteger('rows_col3');
      $t->unsignedTinyInteger('observers_required')->default(1); // 1 or 2
      $t->timestamps();
    });
  }
  public function down() { Schema::dropIfExists('exam_rooms'); }
};
