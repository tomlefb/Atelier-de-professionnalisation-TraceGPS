<?php

// Projet TraceGPS - services web
// Fichier : api/services/GetLesUtilisateursQuiMautorisent.php
// Dernière mise à jour : [Votre Date]
// Rôle : Ce service permet à un utilisateur d'obtenir la liste des utilisateurs qui l'autorisent à consulter leurs parcours

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
            // Récupération de l'objet Utilisateur
            $utilisateur = $dao->getUnUtilisateur($pseudo);

            if ($utilisateur === null) {
                $msg = "Erreur : utilisateur non trouvé.";
                $code_reponse = 404;
            } else {
                // Récupération des utilisateurs qui autorisent
                $utilisateurs = $dao->getLesUtilisateursAutorisant($utilisateur->getId());

                if (empty($utilisateurs)) {
                    $msg = "Aucune autorisation accordée à $pseudo.";
                    $code_reponse = 200;
                } else {
                    $msg = count($utilisateurs) . " autorisation(s) accordée(s) à $pseudo.";
                    $code_reponse = 200;
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
    $donnees = creerFluxXML($msg, isset($utilisateurs) ? $utilisateurs : []);
} else {
    $content_type = "application/json; charset=utf-8";
    $donnees = creerFluxJSON($msg, isset($utilisateurs) ? $utilisateurs : []);
}

// Envoi de la réponse HTTP
$this->envoyerReponse($code_reponse, $content_type, $donnees);

// Fin du script
exit();

// ================================================================================================
// Génération du flux XML
function creerFluxXML($msg, $utilisateurs) {
    $doc = new DOMDocument();
    $doc->version = '1.0';
    $doc->encoding = 'UTF-8';

    // Racine <data>
    $elt_data = $doc->createElement('data');
    $doc->appendChild($elt_data);

    // Élément <reponse>
    $elt_reponse = $doc->createElement('reponse', $msg);
    $elt_data->appendChild($elt_reponse);

    if (!empty($utilisateurs)) {
        // Élément <donnees>
        $elt_donnees = $doc->createElement('donnees');
        $elt_data->appendChild($elt_donnees);

        // Élément <lesUtilisateurs>
        $elt_lesUtilisateurs = $doc->createElement('lesUtilisateurs');
        $elt_donnees->appendChild($elt_lesUtilisateurs);

        foreach ($utilisateurs as $utilisateur) {
            // Élément <utilisateur>
            $elt_utilisateur = $doc->createElement('utilisateur');
            $elt_lesUtilisateurs->appendChild($elt_utilisateur);

            // Ajouter les données de l'utilisateur
            $elt_utilisateur->appendChild($doc->createElement('id', $utilisateur->getId()));
            $elt_utilisateur->appendChild($doc->createElement('pseudo', $utilisateur->getPseudo()));
            $elt_utilisateur->appendChild($doc->createElement('adrMail', $utilisateur->getAdrMail()));
            $elt_utilisateur->appendChild($doc->createElement('numTel', $utilisateur->getNumTel()));
            $elt_utilisateur->appendChild($doc->createElement('niveau', $utilisateur->getNiveau()));
            $elt_utilisateur->appendChild($doc->createElement('dateCreation', $utilisateur->getDateCreation()));
            $elt_utilisateur->appendChild($doc->createElement('nbTraces', $utilisateur->getNbTraces()));
            $elt_utilisateur->appendChild($doc->createElement('dateDerniereTrace', $utilisateur->getDateDerniereTrace()));
        }
    }

    $doc->formatOutput = true;
    return $doc->saveXML();
}


// ================================================================================================
// Génération du flux JSON
function creerFluxJSON($msg, $utilisateurs) {
    $elt_data = ["reponse" => $msg];

    if (!empty($utilisateurs)) {
        $lesUtilisateurs = [];

        foreach ($utilisateurs as $utilisateur) {
            $lesUtilisateurs[] = [
                "id" => $utilisateur->getId(),
                "pseudo" => $utilisateur->getPseudo(),
                "adrMail" => $utilisateur->getAdrMail(),
                "numTel" => $utilisateur->getNumTel(),
                "niveau" => $utilisateur->getNiveau(),
                "dateCreation" => $utilisateur->getDateCreation(),
                "nbTraces" => $utilisateur->getNbTraces(),
                "dateDerniereTrace" => $utilisateur->getDateDerniereTrace()
            ];
        }

        $elt_data["donnees"] = ["lesUtilisateurs" => $lesUtilisateurs];
    }

    return json_encode(["data" => $elt_data], JSON_PRETTY_PRINT);
}
