<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style-mdp.css">
    <link rel="icon" href="logo.jpg" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <title>Réinitialisation de mot de passe - VacancesConnectées</title>
</head>
<body>
    <header>
        <a href="connexion.html">
            <img src="logo.jpg" alt="Logo VacancesConnectées" id="logo">
        </a>
        <h1>VacancesConnectées</h1>
    </header>
    <section class="compte-section">
        <br><br>
        <h2>Réinitialisation de mot de passe</h2>
        <div class="connexion-utilisateur">
            <div class="connection-bar">
                <form id="password-recovery-form" action="php-mdp.php" method="post">
                    <div class="left">
                        <p>Veuillez entrer votre adresse e-mail</p>
                        <div class="icon-container">
                            <i class="fas fa-envelope"></i>
                            <input type="text" name="email" id="email" placeholder="Adresse Email" required>
                        </div>
                    </div>
                    <div id="new-password-section">
                        <div class="icon-container">
                            <p>Veuillez entrer votre nouveau mot de passe</p>
                            <i class="fas fa-lock"></i> 
                            <input type="password" name="password" id="newPassword" placeholder="Nouveau Mot de Passe" required>
                            <i class="fas fa-eye-slash" id="toggleNewPassword"></i>
                        </div>
                    </div>
                    <div id="confirm-password-section">
                        <div class="icon-container">
                            <p>Veuillez confirmer le mot de passe</p>
                            <i class="fas fa-lock"></i> 
                            <input type="password" name="confirmPassword" id="confirmPassword" placeholder="Confirmer le Mot de Passe" required>
                            <i class="fas fa-eye-slash" id="toggleConfirmPassword"></i>
                        </div>
                        <br>
                        <div id="email-error-message" style="color: red;"></div>
                        <div id="error-message" style="color: red;">
                            <?php
                            // Vérifie si un message d'erreur est présent dans l'URL
                            if (isset($_GET['error'])) {
                                // Affiche le message d'erreur
                                echo htmlspecialchars($_GET['error']);
                            }
                            ?>
                        </div>
                        <div class="reset-password-button-container">
                            <button id="reset-password-button">Réinitialiser le mot de passe</button>
                        </div>
                    </div>
                </form>                              
            </div>
        </div>
    </section>
    
    <script src="script-mdp.js"></script>

    <footer>
        <p>&copy; 2024 VacancesConnectées. Tous droits réservés.</p>
    </footer>
</body>
</html>
