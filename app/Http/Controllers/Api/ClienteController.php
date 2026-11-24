<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Cliente::query()->withCount('coches')->orderBy('id')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $cliente = Cliente::create($request->validate([
            'cedula' => ['required', 'string', 'max:20'],
            'direccion' => ['required', 'string', 'max:150'],
            'telefono' => ['required', 'string', 'max:20'],
        ]));

        return response()->json($cliente, 201);
    }

    public function show(Cliente $cliente): JsonResponse
    {
        return response()->json($cliente->load('coches.revisiones'));
    }

    public function update(Request $request, Cliente $cliente): JsonResponse
    {
        $requerido = $request->isMethod('put') ? ['required'] : ['sometimes', 'required'];
        $cliente->update($request->validate([
            'cedula' => [...$requerido, 'string', 'max:20'],
            'direccion' => [...$requerido, 'string', 'max:150'],
            'telefono' => [...$requerido, 'string', 'max:20'],
        ]));

        return response()->json($cliente->fresh());
    }

    public function destroy(Cliente $cliente): JsonResponse
    {
        $cliente->delete();

        return response()->json(null, 204);
    }
}
