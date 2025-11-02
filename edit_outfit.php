<?php
// Étape 1: Sécuriser la page
require_once __DIR__ . '/includes/auth_guard.php';

$user_id = $_SESSION['user_id'];
$outfit_id = $_GET['id'] ?? null;
$outfits_filepath = __DIR__ . '/data/outfits/' . $user_id . '.json';

if (!$outfit_id) {
    header('Location: create_dashboard.php'); // Pas d'ID, on repart
    exit;
}

// Charger toutes les tenues
$outfits = read_json_file($outfits_filepath, []);

// --- LOGIQUE DE CRÉATION ---
if ($outfit_id === 'new') {
    // 1. Créer une nouvelle tenue vide
    $new_outfit_id = 'o_' . uniqid();
    $new_outfit = [
        'outfit_id' => $new_outfit_id,
        'name' => 'Nouvelle Tenue ' . (count($outfits) + 1), // Nom par défaut
        'last_modified' => date('c'),
        'top' => null,
        'bottom' => null,
        'shoes' => null
    ];
    
    // 2. Ajouter au tableau
    $outfits[] = $new_outfit;
    
    // 3. Sauvegarder
    write_json_file($outfits_filepath, $outfits);
    
    // 4. Rediriger vers la page d'édition de cette nouvelle tenue
    header('Location: edit_outfit.php?id=' . $new_outfit_id);
    exit;
}

// --- LOGIQUE DE CHARGEMENT ---
$current_outfit = null;
foreach ($outfits as $outfit) {
    if ($outfit['outfit_id'] === $outfit_id) {
        $current_outfit = $outfit;
        break;
    }
}

// Si l'ID demandé n'est pas trouvé (ou n'appartient pas à l'utilisateur)
if ($current_outfit === null) {
    // On pourrait afficher une erreur, mais le plus simple est de rediriger
    header('Location: create_dashboard.php');
    exit;
}

// Fonction simple pour afficher une carte d'article
function render_item_card($item_type, $item_data) {
    $icons = [
        'top' => '<svg viewBox="0 0 24 24"><path d="M18.37 3.37c-.4-.4-.92-.62-1.49-.62H7.12c-.57 0-1.09.22-1.49.62-.4.4-.62.92-.62 1.49V19.5c0 .83.67 1.5 1.5 1.5s1.5-.67 1.5-1.5v-3h8v3c0 .83.67 1.5 1.5 1.5s1.5-.67 1.5-1.5V4.86c0-.57-.22-1.09-.63-1.49zM16.5 15h-8v-3.36c0-.57.22-1.09.63-1.49.4-.4.92-.62 1.49-.62h3.76c.57 0 1.09.22 1.49.62.4.4.63.92.63 1.49V15z"/></svg>',
        'bottom' => '<svg viewBox="0 0 24 24"><path d="M15 1v1h-2V1H9v1H7V1H1v4l2.5 12h3.5v5h2v-5h2v5h2v-5h3.5L23 5V1h-6zm-6 20v-5H5.74L3.4 5.34V3h3.6v.5c0 .83.67 1.5 1.5 1.5s1.5-.67 1.5-1.5V3h2v.5c0 .83.67 1.5 1.5 1.5s1.5-.67 1.5-1.5V3h3.6l-2.34 11.66H15v5H9z"/></svg>',
        'shoes' => '<svg viewBox="0 0 24 24"><path d="M20.84 15.31c.1.2.16.41.16.63 0 .83-.67 1.5-1.5 1.5H5.61l-.64 3.17c-.08.4-.41.7-.81.7H1.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5h1.7l3.86-19.06c.1-.48.51-.81.99-.81h13.11c.83 0 1.5.67 1.5 1.5 0 .42-.17.8-.44 1.07l-3.34 3.34c-.26.26-.61.41-.98.41H9.17l-1 5h11.21c.45 0 .85.2.1.14.54l1.62 3.24c.23.46.36 1 .36 1.55zM17.5 14h-9.83l.67-3.34c.05-.22.25-.37.47-.37h10.43l-1.74 3.71z"/></svg>'
    ];
    
    $title = ucfirst($item_type === 'top' ? 'Haut' : ($item_type === 'bottom' ? 'Bas' : 'Chaussures'));
    $html_id = "item-card-{$item_type}";

    echo "<div class='item-card' id='{$html_id}' data-item-type='{$item_type}'>";
    echo "<h3>{$title}</h3>";
    echo "<div class='item-card-content'>";

    if ($item_data && !empty($item_data['photo_url'])) {
        // Afficher l'article
        echo "<img src='" . htmlspecialchars($item_data['photo_url']) . "?t=" . time() . "' alt='" . htmlspecialchars($item_data['model']) . "' class='item-photo'>";
        echo "<div class='item-details'>";
        echo "<div class='item-brand'>" . htmlspecialchars($item_data['brand']) . "</div>";
        echo "<div class='item-model'>" . htmlspecialchars($item_data['model']) . "</div>";
        echo "</div>";
    } else {
        // Afficher le placeholder
        echo "<div class='item-placeholder-icon'>" . $icons[$item_type] . "</div>";
        echo "<div class='item-placeholder-text'>Ajouter un article</div>";
    }

    echo "</div>"; // .item-card-content
    echo "</div>"; // .item-card
}
?>

