<?php
require_once __DIR__ . '/../includes/auth_guard.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];
$user_id = $_SESSION['user_id'];
$game_code = $_SESSION['game_code'] ?? null;

// Données du vote
$vote_type = $_POST['vote_type'] ?? null; // 'item' ou 'outfit'
$subject_id = $_POST['subject_id'] ?? null; // item_id ou outfit_id (joueur)
$score = (int)($_POST['score'] ?? 0);

// 1. Validation
if (!$game_code || !$vote_type || !$subject_id || $score < 1 || $score > 10) {
    $response['message'] = 'Données de vote invalides.';
    echo json_encode($response); exit;
}
if ($vote_type !== 'item' && $vote_type !== 'outfit') {
    $response['message'] = 'Type de vote invalide.';
    echo json_encode($response); exit;
}

// 2. Verrouiller et lire le fichier de jeu
$game_filepath = __DIR__ . '/../data/games/' . $game_code . '.json';
$handle = fopen($game_filepath, 'r+');
if (!$handle || !flock($handle, LOCK_EX)) {
    $response['message'] = 'Serveur occupé, veuillez réessayer.';
    if ($handle) fclose($handle);
    echo json_encode($response); exit;
}
$game_data = json_decode(stream_get_contents($handle), true);

// 3. Vérification de sécurité: Ne pas voter pour soi-même
// Trouver le propriétaire de l'article/tenue
$owner_id = null;
if ($vote_type === 'item') {
    // Il faut chercher dans toutes les tenues de tous les joueurs...
    foreach ($game_data['game_data']['player_selections'] as $player_uid => $outfit) {
        if (($outfit['top']['item_id'] ?? '') === $subject_id) $owner_id = $player_uid;
        if (($outfit['bottom']['item_id'] ?? '') === $subject_id) $owner_id = $player_uid;
        if (($outfit['shoes']['item_id'] ?? '') === $subject_id) $owner_id = $player_uid;
        if ($owner_id) break;
    }
} else { // $vote_type === 'outfit'
    // Pour les tenues, le $subject_id EST le $owner_id
    if (isset($game_data['game_data']['player_selections'][$subject_id])) {
        $owner_id = $subject_id;
    }
}

if ($owner_id === $user_id) {
    $response['message'] = 'Validation échouée: Tentative de vote pour soi-même.';
    flock($handle, LOCK_UN); fclose($handle);
    echo json_encode($response); exit;
}

// 4. Enregistrer le vote
$vote_key = $vote_type === 'item' ? 'item_votes' : 'outfit_votes';
if (!isset($game_data['game_data'][$vote_key])) {
    $game_data['game_data'][$vote_key] = (object)[];
}
if (!isset($game_data['game_data'][$vote_key][$subject_id])) {
    $game_data['game_data'][$vote_key][$subject_id] = (object)[];
}
$game_data['game_data'][$vote_key][$subject_id][$user_id] = $score;


// 5. VÉRIFIER SI LA PHASE EST TERMINÉE (la partie la plus complexe)
$total_votes_cast = 0;
foreach ($game_data['game_data'][$vote_key] as $subject => $voters) {
    $total_votes_cast += count((array)$voters);
}

$participants_count = count($game_data['participants']);
$player_count = count($game_data['game_data']['player_selections']);
$total_expected_votes = 0;

if ($vote_type === 'item') {
    $total_articles = $player_count * 3;
    $total_expected_votes = $total_articles * ($participants_count - 1);
    
    if ($total_votes_cast === $total_expected_votes) {
        $game_data['status'] = 'phase3'; // On passe à la phase 3
    }
    
} else { // $vote_type === 'outfit'
    $total_expected_votes = $player_count * ($participants_count - 1);
    
    if ($total_votes_cast === $total_expected_votes) {
        $game_data['status'] = 'results'; // On passe aux résultats
    }
}
$response['total_votes_cast'] = $total_votes_cast; // Debug
$response['total_expected_votes'] = $total_expected_votes; // Debug


// 6. Sauvegarder et déverrouiller
ftruncate($handle, 0);
rewind($handle);
fwrite($handle, json_encode($game_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
fflush($handle);
flock($handle, LOCK_UN);
fclose($handle);

$response['success'] = true;
echo json_encode($response);