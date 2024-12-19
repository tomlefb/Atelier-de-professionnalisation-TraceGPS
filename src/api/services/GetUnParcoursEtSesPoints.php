<?php

// Projet TraceGPS - services web
// Fichier : api/services/GetUnParcoursEtSesPoints.php
// Dernière mise à jour : [Votre Date]
// Rôle : Ce service permet à un utilisateur d'obtenir le détail d'un de ses parcours ou d'un parcours autorisé

// Connexion au DAO
$dao = new DAO();

// Récupération des données transmises
$pseudo = (empty($this->request['pseudo'])) ? "" : $this->request['pseudo'];
$mdpSha1 = (empty($this->request['mdp'])) ? "" : $this->request['mdp'];
$idTrace = (empty($this->request['idTrace'])) ? 0 : $this->request['idTrace'];
$lang = (empty($this->request['lang'])) ? "xml" : $this->request['lang'];

// Par défaut, on utilise XML si lang est absent ou incorrect
if ($lang != "json") $lang = "xml";

// La méthode HTTP utilisée doit être GET
if ($this->getMethodeRequete() != "GET") {
    $msg = "Erreur : méthode HTTP incorrecte.";
    $code_reponse = 406;
} else {
    // Vérification des paramètres obligatoires
    if (empty($pseudo) || empty($mdpSha1) || empty($idTrace)) {
        $msg = "Erreur : données incomplètes.";
        $code_reponse = 400;
    } else {
        // Vérification de l'authentification de l'utilisateur
        if ($dao->getNiveauConnexion($pseudo, $mdpSha1) == 0) {
            $msg = "Erreur : authentification incorrecte.";
            $code_reponse = 401;
        } else {
            // Vérification de l'existence de la trace
            $trace = $dao->getUneTrace($idTrace);
            if ($trace == null) {
                $msg = "Erreur : parcours inexistant.";
                $code_reponse = 404;
            } else {
                // Vérification si l'utilisateur est propriétaire ou autorisé
                $utilisateur = $dao->getUnUtilisateur($pseudo);
                if ($utilisateur == null || ($trace->getIdUtilisateur() != $utilisateur->getId() && !$dao->autoriseAConsulter($trace->getIdUtilisateur(), $utilisateur->getId()))) {
                    $msg = "Erreur : vous n'êtes pas autorisé par le propriétaire du parcours.";
                    $code_reponse = 403;
                } else {
                    // Récupération des points de la trace
                    $lesPoints = $dao->getLesPointsDeTrace($idTrace);
                    $msg = "Données de la trace demandée.";
                    $code_reponse = 200;

                    $donnees = [
                        "trace" => [
                            "id" => $trace->getId(),
                            "dateHeureDebut" => $trace->getDateHeureDebut(),
                            "terminee" => $trace->getTerminee(),
                            "dateHeureFin" => $trace->getDateHeureFin(),
                            "idUtilisateur" => $trace->getIdUtilisateur()
                        ],
                        "lesPoints" => []
                    ];

                    foreach ($lesPoints as $point) {
                        $donnees["lesPoints"][] = [
                            "id" => $point->getId(),
                            "latitude" => $point->getLatitude(),
                            "longitude" => $point->getLongitude(),
                            "altitude" => $point->getAltitude(),
                            "dateHeure" => $point->getDateHeure(),
                            "rythmeCardio" => $point->getRythmeCardio()
                        ];
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
    $reponse = creerFluxXML($msg, isset($donnees) ? $donnees : null);
} else {
    $content_type = "application/json; charset=utf-8";
    $reponse = creerFluxJSON($msg, isset($donnees) ? $donnees : null);
}

// Envoi de la réponse HTTP
$this->envoyerReponse($code_reponse, $content_type, $reponse);

// Fin du script
exit();

// ================================================================================================
// Génération du flux XML
function creerFluxXML($msg, $donnees) {
    $doc = new DOMDocument();
    $doc->version = '1.0';
    $doc->encoding = 'UTF-8';
    $elt_data = $doc->createElement('data');
    $doc->appendChild($elt_data);
    $elt_reponse = $doc->createElement('reponse', $msg);
    $elt_data->appendChild($elt_reponse);

    if ($donnees != null) {
        $elt_donnees = $doc->createElement('donnees');
        $elt_data->appendChild($elt_donnees);

        $elt_trace = $doc->createElement('trace');
        foreach ($donnees['trace'] as $key => $value) {
            $elt_trace->appendChild($doc->createElement($key, $value));
        }
        $elt_donnees->appendChild($elt_trace);

        $elt_points = $doc->createElement('lesPoints');
        foreach ($donnees['lesPoints'] as $point) {
            $elt_point = $doc->createElement('point');
            foreach ($point as $key => $value) {
                $elt_point->appendChild($doc->createElement($key, $value));
            }
            $elt_points->appendChild($elt_point);
        }
        $elt_donnees->appendChild($elt_points);
    }

    $doc->formatOutput = true;
    return $doc->saveXML();
}

// ================================================================================================
// Génération du flux JSON
function creerFluxJSON($msg, $donnees) {
    $data = ["reponse" => $msg];

    if ($donnees != null) {
        $data["donnees"] = $donnees;
    }

    return json_encode(["data" => $data], JSON_PRETTY_PRINT);
}