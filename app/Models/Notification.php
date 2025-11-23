<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;
    protected $fillable = [
        'destinataire_id',       // Destinataire
        'titre',
        'message',
        'dateEnvoi',
        'estLu',
        'type_id'        // Référence vers CodeList (type de notification)
    ];

    /**
     * 🔗 Relation : une notification appartient à un utilisateur
     */
    public function destinataire()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 🔗 Relation : le type de notification vient de CodeList
     */
    public function type()
    {
        return $this->belongsTo(CodeList::class, 'type_id');
    }

    /**
     * Marquer la notification comme lue
     */
    public function marquerCommeLu(): void
    {
        $this->estLu = true;
        $this->save();
    }

    /**
     * Vérifie si la notification est déjà lue
     */
    public function estDejaLue(): bool
    {
        return (bool) $this->estLu;
    }
}
