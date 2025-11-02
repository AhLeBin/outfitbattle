<?php
require_once __DIR__ . '/../includes/functions.php'; // Utilise les fonctions d'OutfitBattle

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

// 1. Validation de base
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Méthode non autorisée.';
    echo json_encode($response); exit;
}

$action = $_POST['action'] ?? null; // 'login' ou 'register'
$username = $_POST['username'] ?? null;
$password = $_POST['password'] ?? null;

if (empty($username) || empty($password)) {
    $response['message'] = 'Nom d\'utilisateur et mot de passe requis.';
    echo json_encode($response); exit;
}

// 2. Lecture de la base de données (version OutfitBattle)
$users_filepath = __DIR__ . '/../data/users.json';
$users = read_json_file($users_filepath, []);

// 3. Trouver l'utilisateur par nom d'utilisateur
$found_user = null;
foreach ($users as $user) {
    // Utilise strcasecmp pour une comparaison insensible à la casse
    if (strcasecmp($user['username'], $username) === 0) {
        $found_user = $user;
        break;
    }
}

// 4. Gérer les actions
if ($action === 'login') {
    
    if ($found_user === null) {
        $response['message'] = 'Nom d\'utilisateur ou mot de passe incorrect.';
        echo json_encode($response); exit;
    }

    // Vérifier le mot de passe
    if (password_verify($password, $found_user['password_hash'])) {
        // Succès ! Démarrer la session
        $_SESSION['user_id'] = $found_user['user_id'];
        // ASTUCE: On stocke le username dans 'user_email' pour la compatibilité
        $_SESSION['user_email'] = $found_user['username']; 

        $response['success'] = true;
        $response['message'] = 'Connexion réussie. Redirection...';
    } else {
        $response['message'] = 'Nom d\'utilisateur ou mot de passe incorrect.';
    }

} elseif ($action === 'register') {

    if ($found_user !== null) {
        $response['message'] = 'Ce nom d\'utilisateur est déjà pris.';
        echo json_encode($response); exit;
    }

    if (strlen($password) < 6) {
        $response['message'] = 'Le mot de passe doit faire au moins 6 caractères.';
        echo json_encode($response); exit;
    }

    // Créer le nouvel utilisateur
    $new_user = [
        'user_id' => 'u_' . uniqid(),
        'username' => $username, // On stocke le nom d'utilisateur
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'created_at' => date('c')
    ];
    
    // Ajouter et sauvegarder
    $users[] = $new_user;
    if (write_json_file($users_filepath, $users)) {
        
        // Créer le fichier de tenues vide
        $outfit_filepath = __DIR__ . '/../data/outfits/' . $new_user['user_id'] . '.json';
        write_json_file($outfit_filepath, []);

        // Connecter l'utilisateur directement après l'inscription
        $_SESSION['user_id'] = $new_user['user_id'];
        $_SESSION['user_email'] = $new_user['username'];

        $response['success'] = true;
        $response['message'] = 'Compte créé ! Connexion...';
    } else {
        $response['message'] = 'Erreur lors de la sauvegarde du compte.';
    }

} else {
    $response['message'] = 'Action non reconnue.';
}

echo json_encode($response);
?>