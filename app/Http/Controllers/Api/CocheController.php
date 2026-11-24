<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coche;
use App\Services\CloudinaryImageService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CocheController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $coches = Coche::query()
            ->with(['cliente', 'revisiones'])
            ->when($request->filled('cliente_id'), fn (Builder $query) => $query
                ->where('cliente_id', $request->integer('cliente_id')))
            ->when(
                collect(['aceite', 'filtro', 'frenos'])->contains(
                    fn (string $campo) => $request->query($campo) !== null
                ),
                function (Builder $query) use ($request): void {
                    $query->whereHas('revisiones', function (Builder $revisiones) use ($request): void {
                        foreach (['aceite', 'filtro', 'frenos'] as $campo) {
                            if ($request->query($campo) !== null) {
                                $revisiones->where($campo, $request->boolean($campo));
                            }
                        }
                    });
                }
            )
            ->orderBy('id')
            ->get();

        return response()->json($coches);
    }

    public function store(Request $request, CloudinaryImageService $cloudinary): JsonResponse
    {
        $datos = $request->validate([
            'marca' => ['required', 'string', 'max:50'],
            'modelo' => ['required', 'string', 'max:50'],
            'color' => ['required', 'string', 'max:50'],
            'precio' => ['required', 'numeric', 'min:0'],
            'foto' => ['nullable', 'image', 'mimes:jpeg,png', 'max:5120'],
            'cliente_id' => ['required', 'integer', Rule::exists('clientes', 'id')],
        ]);

        $this->storeImage($request, $datos, $cloudinary);
        $coche = Coche::create($datos);

        return response()->json($coche->load('cliente', 'revisiones'), 201);
    }

    public function show(Coche $coche): JsonResponse
    {
        return response()->json($coche->load('cliente', 'revisiones'));
    }

    public function update(Request $request, Coche $coche, CloudinaryImageService $cloudinary): JsonResponse
    {
        $requerido = $request->isMethod('put') ? ['required'] : ['sometimes', 'required'];
        $datos = $request->validate([
            'marca' => [...$requerido, 'string', 'max:50'],
            'modelo' => [...$requerido, 'string', 'max:50'],
            'color' => [...$requerido, 'string', 'max:50'],
            'precio' => [...$requerido, 'numeric', 'min:0'],
            'foto' => ['nullable', 'image', 'mimes:jpeg,png', 'max:5120'],
            'cliente_id' => [...$requerido, 'integer', Rule::exists('clientes', 'id')],
        ]);

        $fotoAnterior = $coche->foto_public_id;
        $fotoNueva = $this->storeImage($request, $datos, $cloudinary);
        $coche->update($datos);

        if ($fotoNueva !== null && $fotoAnterior !== null) {
            $cloudinary->delete($fotoAnterior);
        }

        return response()->json($coche->fresh()->load('cliente', 'revisiones'));
    }

    public function destroy(Coche $coche, CloudinaryImageService $cloudinary): JsonResponse
    {
        if ($coche->foto_public_id !== null) {
            $cloudinary->delete($coche->foto_public_id);
        }

        $coche->delete();

        return response()->json(null, 204);
    }

    private function storeImage(
        Request $request,
        array &$datos,
        CloudinaryImageService $cloudinary
    ): ?string {
        if (! $request->hasFile('foto')) {
            return null;
        }

        $image = $cloudinary->upload($request->file('foto'));
        $datos['foto'] = $image['url'];
        $datos['foto_public_id'] = $image['public_id'];

        return $image['public_id'];
    }
}
