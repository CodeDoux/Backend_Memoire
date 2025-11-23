<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Avis extends Model
{
    use HasFactory;
    protected $fillable = [
        'note',
        'commentaire',
        'dateAvis',
        'estVerifie',
        'client_id',     // L'auteur de l'avis (client)
        'produit_id'   // Le produit concerné
    ];

    /**
     * 🔗 Relation : un avis appartient à un utilisateur (client)
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * 🔗 Relation : un avis concerne un produit
     */
    public function produit()
    {
        return $this->belongsTo(Produit::class);
    }

    /**
     * Vérifie si l'avis a été validé (modéré)
     */
    public function estValide(): bool
    {
        return (bool) $this->estVerifie;
    }

    /**
     * Marquer un avis comme vérifié
     */
    public function verifier(): void
    {
        $this->estVerifie = true;
        $this->save();
    }
}
