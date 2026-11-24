<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Services\CloudinaryImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ConcesionarioApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_clientes_coches_and_revisiones_support_crud_and_relationship_filters(): void
    {
        $cliente = $this->postJson('/api/clientes', [
            'cedula' => '1234567890',
            'direccion' => 'Calle 1',
            'telefono' => '5551234',
        ])->assertCreated()->assertJsonPath('cedula', '1234567890');

        $clienteId = $cliente->json('id');

        $this->getJson('/api/clientes')->assertOk()->assertJsonCount(1);
        $this->putJson("/api/clientes/{$clienteId}", [
            'cedula' => '1234567890',
            'direccion' => 'Calle 2',
            'telefono' => '5551234',
        ])->assertOk()->assertJsonPath('direccion', 'Calle 2');
        $this->patchJson("/api/clientes/{$clienteId}", [
            'direccion' => 'Calle 3',
        ])->assertOk()->assertJsonPath('direccion', 'Calle 3');

        $this->getJson("/api/clientes/{$clienteId}")
            ->assertOk()
            ->assertJsonPath('id', $clienteId);

        $coche = $this->postJson('/api/coches', [
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'color' => 'Azul',
            'precio' => 12000.00,
            'cliente_id' => $clienteId,
        ])->assertCreated()->assertJsonPath('cliente.id', $clienteId);

        $cocheId = $coche->json('id');
        $this->getJson("/api/coches/{$cocheId}")
            ->assertOk()
            ->assertJsonPath('id', $cocheId);
        $this->putJson("/api/coches/{$cocheId}", [
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'color' => 'Negro',
            'precio' => 12500.00,
            'cliente_id' => $clienteId,
        ])->assertOk()->assertJsonPath('color', 'Negro');
        $this->patchJson("/api/coches/{$cocheId}", [
            'color' => 'Plata',
        ])->assertOk()->assertJsonPath('color', 'Plata');

        $segundoCoche = $this->postJson('/api/coches', [
            'marca' => 'Honda',
            'modelo' => 'Civic',
            'color' => 'Rojo',
            'precio' => 14000.00,
            'cliente_id' => $clienteId,
        ])->assertCreated();

        $this->postJson('/api/revisiones', [
            'codigo' => 'REV-001',
            'aceite' => true,
            'filtro' => false,
            'frenos' => true,
            'coche_id' => $cocheId,
        ])->assertCreated()->assertJsonPath('coche.id', $cocheId);

        $this->getJson("/api/coches/{$cocheId}/revisiones")
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.codigo', 'REV-001');

        $this->getJson("/api/coches?cliente_id={$clienteId}&aceite=1&frenos=1")
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $cocheId);

        $revisionId = $this->getJson('/api/revisiones')->assertOk()->json('0.id');
        $this->putJson("/api/revisiones/{$revisionId}", [
            'codigo' => 'REV-002',
            'aceite' => false,
            'filtro' => true,
            'frenos' => true,
            'coche_id' => $cocheId,
        ])->assertOk()->assertJsonPath('codigo', 'REV-002');
        $this->patchJson("/api/revisiones/{$revisionId}", [
            'codigo' => 'REV-003',
        ])->assertOk()->assertJsonPath('codigo', 'REV-003');

        $this->deleteJson("/api/revisiones/{$revisionId}")->assertNoContent();
        $this->deleteJson("/api/coches/{$segundoCoche->json('id')}")->assertNoContent();
        $this->deleteJson("/api/clientes/{$clienteId}")->assertNoContent();
        $this->assertDatabaseMissing('coches', ['id' => $cocheId]);
        $this->assertDatabaseMissing('revisiones', ['id' => $revisionId]);
    }

    public function test_api_validates_required_relationships_and_boolean_review_fields(): void
    {
        $this->postJson('/api/coches', [
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'color' => 'Azul',
            'precio' => 12000,
            'cliente_id' => 999,
        ])->assertUnprocessable()->assertJsonValidationErrors('cliente_id');

        $cliente = Cliente::create([
            'cedula' => '1234567890',
            'direccion' => 'Calle 1',
            'telefono' => '5551234',
        ]);
        $coche = $cliente->coches()->create([
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'color' => 'Azul',
            'precio' => 12000,
        ]);

        $this->postJson('/api/revisiones', [
            'codigo' => 'REV-001',
            'aceite' => 'no',
            'filtro' => true,
            'frenos' => true,
            'coche_id' => $coche->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('aceite');
    }

    public function test_car_photos_are_uploaded_replaced_and_deleted(): void
    {
        $cliente = Cliente::create([
            'cedula' => '1234567890',
            'direccion' => 'Calle 1',
            'telefono' => '5551234',
        ]);
        $cloudinary = $this->mock(CloudinaryImageService::class);
        $cloudinary->shouldReceive('upload')->twice()->andReturn(
            ['url' => 'https://res.cloudinary.com/demo/image/upload/coche-1.jpg', 'public_id' => 'coches/coche-1'],
            ['url' => 'https://res.cloudinary.com/demo/image/upload/coche-2.jpg', 'public_id' => 'coches/coche-2']
        );
        $cloudinary->shouldReceive('delete')->once()->with('coches/coche-1');
        $cloudinary->shouldReceive('delete')->once()->with('coches/coche-2');

        $datos = [
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'color' => 'Azul',
            'precio' => 12000,
            'cliente_id' => $cliente->id,
        ];

        $coche = $this->post('/api/coches', [
            ...$datos,
            'foto' => UploadedFile::fake()->createWithContent(
                'coche.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')
            ),
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('foto_public_id', 'coches/coche-1')
            ->json();

        $this->post("/api/coches/{$coche['id']}", [
            ...$datos,
            '_method' => 'PUT',
            'foto' => UploadedFile::fake()->createWithContent(
                'coche-nuevo.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')
            ),
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('foto_public_id', 'coches/coche-2');

        $this->deleteJson("/api/coches/{$coche['id']}")->assertNoContent();
    }

    public function test_car_photo_must_be_an_image(): void
    {
        $cliente = Cliente::create([
            'cedula' => '1234567890',
            'direccion' => 'Calle 1',
            'telefono' => '5551234',
        ]);

        $this->post('/api/coches', [
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'color' => 'Azul',
            'precio' => 12000,
            'cliente_id' => $cliente->id,
            'foto' => 'no-es-una-imagen',
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('foto');
    }

    public function test_cloudinary_requests_use_signed_upload_and_delete_calls(): void
    {
        config([
            'services.cloudinary.cloud_name' => 'demo',
            'services.cloudinary.api_key' => 'test-key',
            'services.cloudinary.api_secret' => 'test-secret',
            'services.cloudinary.folder' => 'concesionario/coches',
        ]);
        Http::fakeSequence()
            ->push([
                'secure_url' => 'https://res.cloudinary.com/demo/image/upload/coche.png',
                'public_id' => 'concesionario/coches/coche',
            ])
            ->push(['result' => 'ok']);

        $cloudinary = app(CloudinaryImageService::class);
        $cloudinary->upload(UploadedFile::fake()->createWithContent('coche.png', 'contenido'));
        $cloudinary->delete('concesionario/coches/coche');

        Http::assertSent(function ($request): bool {
            $data = collect($request->data())->pluck('contents', 'name')->all();

            return $request->url() === 'https://api.cloudinary.com/v1_1/demo/image/upload'
                && $request->hasFile('file')
                && ($data['api_key'] ?? null) === 'test-key'
                && isset($data['timestamp'])
                && ($data['signature'] ?? null) === sha1(
                    'folder=concesionario/coches&timestamp='.$data['timestamp'].'test-secret'
                );
        });
        Http::assertSent(function ($request): bool {
            $data = collect($request->data())->pluck('contents', 'name')->all();

            return $request->url() === 'https://api.cloudinary.com/v1_1/demo/image/destroy'
                && ($data['public_id'] ?? null) === 'concesionario/coches/coche'
                && ($data['signature'] ?? null) === sha1(
                    'public_id=concesionario/coches/coche&timestamp='.$data['timestamp'].'test-secret'
                );
        });
        Http::assertSentCount(2);
    }
}
