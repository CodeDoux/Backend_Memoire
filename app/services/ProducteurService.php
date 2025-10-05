<?php

namespace App\Services;

use App\Models\Producteur;

class ProducteurService
{
    public function index()
    {
        // Charger aussi la relation avec l'utilisateur si nécessaire
        return Producteur::with('utilisateur')->get();
    }

    public function store(array $data)
    {
        return DB::transaction(function () use ($data) {
        // 1. Créer l'utilisateur
        $user = User::create([
            'nomComplet' => $data['nomComplet'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'role' => 'PRO',
        ]);

        // 2. Créer le producteur associé
        $producteur = Producteur::create([
            'user_id' => $user->id,
            'adresse' => $data['adresse'] ?? null,
            'telephone' => $data['telephone'] ?? null,
            'specialite' => $data['specialite'] ?? null,
            'experience' => $data['experience'] ?? null,
        ]);

        return $producteur->load('utilisateur');
    });
    }

    public function show($id)
    {
        return Producteur::with('utilisateur')->findOrFail($id);
    }

    public function update(array $data, $id)
    {
        $producteur = Producteur::findOrFail($id);
        $producteur->update($data);
        return $producteur->load('utilisateur');
    }

    public function destroy($id)
    {
        $producteur = Producteur::findOrFail($id);
        $producteur->delete();

        return ["message" => "Producteur supprimé avec succès"];
    }
}