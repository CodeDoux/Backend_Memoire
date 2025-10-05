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
            'stock' => 'required|numeric|min:0',
            'prix' => 'required|numeric|between:0,999999.99',
            'saison' => 'required|string|max:100',
            'note' => 'required|integer|min:1|max:5',
            'categorie_id' => 'required|integer|exists:categories,id',
            'producteur_id' => 'required|integer|exists:producteurs,id',
            //  tableau d’images
           'images' => 'required|array|min:1',
           'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048'
        ];
    }
}
