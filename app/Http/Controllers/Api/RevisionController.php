<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coche;
use App\Models\Revision;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RevisionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $revisiones = Revision::query()
            ->with('coche.cliente')
            ->when($request->filled('coche_id'), fn ($query) => $query
                ->where('coche_id', $request->integer('coche_id')))
            ->orderBy('id')
            ->get();

        return response()->json($revisiones);
    }

    public function forCoche(Coche $coche): JsonResponse
    {
        return response()->json($coche->revisiones()->orderBy('id')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $revision = Revision::create($request->validate([
            'codigo' => ['required', 'string', 'max:20'],
            'aceite' => ['required', 'boolean'],
            'filtro' => ['required', 'boolean'],
            'frenos' => ['required', 'boolean'],
            'coche_id' => ['required', 'integer', Rule::exists('coches', 'id')],
        ]));

        return response()->json($revision->load('coche.cliente'), 201);
    }

    public function show(Revision $revision): JsonResponse
    {
        return response()->json($revision->load('coche.cliente'));
    }

    public function update(Request $request, Revision $revision): JsonResponse
    {
        $requerido = $request->isMethod('put') ? ['required'] : ['sometimes', 'required'];
        $revision->update($request->validate([
            'codigo' => [...$requerido, 'string', 'max:20'],
            'aceite' => [...$requerido, 'boolean'],
            'filtro' => [...$requerido, 'boolean'],
            'frenos' => [...$requerido, 'boolean'],
            'coche_id' => [...$requerido, 'integer', Rule::exists('coches', 'id')],
        ]));

        return response()->json($revision->fresh()->load('coche.cliente'));
    }

    public function destroy(Revision $revision): JsonResponse
    {
        $revision->delete();

        return response()->json(null, 204);
    }
}
