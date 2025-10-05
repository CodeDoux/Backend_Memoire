<?php

namespace App\services;

use App\Http\Requests\CategorieRequest;
use App\Models\Categorie;
use Illuminate\Http\Request;

class CategorieService
{

    public function index()
    {
        $categories = Categorie::all();
        return $categories;
    }

    public function store(array $request)
    {
        //Metier
        $categorie = Categorie::create($request);
        return $categorie;
    }


    public function show($id)
    {
        //Categorie::find($id);
        $categorie = Categorie::findOrFail($id);
        return $categorie;
    }


    public function update(array $request, $id)
    {
        $categorie = Categorie::findOrFail($id);
        $categorie->update($request);
        return $categorie;
    }


    public function destroy($id)
    {
        Categorie::destroy($id);
        return ["message" => "Catégorie supprimée avec succès"];
    }
}
