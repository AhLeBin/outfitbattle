document.addEventListener("DOMContentLoaded", () => {

    // Conteneurs
    const loadingScreen = document.getElementById("vote-loading");
    const votingScreen = document.getElementById("vote-item-container");
    const waitingScreen = document.getElementById("waiting-container");

    // Éléments de vote
    const progressBar = document.getElementById("progress-bar");
    const progressText = document.getElementById("progress-text");
    const itemImage = document.getElementById("item-image");
    const itemTypeModel = document.getElementById("item-type-model");
    const itemBrand = document.getElementById("item-brand");
    const ratingContainer = document.getElementById("rating-container");
    const selfVoteMessage = document.getElementById("self-vote-message");
    const alreadyVotedMessage = document.getElementById("already-voted-message");
    const starButtons = document.querySelectorAll(".star-btn");

    let currentArticleIndex = 0;
    let articles = ALL_ARTICLES; // Vient du PHP
    let votes = EXISTING_VOTES;   // Vient du PHP
    let pollingInterval = null;

    // --- Fonction 1: Démarrer l'application de vote ---
    function initVotingApp() {
        loadingScreen.style.display = 'none';
        
        // Trouver le premier article NON voté par l'utilisateur
        currentArticleIndex = articles.findIndex(article => {
            const articleVotes = votes[article.item_id] || {};
            return articleVotes[CURRENT_USER_ID] === undefined;
        });

        if (currentArticleIndex === -1) {
            // L'utilisateur a déjà voté pour tout
            showWaitingScreen();
        } else {
            // Afficher l'article
            displayArticle(currentArticleIndex);
            votingScreen.style.display = 'block';
        }
    }

    // --- Fonction 2: Afficher un article ---
    function displayArticle(index) {
        if (index >= articles.length) {
            showWaitingScreen();
            return;
        }

        const article = articles[index];
        const isOwner = (article.owner_id === CURRENT_USER_ID);

        // Mettre à jour la progression
        const progressPercent = ((index + 1) / articles.length) * 100;
        progressBar.style.width = `${progressPercent}%`;
        progressText.textContent = `Article ${index + 1} / ${articles.length}`;

        // Mettre à jour les infos
        itemImage.src = article.photo_url;
        itemTypeModel.textContent = `${article.item_type} - ${article.model}`;
        itemBrand.textContent = article.brand;

        // Réinitialiser les affichages
        ratingContainer.style.display = 'none';
        selfVoteMessage.style.display = 'none';
        alreadyVotedMessage.style.display = 'none';
        
        // Réinitialiser les étoiles
        starButtons.forEach(b => b.classList.remove('selected'));

        // Gérer les cas
        if (isOwner) {
            selfVoteMessage.style.display = 'block';
            // Passer automatiquement au suivant après un délai
            setTimeout(() => {
                displayArticle(index + 1);
            }, 2000); // 2 sec
        } else {
            ratingContainer.style.display = 'block';
        }
    }

    // --- Fonction 3: Soumettre le vote (AJAX) ---
    async function submitVote(score) {
        const article = articles[currentArticleIndex];
        const itemId = article.item_id;

        // Désactiver les boutons
        ratingContainer.style.display = 'none';

        const formData = new FormData();
        formData.append('vote_type', 'item');
        formData.append('subject_id', itemId);
        formData.append('score', score);

        try {
            const response = await fetch('/api/submit_vote.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                // Succès, passer à l'article suivant
                currentArticleIndex++;
                displayArticle(currentArticleIndex);
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

    // --- Fonction 5: Polling (identique à phase 1) ---
    async function pollGameStatus() {
        try {
            const response = await fetch('/api/game_poll.php', { method: 'POST' });
            const result = await response.json();

            if (result.success) {
                if (result.game_status === 'phase3') {
                    // C'est l'heure !
                    clearInterval(pollingInterval);
                    window.location.href = `game_phase_3.php?code=${GAME_CODE}`;
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
            // Visuel : marquer le bouton
            starButtons.forEach(b => b.classList.remove('selected'));
            button.classList.add('selected');
            // Envoyer le vote
            setTimeout(() => submitVote(score), 200); // Léger délai pour le feedback visuel
        });
    });

    // Démarrer
    initVotingApp();
});