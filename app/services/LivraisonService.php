<?php

namespace App\services;

use App\Models\Livraison;
use App\Models\Commande;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LivraisonService
{
    public function index()
    {
        return Livraison::with(['commande.user:id,nomComplet,email'])
            ->orderBy('date_livraison', 'desc')
            ->get();
    }
    public function store(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Valider que la commande existe et n'a pas déjà de livraison
            $commande = Commande::findOrFail($data['commande_id']);

            if ($commande->livraison) {
                throw new \Exception('Cette commande a déjà une livraison associée');
            }
            
            $livraison = Livraison::create($data);

            // Mettre à jour le statut de la commande si nécessaire
            if ($commande->statut === 'EN_PREPARATION') {
                $commande->update(['statut' => 'LIVREE']);
            }

            return $livraison->load(['commande.user']);
        });
    }
     public function show($id)
    {
        return Livraison::with(['commande.user:id,nomComplet,email'])
            ->findOrFail($id);
    }
    public function update(array $data, $id)
    {
        return DB::transaction(function () use ($data, $id) {
            $livraison = Livraison::findOrFail($id);
            $ancienStatut = $livraison->statut;

            $livraison->update($data);

            // Mettre à jour le statut de la commande selon le nouveau statut de livraison
            if (isset($data['statut']) && $data['statut'] !== $ancienStatut) {
                $this->updateCommandeStatut($livraison, $data['statut']);
            }

            return $livraison->load(['commande.user']);
        });
    }
    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $livraison = Livraison::findOrFail($id);
            $commande = $livraison->commande;

            // Remettre le statut de la commande à en_préparation
            if ($commande && in_array($commande->statut, ['prete', 'en_livraison', 'livrée'])) {
                $commande->update(['statut' => 'en_préparation']);
            }

            return $livraison->delete();
        });
    }
    /**
     * Marquer une livraison comme terminée
     */
    public function marquerLivree($livraisonId, $note = null)
    {
        return DB::transaction(function () use ($livraisonId, $note) {
            $livraison = Livraison::findOrFail($livraisonId);

            $livraison->update([
                'statut' => 'livrée',
                'note' => $note ?: $livraison->note
            ]);

            // Mettre à jour la commande
            $livraison->commande->update(['statut' => 'livrée']);

            // Si paiement à la livraison, marquer comme payé
            $paiement = $livraison->commande->paiement;
            if ($paiement && $paiement->mode_paiement === 'à_la_livraison') {
                $paiement->update([
                    'statut' => 'payée',
                    'date_paiement' => now()
                ]);
            }

            return $livraison->load(['commande.user', 'employe']);
        });
    }
    /**
     * Obtenir les livraisons du jour
     */
    public function getLivraisonsAujourdhui()
    {
        return Livraison::with(['commande.user:id,nomComplet,email'])
            ->aujourdhui()
            ->orderBy('date_livraison', 'asc')
            ->get();
    }
    /**
     * Obtenir les livraisons en retard
     */
    public function getLivraisonsEnRetard()
    {
        return Livraison::with(['commande.user:id,nomComplet,email'])
            ->enRetard()
            ->orderBy('date_livraison', 'asc')
            ->get();
    }

    /**
     * Calculer les frais de livraison selon la zone
     */
    public function calculerFraisLivraison($ville, $distance = null)
    {
        $tarifsPredefinies = [
            'Dakar' => 2000,
            'Rufisque' => 2500,
            'Pikine' => 1500,
            'Guédiawaye' => 1800,
            'Thiaroye' => 2200
        ];

        if (isset($tarifsPredefinies[ucfirst(strtolower($ville))])) {
            return $tarifsPredefinies[ucfirst(strtolower($ville))];
        }

        // Calcul basé sur la distance pour les zones non prédéfinies
        if ($distance) {
            return max(1000, $distance * 50); // 50 FCFA par km, minimum 1000
        }

        return 3000; // Tarif par défaut pour les zones éloignées
    }

    /**
     * Planifier les livraisons optimales
     */
    public function planifierLivraisons($date, $employeId = null)
    {
        $livraisons = Livraison::with(['commande.user'])
            ->whereDate('date_livraison', $date)
            ->where('statut', 'non_livrée');

        if ($employeId) {
            $livraisons->where('employe_id', $employeId);
        }

        // Grouper par zone géographique pour optimiser les trajets
        $livraisonsGroupees = $livraisons->get()->groupBy(function ($livraison) {
            return $this->extraireZone($livraison->adresse_livraison);
        });

        return $livraisonsGroupees;
    }

    /**
     * Mettre à jour le statut de la commande selon le statut de livraison
     */
    private function updateCommandeStatut($livraison, $nouveauStatutLivraison)
    {
        $commande = $livraison->commande;
        if (!$commande) return;

        $mapping = [
            'en_cours' => 'en_livraison',
            'livrée' => 'livrée',
            'annulée' => 'annulée'
        ];

        if (isset($mapping[$nouveauStatutLivraison])) {
            $commande->update(['statut' => $mapping[$nouveauStatutLivraison]]);
        }
    }
    /**
     * Extraire la zone d'une adresse
     */
    private function extraireZone($adresse)
    {
        $zones = ['Dakar', 'Pikine', 'Rufisque', 'Guédiawaye', 'Thiaroye'];

        foreach ($zones as $zone) {
            if (stripos($adresse, $zone) !== false) {
                return $zone;
            }
        }

        return 'Autre';
    }
    /**
     * Statistiques des livraisons
     */
    public function getStatistiques($periode = 'mois')
    {
        $dateDebut = match($periode) {
            'jour' => Carbon::today(),
            'semaine' => Carbon::now()->startOfWeek(),
            'mois' => Carbon::now()->startOfMonth(),
            'annee' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth()
        };

        return [
            'total_livraisons' => Livraison::where('created_at', '>=', $dateDebut)->count(),
            'livrees' => Livraison::where('created_at', '>=', $dateDebut)->where('statut', 'livrée')->count(),
            'en_cours' => Livraison::where('statut', 'en_cours')->count(),
            'en_retard' => Livraison::enRetard()->count(),
            'chiffre_affaires_livraisons' => Livraison::join('commandes', 'commandes.id', '=', 'livraisons.commande_id')
                ->where('livraisons.statut', 'livrée')
                ->where('livraisons.created_at', '>=', $dateDebut)
                ->sum('commandes.montant_total'),
            'frais_livraison_total' => Livraison::where('created_at', '>=', $dateDebut)->sum('frais_livraison')
        ];
    }




}