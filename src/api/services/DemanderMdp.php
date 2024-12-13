<?php
// Projet TraceGPS - services web
// fichier : api/services/GetLesUtilisateursQueJautorise.php
// Dernière mise à jour : 05/12/2024

//include_once (__DIR__ . '/../../modele/DAO.php');
//include_once (__DIR__ . '/../../modele/Outils.php');

// Démarrage d'un tampon de sortie pour éviter les sorties involontaires
//ob_start();

// Connexion à la base de données
$dao = new DAO();

// Récupération des données transmises
$pseudo = isset($_GET['pseudo']) ? $_GET['pseudo'] : "";
$mdpSha1 = isset($_GET['mdp']) ? $_GET['mdp'] : "";
$lang = isset($_GET['lang']) ? $_GET['lang'] : "xml"; // Lang par défaut

// Vérification de la méthode HTTP (GET uniquement)
if ($_SERVER['REQUEST_METHOD'] != "GET") {
    $msg = "Erreur : méthode HTTP incorrecte.";
    $code_reponse = 406;
    $utilisateurs = [];
} else {
    // Vérification des données
    if (empty($pseudo) || empty($mdpSha1)) {
        $msg = "Erreur : données incomplètes.";
        $code_reponse = 400;
        $utilisateurs = [];
    } else {// Récupération des utilisateurs autorisés
        $utilisateur = $dao->getUnUtilisateur($pseudo);
        if ($utilisateur == null) {
            $msg = "Erreur : utilisateur inexistant.";
            $code_reponse = 400;
            $utilisateurs = [];}
        else {
            // Génération d'un nouveau mot de passe
            $nouveauMdp = Outils::creerMdp();
            $nouveauMdpSha1 = sha1($nouveauMdp);

            // Mise à jour du mot de passe dans la base de données
            $ok = $dao->modifierMdpUtilisateur($pseudo, $nouveauMdpSha1);
            if (!$ok) {
                $msg = "Erreur : problème lors de l'enregistrement du mot de passe.";
                $code_reponse = 500;
            } else {
                // Utilisation de la méthode envoyerMdp
                $ok = $dao->envoyerMdp($pseudo, $nouveauMdp);

                if (!$ok) {
                    $msg = "Enregistrement effectué ; l'envoi du courriel à l'utilisateur a rencontré un problème.";
                    $code_reponse = 500;
                } else {
                    $msg = "Enregistrement effectué ; vous allez recevoir un courriel avec votre nouveau mot de passe.";
                    $code_reponse = 201;
                }
            }
        }
    }
}
// ferme la connexion à MySQL :
unset($dao);

// création du flux en sortie
if ($lang == "xml") {
    $content_type = "application/xml; charset=utf-8";      // indique le format XML pour la réponse
    $donnees = creerFluxXML($msg,$utilisateurs);
}
else {
    $content_type = "application/json; charset=utf-8";      // indique le format Json pour la réponse
    $donnees = creerFluxJSON($msg, $utilisateurs);
}

// envoi de la réponse HTTP
$this->envoyerReponse($code_reponse, $content_type, $donnees);

// fin du programme (pour ne pas enchainer sur les 2 fonctions qui suivent)
exit;

// ================================================================================================
// Création du flux XML
function creerFluxXML($msg, $utilisateurs) {
    $doc = new DOMDocument();
    $doc->version = '1.0';
    $doc->encoding = 'UTF-8';

    $elt_commentaire = $doc->createComment('Service web GetLesUtilisateursQueJautorise - BTS SIO - Lycée De La Salle - Rennes');
    $doc->appendChild($elt_commentaire);

    $elt_data = $doc->createElement('data');
    $doc->appendChild($elt_data);

    $elt_reponse = $doc->createElement('reponse', $msg);
    $elt_data->appendChild($elt_reponse);

    if (!empty($utilisateurs)) {
        $elt_donnees = $doc->createElement('donnees');
        $elt_data->appendChild($elt_donnees);

        $elt_lesUtilisateurs = $doc->createElement('lesUtilisateurs');
        $elt_donnees->appendChild($elt_lesUtilisateurs);

        foreach ($utilisateurs as $unUtilisateur) {
            $elt_utilisateur = $doc->createElement('utilisateur');
            $elt_lesUtilisateurs->appendChild($elt_utilisateur);

            $elt_utilisateur->appendChild($doc->createElement('id', $unUtilisateur->getId()));
            $elt_utilisateur->appendChild($doc->createElement('pseudo', $unUtilisateur->getPseudo()));
            $elt_utilisateur->appendChild($doc->createElement('adrMail', $unUtilisateur->getAdrMail()));
            $elt_utilisateur->appendChild($doc->createElement('numTel', $unUtilisateur->getNumTel()));
            $elt_utilisateur->appendChild($doc->createElement('niveau', $unUtilisateur->getNiveau()));
            $elt_utilisateur->appendChild($doc->createElement('dateCreation', $unUtilisateur->getDateCreation()));
            $elt_utilisateur->appendChild($doc->createElement('nbTraces', $unUtilisateur->getNbTraces()));
            if ($unUtilisateur->getDateDerniereTrace() != null) {
                $elt_utilisateur->appendChild($doc->createElement('dateDerniereTrace', $unUtilisateur->getDateDerniereTrace()));
            }
        }
    }

    $doc->formatOutput = true;
    return $doc->saveXML();
}

// ================================================================================================
// Création du flux JSON
function creerFluxJSON($msg, $utilisateurs) {
    $data = ["reponse" => $msg];

    if (!empty($utilisateurs)) {
        $lesUtilisateurs = [];
        foreach ($utilisateurs as $unUtilisateur) {
            $utilisateur = [
                "id" => $unUtilisateur->getId(),
                "pseudo" => $unUtilisateur->getPseudo(),
                "adrMail" => $unUtilisateur->getAdrMail(),
                "numTel" => $unUtilisateur->getNumTel(),
                "niveau" => $unUtilisateur->getNiveau(),
                "dateCreation" => $unUtilisateur->getDateCreation(),
                "nbTraces" => $unUtilisateur->getNbTraces(),
            ];
            if ($unUtilisateur->getDateDerniereTrace() != null) {
                $utilisateur["dateDerniereTrace"] = $unUtilisateur->getDateDerniereTrace();
            }
            $lesUtilisateurs[] = $utilisateur;
        }
        $data["donnees"] = ["lesUtilisateurs" => $lesUtilisateurs];
    }

    return json_encode(["data" => $data], JSON_PRETTY_PRINT);
}