<?php

namespace Tests\Feature;

use App\Models\Cliente;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ConcesionarioSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_diagram_tables_and_one_to_many_relationships_are_preserved(): void
    {
        $cliente = Cliente::create([
            'cedula' => '1234567890',
            'direccion' => 'Calle 1',
            'telefono' => '5551234',
        ]);

        $coche = $cliente->coches()->create([
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'color' => 'Azul',
            'precio' => 12000.00,
        ]);

        $cliente->coches()->create([
            'marca' => 'Honda',
            'modelo' => 'Civic',
            'color' => 'Rojo',
            'precio' => 14000.00,
        ]);

        $coche->revisiones()->create([
            'codigo' => 'REV-001',
            'aceite' => true,
            'filtro' => false,
            'frenos' => true,
        ]);

        $this->assertSame(2, $cliente->coches()->count());
        $this->assertSame(1, $coche->revisiones()->count());
        $this->assertSame($cliente->id, $coche->cliente->id);
        $this->assertSame($coche->id, $coche->revisiones()->first()->coche->id);
        $this->assertContains('cliente_id', Schema::getColumnListing('coches'));
        $this->assertContains('foto_public_id', Schema::getColumnListing('coches'));
        $this->assertContains('coche_id', Schema::getColumnListing('revisiones'));
    }

    public function test_foreign_keys_reject_missing_parent_records(): void
    {
        $this->expectException(QueryException::class);

        DB::table('coches')->insert([
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'color' => 'Azul',
            'precio' => 12000.00,
            'cliente_id' => 999,
        ]);
    }
}
