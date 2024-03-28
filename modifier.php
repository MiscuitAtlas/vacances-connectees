<?php
session_start();

// Vérification si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Vérification si l'ancien mot de passe et le nouveau mot de passe ont été soumis
    if (isset($_POST["password"]) && isset($_POST["newPassword"])) {
        // Récupération de l'ancien mot de passe et du nouveau mot de passe soumis
        $old_password = $_POST["password"];
        $new_password = $_POST["newPassword"];

        // Vérification si l'utilisateur est connecté
        if (isset($_SESSION['user_id'])) {
            // Récupération de l'ID de l'utilisateur connecté
            $user_id = $_SESSION['user_id'];

            // Connexion à la base de données
            $servername = "localhost";
            $username = "root";
            $password = ""; // Assurez-vous de mettre votre mot de passe ici si vous en avez un
            $database = "projet";
            $port = NULL;

            // Création de la connexion
            $conn = new mysqli($servername, $username, $password, $database);

            // Vérification de la connexion
            if ($conn->connect_error) {
                die("La connexion a échoué : " . $conn->connect_error);
            }

            // Récupération du mot de passe actuel de l'utilisateur depuis la base de données
            $stmt_password = $conn->prepare("SELECT mot_de_passe FROM utilisateurs WHERE id = ?");
            $stmt_password->bind_param('i', $user_id);
            $stmt_password->execute();
            $result_password = $stmt_password->get_result();
            $row = $result_password->fetch_assoc();
            $current_password_hash = $row['mot_de_passe']; // Mot de passe haché actuel

            // Vérification si l'ancien mot de passe correspond au mot de passe actuel de l'utilisateur
            if (password_verify($old_password, $current_password_hash)) {
                // Hachage du nouveau mot de passe
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                // Mise à jour du mot de passe de l'utilisateur dans la base de données
                $stmt_update_password = $conn->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?");
                $stmt_update_password->bind_param('si', $hashed_password, $user_id);
                $stmt_update_password->execute();

                // Redirection vers la page compte.php avec un message de succès
                $_SESSION['success_message'] = "Mot de passe mis à jour avec succès.";
                header("Location: compte.php"); 
                exit;
            } else {
                // Si l'ancien mot de passe ne correspond pas au mot de passe actuel, stocker un message d'erreur en session
                $_SESSION['error_message'] = "Ancien mot de passe incorrect. Veuillez réessayer.";
            }

            // Fermeture de la connexion à la base de données
            $conn->close();
        } else {
            // Si l'utilisateur n'est pas connecté, rediriger vers la page de connexion
            header("Location: connexion.html");
            exit;
        }
    } else {
        // Si l'ancien mot de passe et/ou le nouveau mot de passe n'ont pas été soumis, stocker un message d'erreur en session
        $_SESSION['error_message'] = "Veuillez remplir tous les champs.";
    }
}

// Redirection vers la page précédente avec le message d'erreur
header("Location: compte.php?error=" . urlencode($_SESSION['error_message']));
?>
