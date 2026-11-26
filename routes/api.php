<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AdministradorApiController;
use App\Http\Controllers\Api\ClienteApiController;
use App\Http\Controllers\Api\ProductoApiController;
use App\Http\Controllers\Api\TipoApiController;
use App\Http\Controllers\Api\MarcaApiController;
use App\Http\Controllers\Api\CategoriaApiController;
use App\Http\Controllers\Api\RentaApiController;
use App\Http\Controllers\Api\ProductoRentaApiController;
use App\Http\Controllers\Api\PagoApiController;
use App\Http\Controllers\Api\DevolucionApiController;

// API REST - Administradores
Route::apiResource('administradores', AdministradorApiController::class);

// API REST - Clientes
Route::apiResource('clientes', ClienteApiController::class);

// API REST - Productos
Route::apiResource('productos', ProductoApiController::class);

// API REST - Tipos
Route::apiResource('tipos', TipoApiController::class);

// API REST - Marcas
Route::apiResource('marcas', MarcaApiController::class);

// API REST - Categorías
Route::apiResource('categorias', CategoriaApiController::class);

// API REST - Rentas
Route::apiResource('rentas', RentaApiController::class);

// API REST - Productos de renta
Route::apiResource('productos-renta', ProductoRentaApiController::class);

// API REST - Pagos
Route::apiResource('pagos', PagoApiController::class);

// API REST - Devoluciones
Route::apiResource('devoluciones', DevolucionApiController::class);