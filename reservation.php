<?php
session_start();

// Vérification si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // Redirection vers la page de connexion si l'utilisateur n'est pas connecté
    header("Location: connexion.html");
    exit;
}

// Vérification si le formulaire de réservation a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupération des données de réservation
    $logement_id = $_POST['logement_id'];
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];
    $user_id = $_SESSION['user_id'];

    // Connexion à la base de données
    $servername = "localhost";
    $username = "root";
    $password = ""; // Mettez votre mot de passe si nécessaire
    $database = "projet";
    $port = NULL;

    $conn = new mysqli($servername, $username, $password, $database);

    // Vérification de la connexion
    if ($conn->connect_error) {
        die("La connexion a échoué : " . $conn->connect_error);
    }

    // Requête pour insérer la réservation dans la base de données
    $stmt_reservation = $conn->prepare("INSERT INTO reservations (utilisateur_id, logement_id, date_arrivee, date_depart) VALUES (?, ?, ?, ?)");
    $stmt_reservation->bind_param("iiss", $user_id, $logement_id, $check_in, $check_out);

    if ($stmt_reservation->execute()) {
        // Réservation réussie
        $_SESSION['success_message'] = "Réservation effectuée avec succès.";
        header("Location: compte.php");
        exit;
    } else {
        // Erreur lors de la réservation
        $_SESSION['error_message'] = "Erreur lors de la réservation. Veuillez réessayer.";
        header("Location: recherche.html");
        exit;
    }

    // Fermeture de la connexion
    $conn->close();
} else {
    // Redirection vers la page de recherche si le formulaire n'a pas été soumis
    header("Location: recherche.html");
    exit;
}
?>
