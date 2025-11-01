<?php
// On vérifie que l'utilisateur est connecté avant tout
require_once __DIR__ . '/../includes/auth_guard.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];
$user_id = $_SESSION['user_id'];

// --- 1. Validation de la requête ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Méthode non autorisée.';
    echo json_encode($response); exit;
}

$outfit_id = $_POST['outfit_id'] ?? null;
$item_type = $_POST['item_type'] ?? null; // 'top', 'bottom', 'shoes'
$price = $_POST['price'] ?? null;
$photo = $_FILES['photo'] ?? null;

// Vérifications basiques
if (!$outfit_id || !$item_type || !in_array($item_type, ['top', 'bottom', 'shoes'])) {
    $response['message'] = 'Données de formulaire invalides (ID ou type).';
    echo json_encode($response); exit;
}
if ($price === null || !is_numeric($price)) {
    $response['message'] = 'Le prix (Sans livraison) est obligatoire et doit être un nombre.';
    echo json_encode($response); exit;
}
if ($photo === null || $photo['error'] !== UPLOAD_ERR_OK) {
    // Note: Si l'utilisateur modifie juste le prix sans changer la photo, il faudra adapter cette logique.
    // Pour l'instant, on part du principe que la photo est toujours (re)fournie.
    $response['message'] = 'La photo est obligatoire. Erreur: ' . ($photo['error'] ?? 'Inconnue');
    echo json_encode($response); exit;
}

// --- 2. Traitement de l'upload de la photo ---

// Valider le type de fichier
$allowed_types = ['image/jpeg', 'image/png'];
$file_type = mime_content_type($photo['tmp_name']);
if (!in_array($file_type, $allowed_types)) {
    $response['message'] = 'Format de fichier non autorisé (JPEG ou PNG requis).';
    echo json_encode($response); exit;
}

// Créer le dossier de l'utilisateur s'il n'existe pas
$upload_dir = __DIR__ . '/../uploads/' . $user_id . '/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Générer un nom de fichier unique
$extension = pathinfo($photo['name'], PATHINFO_EXTENSION);
$filename = $item_type . '_' . $outfit_id . '_' . uniqid() . '.' . $extension;
$upload_path = $upload_dir . $filename;
$public_url = '/uploads/' . $user_id . '/' . $filename; // URL publique

// --- 3. Mise à jour du JSON ---

$outfits_filepath = __DIR__ . '/../data/outfits/' . $user_id . '.json';
// Note: read_json_file gère le verrouillage en lecture (partagé)
$outfits = read_json_file($outfits_filepath);

$outfit_found_key = null;
$old_photo_path = null;

// Trouver la tenue
foreach ($outfits as $key => $outfit) {
    if ($outfit['outfit_id'] === $outfit_id) {
        $outfit_found_key = $key;
        // Vérifier s'il y a une ancienne photo à supprimer
        if (isset($outfit[$item_type]) && !empty($outfit[$item_type]['photo_url'])) {
            // Convertir l'URL publique en chemin de serveur
            $old_photo_path = str_replace('/uploads/', __DIR__ . '/../uploads/', $outfit[$item_type]['photo_url']);
        }
        break;
    }
}

if ($outfit_found_key === null) {
    $response['message'] = 'Tenue non trouvée.';
    echo json_encode($response); exit;
}

// --- 4. Sauvegarder (Fichier & JSON) ---

// d'abord, on déplace le nouveau fichier. Si ça échoue, on arrête tout.
if (!move_uploaded_file($photo['tmp_name'], $upload_path)) {
    $response['message'] = 'Erreur lors de la sauvegarde de l\'image.';
    echo json_encode($response); exit;
}

// Si le déplacement réussit, on supprime l'ancienne photo
if ($old_photo_path && file_exists($old_photo_path)) {
    @unlink($old_photo_path); // @ pour ignorer les erreurs si la suppression échoue
}

// Créer le nouvel objet "item"
$new_item = [
    'item_id' => 'i_' . uniqid(),
    'brand' => $_POST['brand'] ?? 'N/A',
    'model' => $_POST['model'] ?? 'N/A',
    'price' => (float)$price,
    'delivery_price' => (float)($_POST['delivery_price'] ?? 0),
    'delivery_date' => $_POST['delivery_date'] ?? null,
    'photo_url' => $public_url
];

// Mettre à jour le tableau $outfits
$outfits[$outfit_found_key][$item_type] = $new_item;
$outfits[$outfit_found_key]['last_modified'] = date('c');

// Écrire le fichier JSON (avec verrouillage exclusif)
if (write_json_file($outfits_filepath, $outfits)) {
    $response['success'] = true;
    $response['message'] = 'Article sauvegardé !';
    $response['item'] = $new_item; // Renvoyer le nouvel item au JS
} else {
    $response['message'] = 'Erreur lors de la sauvegarde des données.';
    // Si la sauvegarde JSON échoue, on devrait supprimer la photo qu'on vient d'uploader
    @unlink($upload_path);
}

echo json_encode($response);