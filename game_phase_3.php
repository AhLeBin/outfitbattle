<?php
// Étape 1: Sécuriser la page
require_once __DIR__ . '/includes/auth_guard.php';

$user_id = $_SESSION['user_id'];
$game_code = $_SESSION['game_code'] ?? null;

if (!$game_code) {
    header('Location: play_lobby.php'); exit;
}

// Étape 2: Lire les données du jeu
$game_filepath = __DIR__ . '/data/games/' . $game_code . '.json';
$game_data = read_json_file($game_filepath);

// Sécurité: Vérifier le statut
if (empty($game_data) || $game_data['status'] !== 'phase3') {
    if ($game_data['status'] === 'results') {
        header('Location: results.php?code=' . $game_code); exit;
    }
    header('Location: play_lobby.php'); exit;
}

// Étape 3: "Aplatir" la liste des tenues (c'est la liste des joueurs)
$all_outfits = [];
$player_selections = $game_data['game_data']['player_selections'];

foreach ($player_selections as $owner_id => $outfit) {
    // On ajoute l'owner_id à l'objet pour le JS
    $outfit['owner_id'] = $owner_id;
    // On s'assure de récupérer le nom du propriétaire
    foreach ($game_data['participants'] as $p) {
        if ($p['user_id'] === $owner_id) {
            $outfit['owner_email'] = $p['email'];
            break;
        }
    }
    $all_outfits[] = $outfit;
}
shuffle($all_outfits); // Mélanger l'ordre de vote

// Étape 4: Récupérer les votes DÉJÀ faits
$outfit_votes = $game_data['game_data']['outfit_votes'] ?? (object)[];


require_once __DIR__ . '/includes/header.php';
?>

<div class="main-content">
    
    <nav class="breadcrumb">
        <span>Partie: <?php echo htmlspecialchars($game_code); ?></span>
        <span style="float: right;">Phase 3: Tenues Complètes</span>
    </nav>

    <div id="voting-app">
        
        <div id="vote-loading" class="waiting-screen" style="display: flex;">
            <span class="spinner"></span>
            <h3>Chargement des tenues...</h3>
        </div>

        <div id="vote-item-container" style="display: none;">
            <div class="vote-progress">
                <div class="progress-bar" id="progress-bar" style="width: 0%;"></div>
                <span id="progress-text">Tenue 1 / <?php echo count($all_outfits); ?></span>
            </div>
            
            <div class="vote-card outfit-vote-card">
                <div class="outfit-display">
                    <img src="" id="item-image-top" alt="Haut">
                    <img src="" id="item-image-bottom" alt="Bas">
                    <img src="" id="item-image-shoes" alt="Chaussures">
                </div>
                <div class="vote-details">
                    <h3 id="outfit-name">Nom de la tenue</h3>
                    <p id="outfit-owner">par Joueur</p>
                </div>
            </div>
            
            <div id="rating-container" class="rating-container">
                <p>Attribuez une note globale de 1 à 10 :</p>
                <div class="rating-stars" id="rating-stars">
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <button class="star-btn" data-score="<?php echo $i; ?>"><?php echo $i; ?></button>
                    <?php endfor; ?>
                </div>
            </div>
            
            <div id="self-vote-message" class="message" style="display: none; text-align: center;">
                C'est votre tenue. Vous ne pouvez pas la noter.
            </div>
        </div>

        <div id="waiting-container" class="waiting-screen" style="display: none;">
            <span class="spinner"></span>
            <h3>Merci pour vos votes !</h3>
            <p>Calcul des scores et en attente des autres participants...</p>
        </div>
        
    </div>

</div>

<script>
    const CURRENT_USER_ID = "<?php echo $user_id; ?>";
    const GAME_CODE = "<?php echo $game_code; ?>";
    const ALL_OUTFITS = <?php echo json_encode($all_outfits); ?>;
    const EXISTING_VOTES = <?php echo json_encode($outfit_votes); ?>;
</script>
<script src="/js/game_flow_outfits.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>