<?php require_once __DIR__ . '/includes/header.php'; ?>

<div class="main-content">
    <nav class="breadcrumb">
        <a href="hub.php">Hub</a> &gt; <a href="create_dashboard.php">Créer</a> &gt; 
        <span><?php echo htmlspecialchars($current_outfit['name']); ?></span>
    </nav>

    <h2>Éditeur de Tenue</h2>
    <div class="outfit-editor">
        <?php render_item_card('top', $current_outfit['top']); ?>
        <?php render_item_card('bottom', $current_outfit['bottom']); ?>
        <?php render_item_card('shoes', $current_outfit['shoes']); ?>
    </div>
</div>

<div class="modal-overlay" id="item-modal-overlay" style="display: none;">
    <div class="modal-content" id="item-modal-content">
        <button class="modal-close" id="modal-close-btn">&times;</button>
        <h2 id="modal-title">Ajouter un article</h2>
        
        <form id="item-form" enctype="multipart/form-data">
            <input type="hidden" name="outfit_id" id="form-outfit-id" value="<?php echo htmlspecialchars($outfit_id); ?>">
            <input type="hidden" name="item_type" id="form-item-type" value="">
            
            <div id="modal-message" class="message"></div>

            <div class="input-group">
                <label for="form-brand">Marque</label>
                <input type="text" id="form-brand" name="brand">
            </div>
            
            <div class="input-group">
                <label for="form-model">Modèle</label>
                <input type="text" id="form-model" name="model">
            </div>
            
            <div class="form-row">
                <div class="input-group">
                    <label for="form-price">Prix (Sans livraison) *</label>
                    <input type="number" id="form-price" name="price" step="0.01" min="0" required>
                </div>
                <div class="input-group">
                    <label for="form-delivery-price">Prix Livraison</label>
                    <input type="number" id="form-delivery-price" name="delivery_price" step="0.01" min="0">
                </div>
            </div>
            
            <div class="input-group">
                <label for="form-delivery-date">Date livraison</label>
                <input type="date" id="form-delivery-date" name="delivery_date">
            </div>
            
            <div class="input-group">
                <label for="form-photo">Photo *</label>
                <input type="file" id="form-photo" name="photo" accept="image/png, image/jpeg" required>
                <small>Une photo est requise pour la sauvegarde.</small>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" id="modal-cancel-btn">Annuler</button>
                <button type="submit" class="btn btn-primary" id="modal-save-btn" disabled>Sauvegarder</button>
            </div>
            
        </form>
    </div>
</div>

<script src="/js/outfit_editor.js?v=1.1"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>