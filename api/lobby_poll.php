<?php
// Étape 1: Sécuriser la page
require_once __DIR__ . '/../includes/auth_guard.php';

header('Content-Type: application/json');

// Étape 2: Récupérer le code depuis la SESSION (plus sécurisé que POST/GET)
$game_code = $_SESSION['game_code'] ?? null;
if (!$game_code) {
    echo json_encode(['success' => false, 'message' => 'Session de jeu non trouvée.']);
    exit;
}

// Étape 3: Lire le fichier de jeu
$game_filepath = __DIR__ . '/../data/games/' . $game_code . '.json';
$game_data = read_json_file($game_filepath);

if (empty($game_data)) {
    echo json_encode(['success' => false, 'message' => 'Données de jeu non trouvées.']);
    exit;
}

// Étape 4: Renvoyer les données nécessaires
echo json_encode([
    'success' => true,
    'game_status' => $game_data['status'],
    'host_id' => $game_data['host_id'],
    'participants' => $game_data['participants']
]);