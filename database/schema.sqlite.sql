-- Schéma SQLite de VacancesConnectées, identique au schéma MySQL.
-- Il sert aux tests et à la démonstration sans serveur de base de données.

CREATE TABLE utilisateurs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nom TEXT NOT NULL,
    email TEXT NOT NULL COLLATE NOCASE UNIQUE,
    mot_de_passe TEXT NOT NULL,
    cree_le TEXT NOT NULL
);

CREATE TABLE logements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    proprietaire_id INTEGER NOT NULL REFERENCES utilisateurs (id) ON DELETE CASCADE,
    titre TEXT NOT NULL,
    type TEXT NOT NULL,
    cadre TEXT NOT NULL,
    ville TEXT NOT NULL,
    region TEXT NOT NULL,
    prix_nuit INTEGER NOT NULL,
    chambres INTEGER NOT NULL,
    capacite INTEGER NOT NULL,
    description TEXT NOT NULL,
    equipements TEXT NOT NULL DEFAULT '',
    photo TEXT NULL,
    cree_le TEXT NOT NULL
);

CREATE INDEX idx_logements_ville ON logements (ville);

CREATE TABLE reservations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    logement_id INTEGER NOT NULL REFERENCES logements (id) ON DELETE CASCADE,
    voyageur_id INTEGER NOT NULL REFERENCES utilisateurs (id) ON DELETE CASCADE,
    arrivee TEXT NOT NULL,
    depart TEXT NOT NULL,
    voyageurs INTEGER NOT NULL,
    prix_total INTEGER NOT NULL,
    statut TEXT NOT NULL DEFAULT 'confirmee',
    cree_le TEXT NOT NULL
);

CREATE INDEX idx_reservations_periode ON reservations (logement_id, statut, arrivee, depart);
CREATE INDEX idx_reservations_voyageur ON reservations (voyageur_id);

CREATE TABLE avis (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    reservation_id INTEGER NOT NULL UNIQUE REFERENCES reservations (id) ON DELETE CASCADE,
    logement_id INTEGER NOT NULL REFERENCES logements (id) ON DELETE CASCADE,
    auteur_id INTEGER NOT NULL REFERENCES utilisateurs (id) ON DELETE CASCADE,
    note INTEGER NOT NULL,
    commentaire TEXT NOT NULL,
    cree_le TEXT NOT NULL
);

CREATE INDEX idx_avis_logement ON avis (logement_id);

CREATE TABLE jetons_reinitialisation (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    utilisateur_id INTEGER NOT NULL REFERENCES utilisateurs (id) ON DELETE CASCADE,
    empreinte TEXT NOT NULL UNIQUE,
    expire_le TEXT NOT NULL,
    utilise_le TEXT NULL,
    cree_le TEXT NOT NULL
);

CREATE TABLE echecs_connexion (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    empreinte_email TEXT NOT NULL,
    cree_le TEXT NOT NULL
);

CREATE INDEX idx_echecs_email ON echecs_connexion (empreinte_email, cree_le);
