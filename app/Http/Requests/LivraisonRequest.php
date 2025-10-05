<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LivraisonRequest extends FormRequest
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
        $rules = [
            'commande_id' => 'required|exists:commandes,id',
                'adresse_livraison' => 'required|string|max:500',
                'date_livraison' => 'required|date|after:now',
                'statut' => 'required|in:non_livrée,en_cours,livrée,annulée',
                'zone_livraison_id' => 'required|exists:zone_livraison,id'
        ]
        // 👉 Si c’est une mise à jour, rendre les champs optionnels
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['nom'] = 'sometimes|string|max:255';
            $rules['adresse_livraison'] = 'sometimes|string|max:500';
            $rules['date_livraison'] = 'sometimes|date|after_or_equal:today';
            $rules['statut'] = 'sometimes|string|max:500';
            $rules['commande_id'] = 'sometimes|exists:commandes,id';
            $rules['zone_livraison_id'] = 'sometimes|exists:zone_livraison,id';
        }

        return $rules;
    }
}
