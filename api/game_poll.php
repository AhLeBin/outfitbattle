<?php
require_once __DIR__ . '/../includes/auth_guard.php';

header('Content-Type: application/json');

$game_code = $_SESSION['game_code'] ?? null;
if (!$game_code) {
    echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Session de jeu non trouvée.']);
    exit;
}

$game_filepath = __DIR__ . '/../data/games/' . $game_code . '.json';
$game_data = read_json_file($game_filepath); // Pas besoin de "flock" pour une simple lecture

if (empty($game_data)) {
    echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Partie non trouvée.']);
    exit;
}

// Renvoyer l'état actuel
echo json_encode([
    'success' => true,
    'game_status' => $game_data['status']
]);