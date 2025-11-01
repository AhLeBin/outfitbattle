<?php
require_once __DIR__ . '/../includes/functions.php';

// Définir le header de réponse en JSON
header('Content-Type: application/json');

// Initialiser la réponse
$response = ['success' => false, 'message' => ''];

// 1. Validation des entrées (POST)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Méthode non autorisée.';
    echo json_encode($response);
    exit;
}

$email = $_POST['email'] ?? null;
$password = $_POST['password'] ?? null;

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Email invalide.';
    echo json_encode($response);
    exit;
}

if (empty($password) || strlen($password) < 6) {
    $response['message'] = 'Le mot de passe doit faire au moins 6 caractères.';
    echo json_encode($response);
    exit;
}

// 2. Traitement des données
$users_filepath = __DIR__ . '/../data/users.json';
$users = read_json_file($users_filepath);

// 3. Vérifier si l'email existe déjà
foreach ($users as $user) {
    if ($user['email'] === $email) {
        $response['message'] = 'Cet email est déjà utilisé.';
        echo json_encode($response);
        exit;
    }
}

// 4. Créer le nouvel utilisateur
$new_user = [
    'user_id' => 'u_' . uniqid(), // ID utilisateur unique
    'email' => $email,
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    'created_at' => date('c') // Format ISO 8601
];

// 5. Ajouter au tableau et sauvegarder
$users[] = $new_user;
$save_success = write_json_file($users_filepath, $users);

if ($save_success) {
    // 6. Créer le fichier de tenues vide pour cet utilisateur
    $outfit_filepath = __DIR__ . '/../data/outfits/' . $new_user['user_id'] . '.json';
    write_json_file($outfit_filepath, []); // Crée un tableau vide

    $response['success'] = true;
    $response['message'] = 'Compte créé avec succès !';
} else {
    $response['message'] = 'Erreur lors de la sauvegarde du compte.';
}

echo json_encode($response);