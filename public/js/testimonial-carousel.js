// < !--Script pour le carroussel des 3 testimonials-- >
document.addEventListener("DOMContentLoaded", function () {
    const slider = document.querySelector(".testimonial-slider");
    const prevBtn = document.querySelector(".prev-btn");
    const nextBtn = document.querySelector(".next-btn");
    const indicators = document.querySelectorAll(".indicator");
    let currentIndex = 0;

    // Fonction pour mettre à jour le carrousel
    function updateSlider() {
        slider.style.transform = `translateX(-${currentIndex * 100
            }%)`;

        // Mettre à jour les indicateurs
        indicators.forEach((indicator, index) => {
            if (index === currentIndex) {
                indicator.classList.add("bg-gold", "active-indicator");
                indicator.classList.remove("bg-gray-300");
            } else {
                indicator.classList.remove("bg-gold", "active-indicator");
                indicator.classList.add("bg-gray-300");
            }
        });
    }

    // Evenements pour les boutons précédent et suivant
    prevBtn.addEventListener("click", () => {
        currentIndex = (currentIndex - 1 + 3) % 3;
        updateSlider();
    });

    nextBtn.addEventListener("click", () => {
        currentIndex = (currentIndex + 1) % 3;
        updateSlider();
    });

    // Evenements pour les indicateurs.
    indicators.forEach((indicator) => {
        indicator.addEventListener("click", () => {
            currentIndex = parseInt(indicator.dataset.index);
            updateSlider();
        });
    });

    // Interval de changement automatique reglé à 5 secondes
    setInterval(() => {
        currentIndex = (currentIndex + 1) % 3;
        updateSlider();
    }, 5000);
});
