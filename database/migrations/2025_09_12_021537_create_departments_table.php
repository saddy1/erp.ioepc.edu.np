<?php
// database/migrations/2025_12_12_000001_create_departments_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1️⃣ Main departments table
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();  // ECE_COMP, CIVIL, ARCH...
            $table->string('name');            // Full name
            $table->timestamps();
        });

        // 2️⃣ Pivot: department ↔ faculty
        Schema::create('department_faculty', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('faculty_id');

            $table->timestamps();

            $table->unique(['department_id', 'faculty_id']);

            $table->foreign('department_id')
                  ->references('id')->on('departments')
                  ->onDelete('cascade');

            $table->foreign('faculty_id')
                  ->references('id')->on('faculties')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        // Drop pivot first, then main table
        Schema::dropIfExists('department_faculty');
        Schema::dropIfExists('departments');
    }
};
