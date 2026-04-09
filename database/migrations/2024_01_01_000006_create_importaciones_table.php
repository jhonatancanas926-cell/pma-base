<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('importaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nombre_archivo');
            $table->string('tipo')->comment('excel, csv');
            $table->enum('estado', ['procesando', 'completado', 'con_errores', 'fallido'])
                  ->default('procesando');
            $table->integer('filas_total')->default(0);
            $table->integer('filas_exitosas')->default(0);
            $table->integer('filas_con_error')->default(0);
            $table->json('errores')->nullable();
            $table->json('estadisticas')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('importaciones');
    }
};
