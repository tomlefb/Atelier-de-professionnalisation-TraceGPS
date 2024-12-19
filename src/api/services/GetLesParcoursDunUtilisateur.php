<?php

// Projet TraceGPS - services web
// Fichier : api/services/GetLesParcoursDunUtilisateur.php
// Dernière mise à jour : [Votre Date]
// Rôle : Ce service permet à un utilisateur d'obtenir la liste de ses parcours ou de parcours autorisés

// Connexion au DAO
$dao = new DAO();

// Récupération des données transmises
$pseudo = (empty($this->request['pseudo'])) ? "" : $this->request['pseudo'];
$mdpSha1 = (empty($this->request['mdp'])) ? "" : $this->request['mdp'];
$pseudoConsulte = (empty($this->request['pseudoConsulte'])) ? "" : $this->request['pseudoConsulte'];
$lang = (empty($this->request['lang'])) ? "xml" : $this->request['lang'];

// Par défaut, on utilise XML si lang est absent ou incorrect
if ($lang != "json") $lang = "xml";

// La méthode HTTP utilisée doit être GET
if ($this->getMethodeRequete() != "GET") {
    $msg = "Erreur : méthode HTTP incorrecte.";
    $code_reponse = 406;
} else {
    // Vérification des paramètres obligatoires
    if (empty($pseudo) || empty($mdpSha1) || empty($pseudoConsulte)) {
        $msg = "Erreur : données incomplètes.";
        $code_reponse = 400;
    } else {
        // Vérification de l'authentification de l'utilisateur demandeur
        if ($dao->getNiveauConnexion($pseudo, $mdpSha1) == 0) {
            $msg = "Erreur : authentification incorrecte.";
            $code_reponse = 401;
        } else {
            // Vérification de l'existence du pseudo consulté
            $utilisateurConsulte = $dao->getUnUtilisateur($pseudoConsulte);
            if ($utilisateurConsulte == null) {
                $msg = "Erreur : pseudo consulté inexistant.";
                $code_reponse = 404;
            } else {
                $utilisateurDemandeur = $dao->getUnUtilisateur($pseudo);

                // Vérification des autorisations
                if ($utilisateurDemandeur->getId() != $utilisateurConsulte->getId() &&
                    !$dao->autoriseAConsulter($utilisateurConsulte->getId(), $utilisateurDemandeur->getId())) {
                    $msg = "Erreur : vous n'êtes pas autorisé par cet utilisateur.";
                    $code_reponse = 403;
                } else {
                    // Récupération des traces
                    $lesTraces = $dao->getLesTraces($utilisateurConsulte->getId());

                    if (empty($lesTraces)) {
                        $msg = "Aucune trace pour l'utilisateur $pseudoConsulte.";
                        $code_reponse = 200;
                        $donnees = null;
                    } else {
                        $msg = count($lesTraces) . " trace(s) pour l'utilisateur $pseudoConsulte.";
                        $code_reponse = 200;
                        $donnees = [];

                        foreach ($lesTraces as $trace) {
                            $donneeTrace = [
                                "id" => $trace->getId(),
                                "dateHeureDebut" => $trace->getDateHeureDebut(),
                                "terminee" => $trace->getTerminee(),
                                "distance" => $trace->getDistanceTotale(),
                                "idUtilisateur" => $trace->getIdUtilisateur()
                            ];

                            if ($trace->getTerminee()) {
                                $donneeTrace["dateHeureFin"] = $trace->getDateHeureFin();
                            }

                            $donnees[] = $donneeTrace;
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
        $elt_traces = $doc->createElement('lesTraces');
        $elt_donnees->appendChild($elt_traces);

        foreach ($donnees as $trace) {
            $elt_trace = $doc->createElement('trace');
            foreach ($trace as $key => $value) {
                // Vérifiez si $value est null et remplacez-le par une chaîne vide
                $elt_trace->appendChild($doc->createElement($key, $value === null ? '' : $value));
            }
            $elt_traces->appendChild($elt_trace);
        }
    }

    $doc->formatOutput = true;
    return $doc->saveXML();
}

// ================================================================================================
// Génération du flux JSON
function creerFluxJSON($msg, $donnees) {
    $data = ["reponse" => $msg];
    if ($donnees != null) {
        $data["donnees"] = ["lesTraces" => $donnees];
    }
    return json_encode(["data" => $data], JSON_PRETTY_PRINT);
}