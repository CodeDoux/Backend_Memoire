<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProduitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nom' => 'required|string|max:100',
            'description' => 'required|string|max:500',
            'poids' => 'required|string|max:10',
            'stock' => 'required|numeric|min:10',
            'prix' => 'required|numeric|between:0,999999.99',
            'saison' => 'required|string|max:100',
            'note' => 'required|integer',
            'seuilAlerteStock' => 'required|integer|min:5',
            'statut' => 'required|in:DISPONIBLE,EN_RUPTURE',
            'validationAdmin'=>'required|in:EN_ATTENTE,VALIDE,REFUSE',
            'categorie_id' => 'required|integer|exists:categories,id',
            'producteur_id' => 'required|integer|exists:producteurs,id',
            //  tableau d’images
           'images' => 'required|array|min:1',
           'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048'
        ];
        // 👉 Si c’est une mise à jour, rendre les champs optionnels
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['email'] ='sometimes|string|max:100';
            $rules['description'] ='sometimes|string|max:500';
            $rules['poids'] ='sometimes|string|max:10';
            $rules['stock'] ='sometimes|numeric|min:10';
            $rules['prix'] ='sometimes|numeric|between:0,999999.99';
            $rules['saison'] ='sometimes|string|max:100';
            $rules['note'] ='sometimes|integer';
            $rules['seuilAlerteStock'] ='sometimes|integer|min:5';
            $rules['statut'] ='sometimes|in:DISPONIBLE,EN_RUPTURE';
            $rules['categorie_id'] ='sometimes|integer|exists:categories,id';
        }
    }
}
