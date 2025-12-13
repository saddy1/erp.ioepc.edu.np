<?php
// database/migrations/2025_12_12_000003_create_department_teacher_roles_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('department_teacher_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->enum('role', ['hod', 'deputy_hod']);
            $table->timestamps();

            $table->unique(['department_id', 'teacher_id']); // same teacher once per dept
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_teacher_roles');
    }
};
