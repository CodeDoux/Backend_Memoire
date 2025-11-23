<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producteur extends Model
{
    use HasFactory;
    protected $fillable = [
        'entreprise',
        'description',
        'ninea',
        'emailPro',
        'telPro',
        'user_id',
    ];
    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function produits()
    {
        return $this->hasMany(Produit::class);
    }
    public function abonnements()
    {
        return $this->hasMany(Abonnement::class);
    }
}
