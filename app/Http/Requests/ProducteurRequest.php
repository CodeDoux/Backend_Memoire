<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
    return [
        'nomComplet' => 'required|string|min:3|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:5',
            'telephone' => 'required|string|regex:/^(77|78|76|70|75)[0-9]{7}$/',
        'entreprise'   => 'required|string|max:100',
        'description'  => 'required|string|max:500',
        'ninea'        => 'required|string|max:15|unique:producteurs,ninea', // unique pour éviter les doublons
        'emailPro'     => 'required|email|max:255|unique:producteurs,emailPro',
        'telPro'       => [
            'required',
            'string',
            'regex:/^(77|78|76|70|75)[0-9]{7}$/',
            'unique:producteurs,telPro'
        ],
        'user_id'      => 'required|integer|exists:users,id|unique:producteurs,user_id',
    ];
}
public function messages(): array
    {
        return [
            'entreprise.required' => 'Le nom de l\'entreprise est obligatoire.',
            'description.required' => 'La description est obligatoire.',
            
            'ninea.required' => 'Le NINEA est obligatoire.',
            'ninea.unique'   => 'Ce NINEA est déjà enregistré.',

            'emailPro.required' => 'L\'email professionnel est obligatoire.',
            'emailPro.email'    => 'Le format de l\'email est invalide.',
            'emailPro.unique'   => 'Cet email professionnel est déjà utilisé.',

            'telPro.required' => 'Le numéro de téléphone est obligatoire.',
            'telPro.regex'    => 'Le numéro doit être valide (ex: 77xxxxxxx ou +22177xxxxxxx).',
            'telPro.unique'   => 'Ce numéro de téléphone est déjà utilisé.',

            'user_id.required' => 'L\'utilisateur lié est obligatoire.',
            'user_id.exists'   => 'L\'utilisateur spécifié n\'existe pas.',
            'user_id.unique'   => 'Cet utilisateur est déjà lié à un producteur.',
        ];
    }

    /**
     * Pré-traiter les données avant validation
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'entreprise'  => trim($this->entreprise),
            'description' => trim($this->description),
            'ninea'       => strtoupper(trim($this->ninea)),
            'emailPro'    => strtolower(trim($this->emailPro)),
            'telPro'      => preg_replace('/\s+/', '', $this->telPro), // supprime espaces
        ]);
    }
}
