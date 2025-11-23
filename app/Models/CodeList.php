<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CodeList extends Model
{
    use HasFactory;
    protected $fillable = ['type','value','description'];
    /**
     * 🔍 Récupère toutes les entrées d’un type donné
     */
    public static function getByType(string $type)
    {
        return self::where('type', $type)->get();
    }

    /**
     * 🔍 Récupère une entrée unique par sa valeur
     */
    public static function getByValue(string $value)
    {
        return self::where('value', $value)->first();
    }

}
