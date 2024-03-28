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

// Récupérer l'historique des réservations de l'utilisateur
$stmt_reservations = $conn->prepare("SELECT r.date_arrivee, r.date_depart, l.nom, l.localisation FROM reservations r INNER JOIN logements l ON r.logement_id = l.id WHERE r.utilisateur_id = ?");
$stmt_reservations->bind_param('i', $user_id);
$stmt_reservations->execute();
$result_reservations = $stmt_reservations->get_result();
$historique_reservations = $result_reservations->fetch_all(MYSQLI_ASSOC);
?>