<?php
require_once __DIR__ . '/../includes/auth_guard.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => '', 'code' => null];
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];

// 1. Valider le rôle envoyé
$role = $_POST['role'] ?? null;
if ($role !== 'joueur' && $role !== 'juge') {
    $response['message'] = 'Rôle invalide.';
    echo json_encode($response); exit;
}

// 2. Générer un code unique (4 caractères, majuscules)
function generate_game_code(int $length = 4): string {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    do {
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }
        // S'assurer que le fichier n'existe pas déjà
        $filepath = __DIR__ . '/../data/games/' . $code . '.json';
    } while (file_exists($filepath));
    
    return $code;
}

$game_code = generate_game_code();
$game_filepath = __DIR__ . '/../data/games/' . $game_code . '.json';

// 3. Créer la structure de la nouvelle partie
$new_game_data = [
    'game_id' => $game_code,
    'host_id' => $user_id,
    'created_at' => date('c'),
    'status' => 'lobby', // 'lobby', 'phase1', 'phase2', 'phase3', 'results'
    'participants' => [
        [
            'user_id' => $user_id,
            'email' => $user_email,
            'role' => $role
        ]
    ],
    'game_data' => [
        'player_selections' => (object)[], // Utiliser (object) pour JSON {}
        'item_votes' => (object)[],
        'outfit_votes' => (object)[]
    ]
];

// 4. Sauvegarder le nouveau fichier de jeu
if (write_json_file($game_filepath, $new_game_data)) {
    $response['success'] = true;
    $response['code'] = $game_code;
    $response['message'] = 'Partie créée ! Redirection...';
    
    // On stocke le code en session pour le retrouver
    $_SESSION['game_code'] = $game_code;
    $_SESSION['game_role'] = $role;

} else {
    $response['message'] = 'Erreur: Impossible de créer le fichier de la partie.';
}

echo json_encode($response);