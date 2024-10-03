<?php
// Projet TraceGPS
// fichier : modele/Trace.php
// Rôle : la classe Trace représente une trace ou un parcours
// Dernière mise à jour : 9/7/2021 par dP
use modele\Point;
include_once('PointDeTrace.php');
class Trace
{
    // ------------------------------------------------------------------------------------------------------
    // ---------------------------------- Attributs privés de la classe -------------------------------------
    // ------------------------------------------------------------------------------------------------------

    private $id; // identifiant de la trace
    private $dateHeureDebut; // date et heure de début
    private $dateHeureFin; // date et heure de fin
    private $terminee; // true si la trace est terminée, false sinon
    private $idUtilisateur; // identifiant de l'utilisateur ayant créé la trace
    private $lesPointsDeTrace; // la collection (array) des objets PointDeTrace formant la trace

    // ------------------------------------------------------------------------------------------------------
    // ----------------------------------------- Constructeur -----------------------------------------------
    // ------------------------------------------------------------------------------------------------------

    public function __construct($unId, $uneDateHeureDebut, $uneDateHeureFin, $terminee, $unIdUtilisateur) {
        $this->id = $unId;
        $this->dateHeureDebut = $uneDateHeureDebut;
        $this->dateHeureFin = $uneDateHeureFin;
        $this->terminee = $terminee;
        $this->idUtilisateur = $unIdUtilisateur;
        $this->lesPointsDeTrace = []; // initialise une collection vide
    }

    // ------------------------------------------------------------------------------------------------------
    // ---------------------------------------- Getters et Setters ------------------------------------------
    // ------------------------------------------------------------------------------------------------------

    public function getId() {return $this->id;}
    public function setId($unId) {$this->id = $unId;}

    public function getDateHeureDebut() {return $this->dateHeureDebut;}
    public function setDateHeureDebut($uneDateHeureDebut) {$this->dateHeureDebut = $uneDateHeureDebut;}
    public function getDateHeureFin() {return $this->dateHeureFin;}
    public function setDateHeureFin($uneDateHeureFin) {$this->dateHeureFin= $uneDateHeureFin;}

    public function getTerminee() {return $this->terminee;}
    public function setTerminee($terminee) {$this->terminee = $terminee;}

    public function getIdUtilisateur() {return $this->idUtilisateur;}

    public function setIdUtilisateur($unIdUtilisateur) {$this->idUtilisateur = $unIdUtilisateur;}
    public function getLesPointsDeTrace() {return $this->lesPointsDeTrace;}
    public function setLesPointsDeTrace($lesPointsDeTrace) {$this->lesPointsDeTrace = $lesPointsDeTrace;}

    // ------------------------------------------------------------------------------------------------------
    // ---------------------------------------- Méthodes d'instances ----------------------------------------
    // ------------------------------------------------------------------------------------------------------

    // Fournit une chaine contenant toutes les données de l'objet
    public function toString() {
        $msg = "Id : " . $this->getId() . "<br>";
        $msg .= "Utilisateur : " . $this->getIdUtilisateur() . "<br>";
        if ($this->getDateHeureDebut() != null) {
            $msg .= "Heure de début : " . $this->getDateHeureDebut() . "<br>";
        }
        if ($this->getTerminee()) {
            $msg .= "Terminée : Oui <br>";
        }
        else {
            $msg .= "Terminée : Non <br>";
        }
        $msg .= "Nombre de points : " . $this->getNombrePoints() . "<br>";
        if ($this->getNombrePoints() > 0) {
            if ($this->getDateHeureFin() != null) {
                $msg .= "Heure de fin : " . $this->getDateHeureFin() . "<br>";
            }
            $msg .= "Durée en secondes : " . $this->getDureeEnSecondes() . "<br>";
            $msg .= "Durée totale : " . $this->getDureeTotale() . "<br>";
            $msg .= "Distance totale en Km : " . $this->getDistanceTotale() . "<br>";
            $msg .= "Dénivelé en m : " . $this->getDenivele() . "<br>";
            $msg .= "Dénivelé positif en m : " . $this->getDenivelePositif() . "<br>";
            $msg .= "Dénivelé négatif en m : " . $this->getDeniveleNegatif() . "<br>";
            $msg .= "Vitesse moyenne en Km/h : " . $this->getVitesseMoyenne() . "<br>";
            $msg .= "Centre du parcours : " . "<br>";
            $msg .= " - Latitude : " . $this->getCentre()->getLatitude() . "<br>";
            $msg .= " - Longitude : " . $this->getCentre()->getLongitude() . "<br>";
            $msg .= " - Altitude : " . $this->getCentre()->getAltitude() . "<br>";
        }
        return $msg;
    }

    // 1. Méthode getNombrePoints : fournit le nombre de points de la collection
    public function getNombrePoints() {
        return sizeof($this->lesPointsDeTrace);
    }

    // 2. Méthode getCentre : fournit un objet Point correspondant au centre du parcours
    public function getCentre() {
        if ($this->getNombrePoints() == 0) return null;

        $latMin = $latMax = $this->lesPointsDeTrace[0]->getLatitude();
        $longMin = $longMax = $this->lesPointsDeTrace[0]->getLongitude();

        foreach ($this->lesPointsDeTrace as $point) {
            if ($point->getLatitude() < $latMin) $latMin = $point->getLatitude();
            if ($point->getLatitude() > $latMax) $latMax = $point->getLatitude();
            if ($point->getLongitude() < $longMin) $longMin = $point->getLongitude();
            if ($point->getLongitude() > $longMax) $longMax = $point->getLongitude();
        }

        $latCentre = ($latMin + $latMax) / 2;
        $longCentre = ($longMin + $longMax) / 2;

        return new Point($latCentre, $longCentre, 0);
    }

