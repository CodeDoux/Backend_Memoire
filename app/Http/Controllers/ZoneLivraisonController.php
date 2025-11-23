<?php

namespace App\Http\Controllers;

use App\Http\Requests\ZoneLivraisonRequest;
use App\Services\ZoneLivraisonService;
use Illuminate\Http\Request;

class ZoneLivraisonController extends Controller
{
    protected $zoneLivraisonService;

    public function __construct(ZoneLivraisonService $zoneLivraisonService)
    {
        $this->zoneLivraisonService = $zoneLivraisonService;
    }

    /**
     * Afficher la liste de toutes les zones de livraison
     */
    public function index()
    {
        try {
            $zones = $this->zoneLivraisonService->index();
            return response()->json($zones, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la récupération des zones de livraison',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer une nouvelle zone de livraison
     */
    public function store(ZoneLivraisonRequest $request)
    {
        try {
            $zone = $this->zoneLivraisonService->store($request->validated());
            return response()->json([
                'message' => 'Zone de livraison créée avec succès',
                'data' => $zone
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la création de la zone de livraison',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher une zone de livraison spécifique
     */
    public function show($id)
    {
        try {
            $zone = $this->zoneLivraisonService->show($id);
            return response()->json($zone, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Zone de livraison non trouvée'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la récupération de la zone de livraison',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour une zone de livraison
     */
    public function update(ZoneLivraisonRequest $request, $id)
    {
        try {
            $zone = $this->zoneLivraisonService->update($request->validated(), $id);
            return response()->json([
                'message' => 'Zone de livraison mise à jour avec succès',
                'data' => $zone
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Zone de livraison non trouvée'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la mise à jour de la zone de livraison',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une zone de livraison
     */
    public function destroy($id)
    {
        try {
            $result = $this->zoneLivraisonService->destroy($id);
            return response()->json($result, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Zone de livraison non trouvée'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la suppression de la zone de livraison',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les zones actives
     */
    public function getZonesActives()
    {
        try {
            $zones = $this->zoneLivraisonService->getZonesActives();
            return response()->json($zones, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la récupération des zones actives',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rechercher une zone par nom
     */
    public function findByNom(Request $request)
    {
        try {
            $nom = $request->input('nom');
            $zones = $this->zoneLivraisonService->findByNom($nom);
            return response()->json($zones, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la recherche',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les frais de livraison d'une zone
     */
    public function getFraisLivraison($id)
    {
        try {
            $frais = $this->zoneLivraisonService->getFraisLivraison($id);
            return response()->json([
                'zone_id' => $id,
                'frais_livraison' => $frais
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Zone de livraison non trouvée'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la récupération des frais',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}