<?php
namespace App\Services;

use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClientService
{
    public function index()
    {
        // Charger aussi la relation avec l'utilisateur si nécessaire
        return Client::with('user')->get();
    }

    public function store(array $data)
    {
        return DB::transaction(function () use ($data) {
            try {
                // ✅ 1. Créer l’utilisateur associé
                $user = User::create([
                    'nomComplet' => $request->nomComplet,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'tel' => $request->telephone,
                    'role' => 'CLIENT', // ou selon ta logique
                ]);

                // ✅ 2. Créer les adresses (livraison et facturation)
                $adresseLivraison = null;
                $adresseFacturation = null;

                if ($request->has('adresseLivraison')) {
                    $adresseLivraison = Adresse::create([
                        'rue' => $request->input('adresseLivraison.rue'),
                        'ville' => $request->input('adresseLivraison.ville'),
                        'quartier' => $request->input('adresseLivraison.quartier'),
                        'codePostal' => $request->input('adresseLivraison.codePostal'),
                    ]);
                }

                if ($request->has('adresseFacturation')) {
                    $adresseFacturation = Adresse::create([
                        'rue' => $request->input('adresseFacturation.rue'),
                        'ville' => $request->input('adresseFacturation.ville'),
                        'quartier' => $request->input('adresseFacturation.quartier'),
                        'codePostal' => $request->input('adresseFacturation.codePostal'),
                    ]);
                }

                // ✅ 3. Créer le client
                $client = Client::create([
                    'user_id' => $user->id,
                    'adresseLivraison_id' => $adresseLivraison?->id,
                    'adresseFacturation_id' => $adresseFacturation?->id,
                ]);

                // ✅ 4. Retourner la réponse JSON complète
                return response()->json([
                    'message' => 'Client créé avec succès ✅',
                    'client' => $client->load(['user', 'adresseLivraison', 'adresseFacturation']),
                ], 201, [], JSON_UNESCAPED_UNICODE);
            } catch (\Throwable $e) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Erreur lors de la création du client ❌',
                    'error' => $e->getMessage(),
                ], 500);
            }
        });
    }
    

    public function show($id)
    {
         $client = Client::with(['utilisateur', 'adresseLivraison', 'adresseFacturation'])->find($id);

        if (!$client) {
            return response()->json(['message' => 'Client introuvable ❌'], 404);
        }

        return response()->json($client, 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function update(array $data, $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $client = Client::with(['utilisateur', 'adresseLivraison', 'adresseFacturation'])->find($id);

            if (!$client) {
                return response()->json(['message' => 'Client introuvable ❌'], 404);
            }

            try {
                // ✅ Mettre à jour l'utilisateur associé
                $userData = [
                    'nomComplet' => $request->nomComplet ?? $client->user->nomComplet,
                    'email' => $request->email ?? $client->user->email,
                    'tel' => $request->telephone ?? $client->user->tel,
                ];

                if (!empty($request->password)) {
                    $userData['password'] = Hash::make($request->password);
                }

                $client->user->update($userData);

                // ✅ Mettre à jour ou créer l’adresse de livraison
                if ($request->filled('adresse_livraison')) {
                    if ($client->adresseLivraison) {
                        $client->adresseLivraison->update($request->adresse_livraison);
                    } else {
                        $adresseLivraison = Adresse::create($request->adresse_livraison);
                        $client->adresseLivraison_id = $adresseLivraison->id;
                    }
                }

                // ✅ Mettre à jour ou créer l’adresse de facturation
                if ($request->filled('adresse_facturation')) {
                    if ($client->adresseFacturation) {
                        $client->adresseFacturation->update($request->adresse_facturation);
                    } else {
                        $adresseFacturation = Adresse::create($request->adresse_facturation);
                        $client->adresseFacturation_id = $adresseFacturation->id;
                    }
                }

                // ✅ Sauvegarder le client
                $client->save();

                return response()->json([
                    'message' => 'Client mis à jour avec succès ✅',
                    'client' => $client->load(['user', 'adresseLivraison', 'adresseFacturation']),
                ], 200, [], JSON_UNESCAPED_UNICODE);
            } catch (\Throwable $e) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Erreur lors de la mise à jour du client ❌',
                    'error' => $e->getMessage(),
                ], 500);
            }
        });
    }

    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $client = Client::with(['user', 'adresseLivraison', 'adresseFacturation'])->find($id);

            if (!$client) {
                return response()->json(['message' => 'Client introuvable ❌'], 404);
            }

            try {
                // Supprimer les adresses associées si elles existent
                if ($client->adresseLivraison) {
                    $client->adresseLivraison->delete();
                }

                if ($client->adresseFacturation) {
                    $client->adresseFacturation->delete();
                }

                // Supprimer l’utilisateur lié
                $client->user->delete();

                // Supprimer le client lui-même
                $client->delete();

                return response()->json([
                    'message' => 'Client supprimé avec succès 🗑️',
                ], 200, [], JSON_UNESCAPED_UNICODE);
            } catch (\Throwable $e) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Erreur lors de la suppression du client ❌',
                    'error' => $e->getMessage(),
                ], 500);
            }
        });
     
}
}