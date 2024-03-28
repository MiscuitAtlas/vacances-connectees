<?php
session_start();

// Connexion à la base de données
$servername = "localhost";
$username = "root";
$password = ""; // Assurez-vous de mettre votre mot de passe ici si vous en avez un
$database = "projet";
$port = NULL;

// Création de la connexion
$conn = new mysqli($servername, $username, $password, $database); // Utilisez $database au lieu de $dbname

// Vérification de la connexion
if ($conn->connect_error) {
    die("La connexion a échoué : " . $conn->connect_error);
}

// Vérification si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Vérification si l'adresse e-mail a été soumise
    if (isset($_POST["email"])) {
        // Récupération de l'adresse e-mail soumise
        $email = $_POST["email"];

        // Vérification si l'adresse e-mail existe dans la base de données
        $checkEmailSql = "SELECT * FROM utilisateurs WHERE email = '$email'";
        $emailResult = $conn->query($checkEmailSql);

        if ($emailResult->num_rows > 0) {
            // L'email existe dans la base de données, vérifions si les mots de passe correspondent
            if ($_POST["password"] === $_POST["confirmPassword"]) {
                // Les mots de passe correspondent, donc nous changeons le mot de passe de l'utilisateur concerné
                // Récupération du nouveau mot de passe soumis
                $newPassword = $_POST["password"];
                
                // Hachage du nouveau mot de passe
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT); 

                // Mise à jour du mot de passe dans la base de données
                $updateSql = "UPDATE utilisateurs SET mot_de_passe = '$hashedPassword' WHERE email = '$email'";
                if ($conn->query($updateSql) === TRUE) {// Redirection vers la page de connexion
                    header("Location: connexion.html");
                    exit; // Assurez-vous de quitter le script après la redirection
                } else {
                    // En cas d'erreur lors de la mise à jour du mot de passe dans la base de données
                    $_SESSION['error_message'] = "Une erreur s'est produite lors de la réinitialisation du mot de passe. Veuillez réessayer.";
                }
            } else {
                // Si les mots de passe ne correspondent pas, stocker un message d'erreur en session
                $_SESSION['error_message'] = "Les mots de passe ne correspondent pas. Veuillez réessayer.";
            }
        } else {
            // Si l'email n'existe pas dans la base de données, stocker un message d'erreur en session
            $_SESSION['error_message'] = "Aucun compte associé à cette adresse e-mail.";
        }
    } else {
        // Si l'adresse e-mail n'a pas été soumise, stocker un message d'erreur en session
        $_SESSION['error_message'] = "Une erreur s'est produite. Veuillez réessayer.";
    }
}

// Redirection vers la page précédente avec le message d'erreur
header("Location: mdp.php?error=" . urlencode($_SESSION['error_message']));

// Fermeture de la connexion à la base de données
$conn->close();
?>
