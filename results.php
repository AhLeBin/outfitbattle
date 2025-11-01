<?php
// Étape 1: Sécuriser la page
require_once __DIR__ . '/includes/auth_guard.php';

$user_id = $_SESSION['user_id'];
$game_code = $_GET['code'] ?? null;

// Sécurité : On vérifie que l'utilisateur est bien dans la session de jeu
if (!$game_code || $game_code !== ($_SESSION['game_code'] ?? null)) {
    header('Location: hub.php'); // Si pas de code, ou code différent, retour au hub
    exit;
}

// Étape 2: Lire les données du jeu
$game_filepath = __DIR__ . '/data/games/' . $game_code . '.json';
$game_data = read_json_file($game_filepath);

// Sécurité: On ne peut voir les résultats que si la phase est 'results'
if (empty($game_data) || $game_data['status'] !== 'results') {
    // Peut-être que le jeu n'est pas fini ? On le renvoie à la dernière phase connue.
    $last_phase = $game_data['status'] ?? 'lobby';
    if ($last_phase === 'lobby') header('Location: play_lobby.php');
    if ($last_phase === 'phase1') header('Location: game_phase_1.php?code='.$game_code);
    if ($last_phase === 'phase2') header('Location: game_phase_2.php?code='.$game_code);
    if ($last_phase === 'phase3') header('Location: game_phase_3.php?code='.$game_code);
    exit;
}

// --- ÉTAPE 3: CALCUL DES SCORES ---

// Fonctions d'aide
function get_average($votes_object) {
    if (empty($votes_object)) return 0;
    $votes = (array)$votes_object;
    return array_sum($votes) / count($votes);
}

// Formule d'interpolation (pour minimisation: prix, date)
// Note = 10 * (1 - (Val - Min) / (Max - Min))
function calculate_score_min($value, $min, $max) {
    if ($max == $min) {
        return ($value == $min) ? 10 : 0; // Tous identiques, 10 si on est le min, 0 sinon
    }
    $score = 10 * (1 - (($value - $min) / ($max - $min)));
    return max(0, min(10, $score)); // Borner le score entre 0 et 10
}


// Initialiser les structures de données
$player_selections = (array)$game_data['game_data']['player_selections'];
$item_votes = (array)$game_data['game_data']['item_votes'];
$outfit_votes = (array)$game_data['game_data']['outfit_votes'];

$all_players_data = []; // Le tableau final des scores

$temp_data_for_minmax = [
    'delivery_dates' => [],
    'prices' => [ 'top' => [], 'bottom' => [], 'shoes' => [] ]
];

// --- 3.1: Pré-calcul des votes manuels et collecte des données pour Min/Max
foreach ($player_selections as $player_id => $outfit) {
    
    // Initialiser le score de ce joueur
    $all_players_data[$player_id] = [
        'player_email' => '', // On le trouvera plus tard
        'score_manual_top' => 0,
        'score_manual_bottom' => 0,
        'score_manual_shoes' => 0,
        'score_manual_outfit' => 0,
        'score_auto_delivery' => 0,
        'score_auto_price' => 0,
        'final_score' => 0,
        'outfit_name' => $outfit['name'] ?? 'Tenue'
    ];
    
    // Trouver l'email
    foreach ($game_data['participants'] as $p) {
        if ($p['user_id'] === $player_id) {
            $all_players_data[$player_id]['player_email'] = $p['email'];
            break;
        }
    }

    // A) Calcul des scores de votes manuels
    $top_id = $outfit['top']['item_id'] ?? null;
    $bottom_id = $outfit['bottom']['item_id'] ?? null;
    $shoes_id = $outfit['shoes']['item_id'] ?? null;
    
    $all_players_data[$player_id]['score_manual_top'] = get_average($item_votes[$top_id] ?? []);
    $all_players_data[$player_id]['score_manual_bottom'] = get_average($item_votes[$bottom_id] ?? []);
    $all_players_data[$player_id]['score_manual_shoes'] = get_average($item_votes[$shoes_id] ?? []);
    $all_players_data[$player_id]['score_manual_outfit'] = get_average($outfit_votes[$player_id] ?? []);
    
    // B) Collecte des données pour les scores auto
    
    // Dates (en timestamp pour comparaison facile)
    if ($outfit['top']['delivery_date']) $temp_data_for_minmax['delivery_dates'][] = strtotime($outfit['top']['delivery_date']);
    if ($outfit['bottom']['delivery_date']) $temp_data_for_minmax['delivery_dates'][] = strtotime($outfit['bottom']['delivery_date']);
    if ($outfit['shoes']['delivery_date']) $temp_data_for_minmax['delivery_dates'][] = strtotime($outfit['shoes']['delivery_date']);

    // Prix (Total = prix + livraison)
    $temp_data_for_minmax['prices']['top'][] = (float)($outfit['top']['price'] ?? 0) + (float)($outfit['top']['delivery_price'] ?? 0);
    $temp_data_for_minmax['prices']['bottom'][] = (float)($outfit['bottom']['price'] ?? 0) + (float)($outfit['bottom']['delivery_price'] ?? 0);
    $temp_data_for_minmax['prices']['shoes'][] = (float)($outfit['shoes']['price'] ?? 0) + (float)($outfit['shoes']['delivery_price'] ?? 0);
}

// --- 3.2: Trouver les Min/Max
$min_date = min($temp_data_for_minmax['delivery_dates']);
$max_date = max($temp_data_for_minmax['delivery_dates']);

