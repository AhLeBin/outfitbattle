<?php
// Étape 1: Sécuriser la page
require_once __DIR__ . '/includes/auth_guard.php';

// Étape 2: Inclure le header
require_once __DIR__ . '/includes/header.php';
?>

<div class="main-content">
    
    <nav class="breadcrumb">
        <a href="hub.php">Hub</a> &gt; <span>Jouer</span>
        <a href="logout.php" class="logout-link" style="float: right;">[ Déconnexion ]</a>
    </nav>

    <h2>Rejoindre une Battle</h2>
    <p class="subtitle" id="lobby-subtitle">Pour commencer, choisissez votre rôle.</p>

    <div id="role-selection-container" class="lobby-step-container">
        <div class="role-selector">
            
            <div class="role-card" id="role-joueur" data-role="joueur">
                <span class="role-icon">⚔️</span>
                <span class="role-title">Joueur</span>
                <span class="role-desc">Vous soumettez une tenue et notez les autres.</span>
            </div>
            
            <div class="role-card" id="role-juge" data-role="juge">
                <span class="role-icon">⚖️</span>
                <span class="role-title">Juge</span>
                <span class="role-desc">Vous ne jouez pas, vous notez uniquement.</span>
            </div>

        </div>
    </div>

    <div id="join-game-container" class="lobby-step-container" style="display: none;">
        
        <input type="hidden" id="selected-role" value="">
        
        <div class="join-options">
            
            <div class="join-card">
                <h3>Créer une partie</h3>
                <p>Générez un code et partagez-le avec vos amis.</p>
                <button class="btn btn-primary" id="create-game-btn">Créer & Obtenir un Code</button>
            </div>

            <div class="join-card">
                <h3>Rejoindre une partie</h3>
                <p>Entrez un code de 4 caractères pour rejoindre un lobby.</p>
                <form id="join-game-form">
                    <input type="text" id="game-code-input" class="code-input" placeholder="ABCD" maxlength="4" autocapitalize="characters" required>
                    <button type="submit" class="btn btn-secondary">Rejoindre</button>
                </form>
            </div>
        </div>
        
        <div id="lobby-message" class="message" style="display: none; margin-top: 1.5rem;"></div>

        <button id="change-role-btn" class="btn-link">Changer de rôle</button>
    </div>

</div>

<script src="/js/game_lobby.js?v=1.1"></script>

<?php
// Étape 3: Inclure le footer
require_once __DIR__ . '/includes/footer.php';
?>