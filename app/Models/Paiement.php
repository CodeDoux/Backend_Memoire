<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paiement extends Model
{
    protected $fillable = [
        'reference',
        'commande_id',
        'montant',
        'statutPaiement',
        'modePaiement',
        'telephone',
        'operateur',
        'datePaiement',
        'typePaiement'
    ];

    /**
     * Relation : chaque paiement appartient à une commande
     */
    public function commande()
    {
        return $this->belongsTo(Commande::class);
    }

    /**
     * Relation : le statut du paiement (PAYEE, NON_PAYEE, REMBOURSE)
     * → stocké dans la table CodeList
     */
    public function statut()
    {
        return $this->belongsTo(CodeList::class, 'statut_id');
    }

    /**
     *  Relation : mode de paiement (EN_LIGNE, EN_ESPECE)
     * → stocké dans CodeList
     */
    public function modePaiement()
    {
        return $this->belongsTo(CodeList::class, 'modePaiement_id');
    }

    /**
     * Relation : opérateur de paiement (ORANGE_MONEY, WAVE, FREE_MONEY)
     * → stocké dans CodeList
     */
    public function operateur()
    {
        return $this->belongsTo(CodeList::class, 'operateur_id');
    }

    /**
     * Vérifie si le paiement est validé
     */
    public function estValide(): bool
    {
        return $this->statut && $this->statut->value === 'PAYEE';
    }

    /**
     * Vérifie si le paiement est en attente
     */
    public function estEnAttente(): bool
    {
        return $this->statut && $this->statut->value === 'NON_PAYEE';
    }
}
