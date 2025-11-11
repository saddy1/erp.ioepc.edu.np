<?php

// database/migrations/2025_11_09_000005_create_exams_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
{
 Schema::create('exams', function (Blueprint $table) {
    $table->id();
    $table->enum('semester', ['even','odd']);   // ⬅ fixed choices
    $table->enum('batch', ['new','old']);       // ⬅ fixed choices
    $table->string('exam_title');
    $table->time('start_time');
    $table->time('end_time');
    $table->string('first_exam_date_bs');
    $table->unsignedBigInteger('status')->default(0);  // ⬅ new column
    $table->timestamps();
});
}
  public function down() { Schema::dropIfExists('exams'); }
};
