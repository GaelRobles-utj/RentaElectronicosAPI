<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PagoApiController extends Controller
{
    public function index()
    {
        $pagos = Pago::with('renta')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Pagos recuperados correctamente.',
            'data' => $pagos->map(fn($pago) => $this->datosPago($pago))
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'renta_id' => 'required|integer|exists:rentas,id',
            'fecha' => 'required|date',
            'metodo' => 'required|string|max:50',
            'monto' => 'required|numeric|min:0',
            'referencia' => 'nullable|string|max:120',
            'comprobante' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
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

        if ($request->hasFile('comprobante')) {
            $data['comprobante'] = $request->file('comprobante')
                ->store('pagos', 'public');
        }

        $pago = Pago::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Pago registrado correctamente.',
            'data' => $this->datosPago($pago->load('renta'))
        ], 201);
    }

    public function show($id)
    {
        $pago = Pago::with('renta')->find($id);

        if (!$pago) {
            return response()->json([
                'success' => false,
                'message' => 'El pago no existe.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pago encontrado.',
            'data' => $this->datosPago($pago)
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $pago = Pago::find($id);

        if (!$pago) {
            return response()->json([
                'success' => false,
                'message' => 'El pago no existe.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'renta_id' => 'sometimes|required|integer|exists:rentas,id',
            'fecha' => 'sometimes|required|date',
            'metodo' => 'sometimes|required|string|max:50',
            'monto' => 'sometimes|required|numeric|min:0',
            'referencia' => 'sometimes|nullable|string|max:120',
            'comprobante' => 'sometimes|nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
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
        $comprobanteAnterior = $pago->comprobante;

        if ($request->hasFile('comprobante')) {
            $data['comprobante'] = $request->file('comprobante')
                ->store('pagos', 'public');
        } else {
            unset($data['comprobante']);
        }

        $pago->update($data);

        if ($request->hasFile('comprobante') && $comprobanteAnterior) {
            Storage::disk('public')->delete($comprobanteAnterior);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pago actualizado correctamente.',
            'data' => $this->datosPago($pago->fresh('renta'))
        ], 200);
    }

    public function destroy($id)
    {
        $pago = Pago::find($id);

        if (!$pago) {
            return response()->json([
                'success' => false,
                'message' => 'El pago no existe.'
            ], 404);
        }

        $pago->estado = false;
        $pago->save();

        return response()->json([
            'success' => true,
            'message' => 'Pago desactivado correctamente.'
        ], 200);
    }

    private function datosPago(Pago $pago)
    {
        return [
            'id' => $pago->id,
            'renta_id' => $pago->renta_id,
            'renta' => $pago->renta ? [
                'id' => $pago->renta->id,
                'fecha_renta' => $pago->renta->fecha_renta,
                'total' => $pago->renta->total,
                'estado' => $pago->renta->estado,
            ] : null,
            'fecha' => $pago->fecha,
            'metodo' => $pago->metodo,
            'monto' => $pago->monto,
            'referencia' => $pago->referencia,
            'comprobante' => $pago->comprobante,
            'comprobante_url' => $pago->comprobante
                ? Storage::disk('public')->url($pago->comprobante)
                : null,
            'estado' => $pago->estado,
            'created_at' => $pago->created_at,
            'updated_at' => $pago->updated_at,
        ];
    }
}
