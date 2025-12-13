<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('contact')->nullable();

            // Super admin = full system access
            $table->boolean('is_super_admin')->default(false);

            // HOD / Department admin flag (for department-wise access)
            $table->boolean('is_hod')->default(false);

            // Department this admin manages (null for super admin)
            // Make sure you have a `departments` table for this FK.
            $table->foreignId('department_id')
                  ->nullable()
                  ->constrained('departments')
                  ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
