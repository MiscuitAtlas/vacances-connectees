document.getElementById("proposer-form").addEventListener("submit", function(event) {
    event.preventDefault(); // Empêche le formulaire de se soumettre normalement

    // Récupérer les valeurs du formulaire
    const nomLogement = document.getElementById("nom-logement").value;
    const localisation = document.getElementById("localisation").value;
    const nombreChambres = document.getElementById("nombre-chambres").value;
    const nombrePersonnes = document.getElementById("nombre-personnes").value;
    const prixNuit = document.getElementById("prix-nuit").value;
    const description = document.getElementById("description").value;

    // Envoyer les données à votre serveur (à remplacer par votre logique d'envoi côté serveur)
    // Vous pouvez utiliser fetch() ou toute autre méthode d'envoi de requête HTTP

    // Exemple de requête fetch (à adapter selon votre backend)
    fetch("/api/proposer-logement", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            nomLogement: nomLogement,
            localisation: localisation,
            nombreChambres: nombreChambres,
            nombrePersonnes: nombrePersonnes,
            prixNuit: prixNuit,
            description: description
        })
    })
    .then(response => response.json())
    .then(data => {
        // Traiter la réponse du serveur (si nécessaire)
        console.log(data);
        // Rediriger l'utilisateur vers une page de confirmation ou effectuer d'autres actions
    })
    .catch(error => {
        // Gérer les erreurs d'envoi de la requête
        console.error(error);
    });
});