document.addEventListener("DOMContentLoaded", () => {

    const participantList = document.getElementById("participant-list");
    const startGameBtn = document.getElementById("start-game-btn");
    const startGameMessage = document.getElementById("start-game-message");
    const lobbyErrorMessage = document.getElementById("lobby-error-message");
    
    let pollingInterval = null;

    // --- Fonction 1: Mise à jour de l'UI avec les données du poll
    function updateLobbyUI(data) {
        
        // 1. Vérifier si le jeu a changé de statut
        if (data.game_status !== 'lobby') {
            clearInterval(pollingInterval); // Arrêter le polling
            // Rediriger tout le monde vers la phase 1
            window.location.href = `game_phase_1.php?code=${GAME_CODE}`;
            return;
        }

        // 2. Mettre à jour la liste des participants
        participantList.innerHTML = ""; // Vider la liste
        let playerCount = 0;

        data.participants.forEach(p => {
            const li = document.createElement("li");
            
            let roleText = p.role === 'joueur' ? 'Joueur' : 'Juge';
            let hostText = p.user_id === data.host_id ? ' <span class="host-tag">[Hôte]</span>' : '';
            
            li.innerHTML = `
                <span class="p-email">${p.email}</span>
                <span class="p-role p-role-${p.role}">${roleText}</span>
                ${hostText}
            `;
            participantList.appendChild(li);

            if (p.role === 'joueur') {
                playerCount++;
            }
        });

        // 3. Mettre à jour le bouton de l'hôte (s'il existe)
        if (IS_HOST && startGameBtn) {
            if (playerCount >= 2) {
                startGameBtn.disabled = false;
                startGameMessage.textContent = "Prêt à lancer !";
                startGameMessage.className = "status-message success";
            } else {
                startGameBtn.disabled = true;
                startGameMessage.textContent = `Minimum 2 Joueurs requis (${playerCount} actuel).`;
                startGameMessage.className = "status-message";
            }
        }
    }

    // --- Fonction 2: Appel de l'API de Polling ---
    async function pollGameStatus() {
        try {
            const response = await fetch('/api/lobby_poll.php', {
                method: 'POST', // Utiliser POST pour éviter le caching
                headers: { 'Content-Type': 'application/json' }
            });

            if (!response.ok) {
                throw new Error("Erreur réseau lors du polling.");
            }

            const result = await response.json();

            if (result.success) {
                updateLobbyUI(result);
            } else {
                // Afficher une erreur si le poll échoue (ex: session expirée)
                showLobbyError(result.message);
                clearInterval(pollingInterval);
            }
        } catch (error) {
            console.error("Erreur de polling:", error);
            showLobbyError("Connexion au serveur perdue.");
            clearInterval(pollingInterval);
        }
    }

    // --- Fonction 3: Action de Démarrage (Hôte) ---
    async function startGame() {
        if (!IS_HOST || !startGameBtn) return;

        startGameBtn.disabled = true;
        startGameBtn.textContent = "Lancement...";
        lobbyErrorMessage.style.display = 'none';

        try {
            const response = await fetch('/api/start_game.php', { method: 'POST' });
            const result = await response.json();

            if (!result.success) {
                // Si le démarrage échoue (ex: pas assez de joueurs), ré-activer le bouton
                showLobbyError(result.message);
                startGameBtn.disabled = false;
                startGameBtn.textContent = "Lancer la Partie";
            }
            // Si le démarrage réussit, le prochain poll (qui arrive)
            // détectera le changement de statut et redirigera tout le monde.

        } catch (error) {
            showLobbyError("Erreur réseau lors du lancement.");
            startGameBtn.disabled = false;
        }
    }

    function showLobbyError(message) {
        lobbyErrorMessage.textContent = message;
        lobbyErrorMessage.style.display = 'block';
    }

    // --- Initialisation ---
    if (startGameBtn) {
        startGameBtn.addEventListener("click", startGame);
    }

    // Démarrer le polling
    pollGameStatus(); // Appel immédiat au chargement
    pollingInterval = setInterval(pollGameStatus, 3000); // Puis toutes les 3 secondes
});