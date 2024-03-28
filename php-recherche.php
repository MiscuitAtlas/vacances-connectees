<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style-reservation.css">
    <link rel="icon" href="logo.jpg" type="image/x-icon">
    <title>Résultats de la Recherche - VacancesConnectées</title>
</head>

<body>
    <header>
        <a href="recherche.html">
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

    <section class="search-results">
        <?php
        // Connexion à la base de données
        $user = 'root';
        $password = '';
        $database = 'projet';
        $host = 'localhost';
        $port = NULL;

        $conn = new mysqli($host, $user, $password, $database, $port);

        // Vérification de la connexion
        if ($conn->connect_error) {
            die("La connexion a échoué : " . $conn->connect_error);
        }

        // Récupération des données du formulaire
        $destination = $_POST['destination'];
        $check_in = $_POST['check-in'];
        $check_out = $_POST['check-out'];
        $chambres = $_POST['chambres'];
        $personnes = $_POST['personnes'];
        $min_price = $_POST['min-price'];
        $max_price = $_POST['max-price'];

        // Requête SQL pour rechercher des logements
        $sql = "SELECT * FROM logements WHERE localisation LIKE '%$destination%' 
                AND nombre_chambres >= $chambres 
                AND nombre_personnes >= $personnes 
                AND prix >= $min_price 
                AND prix <= $max_price";

        $result = $conn->query($sql);

        // Vérification si des logements sont trouvés
        if ($result->num_rows > 0) {
            // Affichage des résultats
            while ($row = $result->fetch_assoc()) {
                echo "Nom du logement: " . $row["nom"] . "<br>";
                echo "Localisation: " . $row["localisation"] . "<br>";
                echo "Prix: " . $row["prix"] . "<br>";
                echo "Nombre de chambres: " . $row["nombre_chambres"] . "<br>";
                echo "Nombre de personnes: " . $row["nombre_personnes"] . "<br>";
                if (array_key_exists("annonce", $row)) {
                    echo "Description: " . $row["annonce"] . "<br>";
                } else {
                    echo "Aucune Annonce disponible.<br>";
                }
                // Bouton Réserver
                echo "<form action='reservation.php' method='post'>";
                echo "<input type='hidden' name='logement_id' value='" . $row["id"] . "'>";
                echo "<input type='hidden' name='check_in' value='$check_in'>";
                echo "<input type='hidden' name='check_out' value='$check_out'>";
                echo "<br>";
                echo "<button type='submit'>Réserver</button>";
                echo "</form>";
                echo "<br>";
            }
        } else {
            echo "Aucun logement trouvé pour les critères spécifiés.";
        }

        // Fermeture de la connexion
        $conn->close();
        ?>
    </section>

    <footer>
        <p>&copy; 2024 VacancesConnectées. Tous droits réservés.</p>
    </footer>
</body>

</html>
