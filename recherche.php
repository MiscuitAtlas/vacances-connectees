<?php
$user = 'root';
$password = '';
$database = 'projet';
$port = NULL;

$conn = new mysqli('127.0.0.1', $user, $password, $database, $port);

if ($conn->connect_error) {
    die("Échec de la connexion : " . $conn->connect_error);
} else {
    echo "Connexion réussie !";
}

// Opérations sur la base de données ...

$conn->close();
?>
