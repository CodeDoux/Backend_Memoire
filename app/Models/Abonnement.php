<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Abonnement extends Model
{
   protected $fillable = [
        'type_id',           // Référence vers CodeList (type d’abonnement)
        'description',
        'prix',
        'dureeJours',
        'maxProduits',
        'dateDebut',
        'dateFin',
        'statut_id',            // actif / expiré / suspendu (CodeList )
        'producteur_id',     // producteur lié
    ];

    /**
     * 🔗 Le producteur qui a souscrit cet abonnement
     */
    public function producteur()
    {
        return $this->belongsTo(Producteur::class);
    }

    /**
     * 🔗 Type d’abonnement (lié à CodeList)
     */
    public function type()
    {
        return $this->belongsTo(CodeList::class, 'type_id');
    }
    /**
     * 🔗 statut d’abonnement (lié à CodeList)
     */
    public function statut()
    {
        return $this->belongsTo(CodeList::class, 'statut_id');
    }

    /**
     * Vérifie si l’abonnement est actuellement actif.
     */
    public function estActif(): bool
    {
        if (!$this->dateDebut || !$this->dateFin) {
            return false;
        }

        $now = Carbon::now();
        return $now->between($this->dateDebut, $this->dateFin);
    }

    /**
     * Vérifie si l’abonnement est expiré.
     */
    public function estExpire(): bool
    {
        return $this->dateFin && Carbon::parse($this->dateFin)->isPast();
    }

    /**
     * Calcule automatiquement la date de fin à partir de la durée.
     */
    public function setDateFinAutomatique(): void
    {
        if ($this->dateDebut && $this->dureeJours) {
            $this->dateFin = Carbon::parse($this->dateDebut)->addDays($this->dureeJours);
        }
    }
}
