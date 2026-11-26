<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductoRenta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductoRentaApiController extends Controller
{
    public function index()
    {
        $detalles = ProductoRenta::with([
            'renta:id,cliente_id,fecha_renta,fecha_devolucion,estado',
            'producto:id,nombre,precio_dia'
        ])->orderByDesc('id')->get();

        return response()->json([
            'success' => true,
            'message' => 'Productos de renta recuperados correctamente.',
            'data' => $detalles->map(fn($detalle) => $this->datosProductoRenta($detalle))
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'renta_id' => 'required|integer|exists:rentas,id',
            'producto_id' => 'required|integer|exists:productos,id',
            'cantidad' => 'required|integer|min:1',
            'precio_dia' => 'required|numeric|min:0',
            'dias' => 'required|integer|min:1',
            'descuento' => 'sometimes|numeric|min:0',
            'subtotal' => 'required|numeric|min:0',
            'estado' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación.',
                'errors' => $validator->errors()
            ], 422);
        }

        $detalle = ProductoRenta::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Producto de renta registrado correctamente.',
            'data' => $this->datosProductoRenta(
                $detalle->load(['renta', 'producto'])
            )
        ], 201);
    }

    public function show($id)
    {
        $detalle = ProductoRenta::with(['renta', 'producto'])->find($id);

        if (!$detalle) {
            return response()->json([
                'success' => false,
                'message' => 'El producto de renta no existe.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Producto de renta encontrado.',
            'data' => $this->datosProductoRenta($detalle)
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $detalle = ProductoRenta::find($id);

        if (!$detalle) {
            return response()->json([
                'success' => false,
                'message' => 'El producto de renta no existe.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'renta_id' => 'sometimes|required|integer|exists:rentas,id',
            'producto_id' => 'sometimes|required|integer|exists:productos,id',
            'cantidad' => 'sometimes|required|integer|min:1',
            'precio_dia' => 'sometimes|required|numeric|min:0',
            'dias' => 'sometimes|required|integer|min:1',
            'descuento' => 'sometimes|numeric|min:0',
            'subtotal' => 'sometimes|required|numeric|min:0',
            'estado' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación.',
                'errors' => $validator->errors()
            ], 422);
        }

        $detalle->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Producto de renta actualizado correctamente.',
            'data' => $this->datosProductoRenta(
                $detalle->fresh(['renta', 'producto'])
            )
        ], 200);
    }

    public function destroy($id)
    {
        $detalle = ProductoRenta::find($id);

        if (!$detalle) {
            return response()->json([
                'success' => false,
                'message' => 'El producto de renta no existe.'
            ], 404);
        }

        $detalle->estado = false;
        $detalle->save();

        return response()->json([
            'success' => true,
            'message' => 'Producto de renta desactivado correctamente.'
        ], 200);
    }

    private function datosProductoRenta(ProductoRenta $detalle)
    {
        return [
            'id' => $detalle->id,
            'renta_id' => $detalle->renta_id,
            'renta' => $detalle->renta ? [
                'id' => $detalle->renta->id,
                'fecha_renta' => $detalle->renta->fecha_renta,
                'fecha_devolucion' => $detalle->renta->fecha_devolucion,
                'estado' => $detalle->renta->estado,
            ] : null,
            'producto_id' => $detalle->producto_id,
            'producto' => $detalle->producto ? [
                'id' => $detalle->producto->id,
                'nombre' => $detalle->producto->nombre,
                'precio_dia' => $detalle->producto->precio_dia,
            ] : null,
            'cantidad' => $detalle->cantidad,
            'precio_dia' => $detalle->precio_dia,
            'dias' => $detalle->dias,
            'descuento' => $detalle->descuento,
            'subtotal' => $detalle->subtotal,
            'estado' => $detalle->estado,
            'created_at' => $detalle->created_at,
            'updated_at' => $detalle->updated_at,
        ];
    }
}
