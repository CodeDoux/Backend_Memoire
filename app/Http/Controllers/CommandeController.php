<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\services\CommandeService;
use App\Http\Requests\CommandeRequest;
use App\Models\Commande;

class CommandeController extends Controller
{
    protected $commandeService;

    public function __construct(CommandeService $commandeService)
    {
        $this->commandeService = $commandeService;
    }

    public function indexAdmin(){
        // chargement explicite des relations
        $commandes = Commande::with([
            'user:id,nomComplet,email',
            'LigneCommande', // la relation principale
            'LigneCommande.produit:id,nom,prix,description', // Puis les sous-relations
            'paiement:id,commande_id,statut,modePaiement,montant_paye,date_paiement',
            'livraison:id,commande_id,statut,adresse_livraison,date_livraison,frais_livraison'
        ]);
        return $commandes;
    }


    public function stats()
    {
        try {
            return response()->json([
                'pending'   => Commande::where('statut', 'EN_ATTENTE')->count(),
                'processing'=> Commande::where('statut', 'EN_PREPARATION')->count(),
                'delivery'  => Commande::where('statut', 'EN_LIVRAISON')->count(),
                'completed' => Commande::where('statut', 'LIVREE')->count(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la récupération des statistiques',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function index()
    {
        
        try {
            $commandes = $this->commandeService->index();
            return response()->json($commandes, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la récupération des commandes',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(CommandeRequest $request)
    {
        try {
            // Ajouter l'ID du client connecté
            $validatedData = $request->validated();
            $validatedData['client_id'] = auth()->id();

            $commande = $this->commandeService->store($validatedData);

            return response()->json([
                'message' => 'Commande créée avec succès',
                'commande' => $commande
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la création de la commande',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $commande = $this->commandeService->show($id);

            // Vérifier les permissions
            $user = auth()->user();
            if ($user->role === 'CLIENT' && $commande->client_id !== $user->id) {
                return response()->json([
                    'error' => 'Accès non autorisé à cette commande'
                ], 403);
            }

            return response()->json($commande, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Commande non trouvée',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validatedData = $request->validate([
                'statut' => 'sometimes|string|in:en_préparation,prete,en_livraison,livrée,annulée',
                'employe_id' => 'sometimes|nullable|integer|exists:users,id'
            ]);

            $commande = $this->commandeService->update($validatedData, $id);

            return response()->json([
                'message' => 'Commande mise à jour avec succès',
                'commande' => $commande
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

    public function mesCommandes()
    {
        try {
            $user = auth()->user();
            $commandes = $this->commandeService->getByClient($user->id);

            return response()->json($commandes, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la récupération de vos commandes',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $this->commandeService->destroy($id);

            return response()->json([
                'message' => 'Commande supprimée avec succès'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la suppression',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour uniquement le statut d'une commande
     */
    public function updateStatut(Request $request, $id)
    {
        try {
            $validatedData = $request->validate([
                'statut' => 'required|string|in:en_préparation,prete,en_livraison,livrée,annulée'
            ]);

            $commande = $this->commandeService->update($validatedData, $id);

            return response()->json([
                'message' => 'Statut de la commande mis à jour avec succès',
                'commande' => $commande
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la mise à jour du statut',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour le statut de livraison d'une commande
     */
    public function updateLivraisonStatut(Request $request, $id)
    {
        try {
            $validatedData = $request->validate([
                'statut' => 'required|in:non_livrée,en_cours,livrée,annulée'
            ]);

            $commande = Commandes::with('livraison')->findOrFail($id);

            if (!$commande->livraison) {
                return response()->json([
                    'error' => 'Cette commande n\'a pas de livraison associée'
                ], 404);
            }

            // Utiliser le service de livraison pour mettre à jour
            app(\App\services\LivraisonService::class)->update(
                $validatedData,
                $commande->livraison->id
            );

            return response()->json([
                'message' => 'Statut de livraison mis à jour avec succès'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la mise à jour du statut de livraison',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour le statut de paiement d'une commande
     */
    public function updatePaiementStatut(Request $request, $id)
    {
        try {
            $validatedData = $request->validate([
                'statut' => 'required|in:payée,non_payée,en_cours,échec,annulé'
            ]);

            $commande = Commandes::with('paiement')->findOrFail($id);

            if (!$commande->paiement) {
                return response()->json([
                    'error' => 'Cette commande n\'a pas de paiement associé'
                ], 404);
            }

            // Utiliser le service de paiement pour mettre à jour
            app(\App\services\PaiementService::class)->update(
                $validatedData,
                $commande->paiement->id
            );

            return response()->json([
                'message' => 'Statut de paiement mis à jour avec succès'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la mise à jour du statut de paiement',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Appliquer une promotion à une commande existante
     */
    public function appliquerPromotion(Request $request, $id)
    {
        try {
            $validatedData = $request->validate([
                'promo_id' => 'required|exists:promotions,id',
                'produit_ids' => 'required|array|min:1',
                'produit_ids.*' => 'exists:produits,id'
            ]);

            $result = $this->commandeService->appliquerPromotion(
                $id,
                $validatedData['promo_id'],
                $validatedData['produit_ids']
            );

            return response()->json([
                'message' => 'Promotion appliquée avec succès',
                'commande' => $result
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de l\'application de la promotion',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retirer une promotion d'une commande
     */
    public function retirerPromotion(Request $request, $id)
    {
        try {
            $validatedData = $request->validate([
                'produit_ids' => 'required|array|min:1',
                'produit_ids.*' => 'exists:produits,id'
            ]);

            $result = $this->commandeService->retirerPromotion(
                $id,
                $validatedData['produit_ids']
            );

            return response()->json([
                'message' => 'Promotion retirée avec succès',
                'commande' => $result
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la suppression de la promotion',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques des commandes
     */
    public function statistiques(Request $request)
    {
        try {
            $periode = $request->query('periode', 'mois');
            $stats = $this->commandeService->getStatistiques($periode);

            return response()->json($stats, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la récupération des statistiques',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rechercher des commandes selon des critères
     */
    public function rechercher(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'client_id' => 'nullable|exists:users,id',
                'statut' => 'nullable|in:en_préparation,prete,en_livraison,livrée,annulée',
                'date_debut' => 'nullable|date',
                'date_fin' => 'nullable|date|after_or_equal:date_debut',
                'ville' => 'nullable|string|max:100',
                'montant_min' => 'nullable|numeric|min:0',
                'montant_max' => 'nullable|numeric|min:0'
            ]);

            $commandes = $this->commandeService->rechercherCommandes($validatedData);

            return response()->json([
                'commandes' => $commandes,
                'criteres' => $validatedData
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la recherche',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Dupliquer une commande pour un client
     */
    public function dupliquer($id)
    {
        try {
            $user = auth()->user();
            $commandeOriginale = Commandes::where('id', $id)
                ->where('client_id', $user->id)
                ->firstOrFail();

            $nouvelleCommande = $this->commandeService->dupliquerCommande($id);

            return response()->json([
                'message' => 'Commande dupliquée avec succès',
                'commande' => $nouvelleCommande
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la duplication',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérifier la disponibilité des produits avant commande
     */
    public function verifierDisponibilite(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'produits' => 'required|array|min:1',
                'produits.*.produit_id' => 'required|exists:produits,id',
                'produits.*.quantite' => 'required|integer|min:1'
            ]);

            $result = $this->commandeService->verifierDisponibilite($validatedData['produits']);

            return response()->json($result, 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la vérification',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculer le total d'une commande avec promotions
     */
    public function calculerTotal(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'produits' => 'required|array|min:1',
                'produits.*.produit_id' => 'required|exists:produits,id',
                'produits.*.quantite' => 'required|integer|min:1',
                'frais_livraison' => 'nullable|numeric|min:0'
            ]);

            $result = $this->commandeService->calculerTotalAvecPromotions($validatedData['produits']);

            // Ajouter les frais de livraison
            $fraisLivraison = $validatedData['frais_livraison'] ?? 0;
            $result['total_final'] = $result['total_avec_promotions'] + $fraisLivraison;
            $result['frais_livraison'] = $fraisLivraison;

            return response()->json($result, 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors du calcul',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
