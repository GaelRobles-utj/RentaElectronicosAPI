<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CategoriaApiController extends Controller
{
    public function index()
    {
        $categorias = Categoria::orderByDesc('id')->get();

        return response()->json([
            'success' => true,
            'message' => 'Categorías recuperadas correctamente.',
            'data' => $categorias->map(fn($categoria) => $this->datosCategoria($categoria))
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100|unique:categorias,nombre',
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
                ->store('categorias', 'public');
        }

        $categoria = Categoria::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Categoría registrada correctamente.',
            'data' => $this->datosCategoria($categoria)
        ], 201);
    }

    public function show($id)
    {
        $categoria = Categoria::find($id);

        if (!$categoria) {
            return response()->json([
                'success' => false,
                'message' => 'La categoría no existe.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Categoría encontrada.',
            'data' => $this->datosCategoria($categoria)
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $categoria = Categoria::find($id);

        if (!$categoria) {
            return response()->json([
                'success' => false,
                'message' => 'La categoría no existe.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => [
                'sometimes', 'required', 'string', 'max:100',
                Rule::unique('categorias', 'nombre')->ignore($categoria->id)
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
        $imagenAnterior = $categoria->imagen;

        if ($request->hasFile('imagen')) {
            $data['imagen'] = $request->file('imagen')
                ->store('categorias', 'public');
        } else {
            unset($data['imagen']);
        }

        $categoria->update($data);

        if ($request->hasFile('imagen') && $imagenAnterior) {
            Storage::disk('public')->delete($imagenAnterior);
        }

        return response()->json([
            'success' => true,
            'message' => 'Categoría actualizada correctamente.',
            'data' => $this->datosCategoria($categoria->fresh())
        ], 200);
    }

    public function destroy($id)
    {
        $categoria = Categoria::find($id);

        if (!$categoria) {
            return response()->json([
                'success' => false,
                'message' => 'La categoría no existe.'
            ], 404);
        }

        $categoria->estado = false;
        $categoria->save();

        return response()->json([
            'success' => true,
            'message' => 'Categoría desactivada correctamente.'
        ], 200);
    }

    private function datosCategoria(Categoria $categoria)
    {
        return array_merge($categoria->toArray(), [
            'imagen_url' => $categoria->imagen
                ? Storage::disk('public')->url($categoria->imagen)
                : null
        ]);
    }
}
