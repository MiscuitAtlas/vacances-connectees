<?php

declare(strict_types=1);

namespace App\Demo;

use App\Service\Passwords;
use DateTimeImmutable;
use PDO;

/**
 * Données de démonstration, entièrement fictives : comptes, logements, séjours et avis.
 * Les dates sont calculées à partir du jour de l'installation.
 */
final class DemoData
{
    public const PASSWORD = 'vacances-demo';

    private const USERS = [
        'camille' => ['Camille Dupont', 'demo@exemple.fr'],
        'helene' => ['Hélène Marchal', 'hote@exemple.fr'],
        'thomas' => ['Thomas Garnier', 'thomas@exemple.fr'],
        'ines' => ['Inès Benali', 'ines@exemple.fr'],
        'paul' => ['Paul Lefèvre', 'paul@exemple.fr'],
        'julie' => ['Julie Roche', 'julie@exemple.fr'],
        'marc' => ['Marc Aubert', 'marc@exemple.fr'],
        'sophie' => ['Sophie Navarro', 'sophie@exemple.fr'],
    ];

    /**
     * Propriétaire, titre, type, cadre, ville, région, prix, chambres, voyageurs, équipements, description.
     */
    private const LISTINGS = [
        'quiberon' => ['helene', 'Maison de pêcheur face à l\'océan', 'maison', 'mer', 'Quiberon', 'Bretagne', 120, 2, 4,
            'wifi,cuisine,lave_linge,jardin',
            "Petite maison en granit sur la côte sauvage, à deux minutes à pied de la plage.\nLe jardin clos donne sur l'océan : on y dîne en regardant le coucher du soleil. Les commerces du port sont à dix minutes à vélo."],
        'chamonix' => ['thomas', 'Chalet en bois au pied des pistes', 'chalet', 'montagne', 'Chamonix-Mont-Blanc', 'Auvergne-Rhône-Alpes', 210, 3, 7,
            'wifi,cuisine,cheminee,parking,lave_linge',
            "Chalet traditionnel entièrement rénové, face au massif du Mont-Blanc.\nLa navette pour les remontées s'arrête devant la porte. Le soir, le poêle à bois et le grand salon accueillent toute la famille."],
        'gordes' => ['ines', 'Villa avec piscine dans les vignes', 'villa', 'campagne', 'Gordes', 'Provence-Alpes-Côte d\'Azur', 260, 4, 8,
            'wifi,cuisine,piscine,climatisation,parking,jardin',
            "Villa de plain-pied au milieu des vignes, avec une piscine chauffée de dix mètres.\nLe village perché de Gordes est à cinq minutes en voiture. Idéal pour deux familles ou un groupe d'amis."],
        'marseille' => ['ines', 'Appartement lumineux sur le Vieux-Port', 'appartement', 'ville', 'Marseille', 'Provence-Alpes-Côte d\'Azur', 95, 1, 3,
            'wifi,cuisine,climatisation,lave_linge',
            "Appartement au quatrième étage avec ascenseur, fenêtres sur le Vieux-Port.\nLe métro, les restaurants et les navettes vers les calanques sont au pied de l'immeuble."],
        'annecy' => ['helene', 'Cabane perchée au bord du lac', 'cabane', 'lac', 'Annecy', 'Auvergne-Rhône-Alpes', 140, 1, 2,
            'jardin,parking',
            "Cabane en bois perchée à quatre mètres, dans un parc arboré au bord du lac.\nUne terrasse pour le petit déjeuner, un ponton privé pour la baignade. Pour deux personnes, sans enfants en bas âge."],
        'erquy' => ['helene', 'Longère rénovée près des plages', 'maison', 'campagne', 'Erquy', 'Bretagne', 135, 3, 6,
            'wifi,cuisine,cheminee,jardin,parking,animaux',
            "Longère en pierre au calme, entre la lande et les plages du cap.\nGrande cuisine, cheminée et jardin de mille mètres carrés. Les chiens sont les bienvenus."],
        'lyon' => ['paul', 'Studio cosy au cœur du Vieux-Lyon', 'appartement', 'ville', 'Lyon', 'Auvergne-Rhône-Alpes', 75, 1, 2,
            'wifi,cuisine,lave_linge',
            "Studio sous les toits dans une rue piétonne du Vieux-Lyon.\nTout se fait à pied : traboules, bouchons, funiculaire de Fourvière. Le lit est en mezzanine."],
        'biarritz' => ['thomas', 'Villa contemporaine vue mer', 'villa', 'mer', 'Biarritz', 'Nouvelle-Aquitaine', 320, 4, 8,
            'wifi,piscine,climatisation,parking,jardin,cuisine',
            "Villa d'architecte au-dessus de la Côte des Basques, avec vue sur l'océan depuis chaque chambre.\nPiscine à débordement, terrasse de soixante mètres carrés et accès direct au sentier du littoral."],
        'saintlary' => ['paul', 'Chalet familial dans les Pyrénées', 'chalet', 'montagne', 'Saint-Lary-Soulan', 'Occitanie', 150, 3, 6,
            'cheminee,cuisine,parking,animaux',
            "Chalet au calme, à la sortie du village, avec un grand balcon plein sud.\nLe téléphérique est à cinq minutes. L'été, les randonnées vers les lacs partent de la maison."],
        'der' => ['thomas', 'Maison au bord du lac du Der', 'maison', 'lac', 'Giffaumont-Champaubert', 'Grand Est', 110, 2, 5,
            'wifi,jardin,parking,animaux,cuisine',
            "Maison à colombages à deux cents mètres du lac, sur la piste cyclable qui en fait le tour.\nEn automne, les grues cendrées passent au-dessus du jardin par milliers."],
        'capferret' => ['ines', 'Cabane dans les pins du bassin', 'cabane', 'mer', 'Lège-Cap-Ferret', 'Nouvelle-Aquitaine', 125, 1, 3,
            'wifi,jardin',
            "Ancienne cabane ostréicole aménagée, sous les pins, à trois cents mètres de la plage du bassin.\nDouche extérieure, vélos fournis et dégustation d'huîtres au port voisin."],
        'uzes' => ['paul', 'Mas provençal et son olivier', 'maison', 'campagne', 'Uzès', 'Occitanie', 180, 3, 6,
            'piscine,jardin,cuisine,climatisation,parking',
            "Mas en pierre de taille entouré de garrigue, avec une piscine sous un olivier centenaire.\nLe marché du samedi à Uzès est l'un des plus beaux de la région, à dix minutes."],
    ];

