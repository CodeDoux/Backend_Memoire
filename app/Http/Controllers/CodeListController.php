<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\services\CodeListService;
use App\Http\Requests\CodeListRequest;
use App\Models\CodeList;

class CodeListController extends Controller
{
    protected $codelistService;

    public function __construct(CodeListService $codelistService)
        {
            $this->codelistService = $codelistService;
        }
    public function index()
    {
        $codelist=$this->codelistService->index();
        return response()->json($codelist,200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CodeListRequest $request)
    {
        $codelist = $this->codelistService->store($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'codelist créée avec succès',
            'data' => $codelist
        ], 201, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
         $codelist = $this->codelistService->show($id);
        return response()->json($codelist,200,[], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CodeListRequest $request, string $id)
    {
         $codelist= $this->codelistService->update($request->validated(), $id);

        return response()->json([
            "message" => "codelist mise à jour",
            "codelist" => $codelist
        ],status: 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->codelistService->destroy($id);
        return response()->json([
            "message" => "codelist supprimée avec succès"
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function getByType($type)
{
    $codes = CodeList::getByType($type);

    if ($codes->isEmpty()) {
        return response()->json([
            'message' => "Aucune valeur trouvée pour le type : $type"
        ], 404);
    }

    return response()->json($codes, 200, [], JSON_UNESCAPED_UNICODE);
}
}
