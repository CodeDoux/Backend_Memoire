<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        // Convertit "" en null pour éviter les problèmes avec unique
        if ($this->has('code')) {
            $this->merge([
                'code' => $this->input('code') ?: null,
            ]);
        }
    }

    public function rules(): array
    {
        $rules = [
            'nom'            => 'required|string|max:255',
            'description'    => 'nullable|string|max:1000',
            'reduction'      => 'required|numeric|min:1|max:100',
            'dateDebut'      => 'required|date|after_or_equal:today',
            'dateFin'        => 'required|date|after:dateDebut',
            'estActif'       => 'boolean',
            'seuilMinimum'   => 'nullable|numeric|between:0,999999.99',
            'utilisationMax' => 'nullable|integer|min:1',
            'code'           => 'nullable|string|max:20|unique:promotions,code' . ($this->route('promotion') ? ',' . $this->route('promotion') : ''),
            'typePromo'      => 'required|string|in:PRODUIT,COMMANDE',
            'produits'       => 'nullable|array',
            'produits.*'     => 'integer|exists:produits,id',
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $promotionId = $this->route('id');

            $rules['nom'] = 'sometimes|string|max:255';
            $rules['description'] = 'sometimes|nullable|string|max:1000';
            $rules['reduction'] = 'sometimes|numeric|min:1|max:100';
            $rules['dateDebut'] = 'sometimes|date|after_or_equal:today';
            $rules['dateFin'] = 'sometimes|date|after:dateDebut';
            $rules['estActif'] = 'sometimes|boolean';
            $rules['seuilMinimum'] = 'sometimes|nullable|numeric|between:0,999999.99';
            $rules['utilisationMax'] = 'sometimes|integer|min:1';
            $rules['typePromo'] = 'sometimes|string|in:PRODUIT,COMMANDE';
            $rules['produits'] = 'nullable|array';
            $rules['produits.*'] = 'integer|exists:produits,id';

            // Unique pour le code lors de la mise à jour
            $rules['code'] = 'nullable|string|max:20|unique:promotions,code,' . $promotionId;
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom de la promotion est obligatoire.',
            'reduction.required' => 'Le pourcentage de réduction est obligatoire.',
            'reduction.max' => 'La réduction ne peut pas dépasser 100%.',
            'dateDebut.after_or_equal' => 'La date de début doit être aujourd’hui ou plus tard.',
            'dateFin.after' => 'La date de fin doit être postérieure à la date de début.',
            'typePromo.required' => 'Le type de promotion est obligatoire.',
            'produits.*.exists' => 'Un ou plusieurs produits sélectionnés n’existent pas.',
        ];
    }
}
