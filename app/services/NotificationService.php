<?php

namespace App\services;

use App\Http\Requests\NotificationRequest;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotifiationService
{

    public function index()
    {
        $messages = Notification::with(['client:id,nomComplet,email', 'employe:id,nomComplet,email'])
        ->orderBy('created_at', 'asc')
        ->get();
         return response()->json($messages);
    }

    public function store(array $request)
    {
        $message = Notification::create($request);
        return $message;
    }


    public function show($id)
    {
        //Categorie::find($id);
        $message = Notification::findOrFail($id);
        return $categorie;
    }


    public function update(array $request, $id)
    {
        $message = Notification::findOrFail($id);
        $message->update($request);
        return $message;
    }


    public function destroy($id)
    {
        Notification::destroy($id);
    }
}
