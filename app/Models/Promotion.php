<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use HasFactory;
     protected $fillable = [
        'code',
        'nom',
        'description',
        'dateDebut',
        'dateFin',
        'reduction',
        'seuilMinimum',
        'utilisationMax',
        'estActif',
        'typePromo',
    ];

    /**
     * 🔗 Une promotion peut concerner plusieurs produits
     */
    public function produits()
{
    return $this->belongsToMany(Produit::class, 'promo_produits', 'promo_id', 'produit_id')
                ->withTimestamps();
}

    /**
     * 🔗 Type de la promotion (CodeList)
     */
    public function type()
    {
        return $this->belongsTo(CodeList::class, 'type_id');
    }

    /**
     * 🔗 Une promotion peut être appliquée à plusieurs commandes
     */
    public function commandes()
    {
        return $this->hasMany(Commande::class);
    }

    /**
     * ✅ Vérifie si la promotion est active aujourd’hui
     */
    public function estActive()
    {
        $today = now();
        return $this->estActif &&
               $this->dateDebut <= $today &&
               $this->dateFin >= $today;
    }

}
