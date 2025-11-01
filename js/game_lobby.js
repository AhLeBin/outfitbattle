document.addEventListener("DOMContentLoaded", () => {
    
    // Conteneurs
    const roleSelectionContainer = document.getElementById("role-selection-container");
    const joinGameContainer = document.getElementById("join-game-container");
    
    // Éléments interactifs
    const roleCards = document.querySelectorAll(".role-card");
    const selectedRoleInput = document.getElementById("selected-role");
    const changeRoleBtn = document.getElementById("change-role-btn");
    
    // Formulaires
    const createGameBtn = document.getElementById("create-game-btn");
    const joinGameForm = document.getElementById("join-game-form");
    const gameCodeInput = document.getElementById("game-code-input");
    
    // Message
    const lobbyMessage = document.getElementById("lobby-message");
    const lobbySubtitle = document.getElementById("lobby-subtitle");

    // --- Étape 1: Choix du Rôle ---
    roleCards.forEach(card => {
        card.addEventListener("click", () => {
            const role = card.dataset.role;
            const roleName = role === 'joueur' ? 'Joueur' : 'Juge';
            
            selectedRoleInput.value = role;
            
            // Masquer la sélection de rôle
            roleSelectionContainer.style.display = "none";
            
            // Afficher la sélection de partie
            joinGameContainer.style.display = "block";
            lobbySubtitle.textContent = `Vous avez choisi le rôle: ${roleName}.`;
        });
    });

    // --- Retour au choix du Rôle ---
    changeRoleBtn.addEventListener("click", (e) => {
        e.preventDefault();
        joinGameContainer.style.display = "none";
        roleSelectionContainer.style.display = "block";
        lobbySubtitle.textContent = "Pour commencer, choisissez votre rôle.";
        lobbyMessage.style.display = "none";
    });

    // --- Fonction d'affichage des messages ---
    function showLobbyMessage(message, isError = true) {
        lobbyMessage.textContent = message;
        lobbyMessage.className = isError ? 'message error' : 'message success';
    }
    
    // --- Fonction de redirection ---
    function redirectToGame(gameCode) {
        // Redirige vers la salle d'attente
        window.location.href = `game_session.php?code=${gameCode}`;
    }

    // --- AJAX: Créer une partie ---
    createGameBtn.addEventListener("click", async () => {
        createGameBtn.disabled = true;
        createGameBtn.textContent = "Création...";
        lobbyMessage.style.display = "none";

        const formData = new FormData();
        formData.append('role', selectedRoleInput.value);

        try {
            const response = await fetch('/api/create_game.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                showLobbyMessage(result.message, false);
                setTimeout(() => redirectToGame(result.code), 1000);
            } else {
                showLobbyMessage(result.message);
            }

        } catch (error) {
            showLobbyMessage("Erreur réseau. Veuillez réessayer.");
        } finally {
            createGameBtn.disabled = false;
            createGameBtn.textContent = "Créer & Obtenir un Code";
        }
    });
    
    // --- AJAX: Rejoindre une partie ---
    joinGameForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        const joinButton = joinGameForm.querySelector("button[type='submit']");
        joinButton.disabled = true;
        joinButton.textContent = "Recherche...";
        lobbyMessage.style.display = "none";
        
        const gameCode = gameCodeInput.value.toUpperCase();

        const formData = new FormData();
        formData.append('role', selectedRoleInput.value);
        formData.append('code', gameCode);

        try {
            const response = await fetch('/api/join_game.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                showLobbyMessage(result.message, false);
                setTimeout(() => redirectToGame(gameCode), 1000);
            } else {
                showLobbyMessage(result.message);
            }

        } catch (error) {
            showLobbyMessage("Erreur réseau. Veuillez réessayer.");
        } finally {
            joinButton.disabled = false;
            joinButton.textContent = "Rejoindre";
        }
    });

    // --- UX: Mettre en majuscule le code ---
    gameCodeInput.addEventListener('input', (e) => {
        e.target.value = e.target.value.toUpperCase();
    });
});