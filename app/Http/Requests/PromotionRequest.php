<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PromotionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
   public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'reduction' => 'required|numeric|min:1|max:100',
            'dateDebut' => 'required|date|after_or_equal:today',
            'dateFin' => 'required|date|after:dateDebut',
            'active' => 'boolean',
            'produits' => 'sometimes|array',
            'produits.*' => 'exists:produits,id'
        ];

        // 👉 Si c’est une mise à jour, rendre les champs optionnels
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['nom'] = 'sometimes|string|max:255';
            $rules['description'] = 'nullable|string|max:1000';
            $rules['reduction'] = 'sometimes|numeric|min:1|max:100';
            $rules['dateDebut'] = 'sometimes|date|after_or_equal:today';
            $rules['dateFin'] = 'sometimes|date|after:dateDebut';
            $rules['active'] = 'sometimes|boolean';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom de la promotion est obligatoire.',
            'reduction.max' => 'La réduction ne peut pas dépasser 100%.',
            'dateDebut.after_or_equal' => 'La date de début ne peut pas être antérieure à aujourd\'hui.',
            'dateFin.after' => 'La date de fin doit être postérieure à la date de début.',
            'produits.*.exists' => 'Un ou plusieurs produits sélectionnés n’existent pas.',
        ];
    }
}
