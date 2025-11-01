<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

// 1. Validation
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Méthode non autorisée.';
    echo json_encode($response);
    exit;
}

$email = $_POST['email'] ?? null;
$password = $_POST['password'] ?? null;

if (empty($email) || empty($password)) {
    $response['message'] = 'Email et mot de passe requis.';
    echo json_encode($response);
    exit;
}

// 2. Lecture des utilisateurs
$users_filepath = __DIR__ . '/../data/users.json';
$users = read_json_file($users_filepath);

// 3. Trouver l'utilisateur
$found_user = null;
foreach ($users as $user) {
    if ($user['email'] === $email) {
        $found_user = $user;
        break;
    }
}

if ($found_user === null) {
    $response['message'] = 'Email ou mot de passe incorrect.';
    echo json_encode($response);
    exit;
}

// 4. Vérifier le mot de passe
if (password_verify($password, $found_user['password_hash'])) {
    // 5. Succès ! Démarrer la session
    $_SESSION['user_id'] = $found_user['user_id'];
    $_SESSION['user_email'] = $found_user['email'];

    $response['success'] = true;
    $response['message'] = 'Connexion réussie. Redirection...';
} else {
    $response['message'] = 'Email ou mot de passe incorrect.';
}

echo json_encode($response);