    /**
     * Voyageur, logement, arrivée (jours à partir d'aujourd'hui), nuits, voyageurs, statut, avis éventuel.
     */
    private const BOOKINGS = [
        ['camille', 'chamonix', 24, 5, 4, 'confirmee', null],
        ['camille', 'quiberon', -16, 6, 2, 'confirmee', null],
        ['camille', 'lyon', -70, 3, 2, 'confirmee', [5, 'Emplacement parfait pour découvrir la ville à pied. Le studio est petit mais très bien pensé.']],
        ['camille', 'biarritz', 40, 4, 6, 'annulee', null],
        ['julie', 'quiberon', -120, 7, 4, 'confirmee', [5, 'Une semaine magique face à l\'océan. La maison est simple et chaleureuse, on reviendra.']],
        ['marc', 'quiberon', -60, 4, 3, 'confirmee', [4, 'Très bien situé. La literie mériterait d\'être changée mais la vue fait tout oublier.']],
        ['sophie', 'chamonix', -200, 7, 6, 'confirmee', [5, 'Chalet superbe et très bien équipé. La navette devant la porte change la vie avec des enfants.']],
        ['julie', 'chamonix', -90, 3, 4, 'confirmee', [4, 'Belle vue sur le massif, accueil parfait. Un peu de bruit de la route le matin.']],
        ['marc', 'gordes', -150, 7, 8, 'confirmee', [5, 'La piscine et le calme des vignes : exactement ce qu\'on cherchait pour nos vacances entre amis.']],
        ['sophie', 'gordes', -45, 5, 6, 'confirmee', [5, 'Villa impeccable, propriétaire très disponible. Les couchers de soleil depuis la terrasse sont inoubliables.']],
        ['julie', 'marseille', -30, 3, 2, 'confirmee', [4, 'Vue incroyable sur le port. Un peu bruyant le samedi soir, prévoir des bouchons d\'oreille.']],
        ['marc', 'annecy', -100, 2, 2, 'confirmee', [5, 'Une nuit dans les arbres au bord de l\'eau, le ponton au réveil : une expérience à vivre.']],
        ['sophie', 'erquy', -75, 7, 5, 'confirmee', [4, 'Maison spacieuse et jardin immense pour le chien. Le chauffage met du temps à monter.']],
        ['julie', 'erquy', -20, 4, 4, 'confirmee', [5, 'On a adoré les soirées au coin du feu après les balades sur le cap. Tout était prêt à notre arrivée.']],
        ['marc', 'biarritz', -110, 5, 7, 'confirmee', [5, 'La vue depuis les chambres est spectaculaire. Une maison d\'exception.']],
        ['sophie', 'saintlary', -140, 6, 5, 'confirmee', [3, 'Chalet agréable et bien placé, mais la cuisine manque de vaisselle pour six.']],
        ['julie', 'der', -50, 4, 4, 'confirmee', [4, 'Parfait pour faire le tour du lac à vélo avec les enfants. Maison pleine de charme.']],
        ['marc', 'capferret', -35, 3, 2, 'confirmee', [5, 'Petit paradis sous les pins. Les vélos fournis sont un vrai plus.']],
        ['sophie', 'uzes', -160, 7, 6, 'confirmee', [5, 'Le mas est magnifique et la piscine sous l\'olivier, un rêve. Le marché d\'Uzès vaut le détour.']],
        ['julie', 'gordes', 10, 6, 5, 'confirmee', null],
        ['marc', 'quiberon', 8, 7, 4, 'confirmee', null],
        ['sophie', 'annecy', 3, 3, 2, 'confirmee', null],
        ['julie', 'biarritz', 18, 7, 8, 'confirmee', null],
        ['camille', 'erquy', 52, 7, 5, 'confirmee', null],
    ];

