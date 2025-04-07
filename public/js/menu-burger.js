// Menu burger pour mobile
document.addEventListener("DOMContentLoaded", function () {
    const menuToggle = document.getElementById("menu-toggle");
    const mobileMenu = document.getElementById("mobile-menu");

    if (menuToggle && mobileMenu) {
        // Basculer le menu lors du clic sur le bouton burger
        menuToggle.addEventListener("click", function (event) {
            event.stopPropagation(); // Empêcher la propagation pour éviter la fermeture immédiate
            mobileMenu.classList.toggle("open");
            
        });

        // Fermeture menu clic sur un clic exterieur fenetre
        document.addEventListener("click", function (event) {
            if (!mobileMenu.contains(event.target) && !menuToggle.contains(event.target) && mobileMenu.classList.contains("open")) {
                mobileMenu.classList.remove("open");
            }
        });
    }
});
