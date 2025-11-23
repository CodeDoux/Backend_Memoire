<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Administrateur extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'niveau', // ex : 1 = admin simple, 2 = super admin
    ];

    /**
     * 🔗 Relation avec l'utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    /**
     * Accessor pour obtenir le libellé du niveau
     */
    public function getRoleLabelAttribute()
    {
        return match ($this->niveau) {
            0 => 'Super Admin',
            1 => 'Admin Simple',
            default => 'Modérateur',
        };
    }
}
