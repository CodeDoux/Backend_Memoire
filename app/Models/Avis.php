<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Avis extends Model
{
    use HasFactory;
    protected $fillable = ['utilisateur_id', 'note', 'commentaire'];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
