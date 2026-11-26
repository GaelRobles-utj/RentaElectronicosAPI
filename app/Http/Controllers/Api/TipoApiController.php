<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tipo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TipoApiController extends Controller
{
    public function index()
    {
        $tipos = Tipo::orderByDesc('id')->get();

        return response()->json([
            'success' => true,
            'message' => 'Tipos recuperados correctamente.',
            'data' => $tipos->map(fn($tipo) => $this->datosTipo($tipo))
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100|unique:tipos,nombre',
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
                ->store('tipos', 'public');
        }

        $tipo = Tipo::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Tipo registrado correctamente.',
            'data' => $this->datosTipo($tipo)
        ], 201);
    }

    public function show($id)
    {
        $tipo = Tipo::find($id);

        if (!$tipo) {
            return response()->json([
                'success' => false,
                'message' => 'El tipo no existe.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tipo encontrado.',
            'data' => $this->datosTipo($tipo)
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $tipo = Tipo::find($id);

        if (!$tipo) {
            return response()->json([
                'success' => false,
                'message' => 'El tipo no existe.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => [
                'sometimes', 'required', 'string', 'max:100',
                Rule::unique('tipos', 'nombre')->ignore($tipo->id)
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
        $imagenAnterior = $tipo->imagen;

        if ($request->hasFile('imagen')) {
            $data['imagen'] = $request->file('imagen')
                ->store('tipos', 'public');
        } else {
            unset($data['imagen']);
        }

        $tipo->update($data);

        if ($request->hasFile('imagen') && $imagenAnterior) {
            Storage::disk('public')->delete($imagenAnterior);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tipo actualizado correctamente.',
            'data' => $this->datosTipo($tipo->fresh())
        ], 200);
    }

    public function destroy($id)
    {
        $tipo = Tipo::find($id);

        if (!$tipo) {
            return response()->json([
                'success' => false,
                'message' => 'El tipo no existe.'
            ], 404);
        }

        $tipo->estado = false;
        $tipo->save();

        return response()->json([
            'success' => true,
            'message' => 'Tipo desactivado correctamente.'
        ], 200);
    }

    private function datosTipo(Tipo $tipo)
    {
        return array_merge($tipo->toArray(), [
            'imagen_url' => $tipo->imagen
                ? Storage::disk('public')->url($tipo->imagen)
                : null
        ]);
    }
}
