<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;
    protected $fillable = [
        'adresseLivraison_id',
        'adresseFacturation_id',
        'user_id',
    ];

    /**
     * 🔗 Relation avec l'utilisateur (héritage)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 🔗 Adresse de livraison
     */
    public function adresseLivraison()
    {
        return $this->belongsTo(Adresse::class, 'adresseLivraison_id');
    }

    /**
     * 🔗 Adresse de facturation
     */
    public function adresseFacturation()
    {
        return $this->belongsTo(Adresse::class, 'adresseFacturation_id');
    }

    /**
     * 🔗 Les commandes passées par le client
     */
    public function commandes()
    {
        return $this->hasMany(Commande::class);
    }

    /**
     * 🔗 Les avis laissés par le client
     */
    public function avis()
    {
        return $this->hasMany(Avis::class);
    }
}
