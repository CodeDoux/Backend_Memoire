<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailsCommande extends Model
{
    use HasFactory;
     protected $table = 'details_commandes'; // Assurer le bon nom de table

    protected $fillable = [
        'commande_id',
        'produit_id',
        'quantte',
        'image',
        'prix',
        'code', 
        'libelle',        
        'montant_total'   // Montant total pour cette ligne
    ];

    protected $casts = [
        'quantite' => 'integer',
        'image' => 'string',
        'code' => 'string',
        'prix' => 'decimal:2',
        'montant_total' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relations
    public function commande()
    {
        return $this->belongsTo(Commande::class, 'commande_id');
    }

    public function produit()
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }


    // Accesseurs pour compatibilité avec le frontend
    public function getPrixUnitaireAttribute()
    {
        return $this->prixU;
    }

    // Calculer le prix avec promotion
    public function getPrixAvecPromoAttribute()
    {
        if ($this->promotion && $this->promotion->reduction) {
            return $this->prixU * (1 - $this->promotion->reduction / 100);
        }
        return $this->prixU;
    }

    // Calculer le montant total avec promotion
    public function getMontantAvecPromoAttribute()
    {
        return $this->getPrixAvecPromoAttribute() * $this->quantite;
    }

    // Calculer l'économie réalisée
    public function getEconomieAttribute()
    {
        if ($this->promotion && $this->promotion->reduction) {
            $prixOriginal = $this->prixU * $this->quantite;
            return $prixOriginal - $this->getMontantAvecPromoAttribute();
        }
        return 0;
    }
}
