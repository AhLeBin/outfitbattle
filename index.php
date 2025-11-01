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
        <h1>Connexion</h1>
        <form id="login-form">
            <div id="login-message" class="message"></div>
            <div class="input-group">
                <label for="login-email">Email</label>
                <input type="email" id="login-email" name="email" required>
            </div>
            <div class="input-group">
                <label for="login-password">Mot de passe</label>
                <input type="password" id="login-password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">Entrer</button>
        </form>
        <p class="toggle-link">Pas de compte ? <a href="#" id="show-register">S'inscrire</a></p>
    </div>

    <div id="register-form-container" style="display: none;">
        <h1>Inscription</h1>
        <form id="register-form">
            <div id="register-message" class="message"></div>
            <div class="input-group">
                <label for="register-email">Email</label>
                <input type="email" id="register-email" name="email" required>
            </div>
            <div class="input-group">
                <label for="register-password">Mot de passe</label>
                <input type="password" id="register-password" name="password" required>
            </div>
            <div class="input-group">
                <label for="register-confirm-password">Confirmer le mot de passe</label>
                <input type="password" id="register-confirm-password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-primary">Créer le compte</button>
        </form>
        <p class="toggle-link">Déjà un compte ? <a href="#" id="show-login">Se connecter</a></p>
    </div>

</div>

<script src="/js/auth.js"></script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>