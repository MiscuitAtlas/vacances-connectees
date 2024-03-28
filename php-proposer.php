<?php
session_start();

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

// Récupération de l'ID de l'utilisateur à partir de la session
$user_id = $_SESSION['user_id'];

// Récupération des données du formulaire
$nom_logement = $_POST['nom-logement'];
$localisation = $_POST['localisation'];
$nombre_chambres = $_POST['nombre-chambres'];
$nombre_personnes = $_POST['nombre-personnes'];
$prix_nuit = $_POST['prix-nuit'];
$annonce = $_POST['annonce'];

// Requête SQL pour insérer les données dans la base de données
$sql = "INSERT INTO logements (utilisateur_id, nom, localisation, nombre_chambres, nombre_personnes, prix, annonce) 
        VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param('issiiis', $user_id, $nom_logement, $localisation, $nombre_chambres, $nombre_personnes, $prix_nuit, $annonce);

if ($stmt->execute()) {
    // Redirection vers une page de succès
    $_SESSION['success_message'] = "Logement ajouté avec succès";
    header("Location: compte.php"); 
    exit;
} else {
    echo "Erreur lors de la proposition du logement : " . $conn->error;
}

// Fermeture de la connexion
$stmt->close();
$conn->close();
?>
