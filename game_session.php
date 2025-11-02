<?php
// Étape 1: Sécuriser la page
require_once __DIR__ . '/includes/auth_guard.php';

$user_id = $_SESSION['user_id'];
$game_code = $_GET['code'] ?? null;

// Sécurité : Vérifier que l'utilisateur est bien censé être dans cette partie
// (On vérifie que le code en GET correspond à celui de sa session)
if (!$game_code || $game_code !== ($_SESSION['game_code'] ?? null)) {
    // Si le code est manquant ou ne correspond pas, on le renvoie au lobby
    unset($_SESSION['game_code']); // Nettoyer la session
    unset($_SESSION['game_role']);
    header('Location: play_lobby.php');
    exit;
}

// Étape 2: Lire les données de la partie (pour le premier chargement)
$game_filepath = __DIR__ . '/data/games/' . $game_code . '.json';
$game_data = read_json_file($game_filepath);

if (empty($game_data)) {
    // Le fichier de jeu n'existe pas/plus
    unset($_SESSION['game_code']);
    unset($_SESSION['game_role']);
    header('Location: play_lobby.php');
    exit;
}

// L'utilisateur est-il l'hôte ?
$is_host = ($game_data['host_id'] === $user_id);

// Étape 3: Inclure le header
require_once __DIR__ . '/includes/header.php';
?>

<div class="main-content">
    
    <nav class="breadcrumb">
        <a href="hub.php">Hub</a> &gt; <a href="play_lobby.php">Jouer</a> &gt; <span>Lobby</span>
    </nav>

    <h2>Salle d'attente</h2>
    <p class="subtitle">Partagez ce code pour inviter d'autres participants.</p>

    <div class="game-code-display">
        <span>CODE</span>
        <strong id="game-code-text"><?php echo htmlspecialchars($game_code); ?></strong>
    </div>

    <div class="lobby-layout">
        
        <div class="lobby-column">
            <h3>Participants</h3>
            <ul class="participant-list" id="participant-list">
                <li>Chargement...</li>
            </ul>
        </div>

        <div class="lobby-column">
            <h3>Statut</h3>
            <div id="status-container">
                <?php if ($is_host): ?>
                    <button class="btn btn-primary btn-large" id="start-game-btn" disabled>
                        Lancer la Partie
                    </button>
                    <p class="status-message" id="start-game-message">
                        Minimum 2 <strong>Joueurs</strong> requis pour commencer.
                    </p>
                <?php else: ?>
                    <p class="status-message">
                        <span class="spinner"></span>
                        En attente du lancement par l'hôte...
                    </p>
                <?php endif; ?>
            </div>
            <div id="lobby-error-message" class="message error" style="display: none; margin-top: 1rem;"></div>
        </div>

    </div>
</div>

<script>
    const IS_HOST = <?php echo $is_host ? 'true' : 'false'; ?>;
    const GAME_CODE = "<?php echo htmlspecialchars($game_code); ?>";
</script>
<script src="/js/game_session.js?v=1.1"></script>

<?php
// Étape 4: Inclure le footer
require_once __DIR__ . '/includes/footer.php';
?>