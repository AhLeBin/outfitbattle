<?php
// Étape 1: Sécuriser la page.
// auth_guard.php inclut functions.php (qui démarre la session)
// et vérifie si $_SESSION['user_id'] existe.
require_once __DIR__ . '/includes/auth_guard.php';

// Étape 2: Inclure le header
require_once __DIR__ . '/includes/header.php';
?>

<div class="hub-container">

    <div class="user-info">
        Connecté en tant que: 
        <strong><?php echo htmlspecialchars($_SESSION['user_email']); ?></strong>
        <a href="logout.php" class="logout-link">[ Déconnexion ]</a>
    </div>

    <div class="hub-title">
        <h1>OUTFT BATTLE</h1>
        <h2>Que voulez-vous faire ?</h2>
    </div>

    <div class="hub-actions">
        
        <a href="create_dashboard.php" class="action-button create-button">
            <span class="button-icon">🎨</span>
            <span class="button-title">CRÉER</span>
            <span class="button-subtitle">Gérer vos tenues</span>
        </a>
        
        <a href="play_lobby.php" class="action-button play-button">
            <span class="button-icon">⚔️</span>
            <span class="button-title">JOUER</span>
            <span class="button-subtitle">Lancer une battle</span>
        </a>

    </div>

</div>

<?php
// Étape 3: Inclure le footer
require_once __DIR__ . '/includes/footer.php';
?>