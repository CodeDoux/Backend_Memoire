<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromoProduit extends Model
{
    use HasFactory;
    protected $fillable = ['promo_id','produit_id','montantReduction'];

}
