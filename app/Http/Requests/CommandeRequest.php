<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommandeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Autoriser les clients authentifiés à passer commande
    }

    public function rules(): array
    {
        return [
            'produits' => 'required|array|min:1',
            'produits.*.produit_id' => 'required|integer|exists:produits,id',
            'produits.*.quantite' => 'required|numeric|min:0.001',
            'produits.*.prix' => 'required|numeric|min:0',
            'produits.*.promo_id' => 'nullable|integer|exists:promotions,id',

            'montant_total' => 'nullable|numeric|min:0',

            // Nouveau champ code promo
            'code_promo_id' => 'nullable|integer|exists:code_promos,id',

            'infos_livraison' => 'required|array',
            'infos_livraison.nomComplet' => 'required|string|max:255',
            'infos_livraison.telephone' => 'required|string|max:20|regex:/^(\+221)?[0-9\s\-\(\)]{8,}$/',
            'infos_livraison.adresse' => 'required|string|max:500',
            'infos_livraison.ville' => 'required|string|max:255',
            'infos_livraison.codePostal' => 'nullable|string|max:10',
            'infos_livraison.commentaires' => 'nullable|string|max:1000',
            'infos_livraison.fraisLivraison' => 'nullable|numeric|min:0|max:50000',

            'infos_paiement' => 'required|array',
            'infos_paiement.modePaiement' => 'required|in:livraison,enligne',
            'infos_paiement.numeroTelephone' => 'nullable|string|max:20|regex:/^(\+221)?[0-9\s\-\(\)]{8,}$/',
            'infos_paiement.operateur' => 'nullable|in:orange_money,free_money,wave',

            'statut' => 'sometimes|string|in:en_preparation,prete,en_livraison,livree,annulee'
        ];
    }

    public function messages(): array
    {
        return [
            'produits.required' => 'Au moins un produit doit être commandé',
            'produits.min' => 'Au moins un produit doit être commandé',
            'produits.*.produit_id.required' => 'L\'ID du produit est obligatoire',
            'produits.*.produit_id.exists' => 'Le produit sélectionné n\'existe pas dans le catalogue',
            'produits.*.quantite.required' => 'La quantité est obligatoire',
            'produits.*.quantite.min' => 'La quantité doit être d\'au moins 0.01',

            'montant_total.min' => 'Le montant total ne peut pas être négatif',

            'code_promo_id.exists' => 'Le code promo sélectionné n\'est pas valide',

            'infos_livraison.required' => 'Les informations de livraison sont obligatoires',
            'infos_livraison.nomComplet.required' => 'Le nom complet est obligatoire',
            'infos_livraison.telephone.required' => 'Le téléphone est obligatoire',
            'infos_livraison.telephone.regex' => 'Le numéro doit être valide et commencer par +221 ou être un numéro local',
            'infos_livraison.adresse.required' => 'L\'adresse est obligatoire',
            'infos_livraison.ville.required' => 'La ville est obligatoire',
            'infos_livraison.fraisLivraison.max' => 'Les frais de livraison ne peuvent pas dépasser 50 000 FCFA',

            'infos_paiement.required' => 'Les informations de paiement sont obligatoires',
            'infos_paiement.modePaiement.required' => 'Le mode de paiement est obligatoire',
            'infos_paiement.modePaiement.in' => 'Le mode de paiement doit être "livraison" ou "enligne"',
            'infos_paiement.numeroTelephone.regex' => 'Le format du numéro de téléphone n\'est pas valide',
            'infos_paiement.operateur.in' => 'L\'opérateur doit être Orange Money, Free Money ou Wave',
        ];
    }

    protected function prepareForValidation()
    {
        // Nettoyer infos livraison
        if ($this->has('infos_livraison')) {
            $infosLivraison = $this->infos_livraison;

            $infosLivraison['nomComplet'] = ucwords(strtolower(trim($infosLivraison['nomComplet'] ?? '')));
            $infosLivraison['telephone'] = preg_replace('/\D+/', '', $infosLivraison['telephone'] ?? '');
            $infosLivraison['adresse'] = trim($infosLivraison['adresse'] ?? '');
            $infosLivraison['ville'] = strtoupper(trim($infosLivraison['ville'] ?? ''));

            $this->merge(['infos_livraison' => $infosLivraison]);
        }

        // Nettoyer infos paiement
        if ($this->has('infos_paiement')) {
            $infosPaiement = $this->infos_paiement;

            if (!empty($infosPaiement['numeroTelephone'])) {
                $infosPaiement['numeroTelephone'] = preg_replace('/\D+/', '', $infosPaiement['numeroTelephone']);
            }
            if (!empty($infosPaiement['operateur'])) {
                $infosPaiement['operateur'] = strtolower(trim($infosPaiement['operateur']));
            }

            $this->merge(['infos_paiement' => $infosPaiement]);
        }
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->has('produits')) {
                // Charger tous les produits et promotions en une seule requête
                $produitIds = collect($this->produits)->pluck('produit_id');
                $produits = \App\Models\Produit::whereIn('id', $produitIds)->get()->keyBy('id');

                $promoIds = collect($this->produits)->pluck('promo_id')->filter();
                $promos = \App\Models\Promotion::whereIn('id', $promoIds)->get()->keyBy('id');

                foreach ($this->produits as $index => $produitData) {
                    $produit = $produits[$produitData['produit_id']] ?? null;

                    // Vérifier le stock
                    if ($produit && $produit->stock < $produitData['quantite']) {
                        $validator->errors()->add(
                            "produits.{$index}.quantite",
                            "Stock insuffisant pour {$produit->nom}. Stock disponible : {$produit->stock}"
                        );
                    }

                    // Vérifier la promotion
                    if (!empty($produitData['promo_id'])) {
                        $promotion = $promos[$produitData['promo_id']] ?? null;
                        if (!$promotion || $promotion->dateDebut > now() || $promotion->dateFin < now()) {
                            $validator->errors()->add(
                                "produits.{$index}.promo_id",
                                "La promotion sélectionnée n'est plus active"
                            );
                        }
                    }
                }
            }

            // Vérifier le code promo global
            if ($this->filled('code_promo_id')) {
                $codePromo = \App\Models\CodePromo::find($this->code_promo_id);
                if (!$codePromo || !$codePromo->estValide()) {
                    $validator->errors()->add('code_promo_id', 'Ce code promo n\'est pas valide ou a expiré.');
                }
            }

            // Paiement en ligne => numéro & opérateur obligatoires
            if ($this->has('infos_paiement') && $this->infos_paiement['modePaiement'] === 'enligne') {
                if (empty($this->infos_paiement['numeroTelephone'])) {
                    $validator->errors()->add('infos_paiement.numeroTelephone', 'Le numéro de téléphone est obligatoire pour le paiement en ligne');
                }
                if (empty($this->infos_paiement['operateur'])) {
                    $validator->errors()->add('infos_paiement.operateur', 'L\'opérateur est obligatoire pour le paiement en ligne');
                }
            }
        });
    }
}
