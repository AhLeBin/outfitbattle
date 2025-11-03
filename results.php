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
function get_average($votes_array) {
    if (empty($votes_array)) return 0;
    return array_sum($votes_array) / count($votes_array);
}

// --- MODIFICATION: Scores auto sur 5 points ---
function calculate_score_min($value, $min, $max) {
    if ($max == $min) {
        return ($value == $min) ? 5 : 0; // Max 5 points
    }
    // Note = 5 * (1 - (Val - Min) / (Max - Min))
    $score = 5 * (1 - (($value - $min) / ($max - $min))); // Max 5 points
    return max(0, min(5, $score)); // Borner le score entre 0 et 5
}
// --- FIN MODIFICATION ---


// Initialiser les structures de données
$player_selections = $game_data['game_data']['player_selections'] ?? [];
$item_votes = $game_data['game_data']['item_votes'] ?? [];
$outfit_votes = $game_data['game_data']['outfit_votes'] ?? [];

$all_players_data = [];

$temp_data_for_minmax = [
    'delivery_dates' => [ 'top' => [], 'bottom' => [], 'shoes' => [] ],
    'prices' => [ 'top' => [], 'bottom' => [], 'shoes' => [] ]
];

// --- 3.1: Pré-calcul des votes manuels et collecte des données pour Min/Max
foreach ($player_selections as $player_id => $outfit) {
    
    $all_players_data[$player_id] = [
        'player_email' => '',
        'score_manual_top' => 0, 'score_manual_bottom' => 0, 'score_manual_shoes' => 0,
        'score_manual_outfit' => 0,
        'score_auto_delivery' => 0, 'score_auto_price' => 0,
        'final_score' => 0,
        'outfit_name' => $outfit['name'] ?? 'Tenue',
        'score_del_top' => 0, 'score_del_bottom' => 0, 'score_del_shoes' => 0,
        'score_price_top' => 0, 'score_price_bottom' => 0, 'score_price_shoes' => 0,
    ];
    
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
    if (!empty($outfit['top']['delivery_date'])) $temp_data_for_minmax['delivery_dates']['top'][] = strtotime($outfit['top']['delivery_date']);
    if (!empty($outfit['bottom']['delivery_date'])) $temp_data_for_minmax['delivery_dates']['bottom'][] = strtotime($outfit['bottom']['delivery_date']);
    if (!empty($outfit['shoes']['delivery_date'])) $temp_data_for_minmax['delivery_dates']['shoes'][] = strtotime($outfit['shoes']['delivery_date']);

    $temp_data_for_minmax['prices']['top'][] = (float)($outfit['top']['price'] ?? 0) + (float)($outfit['top']['delivery_price'] ?? 0);
    $temp_data_for_minmax['prices']['bottom'][] = (float)($outfit['bottom']['price'] ?? 0) + (float)($outfit['bottom']['delivery_price'] ?? 0);
    $temp_data_for_minmax['prices']['shoes'][] = (float)($outfit['shoes']['price'] ?? 0) + (float)($outfit['shoes']['delivery_price'] ?? 0);
}

// --- 3.2: Trouver les Min/Max
function safe_min(array $values) { return min(empty($values) ? [0] : $values); }
function safe_max(array $values) { return max(empty($values) ? [0] : $values); }

$min_date_top = safe_min($temp_data_for_minmax['delivery_dates']['top']);
$max_date_top = safe_max($temp_data_for_minmax['delivery_dates']['top']);
$min_date_bottom = safe_min($temp_data_for_minmax['delivery_dates']['bottom']);
$max_date_bottom = safe_max($temp_data_for_minmax['delivery_dates']['bottom']);
$min_date_shoes = safe_min($temp_data_for_minmax['delivery_dates']['shoes']);
$max_date_shoes = safe_max($temp_data_for_minmax['delivery_dates']['shoes']);

$min_price_top = safe_min($temp_data_for_minmax['prices']['top']);
$max_price_top = safe_max($temp_data_for_minmax['prices']['top']);
$min_price_bottom = safe_min($temp_data_for_minmax['prices']['bottom']);
$max_price_bottom = safe_max($temp_data_for_minmax['prices']['bottom']);
$min_price_shoes = safe_min($temp_data_for_minmax['prices']['shoes']);
$max_price_shoes = safe_max($temp_data_for_minmax['prices']['shoes']);


