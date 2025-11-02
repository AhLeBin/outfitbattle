<?php
// Étape 1: Sécuriser la page
require_once __DIR__ . '/includes/auth_guard.php';

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['game_role'] ?? 'juge'; // Récupérer le rôle
$game_code = $_SESSION['game_code'] ?? null;

// Sécurité : Si pas de code de jeu en session, retour au lobby
if (!$game_code) {
    header('Location: play_lobby.php');
    exit;
}

// Étape 2: Lire les données du jeu pour vérifier le statut
$game_filepath = __DIR__ . '/data/games/' . $game_code . '.json';
$game_data = read_json_file($game_filepath);

// Si le jeu n'est pas en phase 1, ou n'existe pas, on redirige
if (empty($game_data) || $game_data['status'] !== 'phase1') {
    // (on pourrait rediriger vers la bonne phase, mais lobby est plus simple)
    header('Location: play_lobby.php');
    exit;
}

// Étape 3: (Pour les Joueurs) Charger leurs tenues
$user_outfits = [];
if ($user_role === 'joueur') {
    $outfits_filepath = __DIR__ . '/data/outfits/' . $user_id . '.json';
    $user_outfits = read_json_file($outfits_filepath, []);
}

// Étape 4: Vérifier si ce joueur a DÉJÀ choisi
$has_already_selected = isset($game_data['game_data']['player_selections'][$user_id]);

require_once __DIR__ . '/includes/header.php';
?>

<div class="main-content">
    
    <nav class="breadcrumb">
        <span>Partie: <?php echo htmlspecialchars($game_code); ?></span>
        <span style="float: right;">Rôle: <strong><?php echo ucfirst($user_role); ?></strong></span>
    </nav>

    <h2>Phase 1: Choix de la Tenue</h2>
    
    <div id="waiting-container" 
         class="waiting-screen" 
         style="<?php echo ($user_role === 'juge' || $has_already_selected) ? 'display: flex;' : 'display: none;'; ?>">
        <span class="spinner"></span>
        <h3>En attente des autres joueurs...</h3>
        <p>Les joueurs sont en train de choisir leur tenue pour la battle.</p>
    </div>

    <div id="selection-container" 
         style="<?php echo ($user_role === 'joueur' && !$has_already_selected) ? 'display: block;' : 'display: none;'; ?>">
        
        <p class="subtitle">Sélectionnez la tenue que vous voulez soumettre.</p>

        <div class="outfit-gallery selection-gallery">
            <?php
            if (!empty($user_outfits)):
                foreach ($user_outfits as $outfit):
                    
                    // On ne peut sélectionner qu'une tenue COMPLÈTE
                    $is_complete = !empty($outfit['top']) && !empty($outfit['bottom']) && !empty($outfit['shoes']);
                    
                    $thumbnail_url = '/img/placeholder-top.png'; // Fallback
                    if (!empty($outfit['top']['photo_url'])) {
                        $thumbnail_url = htmlspecialchars($outfit['top']['photo_url']);
                    }
                    
                    $outfit_name = htmlspecialchars($outfit['name'] ?? 'Tenue sans nom');
                    $outfit_id = htmlspecialchars($outfit['outfit_id']);
            ?>
            
            <div class="outfit-card <?php echo $is_complete ? '' : 'incomplete'; ?>" 
                 data-outfit-id="<?php echo $outfit_id; ?>"
                 <?php echo !$is_complete ? 'title="Cette tenue est incomplète (Haut, Bas et Chaussures requis)"' : ''; ?>>
                
                <div class="card-image-wrapper">
                    <img src="<?php echo $thumbnail_url; ?>" alt="<?php echo $outfit_name; ?>" class="card-image">
                </div>
                <div class="card-name"><?php echo $outfit_name; ?></div>
                <?php if (!$is_complete): ?>
                    <div class="incomplete-overlay">Incomplète</div>
                <?php endif; ?>
            </div>

            <?php
                endforeach;
            else:
                echo '<p class="message error" style="grid-column: 1 / -1;">Vous n\'avez aucune tenue. <a href="create_dashboard.php" target="_blank">Créez-en une !</a></p>';
            endif;
            ?>
        </div>
    </div>

</div>

<script>
    const USER_ROLE = "<?php echo $user_role; ?>";
    const GAME_CODE = "<?php echo $game_code; ?>";
    const HAS_SELECTED = <?php echo $has_already_selected ? 'true' : 'false'; ?>;
</script>
<script src="/js/game_phase_1.js?v=1.1"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>