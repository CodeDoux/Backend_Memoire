<?php

namespace App\services;

use App\Models\DetailsCommande;
use App\Models\Commande;
use App\Models\Produit;
use App\services\LivraisonService;
use App\services\PaiementService;
use App\services\PromotionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CommandeService
{
    protected $livraisonService;
    protected $paiementService;
    protected $promotionService;
    public function __construct(
        LivraisonService $livraisonService,
        PaiementService $paiementService,
        PromotionService $promotionService
    ) {
        $this->livraisonService = $livraisonService;
        $this->paiementService = $paiementService;
        $this->promotionService = $promotionService;
    }
    public function index()
    {
        $user = auth()->user();

        // chargement explicite des relations
        $query = Commande::with([
            'user:id,nomComplet,email',
            'detailsCommande', // la relation principale
            'detailsCommande.produit:id,nom,prix,description', // Puis les sous-relations
            'paiement:id,commande_id,statut,mode_paiement,montant_paye,date_paiement',
            'livraison:id,commande_id,statut,adresse_livraison,date_livraison,frais_livraison'
        ]);

        if ($user->role === 'ADMIN' || $user->role === 'PRO') {
            $commandes = $query->orderBy('created_at', 'desc')->get();
        } else {
            $commandes = $query->where('client_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        // Debug: Vérifier si les relations sont bien chargées
        \Log::info('Commandes récupérées avec relations:', [
            'count' => $commandes->count(),
            'premiere_commande' => $commandes->first() ? [
                'id' => $commandes->first()->id,
                'produits_charges' => $commandes->first()->produitCommander->count(),
                'relations_chargees' => $commandes->first()->getRelations()
            ] : null
        ]);

        return $commandes;
    }

     public function store(array $data)
    {
        return DB::transaction(function () use ($data) {
            // 1. Calculer le total avec promotions
            $totalCalcule = $this->calculerTotalAvecPromotions($data['produits'],$data['codePromo'] ?? null);

            // 2. Créer la commande
            $commande = Commande::create([
                'client_id' => $data['client_id'],
                'montant_total' => $totalCalcule['total_avec_promotions'] + ($data['frais_livraison'] ?? 0),
                'statut' => 'en_préparation'
            ]);

            // 3. Ajouter les produits avec promotions
            $this->ajouterProduitsCommande($commande->id, $data['produits']);

            // 4. Créer la livraison via le service dédié
            if (isset($data['infos_livraison'])) {
                $livraisonData = $this->prepareDataLivraison($commande->id, $data['infos_livraison']);
                $this->livraisonService->store($livraisonData);
            }

            // 5. Créer le paiement via le service dédié
            if (isset($data['infos_paiement'])) {
                $paiementData = $this->prepareDataPaiement($commande->id, $data['infos_paiement'], $commande->montant_total);
                $this->paiementService->store($paiementData);
            }

            return $this->show($commande->id);
        });
    }

    public function show($id)
    {
        return Commande::with([
            'user:id,nomComplet,email',
            'livraison',
            'paiement',
            'detailsCommande' => function($query) {
                $query->with([
                    'produit:id,nom,prix,image,description',
                    'promotion:id,nom,reduction,dateDebut,dateFin'
                ]);
            }
        ])->findOrFail($id);
    }

    public function update(array $data, $id)
    {
        return DB::transaction(function () use ($data, $id) {
            $commande = Commande::findOrFail($id);

            // Mettre à jour la commande
            $commande->update($data);

            // Si changement de statut, mettre à jour livraison et paiement via leurs services
            if (isset($data['statut'])) {
                $this->gererChangementStatut($commande, $data['statut']);
            }

            return $this->show($id);
        });
    }

    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $commande = Commande::findOrFail($id);

            // Restaurer le stock avant suppression
            $this->restaurerStock($commande);

            // Supprimer les relations via les services
            if ($commande->livraison) {
                $this->livraisonService->destroy($commande->livraison->id);
            }
            if ($commande->paiement) {
                $this->paiementService->destroy($commande->paiement->id);
            }

            // Supprimer les produits de la commande
            $commande->detailsCommande()->delete();

            // Supprimer la commande
            return $commande->delete();
        });
    }
    public function getByClient($clientId)
    {
        return Commande::with([
            'detailsCommande' => function($query) {
                $query->with([
                    'produit:id,nom,prix,image',
                    'promotion:id,nom,reduction,dateDebut,dateFin'
                ]);
            },
            'user:id,nomComplet,email',
            'paiement:id,commande_id,statut,mode_paiement',
            'livraison:id,commande_id,statut,adresse_livraison,date_livraison,frais_livraison'
        ])->where('client_id', $clientId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
 * Calculer le total en tenant compte des promotions actives (produits + code promo global)
 */
public function calculerTotalAvecPromotions(array $produits, $codePromo = null)
{
    $totalOriginal = 0;
    $totalAvecPromotions = 0;
    $economiesRealisees = 0;

    foreach ($produits as $produitData) {
        $produit = Produits::findOrFail($produitData['produit_id']);
        $prixUnitaire = $produit->prix;
        $quantite = $produitData['quantite'];

        $totalOriginal += $prixUnitaire * $quantite;

        // Vérifier s'il y a une promotion active pour ce produit
        $promotion = $this->promotionService->getPromotionActiveForProduit($produitData['produit_id']);

        if ($promotion) {
            $prixAvecPromo = $prixUnitaire * (1 - $promotion->reduction / 100);
            $totalAvecPromotions += $prixAvecPromo * $quantite;
            $economiesRealisees += ($prixUnitaire - $prixAvecPromo) * $quantite;
        } else {
            $totalAvecPromotions += $prixUnitaire * $quantite;
        }
    }

    // 📌 Vérifier s'il y a un code promo global
    if ($codePromo) {
        $code = \App\Models\CodePromo::where('code', $codePromo)
            ->where('actif', true)
            ->where('date_debut', '<=', now())
            ->where('date_fin', '>=', now())
            ->first();

            $reduction = $totalAvecPromotions * ($code->valeur / 100);

            $totalAvecPromotions -= $reduction;
            $economiesRealisees += $reduction;

            // Éviter un montant négatif
            if ($totalAvecPromotions < 0) {
                $totalAvecPromotions = 0;
            }
        }
        return [
        'total_original' => $totalOriginal,
        'total_avec_promotions' => $totalAvecPromotions,
        'economies_realisees' => $economiesRealisees
    ];
    }

     /**
     * Ajouter les produits à la commande avec gestion des promotions
     */
    private function ajouterProduitsCommande($commandeId, array $produits)
    {
        foreach ($produits as $produitData) {
            $produit = Produit::findOrFail($produitData['produit_id']);
            $promotion = $this->promotionService->getPromotionActiveForProduit($produitData['produit_id']);

            $prixUnitaire = $produit->prix; // Prix actuel du produit
            $quantite = $produitData['quantite'];

            // Calculer le montant avec promotion si applicable
            if ($promotion) {
                $prixAvecPromo = $prixUnitaire * (1 - $promotion->reduction / 100);
                $montantTotal = $prixAvecPromo * $quantite;
                $promoId = $promotion->id;
            } else {
                $montantTotal = $prixUnitaire * $quantite;
                $promoId = null;
            }

            DetailsCommande::create([
                'commande_id' => $commandeId,
                'produit_id' => $produitData['produit_id'],
                'quantite' => $quantite,
                'prix' => $prixUnitaire, // S'assurer que ce champ est toujours rempli
                'montant_total' => $montantTotal,
                'libelle'=>$produitData['nom'],
                'code'=>$produitData['code'],
                'image'=>$produitData['image'],
            ]);

            // Décrémenter le stock
            $this->decrementerStock($produitData['produit_id'], $quantite);
        }
    }
    
/**
     * Préparer les données pour le service de livraison
     */
    private function prepareDataLivraison($commandeId, array $infosLivraison)
    {
        $adresseLivraison = "{$infosLivraison['adresse']}, {$infosLivraison['ville']}";
        if (!empty($infosLivraison['codePostal'])) {
            $adresseLivraison .= " {$infosLivraison['codePostal']}";
        }

        return [
            'commande_id' => $commandeId,
            'adresse_livraison' => $adresseLivraison,
            'date_livraison' => now()->addDay()->format('Y-m-d H:i:s'),
            'statut' => 'non_livrée',
            'note' => $infosLivraison['commentaires'] ?? null,
            'frais_livraison' => $infosLivraison['fraisLivraison'] ?? 0
        ];
    }
     /**
     * Préparer les données pour le service de paiement
     */
    private function prepareDataPaiement($commandeId, array $infosPaiement, $montantTotal)
    {
        $modePaiement = $infosPaiement['modePaiement'] === 'enligne' ? 'en_ligne' : 'à_la_livraison';

        return [
            'commande_id' => $commandeId,
            'mode_paiement' => $modePaiement,
            'montant_payé' => $montantTotal,
            'date_paiement' => now()->format('Y-m-d H:i:s'),
            'statut' => 'non_payée',
            'numero_telephone' => $infosPaiement['numeroTelephone'] ?? null,
            'operateur' => $infosPaiement['operateur'] ?? null
        ];
    }

    /**
     * Gérer les changements de statut de commande
     */
    private function gererChangementStatut($commande, $nouveauStatut)
    {
        // Mettre à jour le statut de livraison via le service
        if ($commande->livraison) {
    $nouveauStatutLivraison = $this->mapStatutCommandeToLivraison($nouveauStatut);
    if ($nouveauStatutLivraison) { // update seulement si statut valide
        $this->livraisonService->update([
            'statut' => $nouveauStatutLivraison
        ], $commande->livraison->id);
    }
        }

        // Mettre à jour le statut de paiement si nécessaire
        if ($commande->paiement && $nouveauStatut === 'livrée' &&
            $commande->paiement->mode_paiement === 'à_la_livraison') {
            $this->paiementService->update([
                'statut' => 'payée',
                'date_paiement' => now()->format('Y-m-d H:i:s')
            ], $commande->paiement->id);
        }

        // Restaurer le stock si commande annulée
        if ($nouveauStatut === 'annulée') {
            $this->restaurerStock($commande);
        }
    }

    /**
     * Mapper le statut de commande vers le statut de livraison
     */
    private function mapStatutCommandeToLivraison($statutCommande)
    {
         $statutsValidesLivraison = [
        'livrée'         => 'livrée',
        'non_livrée'        => 'non_livrée'
    ];

    return $statutsValidesLivraison[$statutCommande] ?? null;
    }

    /**
     * Décrémenter le stock d'un produit
     */
    private function decrementerStock($produitId, $quantite)
    {
        $produit = Produits::findOrFail($produitId);
        if ($produit->stock >= $quantite) {
            $produit->decrement('stock', $quantite);
        } else {
            throw new \Exception("Stock insuffisant pour le produit {$produit->nom} stock dispo : {$produit->stock}");
        }
    }

    /**
     * Restaurer le stock lors d'une annulation
     */
    private function restaurerStock($commande)
    {
        foreach ($commande->produitCommander as $item) {
            $produit = Produits::find($item->produit_id);
            if ($produit) {
                $produit->increment('stock', $item->quantite);
            }
        }
    }



    /**
     * Appliquer une promotion à une commande existante
     */
    public function appliquerPromotion($commandeId, $promoId, array $produitIds)
    {
        return DB::transaction(function () use ($commandeId, $promoId, $produitIds) {
            $commande = Commandes::findOrFail($commandeId);
            $promotion = $this->promotionService->show($promoId);

            if (!$promotion->actif || $promotion->dateDebut > now() || $promotion->dateFin < now()) {
                throw new \Exception('Cette promotion n\'est plus active');
            }

            // Mettre à jour les produits concernés
            foreach ($produitIds as $produitId) {
                $commandeProduit = CommandeProduit::where('commande_id', $commandeId)
                    ->where('produit_id', $produitId)
                    ->first();

                if ($commandeProduit) {
                    $nouveauPrix = $commandeProduit->prixU * (1 - $promotion->reduction / 100);
                    $commandeProduit->update([
                        'promo_id' => $promoId,
                        'montant_total' => $nouveauPrix * $commandeProduit->quantite
                    ]);
                }
            }

            // Recalculer le montant total de la commande
            $nouveauTotal = $commande->produitCommander->sum('montant_total');
            if ($commande->livraison) {
                $nouveauTotal += $commande->livraison->frais_livraison ?? 0;
            }

            $commande->update(['montant_total' => $nouveauTotal]);

            return $this->show($commandeId);
        });
    }

    /**
     * Retirer une promotion d'une commande
     */
    public function retirerPromotion($commandeId, array $produitIds)
    {
        return DB::transaction(function () use ($commandeId, $produitIds) {
            $commande = Commandes::findOrFail($commandeId);

            foreach ($produitIds as $produitId) {
                $commandeProduit = CommandeProduit::where('commande_id', $commandeId)
                    ->where('produit_id', $produitId)
                    ->first();

                if ($commandeProduit && $commandeProduit->promo_id) {
                    // Restaurer le prix original
                    $commandeProduit->update([
                        'promo_id' => null,
                        'montant_total' => $commandeProduit->prixU * $commandeProduit->quantite
                    ]);
                }
            }

            // Recalculer le montant total
            $nouveauTotal = $commande->produitCommander->sum('montant_total');
            if ($commande->livraison) {
                $nouveauTotal += $commande->livraison->frais_livraison ?? 0;
            }

            $commande->update(['montant_total' => $nouveauTotal]);

            return $this->show($commandeId);
        });
    }

    /**
     * Dupliquer une commande
     */
    public function dupliquerCommande($commandeId)
    {
        return DB::transaction(function () use ($commandeId) {
            $commandeOriginale = Commandes::with(['produitCommander', 'livraison', 'paiement'])
                ->findOrFail($commandeId);

            // Créer une nouvelle commande
            $nouvelleCommande = Commandes::create([
                'client_id' => $commandeOriginale->client_id,
                'montant_total' => $commandeOriginale->montant_total,
                'statut' => 'en_préparation'
            ]);

            // Dupliquer les produits
            foreach ($commandeOriginale->produitCommander as $produit) {
                // Vérifier la disponibilité du stock
                $produitModel = Produits::find($produit->produit_id);
                if (!$produitModel || $produitModel->stock < $produit->stock) {
                    throw new \Exception("Stock insuffisant pour {$produitModel->nom}");
                }

                // Vérifier si la promotion est toujours active
                $promoId = null;
                if ($produit->promo_id) {
                    $promotion = $this->promotionService->getPromotionActiveForProduit($produit->produit_id);
                    $promoId = $promotion ? $promotion->id : null;
                }

                CommandeProduit::create([
                    'commande_id' => $nouvelleCommande->id,
                    'produit_id' => $produit->produit_id,
                    'quantite' => $produit->stock,
                    'montant_total' => $produit->montant_total,
                    'promo_id' => $promoId
                ]);

                // Décrémenter le stock
                $this->decrementerStock($produit->produit_id, $produit->quantite);
            }

            // Créer une nouvelle livraison si nécessaire
            if ($commandeOriginale->livraison) {
                $this->livraisonService->store([
                    'commande_id' => $nouvelleCommande->id,
                    'adresse_livraison' => $commandeOriginale->livraison->adresse_livraison,
                    'date_livraison' => now()->addDay()->format('Y-m-d H:i:s'),
                    'statut' => 'non_livrée',
                    'frais_livraison' => $commandeOriginale->livraison->frais_livraison
                ]);
            }

            // Créer un nouveau paiement
            if ($commandeOriginale->paiement) {
                $this->paiementService->store([
                    'commande_id' => $nouvelleCommande->id,
                    'mode_paiement' => $commandeOriginale->paiement->mode_paiement,
                    'montant_payé' => $nouvelleCommande->montant_total,
                    'date_paiement' => now()->format('Y-m-d H:i:s'),
                    'statut' => 'non_payée'
                ]);
            }

            return $this->show($nouvelleCommande->id);
        });
    }

    /**
     * Vérifier la disponibilité des produits
     */
    public function verifierDisponibilite(array $produits)
    {
        $resultats = [];
        $disponible = true;

        foreach ($produits as $produitData) {
            $produit = Produits::find($produitData['produit_id']);

            if (!$produit) {
                $resultats[] = [
                    'produit_id' => $produitData['produit_id'],
                    'disponible' => false,
                    'message' => 'Produit non trouvé'
                ];
                $disponible = false;
            } elseif ($produit->quantite < $produitData['quantite']) {
                $resultats[] = [
                    'produit_id' => $produitData['produit_id'],
                    'nom' => $produit->nom,
                    'disponible' => false,
                    'stock_disponible' => $produit->quantite,
                    'quantite_demandee' => $produitData['quantite'],
                    'message' => "Stock insuffisant. Disponible: {$produit->quantite}"
                ];
                $disponible = false;
            } else {
                $resultats[] = [
                    'produit_id' => $produitData['produit_id'],
                    'nom' => $produit->nom,
                    'disponible' => true,
                    'stock_disponible' => $produit->quantite,
                    'quantite_demandee' => $produitData['quantite']
                ];
            }
        }

        return [
            'global_disponible' => $disponible,
            'details' => $resultats
        ];
    }

    /**
     * Rechercher commandes par critères
     */
    public function rechercherCommandes(array $criteres)
    {
        $query = Commandes::with([
            'user:id,nomComplet,email',
            'livraison.employe:id,nomComplet',
            'paiement',
            'produitCommander.produit:id,nom,prix,image',
            'produitCommander.promotion:id,nom,reduction'
        ]);

        if (isset($criteres['client_id'])) {
            $query->where('client_id', $criteres['client_id']);
        }

        if (isset($criteres['statut'])) {
            $query->where('statut', $criteres['statut']);
        }

        if (isset($criteres['date_debut'])) {
            $query->whereDate('created_at', '>=', $criteres['date_debut']);
        }

        if (isset($criteres['date_fin'])) {
            $query->whereDate('created_at', '<=', $criteres['date_fin']);
        }

        if (isset($criteres['ville'])) {
            $query->whereHas('livraison', function ($q) use ($criteres) {
                $q->where('adresse_livraison', 'LIKE', '%' . $criteres['ville'] . '%');
            });
        }

        if (isset($criteres['montant_min'])) {
            $query->where('montant_total', '>=', $criteres['montant_min']);
        }

        if (isset($criteres['montant_max'])) {
            $query->where('montant_total', '<=', $criteres['montant_max']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Obtenir les statistiques des commandes
     */
    public function getStatistiques($periode = 'mois')
    {
        $dateDebut = match ($periode) {
            'jour' => Carbon::today(),
            'semaine' => Carbon::now()->startOfWeek(),
            'mois' => Carbon::now()->startOfMonth(),
            'annee' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth()
        };

        $commandesParStatut = Commandes::selectRaw('statut, COUNT(*) as count')
            ->where('created_at', '>=', $dateDebut)
            ->groupBy('statut')
            ->get();

        $ventesParJour = Commandes::selectRaw('DATE(created_at) as date, COUNT(*) as commandes, SUM(montant_total) as chiffre_affaires')
            ->where('created_at', '>=', $dateDebut)
            ->where('statut', '!=', 'annulée')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'total_commandes' => Commandes::where('created_at', '>=', $dateDebut)->count(),
            'commandes_livrees' => Commandes::where('created_at', '>=', $dateDebut)->where('statut', 'livrée')->count(),
            'commandes_annulees' => Commandes::where('created_at', '>=', $dateDebut)->where('statut', 'annulée')->count(),
            'chiffre_affaires' => Commandes::where('created_at', '>=', $dateDebut)
                ->where('statut', '!=', 'annulée')
                ->sum('montant_total'),
            'panier_moyen' => Commandes::where('created_at', '>=', $dateDebut)
                ->where('statut', '!=', 'annulée')
                ->avg('montant_total'),
            'commandes_par_statut' => $commandesParStatut,
            'evolution_ventes' => $ventesParJour,
            'clients_actifs' => Commandes::where('created_at', '>=', $dateDebut)
                ->distinct('client_id')
                ->count('client_id'),
            'produits_vendus' => CommandeProduit::join('commandes', 'commandes.id', '=', 'commande_produits.commande_id')
                ->where('commandes.created_at', '>=', $dateDebut)
                ->where('commandes.statut', '!=', 'annulée')
                ->sum('commande_produits.quantite'),
            'economies_promotions' => $this->calculerEconomiesPromotions($dateDebut)
        ];
    }

    /**
     * Calculer les économies réalisées grâce aux promotions
     */
    private function calculerEconomiesPromotions($dateDebut)
    {
        $produitsAvecPromo = CommandeProduit::join('commandes', 'commandes.id', '=', 'commande_produits.commande_id')
            ->join('promotions', 'promotions.id', '=', 'commande_produits.promo_id')
            ->where('commandes.created_at', '>=', $dateDebut)
            ->where('commandes.statut', '!=', 'annulée')
            ->whereNotNull('commande_produits.promo_id')
            ->selectRaw(' commande_produits.quantite, promotions.reduction')
            ->get();

        $economiesTotal = 0;
        foreach ($produitsAvecPromo as $item) {
            $prixOriginal = $item->prixU * $item->quantite;
            $prixAvecPromo = $prixOriginal * (1 - $item->reduction / 100);
            $economiesTotal += ($prixOriginal - $prixAvecPromo);
        }

        return $economiesTotal;
    }
}



