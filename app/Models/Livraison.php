<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Livraison extends Model
{
    use HasFactory;
    protected $fillable = [
        'reference',
        'dateExpedition',
        'dateLivraison',
        'commande_id',
        'statutLivraison',
        'adresseLivraison_id',
        'zoneLivraison_id'
    ];

    /**
     * Relation : chaque livraison est liée à une commande
     */
    public function commande()
    {
        return $this->belongsTo(Commande::class);
    }

    /**
     *  Relation : chaque livraison se fait dans une zone de livraison
     */
    public function zoneLivraison()
    {
        return $this->belongsTo(ZoneLivraison::class);
    }

    /**
     * Relation : statut de la livraison (EN_COURS, LIVRÉE, NON_LIVRÉE)
     * → géré via la table CodeList
     */
    public function statut()
    {
        return $this->belongsTo(CodeList::class, 'statut_id');
    }

    /**
     * Relation : adresse de livraison associée
     */
    public function adresseLivraison()
    {
        return $this->belongsTo(Adresse::class, 'adresseLivraison_id');
    }

    /**
     * Vérifie si la livraison est terminée
     */
    public function estLivree(): bool
    {
        return $this->statut && $this->statut->value === 'LIVREE';
    }

    /**
     *  Vérifie si la livraison est encore en cours
     */
    public function estEnCours(): bool
    {
        return $this->statut && $this->statut->value === 'EN_COURS';
    }
}
