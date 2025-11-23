<?php

namespace App\Services;

use App\Models\ZoneLivraison;

class ZoneLivraisonService
{
    public function index()
    {
        $zones = ZoneLivraison::all();
        return $zones;
    }

    public function store(array $data)
    {
        $zone = ZoneLivraison::create($data);
        return $zone;
    }

    public function show($id)
    {
        $zone = ZoneLivraison::findOrFail($id);
        return $zone;
    }

    public function update(array $data, $id)
    {
        $zone = ZoneLivraison::findOrFail($id);
        $zone->update($data);
        return $zone;
    }

    public function destroy($id)
    {
        $zone = ZoneLivraison::findOrFail($id);
        $zone->delete();
        return ["message" => "Zone de livraison supprimée avec succès"];
    }

    /**
     * Récupérer les zones actives
     */
    public function getZonesActives()
    {
        $zones = ZoneLivraison::where('actif', true)->get();
        return $zones;
    }

    /**
     * Rechercher une zone par nom
     */
    public function findByNom($nom)
    {
        $zone = ZoneLivraison::where('nom', 'ILIKE', "%{$nom}%")->get();
        return $zone;
    }

    /**
     * Calculer les frais de livraison pour une zone
     */
    public function getFraisLivraison($zoneId)
    {
        $zone = ZoneLivraison::findOrFail($zoneId);
        return $zone->fraisLivraison;
    }
}