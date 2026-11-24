<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revisiones', function (Blueprint $table) {

            $table->id();
            $table->string('codigo', 20);
            $table->boolean('aceite');
            $table->boolean('filtro');
            $table->boolean('frenos');
            $table->foreignId('coche_id')->constrained('coches')->cascadeOnDelete();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revisiones');
    }
};
