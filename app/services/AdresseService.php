<?php

namespace App\Services;

use App\Models\Adresse;

class AdresseService
{
    public function index()
    {
        $adresses = Adresse::all();
        return $adresses;
    }

    public function store(array $data)
    {
        $adresse = Adresse::create($data);
        return $adresse;
    }

    public function show($id)
    {
        $adresse = Adresse::with('user')->findOrFail($id);
        return $adresse;
    }

    public function update(array $data, $id)
    {
        $adresse = Adresse::findOrFail($id);
        $adresse->update($data);
        return $adresse->load('user');
    }

    public function destroy($id)
    {
        $adresse = Adresse::findOrFail($id);
        $adresse->delete();
        return ["message" => "Adresse supprimée avec succès"];
    }

    /**
     * Récupérer les adresses d'un utilisateur spécifique
     */
    public function getByUser($userId)
    {
        $adresses = Adresse::where('user_id', $userId)->get();
        return $adresses;
    }

    /**
     * Définir une adresse comme principale pour un utilisateur
     */
    public function setPrincipale($id, $userId)
    {
        // Retirer le statut principale de toutes les adresses de l'utilisateur
        Adresse::where('user_id', $userId)->update(['est_principale' => false]);
        
        // Définir la nouvelle adresse principale
        $adresse = Adresse::findOrFail($id);
        $adresse->update(['est_principale' => true]);
        
        return $adresse;
    }
}