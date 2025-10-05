<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\services\ProduitService;
use App\Http\Requests\ProduitRequest;

class ProduitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    protected $produitService;
    // ✅ Lister les produits d’un producteur connecté
    public function indexProducteur()
    {
        $user = Auth::user();

        // Vérifier rôle
        if ($user->role !== 'PRO') {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        return Produit::with('categorie', 'images')
            ->where('producteur_id', $user->id) // ou producteur_id selon ta DB
            ->get();
    }

    public function __construct(ProduitService $produitService)
    {
        $this->produitService = $produitService;
    }
    public function index(Request $request)
    {
        // Log pour debug
        \Log::info('Accès à index produits', [
            'user' => $request->user()->email ?? 'non défini',
            'role' => $request->user()->role ?? 'non défini'
        ]);

        $produits = $this->produitService->index();
        return response()->json($produits, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProduitRequest $request)
    {
        $data = $request->validated();

       

        $produit = $this->produitService->store($data);

        return response()->json($produit, 201, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $produit = $this->produitService->show($id);
        return response()->json($produit, 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProduitRequest $request, string $id)
    {
        $data = $request->validated();

       

        $produit = $this->produitService->update($data, $id);

        return response()->json([
            "message" => "Produit mis à jour",
            "produit" => $produit
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->produitService->destroy($id);
        return response()->json(["message" => "Produit supprimé"], 200);
    }
}
