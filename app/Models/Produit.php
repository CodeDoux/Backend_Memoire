<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produit extends Model
{
    use HasFactory;
    protected $fillable = ['nom','description','stock','prix','saison','note','categorie_id'];
    //un produit appartient à une catégorie
    public function categorie() {
        return $this->belongsTo(Categorie::class);
    }

    //un produit peut avoir plusieurs promotion
    public function promotions()
    {
        return $this->belongsToMany(Promotion::class, 'promo_produits')
            ->withPivot('montant_reduction')
            ->withTimestamps();
    }
    //un produit peut etre dans plusieurs commande
    public function detailsCommande() {
        return $this->hasMany(DetailsCommande::class);
    }
    public function images()
    {
        return $this->hasMany(Image::class);
    }
    public function imagePrincipale()
{
    return $this->hasOne(Image::class)->where('is_primary', true);
}
     public function producteur()
    {
        return $this->belongsTo(Producteur::class);
    }

}
