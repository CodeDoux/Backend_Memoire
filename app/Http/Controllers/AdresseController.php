<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdresseRequest;
use App\Services\AdresseService;
use Illuminate\Http\Request;

class AdresseController extends Controller
{
    protected $adresseService;

    public function __construct(AdresseService $adresseService)
    {
        $this->adresseService = $adresseService;
    }

    /**
     * Afficher la liste de toutes les adresses
     */
    public function index()
    {
        try {
            $adresses = $this->adresseService->index();
            return response()->json($adresses, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la récupération des adresses',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer une nouvelle adresse
     */
    public function store(AdresseRequest $request)
    {
        try {
            $adresse = $this->adresseService->store($request->validated());
            return response()->json([
                'message' => 'Adresse créée avec succès',
                'data' => $adresse
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la création de l\'adresse',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher une adresse spécifique
     */
    public function show($id)
    {
        try {
            $adresse = $this->adresseService->show($id);
            return response()->json($adresse, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Adresse non trouvée'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la récupération de l\'adresse',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour une adresse
     */
    public function update(AdresseRequest $request, $id)
    {
        try {
            $adresse = $this->adresseService->update($request->validated(), $id);
            return response()->json([
                'message' => 'Adresse mise à jour avec succès',
                'data' => $adresse
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Adresse non trouvée'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la mise à jour de l\'adresse',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une adresse
     */
    public function destroy($id)
    {
        try {
            $result = $this->adresseService->destroy($id);
            return response()->json($result, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Adresse non trouvée'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la suppression de l\'adresse',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les adresses d'un utilisateur
     */
    public function getByUser($userId)
    {
        try {
            $adresses = $this->adresseService->getByUser($userId);
            return response()->json($adresses, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la récupération des adresses de l\'utilisateur',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Définir une adresse comme principale
     */
    public function setPrincipale(Request $request, $id)
    {
        try {
            $userId = $request->user()->id; // ou $request->input('user_id')
            $adresse = $this->adresseService->setPrincipale($id, $userId);
            return response()->json([
                'message' => 'Adresse définie comme principale',
                'data' => $adresse
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Adresse non trouvée'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la définition de l\'adresse principale',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}