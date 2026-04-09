<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('evaluado')->after('password');
            $table->string('documento')->nullable()->unique()->after('role');
            $table->string('programa')->nullable()->after('documento');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'documento', 'programa', 'deleted_at']);
        });
    }
};