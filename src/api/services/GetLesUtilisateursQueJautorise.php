<?php

// Projet TraceGPS - services web
// Fichier : api/services/GetLesUtilisateursQueJautorise.php
// Dernière mise à jour : [Votre Date]
// Rôle : Ce service permet à un utilisateur d'obtenir la liste des utilisateurs qu'il autorise à consulter ses parcours

//include_once('../../modele/DAO.php');

// Connexion au DAO
$dao = new DAO();

// Récupération des données transmises
$pseudo = (empty($this->request['pseudo'])) ? "" : $this->request['pseudo'];
$mdpSha1 = (empty($this->request['mdp'])) ? "" : $this->request['mdp'];
$lang = (empty($this->request['lang'])) ? "xml" : $this->request['lang'];

// Par défaut, on utilise XML si lang est absent ou incorrect
if ($lang != "json") $lang = "xml";

// La méthode HTTP utilisée doit être GET
if ($this->getMethodeRequete() != "GET") {
    $msg = "Erreur : méthode HTTP incorrecte.";
    $code_reponse = 406;
} else {
    // Vérification des paramètres obligatoires
    if (empty($pseudo) || empty($mdpSha1)) {
        $msg = "Erreur : données incomplètes.";
        $code_reponse = 400;
    } else {
        // Vérification de l'authentification de l'utilisateur
        if ($dao->getNiveauConnexion($pseudo, $mdpSha1) == 0) {
            $msg = "Erreur : authentification incorrecte.";
            $code_reponse = 401;
        } else {
            // Récupération des utilisateurs autorisés par l'utilisateur
            $utilisateursAutorises = $dao->getLesUtilisateursAutorises($pseudo);

            if (count($utilisateursAutorises) == 0) {
                $msg = "Aucune autorisation accordée par $pseudo.";
                $code_reponse = 200;
                $donnees = null;
            } else {
                $msg = count($utilisateursAutorises) . " autorisation(s) accordée(s) par $pseudo.";
                $code_reponse = 200;
                $donnees = [];

                foreach ($utilisateursAutorises as $utilisateur) {
                    $dataUtilisateur = [
                        "id" => $utilisateur->getId(),
                        "pseudo" => $utilisateur->getPseudo(),
                        "adrMail" => $utilisateur->getAdrMail(),
                        "numTel" => $utilisateur->getNumTel(),
                        "niveau" => $utilisateur->getNiveau(),
                        "dateCreation" => $utilisateur->getDateCreation(),
                        "nbTraces" => $utilisateur->getNbTraces(),
                    ];
                    if ($utilisateur->getNbTraces() > 0) {
                        $dataUtilisateur["dateDerniereTrace"] = $utilisateur->getDateDerniereTrace();
                    }
                    $donnees[] = $dataUtilisateur;
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
    $reponse = creerFluxXML($msg, $donnees);
} else {
    $content_type = "application/json; charset=utf-8";
    $reponse = creerFluxJSON($msg, $donnees);
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
        $elt_utilisateurs = $doc->createElement('lesUtilisateurs');
        $elt_donnees->appendChild($elt_utilisateurs);

        foreach ($donnees as $utilisateur) {
            $elt_utilisateur = $doc->createElement('utilisateur');
            foreach ($utilisateur as $key => $value) {
                $elt_utilisateur->appendChild($doc->createElement($key, $value));
            }
            $elt_utilisateurs->appendChild($elt_utilisateur);
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
        $data["donnees"] = ["lesUtilisateurs" => $donnees];
    }
    return json_encode(["data" => $data], JSON_PRETTY_PRINT);
}
