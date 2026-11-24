<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coches', function (Blueprint $table) {

            $table->id();
            $table->string('marca', 50);
            $table->string('modelo', 50);
            $table->string('color', 50);
            $table->decimal('precio', 10, 2);
            $table->string('foto', 255)->nullable();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coches');
    }
};
