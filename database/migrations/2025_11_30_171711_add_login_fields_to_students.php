<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('students', function (Blueprint $t) {
            $t->string('password')->nullable()->after('email');   // for CR/VC login
            $t->boolean('can_login')->default(false)->after('password'); // only CR/VC true
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $t) {
            $t->dropColumn(['password', 'can_login']);
        });
    }
};
