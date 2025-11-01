<?php
// Démarrer la session sur toutes les pages qui incluent ce fichier
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Lit un fichier JSON et le retourne en tant que tableau PHP.
 * Crée le fichier s'il n'existe pas.
 * @param string $filepath Chemin vers le fichier JSON
 * @param array $default_data Données par défaut si le fichier est créé (ex: [])
 * @return array
 */
function read_json_file(string $filepath, array $default_data = []): array {
    // Crée le dossier parent si nécessaire
    $dir = dirname($filepath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    // Crée le fichier avec les données par défaut s'il n'existe pas
    if (!file_exists($filepath)) {
        // file_put_contents gère la création
        file_put_contents($filepath, json_encode($default_data, JSON_PRETTY_PRINT));
        return $default_data;
    }

    $content = file_get_contents($filepath);
    if ($content === false) {
        // Gérer l'erreur de lecture
        return $default_data;
    }

    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Gérer l'erreur de JSON invalide (ex: fichier corrompu)
        return $default_data;
    }

    return $data;
}

/**
 * Écrit un tableau PHP dans un fichier JSON de manière sécurisée (avec verrouillage).
 * @param string $filepath Chemin vers le fichier JSON
 * @param array $data Tableau à écrire
 * @return bool True en cas de succès, False en cas d'échec
 */
function write_json_file(string $filepath, array $data): bool {
    // S'assure que le dossier existe
    $dir = dirname($filepath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    // Convertir en JSON
    $json_data = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json_data === false) {
        // Gérer l'erreur d'encodage
        return false;
    }

    // Écrire avec verrouillage exclusif pour éviter la corruption
    if (file_put_contents($filepath, $json_data, LOCK_EX) === false) {
        return false;
    }

    return true;
}

/**
 * Vérifie si l'utilisateur est connecté.
 * Redirige vers la page de connexion si non.
 */
function check_auth(): void {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /index.php'); // Ajuste le chemin si nécessaire
        exit;
    }
}