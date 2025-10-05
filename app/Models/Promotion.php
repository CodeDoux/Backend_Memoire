<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use HasFactory;
    protected $fillable = [
        'nom', 'description', 'dateDebut', 'dateFin', 'reduction', 'active'
    ];
    public function produits()
{
    return $this->belongsToMany(Produit::class, 'promo_produits', 'promo_id', 'produit_id')
                ->withTimestamps();
}
}
