<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.html");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style-compte.css">
    <link rel="icon" href="logo.jpg" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <title>Mon Compte - VacancesConnectées</title>
</head>

<body>
    <header>
        <a href="index.html">
            <img src="logo.jpg" alt="Logo VacancesConnectées" id="logo">
        </a>
        <h1>VacancesConnectées</h1>
        <nav>
            <ul>
                <li><a href="index.html">Accueil</a></li>
                <li><a href="recherche.html">Rechercher</a></li>
                <li><a href="proposer.html">Proposer un logement</a></li>
                <li><a href="compte.php">Mon Compte</a></li>
            </ul>
        </nav>
    </header>

    <section class="compte-section">
        <br>
        <h2>Historique des Réservations</h2>
        <div id="historique-reservations">
            <?php
            include 'php-compte.php';
            
            if (empty($historique_reservations)): ?>
                <p>Aucun historique de réservation.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($historique_reservations as $reservation): ?>
                        <li>
                            <p>Logement : <?php echo $reservation['nom']; ?></p>
                            <p>Localisation : <?php echo $reservation['localisation']; ?></p>
                            <p>Date d'arrivée : <?php echo $reservation['date_arrivee']; ?></p>
                            <p>Date de départ : <?php echo $reservation['date_depart']; ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    
        <h2>Historique des Logements Proposés</h2>
        <div id="logements-proposes">
            <?php
                include 'php-proposition.php';
            
            if (empty($logements_proposes)): ?>
                <p>Aucun logement proposé.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($logements_proposes as $logement): ?>
                        <li>
                            <p>Nom du logement : <?php echo $logement['nom']; ?></p>
                            <p>Localisation : <?php echo $logement['localisation']; ?></p>
                            <p>Prix par nuit : <?php echo $logement['prix']; ?> €</p>
                            <p>Description : <?php echo $logement['annonce']; ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        
        <h2>Changement de mot de passe</h2>
        <form action="modifier.php" method="post">
            <div id="password-section">
                <div class="icon-container">
                    <p>Veuillez entrer votre mot de passe</p>
                    <i class="fas fa-lock"></i> 
                    <input type="password" name="password" id="Password" placeholder="Mot de Passe" required>
                    <i class="fas fa-eye-slash" id="togglePassword"></i>
                </div>
            </div>
            <div id="new-password-section">
                <div class="icon-container">
                    <p>Veuillez entrer votre nouveau mot de passe</p>
                    <i class="fas fa-lock"></i> 
                    <input type="password" name="newPassword" id="newPassword" placeholder="Nouveau Mot de Passe" required>
                    <i class="fas fa-eye-slash" id="toggleNewPassword"></i>
                </div>
            </div>
            <br>
            <input type="hidden" name="action" value="change_password">
            <button type="submit">Modifier</button>
        </form>

        <h2>Déconnexion</h2>
        <form action="deconnexion.php" method="post">
            <input type="hidden" name="deconnexion" value="true">
            <button id="deconnexion-btn" type="submit">Déconnexion</button>
        </form>

    </section>

    <script src="script-compte.js"></script>

    <br><br><br><br><br>
    <footer>
        <p>&copy; 2024 VacancesConnectées. Tous droits réservés.</p>
    </footer>
</body>

</html>
