<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CodePromo extends Model
{
    use HasFactory;
    protected $fillable = [
        'code', 'description', 'reduction',
        'dateDebut', 'dateFin', 'actif'
    ];

    public function commandes()
    {
        return $this->hasMany(Commande::class);
    }

    public function estValive()
    {
        $now = now();
        return $this->actif &&
            (!$this->dateDebut || $this->dateDebut <= $now) &&
            (!$this->dateFin || $this->dateFin >= $now);
    }
}
