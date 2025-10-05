<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategorieController;
use App\Http\Controllers\ProduitController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\CommandeController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('promotions', [PromotionController::class, 'index'])->middleware('auth:sanctum');
Route::apiResource('notification', NotificationController::class)->middleware('auth:sanctum');
Route::apiResource('categories', CategorieController::class)->middleware('auth:sanctum');
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) { return $request->user(); });
    // Pour les admin
    Route::apiResource('users', AuthController::class);
    Route::middleware('role:ADMIN')->group(function() {
       // Route::apiResource('categories', CategorieController::class);
        Route::apiResource('users', AuthController::class);
        Route::post('addPromotion', [PromotionController::class, 'store']);
       
        Route::post('/promotions/{id}/associer-produit', [PromotionController::class, 'associerProduit']);
        Route::apiResource('produits', ProduitController::class);
        Route::apiResource('producteurs', ProduitController::class);
        Route::delete('commandes/{id}', [CommandeController::class, 'destroy']);
   });
      // Pour les producteur
      Route::middleware('role:PRO')->group(function() {
       Route::get('/produitsProducteur', [ProduitController::class, 'indexProducteur']);
       // Route::apiResource('categories', CategorieController::class);
        Route::apiResource('produits', ProduitController::class);
      });

      // Pour les clients
    Route::middleware('role:CLIENT')->group(function() {
        Route::get('produitsClient', [ProduitController::class, 'indexClient']);
        // Le client crée sa commande
        Route::post('commandes', [CommandeController::class, 'store']);
        // Le client voit uniquement ses commandes
        Route::get('mes-commandes', [CommandeController::class, 'mesCommandes']);
    });

});
