<?php
$user = 'root';
$password = '';
$database = 'projet';
$port = NULL;

// Connexion à la base de données
$conn = new mysqli('127.0.0.1', $user, $password, $database, $port);

$response = array(); // Tableau pour stocker la réponse

// Vérification si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Vérification si les champs requis sont renseignés
    if(isset($_POST['username']) && isset($_POST['email']) && isset($_POST['password'])) {
        // Échapper les données entrées pour éviter les attaques par injection SQL
        $username = $conn->real_escape_string($_POST['username']);
        $email = $conn->real_escape_string($_POST['email']);
        $password = $conn->real_escape_string($_POST['password']);

        // Hasher le mot de passe pour plus de sécurité
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Requête pour insérer un nouvel utilisateur dans la base de données
        $sql = "INSERT INTO utilisateurs (nom, email, mot_de_passe) VALUES ('$username', '$email', '$hashed_password')";

        // Exécuter la requête
        if ($conn->query($sql) === TRUE) {
            $response['success'] = true;
             // Redirection vers la page index.html après la création de compte
             header('Location: index.html');
        } else {
            $response['success'] = false;
            $response['message'] = "Erreur lors de la création du compte : " . $conn->error;
        }
    } elseif(isset($_POST['email']) && isset($_POST['password'])) {
        // Échapper les données entrées pour éviter les attaques par injection SQL
        $email = $conn->real_escape_string($_POST['email']);
        $password = $conn->real_escape_string($_POST['password']);

        // Requête pour récupérer l'utilisateur depuis la base de données
        $sql = "SELECT * FROM utilisateurs WHERE email='$email'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            // Utilisateur trouvé, vérification du mot de passe
            $user_data = $result->fetch_assoc();
            if (password_verify($password, $user_data['mot_de_passe'])) {
                // Mot de passe correct, connectez l'utilisateur
                session_start();
                $_SESSION['user_id'] = $user_data['id']; // Stocker l'ID de l'utilisateur dans la session
                $_SESSION['username'] = $user_data['nom'];
                $response['success'] = true;
            } else {
                $response['success'] = false;
                $response['message'] = "Mot de passe incorrect pour l'adresse email : $email.";
            }
        } else {
            $response['success'] = false;
            $response['message'] = "Aucun utilisateur trouvé avec l'adresse email : $email.";
        }
    } else {
        $response['success'] = false;
        $response['message'] = "Tous les champs requis doivent être renseignés.";
    }
}

// Fermeture de la connexion à la base de données
$conn->close();

// Renvoyer la réponse au format JSON
header('Content-Type: application/json');
echo json_encode($response);
?>
