document.addEventListener("DOMContentLoaded", () => {
    
    // Éléments du formulaire
    const authForm = document.getElementById("auth-form");
    const authMessage = document.getElementById("auth-message");
    const confirmGroup = document.getElementById("confirm-password-group");

    // Champs
    const usernameField = document.getElementById("auth-username");
    const passwordField = document.getElementById("auth-password");
    const confirmField = document.getElementById("auth-confirm-password");

    // Boutons
    const loginBtn = document.getElementById("login-btn");
    const showRegisterBtn = document.getElementById("show-register-btn");
    const registerBtn = document.getElementById("register-btn");
    const showLoginBtn = document.getElementById("show-login-btn");

    // --- Gestionnaire de messages ---
    function showMessage(message, isError = true) {
        authMessage.textContent = message;
        authMessage.className = isError ? 'message error' : 'message success';
        authMessage.style.display = "block";
    }

    // --- Bascule vers l'inscription ---
    showRegisterBtn.addEventListener("click", () => {
        loginBtn.style.display = "none";
        showRegisterBtn.style.display = "none";
        
        registerBtn.style.display = "block";
        showLoginBtn.style.display = "block";
        confirmGroup.style.display = "block";
        
        authMessage.style.display = "none";
        confirmField.required = true;
    });

    // --- Bascule vers la connexion ---
    showLoginBtn.addEventListener("click", () => {
        loginBtn.style.display = "block";
        showRegisterBtn.style.display = "block";
        
        registerBtn.style.display = "none";
        showLoginBtn.style.display = "none";
        confirmGroup.style.display = "none";

        authMessage.style.display = "none";
        confirmField.required = false;
    });

    // --- Gestion de la soumission (Connexion) ---
    loginBtn.addEventListener("click", async (e) => {
        e.preventDefault();
        await handleSubmit('login');
    });

    // --- Gestion de la soumission (Inscription) ---
    registerBtn.addEventListener("click", async (e) => {
        e.preventDefault();
        
        // Validation côté client
        if (passwordField.value !== confirmField.value) {
            showMessage("Les mots de passe ne correspondent pas.");
            return;
        }
        if (passwordField.value.length < 6) {
            showMessage("Le mot de passe doit faire au moins 6 caractères.");
            return;
        }
        
        await handleSubmit('register');
    });

    // --- Fonction de soumission générique ---
    async function handleSubmit(action) {
        authMessage.style.display = "none";
        
        const formData = new FormData(authForm);
        // Ajoute l'action au FormData
        formData.append('action', action);

        try {
            const response = await fetch('/api/auth.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showMessage(result.message, false);
                // Redirection vers le hub
                setTimeout(() => {
                    window.location.href = '/hub.php';
                }, 1000);
            } else {
                showMessage(result.message);
            }

        } catch (error) {
            console.error("Erreur Fetch:", error);
            showMessage("Erreur réseau. Veuillez réessayer.");
        }
    }
});