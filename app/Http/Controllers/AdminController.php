<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AdminService;
use App\Models\Administrateur;

class AdminController extends Controller
{
    protected $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
        $this->middleware(['auth:sanctum', 'role:ADMIN']); // sécurité
    }
    public function index()
    {
        $admins = $this->adminService->index();
        $admins->each(function ($admin) {
        $admin->role_label = $admin->role_label; // Accessor déjà calculé
         });
        return response()->json($admins, 200, [], JSON_UNESCAPED_UNICODE);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nomComplet' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'tel' => 'required|string|max:15',
            'niveau' => 'nullable|integer|min:0|max:2',
        ]);

        try {
            $admin = $this->adminService->createAdmin($validated);
            return response()->json([
                'message' => 'Administrateur créé avec succès',
                'admin' => $admin
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erreur création admin : ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

     /**
     * Afficher un admin spécifique
     */
    public function show(string $id)
    {
        $admin = $this->adminService->show($id);
        return response()->json($admin, 200, [], JSON_UNESCAPED_UNICODE);
    }

     public function update(Request $request, string $id){
     // Récupérer l’administrateur pour obtenir l’ID de son user
        $admin = Administrateur::with('user')->findOrFail($id);
        $userId = $admin->user->id;

        // ✅ Validation avec exception sur l'email actuel et mot de passe optionnel
        $validated = $request->validate([
            'nomComplet' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $userId,
            'password' => 'nullable|string|min:6',
            'tel' => 'required|string|max:15',
            'niveau' => 'nullable|integer|min:0|max:2',
        ]);

        // Appel du service métier
        $result = $this->adminService->update($validated, $id);

        return response()->json([
            "message" => "Administrateur mis à jour avec succès",
            "admin" => $result
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function destroy(string $id)
    {
        $this->adminService->destroy($id);
        return response()->json([
            "message" => "admin supprimé avec succès"
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
    


}
