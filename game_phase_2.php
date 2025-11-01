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
if (empty($game_data) || $game_data['status'] !== 'phase2') {
    // Si la phase est déjà passée (ex: phase3), on redirige
    if ($game_data['status'] === 'phase3') {
        header('Location: game_phase_3.php?code=' . $game_code); exit;
    }
    // Sinon, retour au lobby
    header('Location: play_lobby.php'); exit;
}

// Étape 3: "Aplatir" tous les articles de tous les joueurs en une seule liste
$all_articles = [];
$player_selections = $game_data['game_data']['player_selections'];
$all_participants_count = count($game_data['participants']);
$all_player_count = count($player_selections); // Nb de joueurs

foreach ($player_selections as $owner_id => $outfit) {
    // Ajouter le Haut
    if (!empty($outfit['top'])) {
        $outfit['top']['item_type'] = 'Haut';
        $outfit['top']['owner_id'] = $owner_id;
        $all_articles[] = $outfit['top'];
    }
    // Ajouter le Bas
    if (!empty($outfit['bottom'])) {
        $outfit['bottom']['item_type'] = 'Bas';
        $outfit['bottom']['owner_id'] = $owner_id;
        $all_articles[] = $outfit['bottom'];
    }
    // Ajouter les Chaussures
    if (!empty($outfit['shoes'])) {
        $outfit['shoes']['item_type'] = 'Chaussures';
        $outfit['shoes']['owner_id'] = $owner_id;
        $all_articles[] = $outfit['shoes'];
    }
}
// On mélange pour que ce ne soit pas toujours Joueur 1, Joueur 2...
shuffle($all_articles);

// Calculer le nombre total de votes attendus pour cette phase
// (Nb d'articles * (Nb de participants - 1))
// (Chaque participant vote pour tout, sauf pour les 3 articles de sa propre tenue)
// C'est (Total Articles * Total Participants) - (Total Articles)
$total_articles = count($all_articles); // Ex: 2 joueurs = 6 articles
// Ex: 2 joueurs + 1 juge = 3 participants
// 6 articles * (3 participants - 1) = 12 votes attendus
$total_expected_votes = $total_articles * ($all_participants_count - 1);


// Étape 4: Récupérer les votes DÉJÀ faits
$item_votes = $game_data['game_data']['item_votes'] ?? (object)[];


require_once __DIR__ . '/includes/header.php';
?>

<div class="main-content">
    
    <nav class="breadcrumb">
        <span>Partie: <?php echo htmlspecialchars($game_code); ?></span>
        <span style="float: right;">Phase 2: Articles</span>
    </nav>

    <div id="voting-app">
        
        <div id="vote-loading" class="waiting-screen" style="display: flex;">
            <span class="spinner"></span>
            <h3>Chargement des articles...</h3>
        </div>

        <div id="vote-item-container" style="display: none;">
            <div class="vote-progress">
                <div class="progress-bar" id="progress-bar" style="width: 0%;"></div>
                <span id="progress-text">Article 1 / <?php echo $total_articles; ?></span>
            </div>
            
            <div class="vote-card">
                <div class="vote-image-wrapper">
                    <img src="" id="item-image" alt="Article à noter">
                </div>
                <div class="vote-details">
                    <h3 id="item-type-model">Haut - Modèle</h3>
                    <p id="item-brand">Marque</p>
                </div>
            </div>
            
            <div id="rating-container" class="rating-container">
                <p>Attribuez une note de 1 à 10 :</p>
                <div class="rating-stars" id="rating-stars">
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <button class="star-btn" data-score="<?php echo $i; ?>"><?php echo $i; ?></button>
                    <?php endfor; ?>
                </div>
            </div>
            
            <div id="self-vote-message" class="message" style="display: none; text-align: center;">
                C'est votre article. Vous ne pouvez pas le noter.
            </div>
            
             <div id="already-voted-message" class="message" style="display: none; text-align: center;">
                Vous avez déjà noté cet article.
            </div>
        </div>

        <div id="waiting-container" class="waiting-screen" style="display: none;">
            <span class="spinner"></span>
            <h3>Merci pour vos votes !</h3>
            <p>En attente des autres participants...</p>
        </div>
        
    </div>

</div>

<script>
    const CURRENT_USER_ID = "<?php echo $user_id; ?>";
    const GAME_CODE = "<?php echo $game_code; ?>";
    const ALL_ARTICLES = <?php echo json_encode($all_articles); ?>;
    const EXISTING_VOTES = <?php echo json_encode($item_votes); ?>;
    const TOTAL_EXPECTED_VOTES = <?php echo $total_expected_votes; ?>;
</script>
<script src="/js/game_flow.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>