    public static function load(PDO $pdo, DateTimeImmutable $today): void
    {
        $now = $today->setTime(9, 0)->format('Y-m-d H:i:s');
        $hash = Passwords::hash(self::PASSWORD);

        $pdo->beginTransaction();
        $users = [];
        $insertUser = $pdo->prepare('INSERT INTO utilisateurs (nom, email, mot_de_passe, cree_le) VALUES (?, ?, ?, ?)');
        foreach (self::USERS as $key => [$name, $email]) {
            $insertUser->execute([$name, $email, $hash, $now]);
            $users[$key] = (int) $pdo->lastInsertId();
        }

        $listings = [];
        $prices = [];
        $insertListing = $pdo->prepare(
            'INSERT INTO logements (proprietaire_id, titre, type, cadre, ville, region, prix_nuit, chambres, capacite,'
            . ' equipements, description, cree_le) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        );
        foreach (self::LISTINGS as $key => [$owner, $title, $type, $setting, $city, $region, $price, $rooms, $capacity, $amenities, $description]) {
            $insertListing->execute([$users[$owner], $title, $type, $setting, $city, $region, $price, $rooms, $capacity, $amenities, $description, $now]);
            $listings[$key] = (int) $pdo->lastInsertId();
            $prices[$key] = $price;
        }

        $insertBooking = $pdo->prepare(
            'INSERT INTO reservations (logement_id, voyageur_id, arrivee, depart, voyageurs, prix_total, statut, cree_le)'
            . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        );
        $insertReview = $pdo->prepare(
            'INSERT INTO avis (reservation_id, logement_id, auteur_id, note, commentaire, cree_le) VALUES (?, ?, ?, ?, ?, ?)',
        );
        foreach (self::BOOKINGS as [$traveller, $listing, $offset, $nights, $guests, $status, $review]) {
            $arrival = $today->modify(sprintf('%+d days', $offset));
            $departure = $arrival->modify("+$nights days");
            $bookedAt = min($arrival->modify('-30 days'), $today)->setTime(10, 0)->format('Y-m-d H:i:s');
            $insertBooking->execute([
                $listings[$listing], $users[$traveller], $arrival->format('Y-m-d'), $departure->format('Y-m-d'),
                $guests, $nights * $prices[$listing], $status, $bookedAt,
            ]);
            if ($review !== null) {
                $insertReview->execute([
                    (int) $pdo->lastInsertId(), $listings[$listing], $users[$traveller], $review[0], $review[1],
                    $departure->modify('+2 days')->setTime(18, 0)->format('Y-m-d H:i:s'),
                ]);
            }
        }
        $pdo->commit();
    }
}
