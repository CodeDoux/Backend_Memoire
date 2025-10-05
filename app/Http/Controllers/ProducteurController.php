<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ProducteurService;
use App\Http\Requests\ProducteurRequest;

class ProducteurController extends Controller
{
    protected $producteurService;

    public function __construct(ProducteurService $producteurService)
    {
        $this->producteurService = $producteurService;
    }

    /**
     * Afficher la liste des producteurs
     */
    public function index()
    {
        $producteurs = $this->producteurService->index();
        return response()->json($producteurs, 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Créer un nouveau producteur
     */
    public function store(ProducteurRequest $request)
    {
        $producteur = $this->producteurService->store($request->validated());
        return response()->json($producteur, 201, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Afficher un producteur spécifique
     */
    public function show(string $id)
    {
        $producteur = $this->producteurService->show($id);
        return response()->json($producteur, 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Mettre à jour un producteur
     */
    public function update(ProducteurRequest $request, string $id)
    {
        $producteur = $this->producteurService->update($request->validated(), $id);

        return response()->json([
            "message" => "Producteur mis à jour avec succès",
            "producteur" => $producteur
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Supprimer un producteur
     */
    public function destroy(string $id)
    {
        $this->producteurService->destroy($id);
        return response()->json([
            "message" => "Producteur supprimé avec succès"
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
