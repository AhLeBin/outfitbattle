<?php
// Étape 1: Sécuriser la page
require_once __DIR__ . '/../includes/auth_guard.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];
$user_id = $_SESSION['user_id'];
$game_code = $_SESSION['game_code'] ?? null;

if (!$game_code) {
    $response['message'] = 'Session de jeu non trouvée.';
    echo json_encode($response); exit;
}

// Étape 2: On utilise la méthode de lecture/écriture sécurisée (flock)
$game_filepath = __DIR__ . '/../data/games/' . $game_code . '.json';
$handle = fopen($game_filepath, 'r+');
if (!$handle || !flock($handle, LOCK_EX)) {
    $response['message'] = 'Impossible de verrouiller le fichier de jeu.';
    if ($handle) fclose($handle);
    echo json_encode($response); exit;
}

// Lire les données actuelles
$content = stream_get_contents($handle);
$game_data = json_decode($content, true);

// Étape 3: Vérifications
if ($game_data['host_id'] !== $user_id) {
    $response['message'] = 'Vous n\'êtes pas l\'hôte.';
    flock($handle, LOCK_UN); fclose($handle);
    echo json_encode($response); exit;
}
if ($game_data['status'] !== 'lobby') {
    $response['message'] = 'La partie a déjà commencé.';
    flock($handle, LOCK_UN); fclose($handle);
    echo json_encode($response); exit;
}

// Compter les joueurs
$player_count = 0;
foreach ($game_data['participants'] as $p) {
    if ($p['role'] === 'joueur') {
        $player_count++;
    }
}

if ($player_count < 2) { // Règle du cahier des charges
    $response['message'] = 'Il faut au moins 2 joueurs pour commencer.';
    flock($handle, LOCK_UN); fclose($handle);
    echo json_encode($response); exit;
}

// Étape 4: Mettre à jour le statut et sauvegarder
$game_data['status'] = 'phase1'; // <<< CHANGEMENT D'ÉTAT

ftruncate($handle, 0);
rewind($handle);
fwrite($handle, json_encode($game_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
fflush($handle);
flock($handle, LOCK_UN);
fclose($handle);

$response['success'] = true;
$response['message'] = 'La partie commence !';
echo json_encode($response);