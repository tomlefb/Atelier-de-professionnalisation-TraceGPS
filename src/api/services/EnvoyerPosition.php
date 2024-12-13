<?php

// Projet TraceGPS - services web
// Fichier : api/services/EnvoyerPosition.php
// Dernière mise à jour : [Votre Date]
// Rôle : Ce service permet à un utilisateur authentifié d'envoyer sa position

include_once('../../modele/DAO.php');

// Connexion au DAO
$dao = new DAO();

// Récupération des données transmises
$pseudo = (empty($this->request['pseudo'])) ? "" : $this->request['pseudo'];
$mdpSha1 = (empty($this->request['mdp'])) ? "" : $this->request['mdp'];
$idTrace = (empty($this->request['idTrace'])) ? "" : $this->request['idTrace'];
$dateHeure = (empty($this->request['dateHeure'])) ? "" : $this->request['dateHeure'];
$latitude = (empty($this->request['latitude'])) ? "" : $this->request['latitude'];
$longitude = (empty($this->request['longitude'])) ? "" : $this->request['longitude'];
$altitude = (empty($this->request['altitude'])) ? "" : $this->request['altitude'];
$rythmeCardio = (empty($this->request['rythmeCardio'])) ? "" : $this->request['rythmeCardio'];
$lang = (empty($this->request['lang'])) ? "xml" : $this->request['lang'];

// Par défaut, on utilise XML si lang est absent ou incorrect
if ($lang != "json") $lang = "xml";

// La méthode HTTP utilisée doit être GET
if ($this->getMethodeRequete() != "GET") {
    $msg = "Erreur : méthode HTTP incorrecte.";
    $code_reponse = 406;
} else {
    // Vérification des paramètres obligatoires
    if (empty($pseudo) || empty($mdpSha1) || empty($idTrace) || empty($dateHeure) || empty($latitude) || empty($longitude) || empty($altitude)) {
        $msg = "Erreur : données incomplètes.";
        $code_reponse = 400;
    } else {
        // Vérification de l'authentification de l'utilisateur
        if ($dao->getNiveauConnexion($pseudo, $mdpSha1) == 0) {
            $msg = "Erreur : authentification incorrecte.";
            $code_reponse = 401;
        } else {
            // Récupération de la trace
            $trace = $dao->getUneTrace($idTrace);
            if (!$trace) {
                $msg = "Erreur : le numéro de trace n'existe pas.";
                $code_reponse = 404;
            } else {
                // Vérification que la trace appartient bien à l'utilisateur connecté
                $utilisateur = $dao->getUnUtilisateur($pseudo);
                if ($trace->getIdUtilisateur() != $utilisateur->getId()) {
                    $msg = "Erreur : le numéro de trace ne correspond pas à cet utilisateur.";
                    $code_reponse = 403;
                } else {
                    // Vérification que la trace n'est pas terminée
                    if ($trace->getTerminee()) {
                        $msg = "Erreur : la trace est déjà terminée.";
                        $code_reponse = 409;
                    } else {
                        $idPoint = $trace->getNombrePoints()+1;
                        // Création du point de trace avec les nouveaux paramètres
                        $pointDeTrace = new PointDeTrace(
                            $idTrace,
                            $idPoint, // ID du point de trace, probablement auto-généré
                            $latitude,
                            $longitude,
                            $altitude,
                            $dateHeure,
                            $rythmeCardio,
                            0,
                            0,
                            0
                        );

                        // Appel à la méthode pour créer un point de trace
                        $ok = $dao->creerUnPointDeTrace($pointDeTrace);
                        if (!$ok) {
                            $msg = "Erreur : problème lors de l'enregistrement du point.";
                            $code_reponse = 500;
                        } else {
                            $msg = "Point enregistré.";
                            $code_reponse = 200;
                        }
                    }
                }
            }
        }
    }
}

// Fermeture de la connexion au DAO
unset($dao);

// Génération de la réponse
if ($lang == "xml") {
    $content_type = "application/xml; charset=utf-8";
    $donnees = creerFluxXML($msg);
} else {
    $content_type = "application/json; charset=utf-8";
    $donnees = creerFluxJSON($msg);
}

// Envoi de la réponse HTTP
$this->envoyerReponse($code_reponse, $content_type, $donnees);

// Fin du script
exit();

// ================================================================================================
// Génération du flux XML
function creerFluxXML($msg)
{
    $doc = new DOMDocument();
    $doc->version = '1.0';
    $doc->encoding = 'UTF-8';
    $elt_data = $doc->createElement('data');
    $doc->appendChild($elt_data);
    $elt_reponse = $doc->createElement('reponse', $msg);
    $elt_data->appendChild($elt_reponse);
    $doc->formatOutput = true;
    return $doc->saveXML();
}

// ================================================================================================
// Génération du flux JSON
function creerFluxJSON($msg)
{
    $elt_data = ["reponse" => $msg];
    $elt_racine = ["data" => $elt_data];
    return json_encode($elt_racine, JSON_PRETTY_PRINT);
}

?>
