-- Schéma MySQL / MariaDB de VacancesConnectées.
-- Les tables sont créées dans l'ordre de leurs dépendances.

CREATE TABLE utilisateurs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(191) NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    cree_le DATETIME NOT NULL,
    UNIQUE KEY uniq_utilisateurs_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE logements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    proprietaire_id INT UNSIGNED NOT NULL,
    titre VARCHAR(120) NOT NULL,
    type VARCHAR(20) NOT NULL,
    cadre VARCHAR(20) NOT NULL,
    ville VARCHAR(100) NOT NULL,
    region VARCHAR(100) NOT NULL,
    prix_nuit INT UNSIGNED NOT NULL,
    chambres TINYINT UNSIGNED NOT NULL,
    capacite TINYINT UNSIGNED NOT NULL,
    description TEXT NOT NULL,
    equipements VARCHAR(255) NOT NULL DEFAULT '',
    photo VARCHAR(64) NULL,
    cree_le DATETIME NOT NULL,
    KEY idx_logements_ville (ville),
    CONSTRAINT fk_logements_proprietaire FOREIGN KEY (proprietaire_id)
        REFERENCES utilisateurs (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reservations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    logement_id INT UNSIGNED NOT NULL,
    voyageur_id INT UNSIGNED NOT NULL,
    arrivee DATE NOT NULL,
    depart DATE NOT NULL,
    voyageurs TINYINT UNSIGNED NOT NULL,
    prix_total INT UNSIGNED NOT NULL,
    statut VARCHAR(12) NOT NULL DEFAULT 'confirmee',
    cree_le DATETIME NOT NULL,
    KEY idx_reservations_periode (logement_id, statut, arrivee, depart),
    KEY idx_reservations_voyageur (voyageur_id),
    CONSTRAINT fk_reservations_logement FOREIGN KEY (logement_id)
        REFERENCES logements (id) ON DELETE CASCADE,
    CONSTRAINT fk_reservations_voyageur FOREIGN KEY (voyageur_id)
        REFERENCES utilisateurs (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE avis (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT UNSIGNED NOT NULL,
    logement_id INT UNSIGNED NOT NULL,
    auteur_id INT UNSIGNED NOT NULL,
    note TINYINT UNSIGNED NOT NULL,
    commentaire TEXT NOT NULL,
    cree_le DATETIME NOT NULL,
    UNIQUE KEY uniq_avis_reservation (reservation_id),
    KEY idx_avis_logement (logement_id),
    CONSTRAINT fk_avis_reservation FOREIGN KEY (reservation_id)
        REFERENCES reservations (id) ON DELETE CASCADE,
    CONSTRAINT fk_avis_logement FOREIGN KEY (logement_id)
        REFERENCES logements (id) ON DELETE CASCADE,
    CONSTRAINT fk_avis_auteur FOREIGN KEY (auteur_id)
        REFERENCES utilisateurs (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Liens de réinitialisation du mot de passe : seule l'empreinte SHA-256 du jeton est stockée.
CREATE TABLE jetons_reinitialisation (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT UNSIGNED NOT NULL,
    empreinte CHAR(64) NOT NULL,
    expire_le DATETIME NOT NULL,
    utilise_le DATETIME NULL,
    cree_le DATETIME NOT NULL,
    UNIQUE KEY uniq_jetons_empreinte (empreinte),
    CONSTRAINT fk_jetons_utilisateur FOREIGN KEY (utilisateur_id)
        REFERENCES utilisateurs (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Échecs de connexion récents, pour bloquer les essais de mots de passe en série.
CREATE TABLE echecs_connexion (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empreinte_email CHAR(64) NOT NULL,
    cree_le DATETIME NOT NULL,
    KEY idx_echecs_email (empreinte_email, cree_le)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