$min_price_top = min($temp_data_for_minmax['prices']['top']);
$max_price_top = max($temp_data_for_minmax['prices']['top']);
$min_price_bottom = min($temp_data_for_minmax['prices']['bottom']);
$max_price_bottom = max($temp_data_for_minmax['prices']['bottom']);
$min_price_shoes = min($temp_data_for_minmax['prices']['shoes']);
$max_price_shoes = max($temp_data_for_minmax['prices']['shoes']);


// --- 3.3: Calcul final (Scores auto + Score Total)
foreach ($player_selections as $player_id => $outfit) {
    
    // A) Scores de Livraison
    $date_top = strtotime($outfit['top']['delivery_date'] ?? 'now');
    $date_bottom = strtotime($outfit['bottom']['delivery_date'] ?? 'now');
    $date_shoes = strtotime($outfit['shoes']['delivery_date'] ?? 'now');
    
    $score_del_top = calculate_score_min($date_top, $min_date, $max_date);
    $score_del_bottom = calculate_score_min($date_bottom, $min_date, $max_date);
    $score_del_shoes = calculate_score_min($date_shoes, $min_date, $max_date);
    $all_players_data[$player_id]['score_auto_delivery'] = $score_del_top + $score_del_bottom + $score_del_shoes;

    // B) Scores de Prix
    $price_top = (float)($outfit['top']['price'] ?? 0) + (float)($outfit['top']['delivery_price'] ?? 0);
    $price_bottom = (float)($outfit['bottom']['price'] ?? 0) + (float)($outfit['bottom']['delivery_price'] ?? 0);
    $price_shoes = (float)($outfit['shoes']['price'] ?? 0) + (float)($outfit['shoes']['delivery_price'] ?? 0);

    $score_price_top = calculate_score_min($price_top, $min_price_top, $max_price_top);
    $score_price_bottom = calculate_score_min($price_bottom, $min_price_bottom, $max_price_bottom);
    $score_price_shoes = calculate_score_min($price_shoes, $min_price_shoes, $max_price_shoes);
    $all_players_data[$player_id]['score_auto_price'] = $score_price_top + $score_price_bottom + $score_price_shoes;

    // C) SCORE FINAL (selon le cahier des charges)
    $avg_item_score = ($all_players_data[$player_id]['score_manual_top'] + $all_players_data[$player_id]['score_manual_bottom'] + $all_players_data[$player_id]['score_manual_shoes']) / 3;
    
    $all_players_data[$player_id]['final_score'] = 
        $avg_item_score +
        $all_players_data[$player_id]['score_manual_outfit'] +
        $all_players_data[$player_id]['score_auto_delivery'] +
        $all_players_data[$player_id]['score_auto_price'];
}

// --- ÉTAPE 4: Trier les joueurs
uasort($all_players_data, function($a, $b) {
    return $b['final_score'] <=> $a['final_score']; // Tri descendant
});

// Étape 5: Nettoyer la session (le jeu est fini)
unset($_SESSION['game_code']);
unset($_SESSION['game_role']);


require_once __DIR__ . '/includes/header.php';
?>

<div class="main-content">
    
    <nav class="breadcrumb">
        <span>Partie: <?php echo htmlspecialchars($game_code); ?></span>
        <a href="hub.php" class="btn-link" style="float: right;">Retour au Hub</a>
    </nav>

    <h2>Résultats de la Battle</h2>
    <p class="subtitle">Le classement final est basé sur les votes et les scores automatiques.</p>

    <ol class="results-list">
        <?php $rank = 1; ?>
        <?php foreach ($all_players_data as $player_id => $data): ?>
            <li class="result-item <?php echo ($rank === 1) ? 'winner' : ''; ?>">
                <div class="result-rank">
                    <?php 
                        if ($rank === 1) echo '🏆';
                        elseif ($rank === 2) echo '🥈';
                        elseif ($rank === 3) echo '🥉';
                        else echo '#' . $rank;
                    ?>
                </div>
                <div class="result-player">
                    <strong><?php echo htmlspecialchars($data['player_email']); ?></strong>
                    <span><?php echo htmlspecialchars($data['outfit_name']); ?></span>
                </div>
                <div class="result-score">
                    <?php echo number_format($data['final_score'], 2); ?> pts
                </div>
            </li>
            <?php $rank++; ?>
        <?php endforeach; ?>
    </ol>
    
    <?php if (isset($all_players_data[$user_id])): 
        $my_data = $all_players_data[$user_id];
        $my_avg_item_score = ($my_data['score_manual_top'] + $my_data['score_manual_bottom'] + $my_data['score_manual_shoes']) / 3;
    ?>
    <div class="score-breakdown">
        <h3>Mon Récapitulatif (<?php echo htmlspecialchars($my_data['player_email']); ?>)</h3>
        
        <div class="breakdown-grid">
            <div class="breakdown-category">
                <h4>Votes Manuels</h4>
                <ul>
                    <li>Note moyenne Articles (Haut, Bas, Chaussures)</li>
                    <li>Note globale Tenue</li>
                </ul>
                <div class="category-score">
                    <?php echo number_format($my_avg_item_score + $my_data['score_manual_outfit'], 2); ?>
                </div>
            </div>
            
            <div class="breakdown-category">
                <h4>Scores Auto</h4>
                <ul>
                    <li>Points de Livraison (Total 3 articles)</li>
                    <li>Points de Prix (Total 3 articles)</li>
                </ul>
                <div class="category-score">
                    <?php echo number_format($my_data['score_auto_delivery'] + $my_data['score_auto_price'], 2); ?>
                </div>
            </div>
        </div>
        
        <div class="breakdown-total">
            <span>Score Final Total</span>
            <strong><?php echo number_format($my_data['final_score'], 2); ?> pts</strong>
        </div>
    </div>
    <?php endif; ?>
    
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>