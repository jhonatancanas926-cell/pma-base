<?php
// INSTRUCCIONES:
// 1. Copia este archivo a: database/migrations/
// 2. Renómbralo con la fecha actual:
//    2026_04_09_000007_add_edad_sexo_to_users_table.php
// 3. Ejecuta: php artisan migrate

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('edad')->nullable()->after('programa')
                ->comment('Edad del evaluado en años');
            $table->enum('sexo', ['Masculino', 'Femenino', 'Otro'])->nullable()->after('edad')
                ->comment('Sexo del evaluado');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['edad', 'sexo']);
        });
    }
};
