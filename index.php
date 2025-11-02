<?php
require_once __DIR__ . '/includes/header.php';

// Si l'utilisateur est déjà connecté, on le redirige vers le hub
if (isset($_SESSION['user_id'])) {
    header('Location: /hub.php');
    exit;
}
?>

<div class="auth-container">

    <div id="login-form-container">
        <h1>Connexion / Inscription</h1>
        <form id="auth-form">
            <div id="auth-message" class="message" style="display: none;"></div>
            
            <div class="input-group">
                <label for="auth-username">Nom d'utilisateur</label>
                <input type="text" id="auth-username" name="username" required>
            </div>
            
            <div class="input-group">
                <label for="auth-password">Mot de passe</label>
                <input type="password" id="auth-password" name="password" required>
            </div>

            <div class="input-group" id="confirm-password-group" style="display: none;">
                <label for="auth-confirm-password">Confirmer le mot de passe</label>
                <input type="password" id="auth-confirm-password" name="confirm_password">
            </div>

            <div class="form-actions" style="display: flex; flex-direction: column; gap: 1rem;">
                <button type="submit" id="login-btn" class="btn btn-primary">Se connecter</button>
                <button type="button" id="show-register-btn" class="btn btn-secondary">Créer un compte</button>
                <button type="submit" id="register-btn" class="btn btn-primary" style="display: none;">Valider l'inscription</button>
                <button type="button" id="show-login-btn" class="btn-link" style="display: none; margin-top: 0;">Annuler</button>
            </div>
        </form>
    </div>

</div>

<script src="/js/auth.js?v=1.1"></script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>