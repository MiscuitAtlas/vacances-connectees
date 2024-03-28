document.addEventListener("DOMContentLoaded", function() {
    const passwordField = document.getElementById('Password');
    const togglePassword = document.getElementById('togglePassword');
    const createPasswordField = document.getElementById('newPassword');
    const toggleCreatePassword = document.getElementById('toggleNewPassword');

    togglePassword.addEventListener('click', togglePasswordVisibility.bind(null, passwordField, togglePassword));
    toggleCreatePassword.addEventListener('click', togglePasswordVisibility.bind(null, createPasswordField, toggleCreatePassword));

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
});