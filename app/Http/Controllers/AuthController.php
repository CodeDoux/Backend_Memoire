<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\services\UserService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
       protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    // Liste des utilisateurs (réservée aux admins)
    public function index()
    {
        $users = $this->userService->index();
        return response()->json($users, 200);
    }

    // Création d'un utilisateur par un admin
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nomComplet' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:5',
            'role' => 'in:ADMIN,PRO,CLIENT',
            'tel'=>'nullable|string|max:20|regex:/^(\+221)?[0-9\s\-\(\)]{8,}$/',
        ]);

        $user = User::create([
            'nomComplet' => $validated['nomComplet'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role' => strtoupper($request->role ?? 'CLIENT'),
            'tel'=>$validated['tel'],
        ]);

        return response()->json($user, 201, [], JSON_UNESCAPED_UNICODE);
    }

    // Inscription par l'utilisateur
    public function register(Request $request)
    {
        $validated = $request->validate([
            'nomComplet' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:5',
            'role' => 'in:ADMIN,PRO,CLIENT',
            'tel'=>'nullable|string|max:20|regex:/^(\+221)?[0-9\s\-\(\)]{8,}$/',
        ]);

        $user = User::create([
            'nomComplet' => $validated['nomComplet'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role' => strtoupper($request->role ?? 'CLIENT'),
            'tel'=>$validated['tel'],
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    // Connexion
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Les informations sont incorrectes.']
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    // Déconnexion
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Déconnecté avec succès'
        ], 200);
    }

    public function show(string $id)
    {
        $user = $this->userService->show($id);
        return response()->json($user, 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'nomComplet' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,'.$id,
            'password' => 'sometimes|string|min:5',
            'role' => 'in:ADMIN,EMPLOYE,CLIENT',
            'tel'=>'sometimes|string|max:20|regex:/^(\+221)?[0-9\s\-\(\)]{8,}$/',
        ]);

        $user = $this->userService->update($validated, $id);

        return response()->json([
            "message" => "Utilisateur mis à jour avec succès",
            "user" => $user
        ], 200);
    }

    public function destroy(string $id)
    {
        $this->userService->destroy($id);

        return response()->json([
            "message" => "Utilisateur supprimé avec succès"
        ], 200);
    }
}
