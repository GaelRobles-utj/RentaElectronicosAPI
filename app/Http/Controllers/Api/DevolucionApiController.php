<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Devolucion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DevolucionApiController extends Controller
{
    public function index()
    {
        $devoluciones = Devolucion::with('renta')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Devoluciones recuperadas correctamente.',
            'data' => $devoluciones->map(fn($devolucion) => $this->datosDevolucion($devolucion))
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'renta_id' => 'required|integer|exists:rentas,id',
            'fecha' => 'required|date',
            'observacion' => 'nullable|string',
            'cargo_extra' => 'sometimes|numeric|min:0',
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
                ->store('devoluciones', 'public');
        }

        $devolucion = Devolucion::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Devolución registrada correctamente.',
            'data' => $this->datosDevolucion($devolucion->load('renta'))
        ], 201);
    }

    public function show($id)
    {
        $devolucion = Devolucion::with('renta')->find($id);

        if (!$devolucion) {
            return response()->json([
                'success' => false,
                'message' => 'La devolución no existe.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Devolución encontrada.',
            'data' => $this->datosDevolucion($devolucion)
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $devolucion = Devolucion::find($id);

        if (!$devolucion) {
            return response()->json([
                'success' => false,
                'message' => 'La devolución no existe.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'renta_id' => 'sometimes|required|integer|exists:rentas,id',
            'fecha' => 'sometimes|required|date',
            'observacion' => 'sometimes|nullable|string',
            'cargo_extra' => 'sometimes|numeric|min:0',
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
        $imagenAnterior = $devolucion->imagen;

        if ($request->hasFile('imagen')) {
            $data['imagen'] = $request->file('imagen')
                ->store('devoluciones', 'public');
        } else {
            unset($data['imagen']);
        }

        $devolucion->update($data);

        if ($request->hasFile('imagen') && $imagenAnterior) {
            Storage::disk('public')->delete($imagenAnterior);
        }

        return response()->json([
            'success' => true,
            'message' => 'Devolución actualizada correctamente.',
            'data' => $this->datosDevolucion($devolucion->fresh('renta'))
        ], 200);
    }

    public function destroy($id)
    {
        $devolucion = Devolucion::find($id);

        if (!$devolucion) {
            return response()->json([
                'success' => false,
                'message' => 'La devolución no existe.'
            ], 404);
        }

        $devolucion->estado = false;
        $devolucion->save();

        return response()->json([
            'success' => true,
            'message' => 'Devolución desactivada correctamente.'
        ], 200);
    }

    private function datosDevolucion(Devolucion $devolucion)
    {
        return [
            'id' => $devolucion->id,
            'renta_id' => $devolucion->renta_id,
            'renta' => $devolucion->renta ? [
                'id' => $devolucion->renta->id,
                'fecha_renta' => $devolucion->renta->fecha_renta,
                'fecha_devolucion' => $devolucion->renta->fecha_devolucion,
                'estado' => $devolucion->renta->estado,
            ] : null,
            'fecha' => $devolucion->fecha,
            'observacion' => $devolucion->observacion,
            'cargo_extra' => $devolucion->cargo_extra,
            'imagen' => $devolucion->imagen,
            'imagen_url' => $devolucion->imagen
                ? Storage::disk('public')->url($devolucion->imagen)
                : null,
            'estado' => $devolucion->estado,
            'created_at' => $devolucion->created_at,
            'updated_at' => $devolucion->updated_at,
        ];
    }
}
