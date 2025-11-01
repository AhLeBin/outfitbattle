document.addEventListener("DOMContentLoaded", () => {
    
    // --- Éléments du DOM ---
    const itemCards = document.querySelectorAll(".item-card");
    const modalOverlay = document.getElementById("item-modal-overlay");
    const modalContent = document.getElementById("item-modal-content");
    const closeModalBtn = document.getElementById("modal-close-btn");
    const cancelModalBtn = document.getElementById("modal-cancel-btn");
    
    // Formulaire
    const itemForm = document.getElementById("item-form");
    const modalTitle = document.getElementById("modal-title");
    const modalMessage = document.getElementById("modal-message");
    const saveBtn = document.getElementById("modal-save-btn");
    
    // Champs de formulaire
    const formOutfitId = document.getElementById("form-outfit-id"); // Est déjà rempli par PHP
    const formItemType = document.getElementById("form-item-type");
    const formPrice = document.getElementById("form-price");
    const formPhoto = document.getElementById("form-photo");
    // ... (on pourrait pré-remplir les autres champs, mais on garde simple pour l'instant)

    let currentEditingCard = null; // Stocke la carte (Haut, Bas, Chaussures) en cours d'édition

    // --- Fonctions Modal ---
    function openModal(cardElement) {
        currentEditingCard = cardElement;
        const itemType = cardElement.dataset.itemType;
        const typeName = itemType === 'top' ? 'Haut' : (itemType === 'bottom' ? 'Bas' : 'Chaussures');
        
        // 1. Réinitialiser le formulaire
        itemForm.reset();
        modalMessage.style.display = 'none';
        
        // 2. Configurer le formulaire
        modalTitle.textContent = `Ajouter / Modifier: ${typeName}`;
        formItemType.value = itemType;
        
        // 3. (Amélioration) Pré-remplir les données si elles existent (pour l'instant on se concentre sur 'new')
        // TODO: Lire les données de la carte et pré-remplir le formulaire
        
        // 4. Réinitialiser la validation
        validateForm(); 
        
        // 5. Afficher le modal
        modalOverlay.style.display = "flex";
    }

    function closeModal() {
        modalOverlay.style.display = "none";
        currentEditingCard = null;
    }

    // --- Validation (Client-side) ---
    function validateForm() {
        // Validation: Prix ET Photo sont requis (pour une *nouvelle* photo)
        const priceValid = formPrice.value.trim() !== "" && parseFloat(formPrice.value) >= 0;
        const photoValid = formPhoto.files.length > 0;

        // Note: Pour l'instant, on exige une photo à *chaque* sauvegarde.
        if (priceValid && photoValid) {
            saveBtn.disabled = false;
        } else {
            saveBtn.disabled = true;
        }
    }

    // --- Gestionnaire de messages (erreur/succès) ---
    function showModalMessage(message, isError = true) {
        modalMessage.textContent = message;
        modalMessage.className = isError ? 'message error' : 'message success';
    }

    // --- Écouteurs d'événements ---
    
    // Ouvrir le modal
    itemCards.forEach(card => {
        card.addEventListener("click", () => {
            openModal(card);
        });
    });

    // Fermer le modal
    closeModalBtn.addEventListener("click", closeModal);
    cancelModalBtn.addEventListener("click", closeModal);
    modalOverlay.addEventListener("click", (e) => {
        if (e.target === modalOverlay) {
            closeModal();
        }
    });

    // Validation en temps réel
    formPrice.addEventListener("input", validateForm);
    formPhoto.addEventListener("change", validateForm);

    // --- Soumission AJAX du formulaire ---
    itemForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        
        // Afficher un état de chargement
        saveBtn.disabled = true;
        saveBtn.textContent = "Sauvegarde...";
        modalMessage.style.display = "none";

        const formData = new FormData(itemForm);

        try {
            const response = await fetch('/api/save_item.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success && result.item) {
                // Succès !
                showModalMessage(result.message, false);
                
                // Mettre à jour la carte dynamiquement
                updateCardUI(currentEditingCard, result.item);

                // Fermer le modal après un court délai
                setTimeout(closeModal, 1500);

            } else {
                // Erreur
                showModalMessage(result.message || "Une erreur inconnue est survenue.");
            }

        } catch (error) {
            console.error("Erreur Fetch:", error);
            showModalMessage("Erreur réseau. Impossible de contacter le serveur.");
        } finally {
            // Rétablir le bouton
            saveBtn.disabled = false;
            saveBtn.textContent = "Sauvegarder";
        }
    });

    // --- Fonction de mise à jour de l'UI ---
    function updateCardUI(cardElement, item) {
        const content = cardElement.querySelector(".item-card-content");
        
        // Vider l'ancien contenu (placeholder)
        content.innerHTML = "";
        
        // Ajouter la nouvelle image
        // On ajoute un timestamp pour forcer le navigateur à rafraîchir le cache
        const imageUrl = `${item.photo_url}?t=${new Date().getTime()}`; 
        const img = document.createElement("img");
        img.src = imageUrl;
        img.alt = item.model;
        img.className = "item-photo";
        
        // Ajouter les détails
        const details = document.createElement("div");
        details.className = "item-details";
        details.innerHTML = `
            <div classclass="item-brand">${item.brand}</div>
            <div class="item-model">${item.model}</div>
        `;
        
        content.appendChild(img);
        content.appendChild(details);
    }
});