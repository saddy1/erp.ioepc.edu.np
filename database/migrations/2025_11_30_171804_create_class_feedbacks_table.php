<?php

// database/migrations/2025_12_01_000003_create_class_feedbacks_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('class_feedbacks', function (Blueprint $t) {
            $t->id();

            $t->foreignId('routine_id')
              ->constrained('routines')
              ->cascadeOnDelete();

            $t->foreignId('student_id')
              ->constrained('students')
              ->cascadeOnDelete();

            $t->date('date');                      // which day this class was scheduled
            $t->boolean('was_taught');             // 1 = yes, 0 = not taught
            $t->text('remarks')->nullable();       // optional comment like "sir absent"

            $t->timestamps();

            $t->unique(['routine_id','student_id','date'], 'class_feedback_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_feedbacks');
    }
};
