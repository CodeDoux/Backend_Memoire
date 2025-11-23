<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commande extends Model
{
    use HasFactory;
    protected $fillable = [
        'reference',
        'dateCommande',
        'client_id',
        'montantTotal',
        'modeLivraison',
        'statut',
        'infos_livraison',
        'infos_paiement',
        'code_promo_id'
    ];

    protected $casts = [
        'infos_livraison' => 'json',
        'infos_paiement' => 'json',
        'montant_total' => 'decimal:2'
    ];

    // Une commande appartient à un utilisateur
    public function user()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    // Alias pour compatibilité
    public function client()
    {
        return $this->user();
    }

    // Une commande contient plusieurs produits via detailsCommande
    public function LigneCommande()
    {
        return $this->hasMany(LigneCommande::class, 'commande_id');
    }
    //pour récupérer tous les produits
    public function produits()
    {
    return $this->belongsToMany(Produit::class, 'details_produits')
                ->withPivot('quantite', 'prix')
                ->withTimestamps();
    }

    // Une commande a un paiement
    public function paiement()
    {
        return $this->hasOne(Paiement::class, 'commande_id');
    }

    // Une commande peut avoir une livraison
    public function livraison()
    {
        return $this->hasOne(Livraison::class, 'commande_id');
    }

    // Accesseur pour le nom complet du client
    public function getNomClientAttribute()
    {
        if ($this->user) {
            return $this->user->nomComplet;
        }
        return 'Client inconnu';
    }

    // Méthode pour calculer le total
    public function calculerTotal()
    {
        return $this->produitCommander->sum(function ($item) {
            return $item->quantite * $item->prix;
        });
    }
    public function Promotion()
{
    return $this->belongsTo(Promotion::class);
}
}
