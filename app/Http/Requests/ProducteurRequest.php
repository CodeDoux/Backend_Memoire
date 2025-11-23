<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProducteurRequest extends FormRequest
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
        $producteurId = $this->route('id'); // utile pour les updates (PUT/PATCH)

        return [
            // Informations du compte utilisateur
            'nomComplet' => 'required|string|min:3|max:100',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($producteurId, 'id'),
            ],
            'password' => $this->isMethod('post') ? 'required|string|min:5' : 'nullable|string|min:5',
            'telephone' => [
                'required',
                'string',
                'regex:/^(77|78|76|70|75)[0-9]{7}$/',
            ],

            // Informations du producteur
            'entreprise' => 'required|string|max:100',
            'description' => 'required|string|max:500',
            'ninea' => [
                'required',
                'string',
                'max:15',
                Rule::unique('producteurs', 'ninea')->ignore($producteurId, 'id'),
            ],
            'emailPro' => [
                'required',
                'email',
                'max:255',
                Rule::unique('producteurs', 'emailPro')->ignore($producteurId, 'id'),
            ],
            'telPro' => [
                'required',
                'string',
                'regex:/^(77|78|76|70|75)[0-9]{7}$/',
                Rule::unique('producteurs', 'telPro')->ignore($producteurId, 'id'),
            ],
        ];

        // 👉 Si c’est une mise à jour, rendre les champs optionnels
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['nomComplet'] = [
                'sometimes',
                'email',
                Rule::unique('users', 'email')->ignore($producteurId, 'id'),
            ];
            $rules['email'] ='sometimes|email|unique:users,email,'.$id;
            $rules['password'] = 'sometimes|string|min:5';
            $rules['telephone'] = [
                'sometimes',
                'string',
                'regex:/^(77|78|76|70|75)[0-9]{7}$/',
            ];
            $rules['entreprise'] = 'sometimes|string|max:100';
            $rules['description'] = 'sometimes|string|max:500';
            $rules['ninea'] = [
                'sometimes',
                'string',
                'max:15',
                Rule::unique('producteurs', 'ninea')->ignore($producteurId, 'id'),
            ];
            $rules['emailPro'] = [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('producteurs', 'emailPro')->ignore($producteurId, 'id'),
            ];
            $rules['telPro'] = [
                'sometimes',
                'string',
                'regex:/^(77|78|76|70|75)[0-9]{7}$/',
                Rule::unique('producteurs', 'telPro')->ignore($producteurId, 'id'),
            ];
        }
    }

    public function messages(): array
    {
        return [
            'nomComplet.required' => 'Le nom complet est obligatoire.',
            'email.required' => 'L’adresse email est obligatoire.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'password.required' => 'Le mot de passe est obligatoire lors de la création.',
            'telephone.regex' => 'Le téléphone doit commencer par 77, 78, 76, 70 ou 75.',

            'entreprise.required' => 'Le nom de l’entreprise est obligatoire.',
            'description.required' => 'La description est obligatoire.',
            'ninea.required' => 'Le NINEA est obligatoire.',
            'ninea.unique' => 'Ce NINEA est déjà enregistré.',
            'emailPro.unique' => 'Cet email professionnel est déjà utilisé.',
            'telPro.unique' => 'Ce numéro professionnel est déjà utilisé.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'entreprise' => trim($this->entreprise),
            'description' => trim($this->description),
            'ninea' => strtoupper(trim($this->ninea)),
            'emailPro' => strtolower(trim($this->emailPro)),
            'telPro' => preg_replace('/\s+/', '', $this->telPro), // retire les espaces
        ]);
    }
}
