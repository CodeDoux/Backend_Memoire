<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Produit;
use App\Models\Commande;
use App\Models\User;
use App\Models\Livraison;
use App\Models\Promotion;
use App\Models\Categorie;
use App\Models\Avis;

class DashboardController extends Controller
{
    public function stats()
    {
        try {
            return response()->json([
                'orders' => Commande::count(),
                'users' => User::count(),
                'pendingProducts' => Produit::where('validationAdmin', 'EN_ATTENTE')->count(),
                'deliveries' => Livraison::where('statut', 'EN_COURS')->count(),
                'pendingPromos' => Promotion::where('estActif', false)->count(),
                'pendingReviews' => Avis::where('statut', 'EN_ATTENTE')->count(),
                'categories' => Categorie::count()
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
