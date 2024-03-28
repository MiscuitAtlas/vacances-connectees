//Afficher / cacher les mdp
const passwordField = document.getElementById('password');
const togglePassword = document.getElementById('togglePassword');
const createPasswordField = document.getElementById('createPassword');
const toggleCreatePassword = document.getElementById('toggleCreatePassword');


togglePassword.addEventListener('click', togglePasswordVisibility.bind(null, passwordField, togglePassword));
toggleCreatePassword.addEventListener('click', togglePasswordVisibility.bind(null, createPasswordField, toggleCreatePassword));


function togglePasswordVisibility(passwordField, eyeIcon) {
    const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordField.setAttribute('type', type);
    eyeIcon.classList.toggle('fa-eye-slash');
    eyeIcon.classList.toggle('fa-eye');
}

//Mot de passe
const forgotPasswordLink = document.getElementById('forgot-password-link');

// Gérer le clic sur le lien "Mot de passe oublié ?"
document.getElementById('forgot-password-link').addEventListener('click', function(event) {
    event.preventDefault(); // Empêcher le comportement par défaut du lien
    window.location.href = "mdp.php"; // Rediriger vers la page "mdp.php"
});


//Afficher les erreurs
document.getElementById('connexion-form').addEventListener('submit', function(event) {
    event.preventDefault(); // Empêche le formulaire de se soumettre normalement
    
    // Récupère les valeurs des champs
    var email = document.getElementById('email').value;
    var password = document.getElementById('password').value;

    // Envoie une requête AJAX au serveur
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'php-connexion.php', true);
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState == XMLHttpRequest.DONE) {
            if (xhr.status == 200) {
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    // Si la connexion est réussie, redirigez l'utilisateur
                    window.location.href = 'index.html';
                } else {
                    // Affichez le message d'erreur à côté du champ d'adresse e-mail
                    document.getElementById('email-error-message').textContent = response.message;
                }
            } else {
                console.error('Erreur lors de la requête : ' + xhr.status);
            }
        }
    };
    // Envoie des données du formulaire
    var params = 'email=' + encodeURIComponent(email) + '&password=' + encodeURIComponent(password);
    xhr.send(params);
});
