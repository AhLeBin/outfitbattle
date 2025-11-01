<?php
require_once __DIR__ . '/../includes/auth_guard.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];

// 1. Valider le code et le rôle
$role = $_POST['role'] ?? null;
$game_code = strtoupper(trim($_POST['code'] ?? ''));

if (empty($game_code) || strlen($game_code) !== 4) {
    $response['message'] = 'Le code doit faire 4 caractères.';
    echo json_encode($response); exit;
}
if ($role !== 'joueur' && $role !== 'juge') {
    $response['message'] = 'Rôle invalide.';
    echo json_encode($response); exit;
}

// 2. Vérifier si la partie existe
$game_filepath = __DIR__ . '/../data/games/' . $game_code . '.json';
if (!file_exists($game_filepath)) {
    $response['message'] = 'Partie non trouvée. Vérifiez le code.';
    echo json_encode($response); exit;
}

// 3. Lire les données de la partie (en mode "flock" pour la sécurité)
// On utilise file_get_contents + flock pour un contrôle manuel
$handle = fopen($game_filepath, 'r+');
if (!$handle) {
    $response['message'] = 'Erreur lors de l\'ouverture de la partie.';
    echo json_encode($response); exit;
}

// Verrouillage exclusif
if (!flock($handle, LOCK_EX)) {
    $response['message'] = 'Le lobby est occupé, veuillez réessayer.';
    fclose($handle);
    echo json_encode($response); exit;
}

// Lire les données
$content = stream_get_contents($handle);
$game_data = json_decode($content, true);

// 4. Vérifier si le jeu est déjà lancé
if ($game_data['status'] !== 'lobby') {
    $response['message'] = 'Cette partie a déjà commencé.';
    flock($handle, LOCK_UN); // Déverrouiller
    fclose($handle);
    echo json_encode($response); exit;
}

// 5. Vérifier si l'utilisateur est déjà dans la partie
$user_found = false;
foreach ($game_data['participants'] as $key => $participant) {
    if ($participant['user_id'] === $user_id) {
        $user_found = true;
        // Mettre à jour son rôle s'il a changé
        $game_data['participants'][$key]['role'] = $role;
        break;
    }
}

// 6. Ajouter le participant s'il n'y est pas
if (!$user_found) {
    $game_data['participants'][] = [
        'user_id' => $user_id,
        'email' => $user_email,
        'role' => $role
    ];
}

// 7. Ré-écrire le fichier JSON (dans le même handle verrouillé)
ftruncate($handle, 0); // Vider le fichier
rewind($handle);       // Rembobiner au début
fwrite($handle, json_encode($game_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
fflush($handle);       // Vider le buffer
flock($handle, LOCK_UN); // Déverrouiller
fclose($handle);

// 8. Succès
$response['success'] = true;
$response['message'] = 'Partie rejointe ! Redirection...';

// On stocke le code en session
$_SESSION['game_code'] = $game_code;
$_SESSION['game_role'] = $role;

echo json_encode($response);