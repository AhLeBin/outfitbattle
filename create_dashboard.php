<?php
// Étape 1: Sécuriser la page
require_once __DIR__ . '/includes/auth_guard.php';

// Récupérer l'ID de l'utilisateur depuis la session
$user_id = $_SESSION['user_id'];

// Étape 2: Lire le fichier de tenues de l'utilisateur
// La fonction read_json_file gère le cas où le fichier n'existe pas encore
$outfits_filepath = __DIR__ . '/data/outfits/' . $user_id . '.json';
$outfits = read_json_file($outfits_filepath, []); // [] par défaut si vide ou nouveau

// Étape 3: Inclure le header
require_once __DIR__ . '/includes/header.php';
?>

<div class="main-content">
    
    <nav class="breadcrumb">
        <a href="hub.php">Hub</a> &gt; <span>Créer</span>
        <a href="logout.php" class="logout-link" style="float: right;">[ Déconnexion ]</a>
    </nav>

    <h2>Mes Tenues</h2>
    <p class="subtitle">Gérez vos tenues existantes ou créez-en une nouvelle.</p>

    <div class="outfit-gallery">

        <a href="edit_outfit.php?id=new" class="outfit-card new-outfit-card">
            <div class="icon-plus">+</div>
            <div class="card-name">Créer une tenue</div>
        </a>

        <?php
        // Étape 4: Boucler sur les tenues et les afficher
        if (!empty($outfits)):
            foreach ($outfits as $outfit):
                
                // Déterminer quelle image afficher (le 'Haut' en priorité)
                $thumbnail_url = '/img/placeholder-top.png'; // Image par défaut
                if (isset($outfit['top']) && !empty($outfit['top']['photo_url'])) {
                    $thumbnail_url = htmlspecialchars($outfit['top']['photo_url']);
                } elseif (isset($outfit['bottom']) && !empty($outfit['bottom']['photo_url'])) {
                    $thumbnail_url = htmlspecialchars($outfit['bottom']['photo_url']);
                } elseif (isset($outfit['shoes']) && !empty($outfit['shoes']['photo_url'])) {
                    $thumbnail_url = htmlspecialchars($outfit['shoes']['photo_url']);
                }
                
                $outfit_name = htmlspecialchars($outfit['name'] ?? 'Tenue sans nom');
                $outfit_id = htmlspecialchars($outfit['outfit_id']);
        ?>

        <a href="edit_outfit.php?id=<?php echo $outfit_id; ?>" class="outfit-card">
            <div class="card-image-wrapper">
                <img src="<?php echo $thumbnail_url; ?>" alt="<?php echo $outfit_name; ?>" class="card-image">
            </div>
            <div class="card-name"><?php echo $outfit_name; ?></div>
        </a>

        <?php
            endforeach;
        endif;
        ?>

    </div> </div> <?php
// Étape 5: Inclure le footer
require_once __DIR__ . '/includes/footer.php';
?>