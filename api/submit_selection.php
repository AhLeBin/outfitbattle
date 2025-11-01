<?php
require_once __DIR__ . '/../includes/auth_guard.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];
$user_id = $_SESSION['user_id'];
$game_code = $_SESSION['game_code'] ?? null;
$outfit_id = $_POST['outfit_id'] ?? null;

if (!$game_code || !$outfit_id) {
    $response['message'] = 'Données manquantes.';
    echo json_encode($response); exit;
}

// 1. Récupérer la tenue complète depuis le fichier de l'utilisateur
$outfits_filepath = __DIR__ . '/../data/outfits/' . $user_id . '.json';
$user_outfits = read_json_file($outfits_filepath);
$selected_outfit = null;

foreach ($user_outfits as $outfit) {
    if ($outfit['outfit_id'] === $outfit_id) {
        $selected_outfit = $outfit;
        break;
    }
}

if ($selected_outfit === null) {
    $response['message'] = 'Tenue non trouvée.';
    echo json_encode($response); exit;
}

// 2. Vérifier que la tenue est complète
if (empty($selected_outfit['top']) || empty($selected_outfit['bottom']) || empty($selected_outfit['shoes'])) {
    $response['message'] = 'La tenue sélectionnée est incomplète.';
    echo json_encode($response); exit;
}

// 3. Verrouiller et mettre à jour le fichier de jeu
$game_filepath = __DIR__ . '/../data/games/' . $game_code . '.json';

$handle = fopen($game_filepath, 'r+');
if (!$handle || !flock($handle, LOCK_EX)) {
    $response['message'] = 'Serveur occupé, veuillez réessayer.';
    if ($handle) fclose($handle);
    echo json_encode($response); exit;
}

$game_data = json_decode(stream_get_contents($handle), true);

if ($game_data['status'] !== 'phase1') {
    $response['message'] = 'La phase de sélection est terminée.';
    flock($handle, LOCK_UN); fclose($handle);
    echo json_encode($response); exit;
}

// 4. Enregistrer la sélection (on copie l'intégralité de la tenue)
// C'est une "dénormalisation" : le fichier de jeu est un snapshot.
$game_data['game_data']['player_selections'][$user_id] = $selected_outfit;

// 5. Vérifier si tous les joueurs ont voté
$player_count = 0;
foreach ($game_data['participants'] as $p) {
    if ($p['role'] === 'joueur') {
        $player_count++;
    }
}

$selection_count = count($game_data['game_data']['player_selections']);

if ($player_count === $selection_count) {
    // Tous les joueurs ont choisi ! On passe à la phase 2
    $game_data['status'] = 'phase2';
}

// 6. Sauvegarder et déverrouiller
ftruncate($handle, 0);
rewind($handle);
fwrite($handle, json_encode($game_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
fflush($handle);
flock($handle, LOCK_UN);
fclose($handle);

$response['success'] = true;
echo json_encode($response);