<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Marca;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MarcaApiController extends Controller
{
    public function index()
    {
        $marcas = Marca::orderByDesc('id')->get();

        return response()->json([
            'success' => true,
            'message' => 'Marcas recuperadas correctamente.',
            'data' => $marcas->map(fn($marca) => $this->datosMarca($marca))
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100|unique:marcas,nombre',
            'imagen' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'estado' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación.',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        if ($request->hasFile('imagen')) {
            $data['imagen'] = $request->file('imagen')
                ->store('marcas', 'public');
        }

        $marca = Marca::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Marca registrada correctamente.',
            'data' => $this->datosMarca($marca)
        ], 201);
    }

    public function show($id)
    {
        $marca = Marca::find($id);

        if (!$marca) {
            return response()->json([
                'success' => false,
                'message' => 'La marca no existe.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Marca encontrada.',
            'data' => $this->datosMarca($marca)
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $marca = Marca::find($id);

        if (!$marca) {
            return response()->json([
                'success' => false,
                'message' => 'La marca no existe.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => [
                'sometimes', 'required', 'string', 'max:100',
                Rule::unique('marcas', 'nombre')->ignore($marca->id)
            ],
            'imagen' => 'sometimes|nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'estado' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación.',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $imagenAnterior = $marca->imagen;

        if ($request->hasFile('imagen')) {
            $data['imagen'] = $request->file('imagen')
                ->store('marcas', 'public');
        } else {
            unset($data['imagen']);
        }

        $marca->update($data);

        if ($request->hasFile('imagen') && $imagenAnterior) {
            Storage::disk('public')->delete($imagenAnterior);
        }

        return response()->json([
            'success' => true,
            'message' => 'Marca actualizada correctamente.',
            'data' => $this->datosMarca($marca->fresh())
        ], 200);
    }

    public function destroy($id)
    {
        $marca = Marca::find($id);

        if (!$marca) {
            return response()->json([
                'success' => false,
                'message' => 'La marca no existe.'
            ], 404);
        }

        $marca->estado = false;
        $marca->save();

        return response()->json([
            'success' => true,
            'message' => 'Marca desactivada correctamente.'
        ], 200);
    }

    private function datosMarca(Marca $marca)
    {
        return array_merge($marca->toArray(), [
            'imagen_url' => $marca->imagen
                ? Storage::disk('public')->url($marca->imagen)
                : null
        ]);
    }
}
