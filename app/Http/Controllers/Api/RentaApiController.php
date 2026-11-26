<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Renta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RentaApiController extends Controller
{
    public function index()
    {
        $rentas = Renta::with('cliente:id,nombres,apellidos,correo')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Rentas recuperadas correctamente.',
            'data' => $rentas->map(fn($renta) => $this->datosRenta($renta))
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cliente_id' => 'required|integer|exists:clientes,id',
            'fecha_renta' => 'required|date',
            'fecha_devolucion' => 'required|date',
            'iva' => 'sometimes|numeric|min:0',
            'descuento' => 'sometimes|numeric|min:0',
            'deposito' => 'sometimes|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'estado' => 'sometimes|string|max:30',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación.',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        if (strtotime($data['fecha_devolucion']) < strtotime($data['fecha_renta'])) {
            return response()->json([
                'success' => false,
                'message' => 'La fecha de devolución no puede ser anterior a la fecha de renta.'
            ], 422);
        }

        $renta = Renta::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Renta registrada correctamente.',
            'data' => $this->datosRenta($renta->load('cliente'))
        ], 201);
    }

    public function show($id)
    {
        $renta = Renta::with('cliente')->find($id);

        if (!$renta) {
            return response()->json([
                'success' => false,
                'message' => 'La renta no existe.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Renta encontrada.',
            'data' => $this->datosRenta($renta)
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $renta = Renta::find($id);

        if (!$renta) {
            return response()->json([
                'success' => false,
                'message' => 'La renta no existe.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'cliente_id' => 'sometimes|required|integer|exists:clientes,id',
            'fecha_renta' => 'sometimes|required|date',
            'fecha_devolucion' => 'sometimes|required|date',
            'iva' => 'sometimes|numeric|min:0',
            'descuento' => 'sometimes|numeric|min:0',
            'deposito' => 'sometimes|numeric|min:0',
            'total' => 'sometimes|required|numeric|min:0',
            'estado' => 'sometimes|required|string|max:30',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación.',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        $fechaRenta = $data['fecha_renta'] ?? $renta->fecha_renta;
        $fechaDevolucion = $data['fecha_devolucion'] ?? $renta->fecha_devolucion;

        if (strtotime($fechaDevolucion) < strtotime($fechaRenta)) {
            return response()->json([
                'success' => false,
                'message' => 'La fecha de devolución no puede ser anterior a la fecha de renta.'
            ], 422);
        }

        $renta->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Renta actualizada correctamente.',
            'data' => $this->datosRenta($renta->fresh('cliente'))
        ], 200);
    }

    public function destroy($id)
    {
        $renta = Renta::find($id);

        if (!$renta) {
            return response()->json([
                'success' => false,
                'message' => 'La renta no existe.'
            ], 404);
        }

        // Cancelación lógica para conservar el historial.
        $renta->estado = 'cancelada';
        $renta->save();

        return response()->json([
            'success' => true,
            'message' => 'Renta cancelada correctamente.'
        ], 200);
    }

    private function datosRenta(Renta $renta)
    {
        return [
            'id' => $renta->id,
            'cliente_id' => $renta->cliente_id,
            'cliente' => $renta->cliente ? [
                'id' => $renta->cliente->id,
                'nombres' => $renta->cliente->nombres,
                'apellidos' => $renta->cliente->apellidos,
                'correo' => $renta->cliente->correo,
            ] : null,
            'fecha_renta' => $renta->fecha_renta,
            'fecha_devolucion' => $renta->fecha_devolucion,
            'iva' => $renta->iva,
            'descuento' => $renta->descuento,
            'deposito' => $renta->deposito,
            'total' => $renta->total,
            'estado' => $renta->estado,
            'created_at' => $renta->created_at,
            'updated_at' => $renta->updated_at,
        ];
    }
}
