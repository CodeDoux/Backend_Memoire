<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategorieController;
use App\Http\Controllers\ProduitController;
use App\Http\Controllers\ProducteurController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\CommandeController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CodeListController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;

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
Route::post('clients', [ClientController::class,'store']);
Route::get('produits', [ProduitController::class, 'index']);
Route::get('producteurs', [ProducteurController::class, 'index']);
Route::get('categories', [CategorieController::class, 'index'])->middleware('auth:sanctum');
Route::post('addProducteur', [ProducteurController::class, 'store']);
Route::get('promotions', [PromotionController::class, 'index'])->middleware('auth:sanctum');
Route::apiResource('notification', NotificationController::class)->middleware('auth:sanctum');
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) { return $request->user(); });
    // Pour les admin
    Route::apiResource('users', AuthController::class);
    Route::get('/user', [AuthController::class, 'user']);
    Route::middleware('role:ADMIN')->group(function() {
      //Route::apiResource('clients', ClientController::class);
        Route::get('/commandes/stats', [CommandeController::class, 'stats']);
        Route::get('/commandes', [CommandeController::class, 'indexAdmin']);
        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
        Route::apiResource('codelist', CodeListController::class);
        Route::get('/produits/stats', [ProduitController::class, 'stats']);
        //Route::apiResource('categories', CategorieController::class);
        Route::apiResource('administrateurs', AdminController::class);
        Route::patch('/produits/{id}/validation', [ProduitController::class, 'updateValidation']);       
        
        //Route::apiResource('producteurs', ProducteurController::class);
        Route::delete('commandes/{id}', [CommandeController::class, 'destroy']);
   });
      // Pour les producteur
      Route::middleware('role:PRO')->group(function() {
        Route::get('/codelist/type/{type}', [CodeListController::class, 'getByType']);
       
      
       //Route::apiResource('promotions', PromotionController::class);
       Route::get('promotions',[PromotionController::class, 'index']);
       Route::get('produitsProducteur', [ProduitController::class, 'indexProducteur']);
        //Route::apiResource('categories', CategorieController::class);
        Route::post('/promotions/{id}/associer-produit', [PromotionController::class, 'associerProduit']);
        Route::post('addPromotion', [PromotionController::class, 'store']);
        Route::put('promotions/{id}', [PromotionController::class, 'update']);
        Route::patch('/promotions/{id}/toggle', [PromotionController::class, 'toggle']);
        Route::post('produits', [ProduitController::class, 'store']);
        Route::delete('produits/{id}', [ProduitController::class, 'destroy']);
        Route::put('produits/{id}', [ProduitController::class, 'update']);
        Route::get('produits/{id}', [ProduitController::class, 'show']);

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
