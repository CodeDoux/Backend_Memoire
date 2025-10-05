<?php

namespace App\services;

use App\Models\Paiement;
use App\Models\Commande;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PaiementService
{
    public function index()
    {
        return Paiement::with(['commande.user:id,nomComplet,email'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function store(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Valider que la commande existe et n'a pas déjà de paiement
            $commande = Commande::findOrFail($data['commande_id']);

            if ($commande->paiement) {
                throw new \Exception('Cette commande a déjà un paiement associé');
            }

            // Générer une référence de transaction si paiement en ligne
            if ($data['mode_paiement'] === 'en_ligne') {
                $data['reference_transaction'] = $this->genererReferenceTransaction();
            }

            $paiement = Paiement::create($data);

            return $paiement->load(['commande.user']);
        });
    }

    public function show($id)
    {
        return Paiement::with(['commande.user:id,nomComplet,email'])->findOrFail($id);
    }

    public function update(array $data, $id)
    {
        return DB::transaction(function () use ($data, $id) {
            $paiement = Paiement::findOrFail($id);

            $paiement->update($data);

            return $paiement->load(['commande.user']);
        });
    }

    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $paiement = Paiement::findOrFail($id);
            return $paiement->delete();
        });
    }

    /**
     * Marquer un paiement comme payé
     */
    public function marquerPaye($paiementId, $referenceTransaction = null)
    {
        return DB::transaction(function () use ($paiementId, $referenceTransaction) {
            $paiement = Paiement::findOrFail($paiementId);

            $updateData = [
                'statut' => 'payée',
                'date_paiement' => now()
            ];

            if ($referenceTransaction) {
                $updateData['reference_transaction'] = $referenceTransaction;
            }

            $paiement->update($updateData);

            return $paiement->load(['commande.user']);
        });
    }

    /**
     * Traiter un paiement mobile money
     */
   public function traiterPaiementMobileMoney($paiementId, $numeroTelephone, $operateur)
{
    return DB::transaction(function () use ($paiementId, $numeroTelephone, $operateur) {
        $paiement = Paiement::findOrFail($paiementId);

        if ($paiement->mode_paiement !== 'en_ligne') {
            throw new \Exception('Ce paiement n\'est pas configuré pour le mobile money');
        }

        // Appel API ou simulation
        $referenceTransaction = $this->initierTransactionMobileMoney(
            $numeroTelephone,
            $operateur,
            $paiement->montant_payé
        );

        $paiement->update([
            'numero_telephone' => $numeroTelephone,
            'operateur' => $operateur,
            'reference_transaction' => $referenceTransaction,
            'statut' => 'en_cours'
        ]);

        return $paiement;
    });
}
    /**
     * Confirmer un paiement mobile money
     */
    public function confirmerPaiementMobileMoney($paiementId, $statutTransaction)
    {
        $paiement = Paiement::findOrFail($paiementId);

        if ($statutTransaction === 'success') {
            $paiement->update([
                'statut' => 'payée',
                'date_paiement' => now()
            ]);
        } else {
            $paiement->update([
                'statut' => 'échec'
            ]);
        }

        return $paiement;
    }

    /**
     * Obtenir les paiements en attente
     */
    public function getPaiementsEnAttente()
{
    return Paiement::with(['commande.user:id,nomComplet,email'])
        ->whereIn('statut', ['non_payée', 'en_cours'])
        ->orderBy('created_at', 'asc')
        ->get();
}

    /**
     * Obtenir les paiements par mode
     */
    public function getPaiementsParMode($mode)
    {
        return Paiements::with(['commande.user:id,nomComplet,email'])
            ->where('mode_paiement', $mode)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Obtenir les paiements par opérateur mobile money
     */
    public function getPaiementsParOperateur($operateur)
    {
        return Paiement::with(['commande.user:id,nomComplet,email'])
            ->where('operateur', $operateur)
            ->where('mode_paiement', 'en_ligne')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Calculer les revenus par période
     */
    public function getRevenusPeriode($dateDebut, $dateFin)
    {
        return Paiement::where('statut', 'payée')
            ->whereBetween('date_paiement', [$dateDebut, $dateFin])
            ->sum('montant_payé');
    }

    /**
     * Générer une référence de transaction unique
     */
    private function genererReferenceTransaction()
    {
        return 'TXN_' . time() . '_' . strtoupper(substr(uniqid(), -6));
    }

    /**
     * Initier une transaction mobile money (simulation)
     */
    private function initierTransactionMobileMoney($numeroTelephone, $operateur, $montant)
    {
        // Ici vous intégreriez avec l'API réelle du fournisseur
        // Pour la simulation, on génère juste une référence

        $prefixes = [
            'orange' => 'OM_',
            'mtn' => 'MTN_',
            'moov' => 'MV_'
        ];

        $prefix = $prefixes[$operateur] ?? 'TXN_';
        return $prefix . time() . '_' . rand(1000, 9999);
    }

    /**
     * Statistiques des paiements
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

        $paiementsParMode = Paiement::selectRaw('mode_paiement, COUNT(*) as count, SUM(montant_payé) as total')
            ->where('created_at', '>=', $dateDebut)
            ->where('statut', 'payée')
            ->groupBy('mode_paiement')
            ->get();

        $paiementsParOperateur = Paiement::selectRaw('operateur, COUNT(*) as count, SUM(montant_payé) as total')
            ->where('created_at', '>=', $dateDebut)
            ->where('statut', 'payée')
            ->where('mode_paiement', 'en_ligne')
            ->groupBy('operateur')
            ->get();

        return [
            'total_paiements' => Paiement::where('created_at', '>=', $dateDebut)->count(),
            'paiements_reussis' => Paiement::where('created_at', '>=', $dateDebut)->where('statut', 'payée')->count(),
            'paiements_en_attente' => Paiement::whereIn('statut', ['non_payée', 'en_cours'])->count(),
            'paiements_echec' => Paiement::where('created_at', '>=', $dateDebut)->where('statut', 'échec')->count(),
            'revenus_total' => Paiement::where('created_at', '>=', $dateDebut)->where('statut', 'payée')->sum('montant_payé'),
            'revenus_par_mode' => $paiementsParMode,
            'revenus_par_operateur' => $paiementsParOperateur,
            'montant_moyen' => Paiement::where('created_at', '>=', $dateDebut)->where('statut', 'payée')->avg('montant_payé')
        ];
    }

    /**
     * Générer un rapport de paiements
     */
    public function genererRapport($dateDebut, $dateFin)
    {
        return [
            'periode' => [
                'debut' => $dateDebut,
                'fin' => $dateFin
            ],
            'resume' => [
                'total_transactions' => Paiement::whereBetween('created_at', [$dateDebut, $dateFin])->count(),
                'transactions_reussies' => Paiement::whereBetween('created_at', [$dateDebut, $dateFin])->where('statut', 'payée')->count(),
                'revenus_total' => Paiement::whereBetween('date_paiement', [$dateDebut, $dateFin])->where('statut', 'payée')->sum('montant_payé')
            ],
            'details_par_mode' => Paiement::selectRaw('mode_paiement, COUNT(*) as transactions, SUM(montant_payé) as revenus')
                ->whereBetween('created_at', [$dateDebut, $dateFin])
                ->where('statut', 'payée')
                ->groupBy('mode_paiement')
                ->get(),
            'details_par_operateur' => Paiement::selectRaw('operateur, COUNT(*) as transactions, SUM(montant_payé) as revenus')
                ->whereBetween('created_at', [$dateDebut, $dateFin])
                ->where('statut', 'payée')
                ->where('mode_paiement', 'en_ligne')
                ->groupBy('operateur')
                ->get()
        ];
    }

    /**
     * Vérifier le statut d'un paiement mobile money
     */
    public function verifierStatutPaiement($referenceTransaction)
    {
        // Ici vous intégreriez avec l'API du fournisseur pour vérifier le statut
        // Pour la simulation, on retourne un statut aléatoire

        $statuts = ['success', 'pending', 'failed'];
        return $statuts[array_rand($statuts)];
    }

    /**
     * Annuler un paiement
     */
    public function annulerPaiement($paiementId, $motif = null)
    {
        return DB::transaction(function () use ($paiementId, $motif) {
            $paiement = Paiements::findOrFail($paiementId);

            if ($paiement->statut === 'payée') {
                throw new \Exception('Un paiement déjà effectué ne peut pas être annulé');
            }

            $paiement->update([
                'statut' => 'annulé',
                'note' => $motif
            ]);

            return $paiement;
        });
    }

    /**
     * Rembourser un paiement
     */
    public function rembourserPaiement($paiementId, $montantRemboursement = null, $motif = null)
{
    return DB::transaction(function () use ($paiementId, $montantRemboursement, $motif) {
        $paiement = Paiement::findOrFail($paiementId);

        if ($paiement->statut !== 'payée') {
            throw new \Exception('Seuls les paiements effectués peuvent être remboursés');
        }

        $montantRemboursement = $montantRemboursement ?: $paiement->montant_payé;

        if ($montantRemboursement > $paiement->montant_payé) {
            throw new \Exception('Le montant du remboursement ne peut pas être supérieur au montant payé');
        }

        // Créer un remboursement
        $remboursement = Paiement::create([
            'commande_id' => $paiement->commande_id,
            'mode_paiement' => $paiement->mode_paiement,
            'montant_payé' => -$montantRemboursement,
            'statut' => 'remboursé',
            'date_paiement' => now(),
            'reference_transaction' => 'REFUND_' . $paiement->reference_transaction,
            'note' => $motif
        ]);

        // Mettre à jour le paiement original
        $paiement->update([
            'statut' => $montantRemboursement == $paiement->montant_payé ? 'remboursé_total' : 'remboursé_partiel'
        ]);

        return $remboursement;
    });
}
}
