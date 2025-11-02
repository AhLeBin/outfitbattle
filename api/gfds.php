<?php
/**
 * Script d'initialisation de la base de données JSON
 * À exécuter une seule fois pour créer la structure de dossiers
 */

echo "Initialisation de la base de données...\n";

// Créer les dossiers nécessaires
$folders = [
    __DIR__ . '/data',
    __DIR__ . '/data/outfits',
    __DIR__ . '/data/games',
    __DIR__ . '/uploads'
];

foreach ($folders as $folder) {
    if (!is_dir($folder)) {
        if (mkdir($folder, 0755, true)) {
            echo "✓ Dossier créé: $folder\n";
        } else {
            echo "✗ Erreur lors de la création de: $folder\n";
        }
    } else {
        echo "○ Dossier existe déjà: $folder\n";
    }
}

// Créer le fichier users.json s'il n'existe pas
$users_file = __DIR__ . '/data/users.json';
if (!file_exists($users_file)) {
    if (file_put_contents($users_file, '[]')) {
        echo "✓ Fichier créé: $users_file\n";
    } else {
        echo "✗ Erreur lors de la création de: $users_file\n";
    }
} else {
    echo "○ Fichier existe déjà: $users_file\n";
}

// Créer les fichiers .htaccess de sécurité
$htaccess_data = "# Bloquer tout accès web direct\nDeny from all";
$htaccess_uploads = "# Désactiver l'exécution de scripts\n<FilesMatch \"\.(php|phtml|php3|php4|php5|php7|pl|py|cgi)$\">\n    Order Deny,Allow\n    Deny from all\n</FilesMatch>";

$security_files = [
    __DIR__ . '/data/.htaccess' => $htaccess_data,
    __DIR__ . '/uploads/.htaccess' => $htaccess_uploads
];

foreach ($security_files as $file => $content) {
    if (!file_exists($file)) {
        if (file_put_contents($file, $content)) {
            echo "✓ Fichier de sécurité créé: $file\n";
        } else {
            echo "✗ Erreur lors de la création de: $file\n";
        }
    } else {
        echo "○ Fichier de sécurité existe déjà: $file\n";
    }
}

echo "\n=== Initialisation terminée ===\n";
echo "Vous pouvez maintenant utiliser l'application.\n";
echo "URL de test: http://localhost/index.php\n";