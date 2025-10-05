<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\LivraisonRequest;
use App\services\LivraisonService;

class LivraisonController extends Controller
{
    /**
     * Display a listing of the resource.
     */
   protected $livraisonService;

    public function __construct(LivraisonService $livraisonService)
    {
        $this->livraisonService = $livraisonService;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function index()
    {
        try {
            $livraisons = $this->livraisonService->index();
            return response()->json($livraisons, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la récupération des livraisons',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
   public function store(LivraisonRequest $request)
    {
        try {
            $validatedData = $request->validate();

            $livraison = $this->livraisonService->store($validatedData);

            return response()->json([
                'message' => 'Livraison créée avec succès',
                'livraison' => $livraison
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Données de validation invalides',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la création de la livraison',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function show($id)
    {
        try {
            $livraison = $this->livraisonService->show($id);
            return response()->json($livraison, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Livraison non trouvée',
                'message' => $e->getMessage()
            ], 404);
        }
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(LivraisonRequest $request, $id)
    {
        try {
            $validatedData = $request->validate();

            $livraison = $this->livraisonService->update($validatedData, $id);

            return response()->json([
                'message' => 'Livraison mise à jour avec succès',
                'livraison' => $livraison
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Données de validation invalides',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la mise à jour',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $this->livraisonService->destroy($id);

            return response()->json([
                'message' => 'Livraison supprimée avec succès'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la suppression',
                'message' => $e->getMessage()
            ], 500);
        }
    }

     /**
     * Marquer une livraison comme terminée
     */
    public function marquerLivree(Request $request, $id)
    {
        try {
            $validatedData = $request->validate([
                'note' => 'nullable|string|max:1000'
            ]);

            $livraison = $this->livraisonService->marquerLivree($id, $validatedData['note'] ?? null);

            return response()->json([
                'message' => 'Livraison marquée comme terminée',
                'livraison' => $livraison
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la finalisation',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les livraisons du jour
     */
    public function livraisonsAujourdhui()
    {
        try {
            $livraisons = $this->livraisonService->getLivraisonsAujourdhui();
            return response()->json($livraisons, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la récupération',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtenir les livraisons en retard
     */
    public function livraisonsEnRetard()
    {
        try {
            $livraisons = $this->livraisonService->getLivraisonsEnRetard();
            return response()->json($livraisons, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la récupération',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Calculer les frais de livraison
     */
    public function calculerFrais(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'ville' => 'required|string|max:100',
                'distance' => 'nullable|numeric|min:0'
            ]);

            $frais = $this->livraisonService->calculerFraisLivraison(
                $validatedData['ville'],
                $validatedData['distance'] ?? null
            );

            return response()->json([
                'ville' => $validatedData['ville'],
                'frais_livraison' => $frais
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors du calcul',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques des livraisons
     */
    public function statistiques(Request $request)
    {
        try {
            $periode = $request->query('periode', 'mois');
            $stats = $this->livraisonService->getStatistiques($periode);

            return response()->json($stats, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la récupération des statistiques',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Planifier les livraisons
     */
    public function planifier(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'date' => 'required|date',
                'employe_id' => 'nullable|exists:users,id'
            ]);

            $planning = $this->livraisonService->planifierLivraisons(
                $validatedData['date'],
                $validatedData['employe_id'] ?? null
            );

            return response()->json([
                'date' => $validatedData['date'],
                'planning' => $planning
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la planification',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Mettre à jour le statut de livraison d'une commande
     */
    public function updateStatutLivraison(Request $request, $commandeId)
    {
        try {
            $validatedData = $request->validate([
                'statut' => 'required|in:non_livrée,en_cours,livrée,annulée'
            ]);

            // Trouver la livraison par commande_id
            $livraison = \App\Models\Livraisons::where('commande_id', $commandeId)->firstOrFail();

            $livraisonUpdated = $this->livraisonService->update($validatedData, $livraison->id);

            return response()->json([
                'message' => 'Statut de livraison mis à jour avec succès',
                'livraison' => $livraisonUpdated
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la mise à jour du statut',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
