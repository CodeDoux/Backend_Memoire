<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\services\CategorieService;
use App\Http\Requests\CategorieRequest;

class CategorieController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    protected $categorieService;

    public function __construct(CategorieService $categorieService)
        {
            $this->categorieService = $categorieService;
        }
    public function index()
    {
        $categories=$this->categorieService->index();
        return response()->json($categories,200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CategorieRequest $request)
    {
        $categorie = $this->categorieService->store($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Catégorie créée avec succès',
            'data' => $categorie
        ], 201, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
         $categorie = $this->categorieService->show($id);
        return response()->json($categorie,200,[], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CategorieRequest $request, string $id)
    {
         $categorie= $this->categorieService->update($request->validated(), $id);

        return response()->json([
            "message" => "categorie mise à jour",
            "categorie" => $categorie
        ],status: 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->categorieService->destroy($id);
        return response()->json([
            "message" => "Catégorie supprimée avec succès"
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
