<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Livraison extends Model
{
    use HasFactory;
    protected $fillable = [
        'commande_id', 'adresse_livraison',
        'statut', 'date_livraison', 'zone_livraison_id'
    ];

    public function commande()
    {
        return $this->belongsTo(Commande::class);
    }
    public function zoneLivraison()
    {
        return $this->belongsTo(ZoneLivraison::class);
    }
    
}
