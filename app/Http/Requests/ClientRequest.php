<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClientRequest extends FormRequest
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
        
        // Si on est en mode PUT/PATCH, on récupère l'id de l'utilisateur lié au client
        $clientId = $this->route('client'); // ex: /clients/{client}
        $userId = null;

        if ($clientId && $this->method() !== 'POST') {
            $client = \App\Models\Client::with('user')->find($clientId);
            $userId = $client?->user?->id;
        }

        return [
            // ✅ Informations du compte utilisateur
            'nomComplet' => 'required|string|min:3|max:100',

            'email' => [
                'required',
                'email',
                // ignore l’utilisateur actuel en cas de mise à jour
                Rule::unique('users', 'email')->ignore($userId, 'id'),
            ],

            'password' => $this->isMethod('post')
                ? 'required|string|min:5'
                : 'nullable|string|min:5',

            'telephone' => [
                'required',
                'string',
                'regex:/^(77|78|76|70|75)[0-9]{7}$/',
            ],

            // ✅ Informations du client
            'adresse_livraison' => 'nullable|array',
            'adresse_facturation' => 'nullable|array',

            // Champs de l'adresse de livraison
            'adresse_livraison.rue' => 'nullable|string|max:255',
            'adresse_livraison.ville' => 'nullable|string|max:100',
            'adresse_livraison.pays' => 'nullable|string|max:100',

            // Champs de l'adresse de facturation
            'adresse_facturation.rue' => 'nullable|string|max:255',
            'adresse_facturation.ville' => 'nullable|string|max:100',
            'adresse_facturation.pays' => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'nomComplet.required' => 'Le nom complet est obligatoire.',
            'email.required' => 'L’adresse email est obligatoire.',
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            'telephone.regex' => 'Le numéro doit être un numéro valide au format sénégalais.',
            'password.min' => 'Le mot de passe doit comporter au moins 5 caractères.',
        ];
    }
}
