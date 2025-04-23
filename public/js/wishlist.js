/**
 * Gestion des interactions Wishlist sans rechargement de page
 */
document.addEventListener('DOMContentLoaded', function () {
    // Sélectionner tous les liens de wishlist
    const wishlistLinks = document.querySelectorAll('a[href*="wishlist_add"], a[href*="wishlist_remove"]');

    wishlistLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();

            const url = this.getAttribute('href');
            const isAdding = url.includes('wishlist_add');

            // Effectuer la requête AJAX
            fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Mettre à jour l'interface utilisateur
                        const icon = this.querySelector('i');
                        const text = this.querySelector('span');

                        if (isAdding) {
                            // Transformation en "retirer de la wishlist"
                            icon.classList.remove('bi-heart');
                            icon.classList.add('bi-heart-fill');
                            this.classList.remove('text-gray-600');
                            this.classList.add('text-red-600');
                            text.textContent = 'Dans ma liste de souhaits';

                            // Mettre à jour le lien pour permettre la suppression
                            const productId = url.split('/').pop();
                            this.setAttribute('href', `/wishlist/remove/${productId}`);
                        } else {
                            // Transformation en "ajouter à la wishlist"
                            icon.classList.remove('bi-heart-fill');
                            icon.classList.add('bi-heart');
                            this.classList.remove('text-red-600');
                            this.classList.add('text-gray-600');
                            text.textContent = 'Ajouter à ma liste de souhaits';

                            // Mettre à jour le lien pour permettre l'ajout
                            const productId = url.split('/').pop();
                            this.setAttribute('href', `/wishlist/add/${productId}`);
                        }

                        // Afficher un message temporaire
                        const message = isAdding ? 'Produit ajouté à votre wishlist' : 'Produit retiré de votre wishlist';
                        showFlashMessage(message, 'success');
                    } else {
                        showFlashMessage(data.message || 'Une erreur est survenue', 'error');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    showFlashMessage('Une erreur est survenue', 'error');
                });
        });
    });

    // Fonction pour afficher un message flash temporaire
    function showFlashMessage(message, type) {
        const flashContainer = document.getElementById('flash-messages');
        if (!flashContainer) {
            const newContainer = document.createElement('div');
            newContainer.id = 'flash-messages';
            newContainer.className = 'fixed top-4 right-4 z-50';
            document.body.appendChild(newContainer);
        }

        const messageElement = document.createElement('div');
        messageElement.className = `py-2 px-4 rounded shadow-md ${type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`;
        messageElement.textContent = message;

        const container = document.getElementById('flash-messages') || newContainer;
        container.appendChild(messageElement);

        // Supprimer le message après 3 secondes
        setTimeout(() => {
            messageElement.remove();
        }, 3000);
    }
});