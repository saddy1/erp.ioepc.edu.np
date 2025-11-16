<?php
// database/migrations/2025_11_14_000002_create_students_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('students', function (Blueprint $t) {
      $t->id();
      $t->string('campus_roll_no', 50)->unique(); // e.g., PUR081BAG009
      $t->string('name', 191);
      $t->string('campus_code', 10)->nullable();  // PUR
      $t->string('batch_code', 10)->nullable();   // 081
      $t->string('program_code', 20)->nullable(); // BAG
      $t->foreignId('faculty_id')->nullable()->constrained('faculties')->nullOnDelete();
      $t->timestamps();
    });
  }
  public function down(): void { Schema::dropIfExists('students'); }
};
