document.addEventListener("DOMContentLoaded", () => {

    const selectionContainer = document.getElementById("selection-container");
    const waitingContainer = document.getElementById("waiting-container");
    const selectionGallery = document.querySelector(".selection-gallery");

    let pollingInterval = null;

    // --- Fonction 1: Soumettre la sélection (pour les Joueurs) ---
    async function submitSelection(outfitId) {
        
        // Trouver la carte cliquée et la désactiver
        const selectedCard = document.querySelector(`.outfit-card[data-outfit-id="${outfitId}"]`);
        if (selectedCard) {
            selectedCard.classList.add('selected');
        }

        // Désactiver toute la galerie pour éviter double-clic
        selectionGallery.classList.add('disabled');

        const formData = new FormData();
        formData.append('outfit_id', outfitId);

        try {
            const response = await fetch('/api/submit_selection.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                // C'est bon, on passe à l'écran d'attente
                selectionContainer.style.display = 'none';
                waitingContainer.style.display = 'flex';
                startPolling(); // Démarrer le polling
            } else {
                alert(`Erreur: ${result.message}`);
                // Ré-activer la galerie
                selectionGallery.classList.remove('disabled');
                if (selectedCard) {
                    selectedCard.classList.remove('selected');
                }
            }
        } catch (error) {
            alert("Erreur réseau. Veuillez réessayer.");
            selectionGallery.classList.remove('disabled');
        }
    }

    // --- Fonction 2: Polling (pour tout le monde en attente) ---
    async function pollGameStatus() {
        try {
            const response = await fetch('/api/game_poll.php', { method: 'POST' });
            const result = await response.json();

            if (result.success) {
                if (result.game_status === 'phase2') {
                    // C'est l'heure !
                    clearInterval(pollingInterval);
                    window.location.href = `game_phase_2.php?code=${GAME_CODE}`;
                }
                // Si 'phase1', on ne fait rien et on attend le prochain poll.
            } else {
                // Erreur de polling
                clearInterval(pollingInterval);
                waitingContainer.innerHTML = `<h3>Erreur de connexion</h3><p>${result.message}</p>`;
            }
        } catch (error) {
            clearInterval(pollingInterval);
            waitingContainer.innerHTML = '<h3>Erreur réseau</h3><p>Connexion perdue.</p>';
        }
    }

    function startPolling() {
        if (!pollingInterval) {
            pollingInterval = setInterval(pollGameStatus, 3000);
        }
    }

    // --- Initialisation ---
    
    if (USER_ROLE === 'joueur' && !HAS_SELECTED) {
        // Le joueur doit choisir
        const outfitCards = document.querySelectorAll(".outfit-card:not(.incomplete)");
        outfitCards.forEach(card => {
            card.addEventListener("click", () => {
                const outfitId = card.dataset.outfitId;
                // Simple confirmation
                if (confirm("Valider cette tenue ? Vous ne pourrez pas changer.")) {
                    submitSelection(outfitId);
                }
            });
        });
    } else {
        // L'utilisateur est un Juge, OU un Joueur qui a déjà voté
        // On démarre le polling immédiatement
        startPolling();
        pollGameStatus(); // Appel immédiat
    }
});