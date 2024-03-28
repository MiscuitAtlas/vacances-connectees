const searchForm = document.getElementById("search-form");

searchForm.addEventListener("submit", function(event) {
    event.preventDefault();

    const destination = document.getElementById("destination").value;
    const checkInDate = document.getElementById("check-in").value;
    const checkOutDate = document.getElementById("check-out").value;
    const chambres = document.getElementById("chambres").value;
    const personnes = document.getElementById("personnes").value;
    const minPrice = document.getElementById("min-price").value;
    const maxPrice = document.getElementById("max-price").value;

    // Effectuer la logique de recherche en utilisant les valeurs récupérées

    // Simuler des résultats de recherche (à remplacer par votre logique de recherche réelle)
    const results = [
        {
            nom: "Logement 1",
            localisation: "Destination 1",
            prix: 100,
            chambres: 2,
            personnes: 4
        },
        {
            nom: "Logement 2",
            localisation: "Destination 2",
            prix: 150,
            chambres: 3,
            personnes: 6
        }
    ];

    // Afficher les résultats de la recherche (à remplacer par votre propre méthode d'affichage)
    displayResults(results);
});

function displayResults(results) {
    const resultsContainer = document.getElementById("search-results");
    resultsContainer.innerHTML = ""; // Efface les résultats précédents

    if (results.length === 0) {
        resultsContainer.innerHTML = "<p>Aucun logement trouvé.</p>";
    } else {
        results.forEach(logement => {
            const logementElement = document.createElement("div");
            logementElement.className = "logement";
            logementElement.innerHTML = `<h3>${logement.nom}</h3>
                                         <p>Localisation : ${logement.localisation}</p>
                                         <p>Prix : ${logement.prix} €</p>
                                         <p>Chambres : ${logement.chambres}</p>
                                         <p>Personnes : ${logement.personnes}</p>`;
            resultsContainer.appendChild(logementElement);
        });
    }
}