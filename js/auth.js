document.addEventListener("DOMContentLoaded", () => {

    const loginFormContainer = document.getElementById("login-form-container");
    const registerFormContainer = document.getElementById("register-form-container");
    const showRegisterLink = document.getElementById("show-register");
    const showLoginLink = document.getElementById("show-login");

    const loginForm = document.getElementById("login-form");
    const registerForm = document.getElementById("register-form");

    const loginMessage = document.getElementById("login-message");
    const registerMessage = document.getElementById("register-message");

    // --- Bascule entre les formulaires ---
    showRegisterLink.addEventListener("click", (e) => {
        e.preventDefault();
        loginFormContainer.style.display = "none";
        registerFormContainer.style.display = "block";
        loginMessage.style.display = "none";
    });

    showLoginLink.addEventListener("click", (e) => {
        e.preventDefault();
        registerFormContainer.style.display = "none";
        loginFormContainer.style.display = "block";
        registerMessage.style.display = "none";
    });

    // --- Gestionnaire de messages (erreur/succès) ---
    function showMessage(element, message, isError = true) {
        element.textContent = message;
        element.className = isError ? 'message error' : 'message success';
    }

    // --- Soumission du formulaire d'inscription ---
    registerForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        registerMessage.style.display = "none";

        const email = document.getElementById("register-email").value;
        const password = document.getElementById("register-password").value;
        const confirmPassword = document.getElementById("register-confirm-password").value;

        // 1. Validation côté client
        if (password !== confirmPassword) {
            showMessage(registerMessage, "Les mots de passe ne correspondent pas.");
            return;
        }
        if (password.length < 6) {
            showMessage(registerMessage, "Le mot de passe doit faire au moins 6 caractères.");
            return;
        }

        // 2. Préparation des données pour l'API
        const formData = new FormData();
        formData.append('email', email);
        formData.append('password', password);

        // 3. Appel AJAX (Fetch)
        try {
            const response = await fetch('/api/register.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showMessage(registerMessage, result.message, false);
                // On bascule vers le formulaire de connexion après succès
                setTimeout(() => {
                    showLoginLink.click();
                    loginMessage.textContent = "Compte créé. Veuillez vous connecter.";
                    loginMessage.className = 'message success';
                    document.getElementById("login-email").value = email; // Pré-remplir l'email
                }, 2000);
            } else {
                showMessage(registerMessage, result.message);
            }

        } catch (error) {
            showMessage(registerMessage, "Erreur réseau. Veuillez réessayer.");
        }
    });


    // --- Soumission du formulaire de connexion ---
    loginForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        loginMessage.style.display = "none";

        const formData = new FormData(loginForm);

        try {
            const response = await fetch('/api/login.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showMessage(loginMessage, result.message, false);
                // Redirection vers le hub
                setTimeout(() => {
                    window.location.href = '/hub.php';
                }, 1000);
            } else {
                showMessage(loginMessage, result.message);
            }

        } catch (error) {
            showMessage(loginMessage, "Erreur réseau. Veuillez réessayer.");
        }
    });
});