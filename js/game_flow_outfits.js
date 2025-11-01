document.addEventListener("DOMContentLoaded", () => {

    // Conteneurs
    const loadingScreen = document.getElementById("vote-loading");
    const votingScreen = document.getElementById("vote-item-container");
    const waitingScreen = document.getElementById("waiting-container");

    // Éléments de vote
    const progressBar = document.getElementById("progress-bar");
    const progressText = document.getElementById("progress-text");
    
    // Éléments de la carte Tenue
    const imgTop = document.getElementById("item-image-top");
    const imgBottom = document.getElementById("item-image-bottom");
    const imgShoes = document.getElementById("item-image-shoes");
    const outfitName = document.getElementById("outfit-name");
    const outfitOwner = document.getElementById("outfit-owner");
    
    // Éléments communs
    const ratingContainer = document.getElementById("rating-container");
    const selfVoteMessage = document.getElementById("self-vote-message");
    const starButtons = document.querySelectorAll(".star-btn");

    let currentOutfitIndex = 0;
    let outfits = ALL_OUTFITS; // Vient du PHP
    let votes = EXISTING_VOTES;   // Vient du PHP
    let pollingInterval = null;

    // --- Fonction 1: Démarrer l'application de vote ---
    function initVotingApp() {
        loadingScreen.style.display = 'none';
        
        // Trouver le premier article NON voté par l'utilisateur
        currentOutfitIndex = outfits.findIndex(outfit => {
            const outfitVotes = votes[outfit.owner_id] || {}; // L'ID est l'owner_id
            return outfitVotes[CURRENT_USER_ID] === undefined;
        });

        if (currentOutfitIndex === -1) {
            // L'utilisateur a déjà voté pour tout
            showWaitingScreen();
        } else {
            // Afficher l'article
            displayOutfit(currentOutfitIndex);
            votingScreen.style.display = 'block';
        }
    }

    // --- Fonction 2: Afficher une tenue ---
    function displayOutfit(index) {
        if (index >= outfits.length) {
            showWaitingScreen();
            return;
        }

        const outfit = outfits[index];
        const isOwner = (outfit.owner_id === CURRENT_USER_ID);

        // Mettre à jour la progression
        const progressPercent = ((index + 1) / outfits.length) * 100;
        progressBar.style.width = `${progressPercent}%`;
        progressText.textContent = `Tenue ${index + 1} / ${outfits.length}`;

        // Mettre à jour les infos
        imgTop.src = outfit.top.photo_url;
        imgBottom.src = outfit.bottom.photo_url;
        imgShoes.src = outfit.shoes.photo_url;
        outfitName.textContent = outfit.name;
        outfitOwner.textContent = `par ${outfit.owner_email}`;

        // Réinitialiser les affichages
        ratingContainer.style.display = 'none';
        selfVoteMessage.style.display = 'none';
        starButtons.forEach(b => b.classList.remove('selected'));

        // Gérer les cas
        if (isOwner) {
            selfVoteMessage.style.display = 'block';
            // Passer automatiquement au suivant après un délai
            setTimeout(() => {
                displayOutfit(index + 1);
            }, 2000); // 2 sec
        } else {
            ratingContainer.style.display = 'block';
        }
    }

    // --- Fonction 3: Soumettre le vote (AJAX) ---
    async function submitVote(score) {
        const outfit = outfits[currentOutfitIndex];
        const ownerId = outfit.owner_id; // L'ID du sujet est l'ID du propriétaire

        // Désactiver les boutons
        ratingContainer.style.display = 'none';

        const formData = new FormData();
        formData.append('vote_type', 'outfit');
        formData.append('subject_id', ownerId); // ID du joueur
        formData.append('score', score);

        try {
            const response = await fetch('/api/submit_vote.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                // Succès, passer à l'article suivant
                currentOutfitIndex++;
                displayOutfit(currentOutfitIndex);
            } else {
                alert(`Erreur: ${result.message}`);
                // Ré-afficher les boutons si échec
                ratingContainer.style.display = 'block';
            }
        } catch (error) {
            alert("Erreur réseau. Veuillez réessayer.");
            ratingContainer.style.display = 'block';
        }
    }

    // --- Fonction 4: Afficher l'écran d'attente final ---
    function showWaitingScreen() {
        votingScreen.style.display = 'none';
        waitingScreen.style.display = 'flex';
        // Démarrer le polling pour la phase 3
        startPolling();
    }

    // --- Fonction 5: Polling (Poll pour les RÉSULTATS) ---
    async function pollGameStatus() {
        try {
            const response = await fetch('/api/game_poll.php', { method: 'POST' });
            const result = await response.json();

            if (result.success) {
                if (result.game_status === 'results') {
                    // C'est l'heure !
                    clearInterval(pollingInterval);
                    // On affiche le calcul...
                    waitingScreen.querySelector('h3').textContent = "Calcul des scores...";
                    waitingScreen.querySelector('p').textContent = "Redirection vers les résultats.";
                    setTimeout(() => {
                         window.location.href = `results.php?code=${GAME_CODE}`;
                    }, 1500);
                }
            } else {
                clearInterval(pollingInterval);
                waitingScreen.innerHTML = `<h3>Erreur de connexion</h3><p>${result.message}</p>`;
            }
        } catch (error) {
            clearInterval(pollingInterval);
            waitingScreen.innerHTML = '<h3>Erreur réseau</h3><p>Connexion perdue.</p>';
        }
    }

    function startPolling() {
        if (!pollingInterval) {
            pollGameStatus(); // Appel immédiat
            pollingInterval = setInterval(pollGameStatus, 3000);
        }
    }

    // --- Initialisation ---
    starButtons.forEach(button => {
        button.addEventListener("click", () => {
            const score = button.dataset.score;
            starButtons.forEach(b => b.classList.remove('selected'));
            button.classList.add('selected');
            setTimeout(() => submitVote(score), 200);
        });
    });

    // Démarrer
    initVotingApp();
});