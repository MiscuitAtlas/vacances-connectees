CREATE TABLE logements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    nom VARCHAR(100) NOT NULL,
    localisation VARCHAR(100) NOT NULL,
    prix DECIMAL(10, 2) NOT NULL,
    nombre_chambres INT NOT NULL,
    nombre_personnes INT NOT NULL,
    annonce TEXT,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)
);

CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    logement_id INT NOT NULL,
    date_arrivee DATE NOT NULL,
    date_depart DATE NOT NULL,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id),
    FOREIGN KEY (logement_id) REFERENCES logements(id)
);

CREATE TABLE utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    mot_de_passe VARCHAR(100) NOT NULL
);