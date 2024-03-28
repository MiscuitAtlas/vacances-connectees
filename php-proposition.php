<?php
$user = 'root';
$password = '';
$database = 'projet';
$port = NULL;

// Connexion à la base de données
$conn = new mysqli('127.0.0.1', $user, $password, $database, $port);

// Vérifier la connexion
if ($conn->connect_error) {
    echo "Erreur de connexion à la base de données : " . $conn->connect_error;
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt_logements = $conn->prepare("SELECT l.nom, l.localisation, l.prix, l.annonce FROM logements l WHERE l.utilisateur_id = ?");
$stmt_logements->bind_param('i', $user_id);
$stmt_logements->execute();
$result_logements = $stmt_logements->get_result();
$logements_proposes = $result_logements->fetch_all(MYSQLI_ASSOC);
?>
