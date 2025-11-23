<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\services\ClientService;
use App\Http\Requests\ClientRequest;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;

class ClientController extends Controller
{
    protected $clientService;

    public function __construct(ClientService $clientService)
    {
        $this->clientService = $clientService;
    }

    /**
     * Afficher la liste des clients
     */
    public function index()
    {
        $clients = $this->clientService->index();
        return response()->json($clients, 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Créer un nouveau client
     */
    public function store(ClientRequest $request)
    {
        $client = $this->clientService->store($request->validated());
        return response()->json($client, 201, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Afficher un client spécifique
     */
    public function show(string $id)
    {
        $client = $this->clientService->show($id);
        return response()->json($client, 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Mettre à jour un client
     */
    public function update(ClientRequest $request, string $id)
    {
        $client = $this->clientService->update($request->validated(), $id);

        return response()->json([
            "message" => "client mis à jour avec succès",
            "client" => $client
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Supprimer un client
     */
    public function destroy(string $id)
    {
        $this->clientService->destroy($id);
        return response()->json([
            "message" => "client supprimé avec succès"
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
