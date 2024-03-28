document.addEventListener("DOMContentLoaded", function() {
    // Récupération des éléments du DOM
    const toggleNewPassword = document.getElementById("toggleNewPassword");
    const toggleConfirmPassword = document.getElementById("toggleConfirmPassword");
    const newPasswordInput = document.getElementById("newPassword");
    const confirmPasswordInput = document.getElementById("confirmPassword");

    // Fonction pour afficher ou masquer le champ de mot de passe
    function togglePasswordVisibility(inputField, toggleIcon) {
        if (inputField.type === "password") {
            inputField.type = "text";
            toggleIcon.classList.remove("fa-eye-slash");
            toggleIcon.classList.add("fa-eye");
        } else {
            inputField.type = "password";
            toggleIcon.classList.remove("fa-eye");
            toggleIcon.classList.add("fa-eye-slash");
        }
    }

    // Écouteurs d'événements pour basculer la visibilité des mots de passe
    toggleNewPassword.addEventListener("click", function() {
        togglePasswordVisibility(newPasswordInput, toggleNewPassword);
    });

    toggleConfirmPassword.addEventListener("click", function() {
        togglePasswordVisibility(confirmPasswordInput, toggleConfirmPassword);
    });

    // Événement pour afficher les sections de nouveau mot de passe et de confirmation une fois l'email saisi
    const emailInput = document.getElementById("email");
    emailInput.addEventListener("input", function(event) {
        newPasswordSection.style.display = "block";
        confirmPasswordSection.style.display = "block";
        resetPasswordButton.style.display = "block";
    });
});

