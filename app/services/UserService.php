<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserService
{
    /**
     * Récupérer tous les utilisateurs
     */
    public function index()
    {
        return User::all();
    }

    /**
     * Créer un utilisateur
     */
    public function store(array $data)
    {
        // Vérifier unicité email
        if (User::where('email', $data['email'])->exists()) {
            throw ValidationException::withMessages([
                'email' => 'Cet email est déjà utilisé par un autre utilisateur.'
            ]);
        }

        // Hash du mot de passe
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user = User::create($data);

        return [
            "message" => "Utilisateur créé avec succès",
            "user" => $user
        ];
    }

    /**
     * Récupérer un utilisateur par ID
     */
    public function show($id)
    {
        return User::findOrFail($id);
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function update(array $data, $id)
    {
        $user = User::findOrFail($id);

        // Vérifier si l'email est déjà utilisé par un autre
        if (isset($data['email']) && $data['email'] !== $user->email) {
            if (User::where('email', $data['email'])->exists()) {
                throw ValidationException::withMessages([
                    'email' => 'Cet email est déjà utilisé par un autre utilisateur.'
                ]);
            }
        }

        // Hash du mot de passe si fourni
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return [
            "message" => "Utilisateur mis à jour avec succès",
            "user" => $user
        ];
    }

    /**
     * Supprimer un utilisateur
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return [
            "message" => "Utilisateur supprimé avec succès"
        ];
    }
}
