<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PaiementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
   protected $paiementService;

    public function __construct(PaiementService $paiementService)
    {
        $this->paiementService = $paiementService;
    }

    public function index()
    {
        try {
            $paiements = $this->paiementService->index();
            return response()->json($paiements, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la récupération des paiements',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(PaiementRequest $request)
    {
        try {
            $validatedData = $request->validate();

            $paiement = $this->paiementService->store($validatedData);

            return response()->json([
                'message' => 'Paiement créé avec succès',
                'paiement' => $paiement
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Données de validation invalides',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la création du paiement',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $paiement = $this->paiementService->show($id);
            return response()->json($paiement, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Paiement non trouvé',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    public function update(PaiementRequest $request, $id)
    {
        try {
            $validatedData = $request->validate();

            $paiement = $this->paiementService->update($validatedData, $id);

            return response()->json([
                'message' => 'Paiement mis à jour avec succès',
                'paiement' => $paiement
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

    public function destroy($id)
    {
        try {
            $this->paiementService->destroy($id);

            return response()->json([
                'message' => 'Paiement supprimé avec succès'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la suppression',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marquer un paiement comme payé
     */
    public function marquerPaye(Request $request, $id)
    {
        try {
            $validatedData = $request->validate([
                'reference_transaction' => 'nullable|string|max:100'
            ]);

            $paiement = $this->paiementService->marquerPaye(
                $id,
                $validatedData['reference_transaction'] ?? null
            );

            return response()->json([
                'message' => 'Paiement marqué comme payé',
                'paiement' => $paiement
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la validation du paiement',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Traiter un paiement mobile money
     */
    public function traiterMobileMoney(Request $request, $id)
    {
        try {
            $validatedData = $request->validate([
                'numero_telephone' => 'required|string|max:20',
                'operateur' => 'required|in:orange,mtn,moov'
            ]);

            $paiement = $this->paiementService->traiterPaiementMobileMoney(
                $id,
                $validatedData['numero_telephone'],
                $validatedData['operateur']
            );

            return response()->json([
                'message' => 'Transaction mobile money initiée',
                'paiement' => $paiement
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors du traitement mobile money',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirmer un paiement mobile money
     */
    public function confirmerMobileMoney(Request $request, $id)
    {
        try {
            $validatedData = $request->validate([
                'statut_transaction' => 'required|in:success,failed'
            ]);

            $paiement = $this->paiementService->confirmerPaiementMobileMoney(
                $id,
                $validatedData['statut_transaction']
            );

            return response()->json([
                'message' => 'Statut du paiement mobile money mis à jour',
                'paiement' => $paiement
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la confirmation',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les paiements en attente
     */
    public function enAttente()
    {
        try {
            $paiements = $this->paiementService->getPaiementsEnAttente();
            return response()->json($paiements, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la récupération',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques des paiements
     */
    public function statistiques(Request $request)
    {
        try {
            $periode = $request->query('periode', 'mois');
            $stats = $this->paiementService->getStatistiques($periode);

            return response()->json($stats, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la récupération des statistiques',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Annuler un paiement
     */
    public function annuler(Request $request, $id)
    {
        try {
            $validatedData = $request->validate([
                'motif' => 'nullable|string|max:500'
            ]);

            $paiement = $this->paiementService->annulerPaiement($id, $validatedData['motif'] ?? null);

            return response()->json([
                'message' => 'Paiement annulé avec succès',
                'paiement' => $paiement
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de l\'annulation',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rembourser un paiement
     */
    public function rembourser(Request $request, $id)
    {
        try {
            $validatedData = $request->validate([
                'montant_remboursement' => 'nullable|numeric|min:0',
                'motif' => 'nullable|string|max:500'
            ]);

            $remboursement = $this->paiementService->rembourserPaiement(
                $id,
                $validatedData['montant_remboursement'] ?? null,
                $validatedData['motif'] ?? null
            );

            return response()->json([
                'message' => 'Remboursement effectué avec succès',
                'remboursement' => $remboursement
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors du remboursement',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour le statut de paiement d'une commande
     */
    public function updateStatutPaiement(Request $request, $commandeId)
    {
        try {
            $validatedData = $request->validate([
                'statut' => 'required|in:payée,non_payée,en_cours,échec,annulé,remboursé_total,remboursé_partiel'
            ]);

            // Trouver le paiement par commande_id
            $paiement = \App\Models\Paiements::where('commande_id', $commandeId)->firstOrFail();

            $paiementUpdated = $this->paiementService->update($validatedData, $paiement->id);

            return response()->json([
                'message' => 'Statut de paiement mis à jour avec succès',
                'paiement' => $paiementUpdated
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la mise à jour du statut',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer un rapport de paiements
     */
    public function rapport(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'date_debut' => 'required|date',
                'date_fin' => 'required|date|after_or_equal:date_debut'
            ]);

            $rapport = $this->paiementService->genererRapport(
                $validatedData['date_debut'],
                $validatedData['date_fin']
            );

            return response()->json($rapport, 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la génération du rapport',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