// --- 3.3: Calcul final (Scores auto + Score Total)
foreach ($player_selections as $player_id => $outfit) {
    
    // A) Scores de Livraison (maintenant sur 5)
    $date_top = strtotime($outfit['top']['delivery_date'] ?? 'now');
    $date_bottom = strtotime($outfit['bottom']['delivery_date'] ?? 'now');
    $date_shoes = strtotime($outfit['shoes']['delivery_date'] ?? 'now');
    
    $all_players_data[$player_id]['score_del_top'] = calculate_score_min($date_top, $min_date_top, $max_date_top);
    $all_players_data[$player_id]['score_del_bottom'] = calculate_score_min($date_bottom, $min_date_bottom, $max_date_bottom);
    $all_players_data[$player_id]['score_del_shoes'] = calculate_score_min($date_shoes, $min_date_shoes, $max_date_shoes);
    
    $all_players_data[$player_id]['score_auto_delivery'] = 
        $all_players_data[$player_id]['score_del_top'] + 
        $all_players_data[$player_id]['score_del_bottom'] + 
        $all_players_data[$player_id]['score_del_shoes'];

    // B) Scores de Prix (maintenant sur 5)
    $price_top = (float)($outfit['top']['price'] ?? 0) + (float)($outfit['top']['delivery_price'] ?? 0);
    $price_bottom = (float)($outfit['bottom']['price'] ?? 0) + (float)($outfit['bottom']['delivery_price'] ?? 0);
    $price_shoes = (float)($outfit['shoes']['price'] ?? 0) + (float)($outfit['shoes']['delivery_price'] ?? 0);

    $all_players_data[$player_id]['score_price_top'] = calculate_score_min($price_top, $min_price_top, $max_price_top);
    $all_players_data[$player_id]['score_price_bottom'] = calculate_score_min($price_bottom, $min_price_bottom, $max_price_bottom);
    $all_players_data[$player_id]['score_price_shoes'] = calculate_score_min($price_shoes, $min_price_shoes, $max_price_shoes);
    
    $all_players_data[$player_id]['score_auto_price'] = 
        $all_players_data[$player_id]['score_price_top'] + 
        $all_players_data[$player_id]['score_price_bottom'] + 
        $all_players_data[$player_id]['score_price_shoes'];

    // C) SCORE FINAL (Scores auto totaux max 15+15=30, Scores manuels max 10+10=20)
    $avg_item_score = ($all_players_data[$player_id]['score_manual_top'] + $all_players_data[$player_id]['score_manual_bottom'] + $all_players_data[$player_id]['score_manual_shoes']) / 3;
    
    $all_players_data[$player_id]['final_score'] = 
        $avg_item_score +
        $all_players_data[$player_id]['score_manual_outfit'] +
        $all_players_data[$player_id]['score_auto_delivery'] +
        $all_players_data[$player_id]['score_auto_price'];
}

// --- ÉTAPE 3.5: PRÉ-CALCUL DES CLASSEMENTS INDIVIDUELS ---

function get_rank(array $sorted_scores, float $my_score): int {
    $rank = array_search($my_score, $sorted_scores);
    return ($rank === false) ? 0 : $rank + 1;
}

function get_sorted_list(array $all_data, string $key): array {
    $scores = array_column($all_data, $key);
    rsort($scores); 
    return array_values(array_unique($scores)); 
}