    // 3. Méthode getDenivele : fournit l'écart d'altitude entre le point le plus bas et le point le plus haut du parcours
    public function getDenivele() {
        if ($this->getNombrePoints() == 0) return 0;

        $altMin = $altMax = $this->lesPointsDeTrace[0]->getAltitude();

        foreach ($this->lesPointsDeTrace as $point) {
            if ($point->getAltitude() < $altMin) $altMin = $point->getAltitude();
            if ($point->getAltitude() > $altMax) $altMax = $point->getAltitude();
        }

        return $altMax - $altMin;
    }

    // 4. Méthode getDureeEnSecondes : fournit le temps cumulé au dernier point
    public function getDureeEnSecondes() {
        if ($this->getNombrePoints() == 0) return 0;

        return $this->lesPointsDeTrace[$this->getNombrePoints() - 1]->getTempsCumule();
    }

    // 5. Méthode getDureeTotale : fournit la durée totale sous la forme "hh:mm:ss"
    public function getDureeTotale() {
        $duree = $this->getDureeEnSecondes();
        $heures = floor($duree / 3600);
        $minutes = floor(($duree % 3600) / 60);
        $secondes = $duree % 60;

        return sprintf("%02d", $heures) . ":" . sprintf("%02d", $minutes) . ":" . sprintf("%02d", $secondes);
    }

    // 6. Méthode getDistanceTotale : fournit la distance cumulée au dernier point
    public function getDistanceTotale() {
        if ($this->getNombrePoints() == 0) return 0;

        return $this->lesPointsDeTrace[$this->getNombrePoints() - 1]->getDistanceCumulee();
    }

    // 7. Méthode getDenivelePositif : fournit le cumul des écarts d'altitude montants
    public function getDenivelePositif() {
        $denivelePositif = 0;

        for ($i = 1; $i < $this->getNombrePoints(); $i++) {
            $diffAlt = $this->lesPointsDeTrace[$i]->getAltitude() - $this->lesPointsDeTrace[$i - 1]->getAltitude();
            if ($diffAlt > 0) $denivelePositif += $diffAlt;
        }

        return $denivelePositif;
    }

    // 8. Méthode getDeniveleNegatif : fournit le cumul des écarts d'altitude descendants
    public function getDeniveleNegatif() {
        $deniveleNegatif = 0;

        for ($i = 1; $i < $this->getNombrePoints(); $i++) {
            $diffAlt = $this->lesPointsDeTrace[$i]->getAltitude() - $this->lesPointsDeTrace[$i - 1]->getAltitude();
            if ($diffAlt < 0) $deniveleNegatif += abs($diffAlt);
        }

        return $deniveleNegatif;
    }

    // 9. Méthode getVitesseMoyenne : fournit la vitesse moyenne sur la totalité du parcours
    public function getVitesseMoyenne() {
        $distanceTotale = $this->getDistanceTotale();
        $dureeEnSecondes = $this->getDureeEnSecondes();

        if ($dureeEnSecondes == 0) return 0;

        return ($distanceTotale / $dureeEnSecondes) * 3600; // Conversion en km/h
    }

    // 10. Méthode ajouterPoint : ajoute un point à la collection
    public function ajouterPoint($unPointDeTrace) {
        $nbPoints = $this->getNombrePoints();

        // Si c'est le premier point, initialiser les attributs à 0
        if ($nbPoints == 0) {
            $unPointDeTrace->setTempsCumule(0);
            $unPointDeTrace->setDistanceCumulee(0);
            $unPointDeTrace->setVitesse(0);
        } else {
            $dernierPoint = $this->lesPointsDeTrace[$nbPoints - 1];

            // Calculer le temps cumulé
            $tempsCumule = $dernierPoint->getTempsCumule() + strtotime($unPointDeTrace->getDateHeure()) - strtotime($dernierPoint->getDateHeure());
            $unPointDeTrace->setTempsCumule($tempsCumule);

            // Calculer la distance cumulée
            $distanceCumulee = $dernierPoint->getDistanceCumulee() + Point::getDistance($dernierPoint, $unPointDeTrace);
            $unPointDeTrace->setDistanceCumulee($distanceCumulee);

            // Calculer la vitesse (km/h)
            $tempsEnHeures = ($tempsCumule - $dernierPoint->getTempsCumule()) / 3600;
            $vitesse = $tempsEnHeures > 0 ? Point::getDistance($dernierPoint, $unPointDeTrace) / $tempsEnHeures : 0;
            $unPointDeTrace->setVitesse($vitesse);
        }

        // Ajouter le point à la collection
        $this->lesPointsDeTrace[] = $unPointDeTrace;
    }

    // 11. Méthode viderListePoints : permet de vider la collection
    public function viderListePoints() {
        $this->lesPointsDeTrace = [];
    }

} // fin de la classe Trace

// ATTENTION : on ne met pas de balise de fin de script pour ne pas prendre le risque
// d'enregistrer d'espaces après la balise de fin de script !!!!!!!!!!!!
