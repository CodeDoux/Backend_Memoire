<?php

namespace App\Services;

use App\Models\Administrateur;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminService
{
    /**
     * Récupérer tous les utilisateurs
     */
    public function index()
    {
        return Administrateur::with(['user'])->get();
    }

    public function getAllUsers()
    {
        return User::with(['producteur', 'administrateur'])->get();
    }
    /**
     * Créer un utilisateur
     */
    public function createAdmin(array $data)
    {
        try {
            // 1️⃣ Créer l'utilisateur lié
            $user = User::create([
                'nomComplet' => $data['nomComplet'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'tel' => $data['tel'],
                'role' => 'ADMIN',
            ]);

            // 2️⃣ Créer le profil administrateur
            $admin = Administrateur::create([
                'niveau' => $data['niveau'] ?? 1,
                'user_id' => $user->id,
            ]);

            return $admin->load('user');
        } catch (\Exception $e) {
            Log::error('Erreur création admin : ' . $e->getMessage());
            throw $e;
        }
    }


    /**
     * Récupérer un Admin par ID
     */

    public function show($id)
    {
        return Administrateur::with('user')->findOrFail($id);
    }

    /**
     * Mettre à jour un Admin
     */
    public function update(array $data, $id)
{
    $admin = Administrateur::with('user')->findOrFail($id);

    // Vérification de l'email si modifié
    if (isset($data['email']) && $data['email'] !== $admin->user->email) {
        if (User::where('email', $data['email'])->exists()) {
            throw ValidationException::withMessages([
                'email' => 'Cet email est déjà utilisé par un autre utilisateur.'
            ]);
        }
    }

    // Mise à jour des informations utilisateur
    $userData = [];

    if (isset($data['nomComplet'])) {
        $userData['nomComplet'] = $data['nomComplet'];
    }

    if (isset($data['email'])) {
        $userData['email'] = $data['email'];
    }

    if (isset($data['tel'])) {
        $userData['tel'] = $data['tel'];
    }

    if (isset($data['password']) && !empty($data['password'])) {
        $userData['password'] = Hash::make($data['password']);
    }

    if (!empty($userData)) {
        $admin->user->update($userData);
    }

    // Mise à jour du niveau de l'administrateur (dans sa propre table)
    if (isset($data['niveau'])) {
        $admin->niveau = $data['niveau'];
        $admin->save();
    }

    return [
        "message" => "Administrateur mis à jour avec succès",
        "admin" => $admin->load('user')
    ];
}

    /**
     * Supprimer un utilisateur
     */
    public function destroy($id)
    {
        $admin = Administrateur::with('user')->findOrFail($id);

    // Supprimer l'utilisateur lié (cascade)
    $admin->user()->delete();

    // Supprimer le profil admin
    $admin->delete();

    return [
        "message" => "Administrateur supprimé avec succès"
    ];
    }
}
