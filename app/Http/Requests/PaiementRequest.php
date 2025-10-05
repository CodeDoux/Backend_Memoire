<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaiementRequest extends FormRequest
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
            'commande_id' => 'required|exists:commandes,id',
            'mode_paiement' => 'required|in:en_ligne,à_la_livraison',
            'montant_paye' => 'required|numeric|min:0|max:10000000',
            'date_paiement' => 'required|date',
            'statut' => 'required|in:en_attente,payée,non_payée,échouée,remboursée'
            'numero_telephone' => 'nullable|string|max:20|regex:/^(\+221)?[0-9\s\-\(\)]{8,}$/',
            'operateur' => 'nullable|in:orange_money,free_money,wave,carte_bancaire',
            'reference_transaction' => 'nullable|string|max:100|unique:paiements,reference_transaction'
        ];

        // Pour la mise à jour, les règles peuvent être moins strictes
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['commande_id'] = 'sometimes|exists:commandes,id';
            $rules['mode_paiement'] = 'sometimes|in:en_ligne,à_la_livraison';
            $rules['montant_paye'] = 'sometimes|numeric|min:0|max:10000000';
            $rules['date_paiement'] = 'sometimes|date';
            $rules['statut'] = 'sometimes|in:en_attente,payée,non_payée,échouée,remboursée';

            $paiementId = $this->route('paiement');
            $rules['reference_transaction'] = 'nullable|string|max:100|unique:paiements,reference_transaction,' . $paiementId;
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'commande_id.required' => 'L\'ID de la commande est obligatoire',
            'commande_id.exists' => 'La commande spécifiée n\'existe pas',
            'mode_paiement.required' => 'Le mode de paiement est obligatoire',
            'mode_paiement.in' => 'Le mode de paiement doit être "en_ligne" ou "à_la_livraison"',
            'montant_payé.required' => 'Le montant payé est obligatoire',
            'montant_payé.numeric' => 'Le montant payé doit être un nombre',
            'montant_payé.min' => 'Le montant payé ne peut pas être négatif',
            'montant_payé.max' => 'Le montant payé ne peut pas dépasser 10,000,000 FCFA',
            'date_paiement.required' => 'La date de paiement est obligatoire',
            'statut.required' => 'Le statut du paiement est obligatoire',
            'statut.in' => 'Le statut du paiement n\'est pas valide',
            'numero_telephone.regex' => 'Le format du numéro de téléphone n\'est pas valide',
            'operateur.in' => 'L\'opérateur doit être Orange Money, Free Money, Wave ou Carte Bancaire',
            'reference_transaction.unique' => 'Cette référence de transaction existe déjà'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'numero_telephone' => $this->numero_telephone ? trim($this->numero_telephone) : null,
            'reference_transaction' => $this->reference_transaction ? trim($this->reference_transaction) : null
        ]);
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Si mode paiement en ligne, vérifier que numero_telephone et operateur sont fournis
            if ($this->mode_paiement === 'en_ligne') {
                if (!$this->numero_telephone) {
                    $validator->errors()->add('numero_telephone', 'Le numéro de téléphone est obligatoire pour le paiement en ligne');
                }
                if (!$this->operateur) {
                    $validator->errors()->add('operateur', 'L\'opérateur est obligatoire pour le paiement en ligne');
                }
            }

            // Vérifier que la commande n'a pas déjà un paiement (pour création uniquement)
            if ($this->isMethod('POST') && $this->commande_id) {
                $paiementExistant = \App\Models\Paiement::where('commande_id', $this->commande_id)->exists();
                if ($paiementExistant) {
                    $validator->errors()->add('commande_id', 'Cette commande a déjà un paiement associé');
                }
            }

            // Vérifier la cohérence montant payé vs commande
            if ($this->commande_id && $this->montant_payé) {
                $commande = \App\Models\Commande::find($this->commande_id);
                if ($commande && $this->montant_payé > $commande->montant_total * 1.1) { // Tolérance de 10%
                    $validator->errors()->add('montant_paye', 'Le montant payé ne peut pas dépasser significativement le total de la commande');
                }
            }
        });
    }
}
