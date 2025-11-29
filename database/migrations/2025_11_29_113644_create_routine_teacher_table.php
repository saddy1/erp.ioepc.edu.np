<?php

// database/migrations/2025_11_29_000000_create_routine_teacher_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('routine_teacher', function (Blueprint $t) {
            $t->id();
            $t->foreignId('routine_id')
              ->constrained('routines')
              ->cascadeOnDelete();

            $t->foreignId('teacher_id')
              ->constrained('teachers')
              ->cascadeOnDelete();

            $t->unique(['routine_id', 'teacher_id'], 'routine_teacher_uq');
        });

        // optional: if you want to keep old data
        Schema::table('routines', function (Blueprint $t) {
            // teacher_id column stays for backward compatibility;
            // we'll still use it as "primary" teacher but pivot is source of truth.
        });
    }

    public function down()
    {
        Schema::dropIfExists('routine_teacher');
    }
};
