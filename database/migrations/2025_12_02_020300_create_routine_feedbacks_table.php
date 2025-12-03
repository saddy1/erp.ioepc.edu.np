<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up()
    {
        Schema::create('routine_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('routine_id')->constrained('routines')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->date('class_date');
            $table->enum('status', ['taught', 'not_taught']);
            $table->timestamps();

            // Unique constraint: one feedback per routine per student per date
            $table->unique(['routine_id', 'student_id', 'class_date'],'routine_feedback_unique');
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('routine_feedback');
    }
};
