<?php

namespace App\services;

use App\Http\Requests\CodeListRequest;
use App\Models\CodeList;
use Illuminate\Http\Request;

class CodeListService
{

    public function index()
    {
        $codelist = CodeList::all();
        return $codelist;
    }

    public function store(array $request)
    {
        //Metier
        $codelist = CodeList::create($request);
        return $codelist;
    }


    public function show($id)
    {
        //codelist::find($id);
        $codelist = CodeList::findOrFail($id);
        return $codelist;
    }


    public function update(array $request, $id)
    {
        $codelist = CodeList::findOrFail($id);
        $codelist->update($request);
        return $codelist;
    }


    public function destroy($id)
    {
        CodeList::destroy($id);
        return ["message" => "codelist supprimée avec succès"];
    }
}
