<?php

namespace App\Services;

use App\Models\Produit;
use App\Models\Categorie;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class ProduitService
{
    /**
     * Récupérer tous les produits avec leur catégorie
     */
    public function index()
    {
        return Produit::with(['categorie', 'producteur', 'images'])->get();
    }

    /**
     * Créer un produit
     */
   public function store(array $data)
{
    return DB::transaction(function () use ($data) {
        try {
            // 1️⃣ Créer le produit
            $produit = Produit::create([
                'nom' => $data['nom'],
                'description' => $data['description'],
                'stock' => $data['stock'],
                'prix' => $data['prix'],
                'poids' => $data['poids'] ?? null,
                'saison' => $data['saison']?? null,
                'note' => $data['note'],
                'seuilAlerteStock' => $data['seuilAlerteStock'],
                'categorie_id' => $data['categorie_id'],
                'producteur_id' => $data['producteur_id'],
                'statut' => $data['statut'] ?? 'DISPONIBLE',
                'validationAdmin' => $data['validationAdmin'] ?? 'EN_ATTENTE',
                'dateAjout' => now(),
            ]);

            // 2️⃣ Sauvegarder les images si elles existent
            if (!empty($data['images']) && is_array($data['images'])) {
                foreach ($data['images'] as $index => $imageFile) {
                    if (!$imageFile->isValid()) {
                        throw new \Exception("Erreur lors du téléchargement de l'image.");
                    }

                    // Sauvegarde du fichier dans storage/app/public/produits
                    $path = $imageFile->store('produits', 'public');

                    if (!$path) {
                        throw new \Exception("Impossible de sauvegarder l'image.");
                    }

                    // 3️⃣ Créer l’enregistrement image lié au produit
                    $produit->images()->create([
                        'chemin' => $path,
                        'isPrimary' => $index===0, // ✅ corrige le nom de la colonne
                        'dateCreation' => now(),
                        'altText' => $produit->nom . " image " . ($index + 1),
                        'producteur_id'=>$produit['id'],
                    ]);
                }
            }

            // 4️⃣ Retourner le produit avec ses relations
            return $produit->load('categorie', 'images');

        } catch (\Exception $e) {
            // 🧨 En cas d'erreur, rollback et suppression fichiers
            DB::rollBack();

            if (isset($path) && \Storage::disk('public')->exists($path)) {
                \Storage::disk('public')->delete($path);
            }

            throw $e;
        }
    });
}

    /**
     * Afficher un produit par ID
     */
    public function show($id)
{
    $produit = Produit::with(['categorie', 'images'])->findOrFail($id);

    return $produit;
}
    /**
     * Mettre à jour un produit
     */
    public function update(array $data, $id)
{
    return DB::transaction(function () use ($data, $id) {
        try {
            $produit = Produit::findOrFail($id);

            // 1. Mise à jour des infos produit
            $produit->update($data);

            // 2. Suppression d'images si demandé
            if (!empty($data['images_a_supprimer'])) {
                foreach ($data['images_a_supprimer'] as $imageId) {
                    $image = $produit->images()->find($imageId);
                    if ($image) {
                        // Supprimer le fichier du storage
                        if (\Storage::disk('public')->exists($image->chemin)) {
                            \Storage::disk('public')->delete($image->chemin);
                        }
                        $image->delete();
                    }
                }
            }

            // 3. Ajout de nouvelles images
            if (isset($data['images'])) {
                foreach ($data['images'] as $index => $imageFile) {
                    if (!$imageFile->isValid()) {
                        throw new \Exception("Erreur lors du téléchargement de l'image.");
                    }

                    $path = $imageFile->store('produits', 'public');
                    if (!$path) {
                        throw new \Exception("Impossible de sauvegarder l'image.");
                    }

                    $produit->images()->create([
                        'chemin' => $path,
                        'isPrimary' => $index===0, 
                        'dateCreation' => now(),
                        'altText' => $produit->nom . " image " . ($index + 1),
                        'producteur_id'=>$produit['id'],
                    ]);
                }
            }

            // 4. Changer l’image principale
            if (!empty($data['image_principale_id'])) {
                $produit->images()->update(['is_primary' => false]);
                $imagePrincipale = $produit->images()->find($data['image_principale_id']);
                if ($imagePrincipale) {
                    $imagePrincipale->update(['is_primary' => true]);
                }
            }

            return $produit->load('categorie', 'images');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    });
}

    /**
     * Supprimer un produit
     */
    public function destroy($id)
    {
        $produit = Produit::with('images')->findOrFail($id);

    // Supprimer les fichiers images du storage
    foreach ($produit->images as $image) {
        if (\Storage::disk('public')->exists($image->chemin)) {
            \Storage::disk('public')->delete($image->chemin);
        }
    }

    // Supprimer le produit (ce qui supprime aussi les images via ON DELETE CASCADE si configuré)
    $produit->delete();

    return [
        "message" => "Produit et ses images supprimés avec succès"
    ];
    }
}