$ranks = [
    'manual_top' => get_sorted_list($all_players_data, 'score_manual_top'),
    'manual_bottom' => get_sorted_list($all_players_data, 'score_manual_bottom'),
    'manual_shoes' => get_sorted_list($all_players_data, 'score_manual_shoes'),
    'manual_outfit' => get_sorted_list($all_players_data, 'score_manual_outfit'),
    'del_top' => get_sorted_list($all_players_data, 'score_del_top'),
    'del_bottom' => get_sorted_list($all_players_data, 'score_del_bottom'),
    'del_shoes' => get_sorted_list($all_players_data, 'score_del_shoes'),
    'price_top' => get_sorted_list($all_players_data, 'score_price_top'),
    'price_bottom' => get_sorted_list($all_players_data, 'score_price_bottom'),
    'price_shoes' => get_sorted_list($all_players_data, 'score_price_shoes'),
];


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
        $my_total_manual_category = $my_avg_item_score + $my_data['score_manual_outfit'];
        $my_total_auto_category = $my_data['score_auto_delivery'] + $my_data['score_auto_price'];
    ?>
    <div class="score-breakdown">
        <h3>Mon Récapitulatif (<?php echo htmlspecialchars($my_data['player_email']); ?>)</h3>
        
        <div class="breakdown-grid">
            
            <div class="breakdown-category">
                <h4>Votes Manuels (Max 20 pts)</h4>
                <ul class="detailed-scores">
                    <li>
                        <span>Vote Haut</span>
                        <span class="rank">#<?php echo get_rank($ranks['manual_top'], $my_data['score_manual_top']); ?></span>
                        <span class="score"><?php echo number_format($my_data['score_manual_top'], 2); ?> pts</span>
                    </li>
                    <li>
                        <span>Vote Bas</span>
                        <span class="rank">#<?php echo get_rank($ranks['manual_bottom'], $my_data['score_manual_bottom']); ?></span>
                        <span class="score"><?php echo number_format($my_data['score_manual_bottom'], 2); ?> pts</span>
                    </li>
                    <li>
                        <span>Vote Chaussures</span>
                        <span class="rank">#<?php echo get_rank($ranks['manual_shoes'], $my_data['score_manual_shoes']); ?></span>
                        <span class="score"><?php echo number_format($my_data['score_manual_shoes'], 2); ?> pts</span>
                    </li>
                    <hr>
                    <li>
                        <span>Vote Tenue Globale</span>
                        <span class="rank">#<?php echo get_rank($ranks['manual_outfit'], $my_data['score_manual_outfit']); ?></span>
                        <span class="score"><?php echo number_format($my_data['score_manual_outfit'], 2); ?> pts</span>
                    </li>
                </ul>
                <div class="category-score">
                    <?php echo number_format($my_total_manual_category, 2); ?>
                </div>
            </div>
            
            <div class="breakdown-category">
                <h4>Scores Auto (Max 30 pts)</h4>
                 <ul class="detailed-scores">
                    <li>
                        <span>Livraison Haut</span>
                        <span class="rank">#<?php echo get_rank($ranks['del_top'], $my_data['score_del_top']); ?></span>
                        <span class="score"><?php echo number_format($my_data['score_del_top'], 2); ?> pts</span>
                    </li>
                    <li>
                        <span>Livraison Bas</span>
                        <span class="rank">#<?php echo get_rank($ranks['del_bottom'], $my_data['score_del_bottom']); ?></span>
                        <span class="score"><?php echo number_format($my_data['score_del_bottom'], 2); ?> pts</span>
                    </li>
                    <li>
                        <span>Livraison Chaussures</span>
                        <span class="rank">#<?php echo get_rank($ranks['del_shoes'], $my_data['score_del_shoes']); ?></span>
                        <span class="score"><?php echo number_format($my_data['score_del_shoes'], 2); ?> pts</span>
                    </li>
                    <hr>
                    <li>
                        <span>Prix Haut</span>
                        <span class="rank">#<?php echo get_rank($ranks['price_top'], $my_data['score_price_top']); ?></span>
                        <span class="score"><?php echo number_format($my_data['score_price_top'], 2); ?> pts</span>
                    </li>
                    <li>
                        <span>Prix Bas</span>
                        <span class="rank">#<?php echo get_rank($ranks['price_bottom'], $my_data['score_price_bottom']); ?></span>
                        <span class="score"><?php echo number_format($my_data['score_price_bottom'], 2); ?> pts</span>
                    </li>
                    <li>
                        <span>Prix Chaussures</span>
                        <span class="rank">#<?php echo get_rank($ranks['price_shoes'], $my_data['score_price_shoes']); ?></span>
                        <span class="score"><?php echo number_format($my_data['score_price_shoes'], 2); ?> pts</span>
                    </li>
                </ul>
                <div class="category-score">
                    <?php echo number_format($my_total_auto_category, 2); ?>
                </div>
            </div>

        </div>
        
        <div class="breakdown-total">
            <span>Score Final Total (Max 50 pts)</span>
            <strong><?php echo number_format($my_data['final_score'], 2); ?> pts</strong>
        </div>
    </div>
    <?php endif; ?>
    
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>