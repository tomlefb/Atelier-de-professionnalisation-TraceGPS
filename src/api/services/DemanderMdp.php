<?php

// Projet TraceGPS - services web
// Fichier : api/services/DemanderMdp.php
// Dernière mise à jour : [Votre Date]
// Rôle : Ce service permet à un utilisateur de demander un nouveau mot de passe

// Connexion au DAO
$dao = new DAO();

// Récupération des données transmises
$pseudo = (empty($this->request['pseudo'])) ? "" : $this->request['pseudo'];
$lang = (empty($this->request['lang'])) ? "xml" : $this->request['lang'];

// Par défaut, on utilise XML si lang est absent ou incorrect
if ($lang != "json") $lang = "xml";

// La méthode HTTP utilisée doit être GET
if ($this->getMethodeRequete() != "GET") {
    $msg = "Erreur : méthode HTTP incorrecte.";
    $code_reponse = 406;
} else {
    // Vérification des paramètres obligatoires
    if (empty($pseudo)) {
        $msg = "Erreur : données incomplètes.";
        $code_reponse = 400;
    } else {
        // Vérification de l'existence de l'utilisateur
        $utilisateur = $dao->getUnUtilisateur($pseudo);
        if (!$utilisateur) {
            $msg = "Erreur : pseudo inexistant.";
            $code_reponse = 404;
        } else {
            // Génération d'un nouveau mot de passe
            $nouveauMdp = genererMotDePasse();
            $mdpSha1 = sha1($nouveauMdp);

            // Mise à jour du mot de passe dans la base de données
            $okMiseAJour = $dao->modifierMdpUtilisateur($pseudo, $mdpSha1);
            if (!$okMiseAJour) {
                $msg = "Erreur : problème lors de l'enregistrement du mot de passe.";
                $code_reponse = 500;
            } else {
                // Envoi du courriel avec le nouveau mot de passe
                $okEnvoiCourriel = $dao->envoyerMdp($pseudo, $nouveauMdp);
                if (!$okEnvoiCourriel) {
                    $msg = "Enregistrement effectué ; l'envoi du courriel de confirmation a rencontré un problème.";
                    $code_reponse = 500;
                } else {
                    $msg = "Vous allez recevoir un courriel avec votre nouveau mot de passe.";
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

// ================================================================================================
// Fonction pour générer un mot de passe de 8 caractères organisés en 4 syllabes (consonne + voyelle)
function genererMotDePasse()
{
    $consonnes = ['b', 'c', 'd', 'f', 'g', 'h', 'j', 'k', 'l', 'm', 'n', 'p', 'r', 's', 't', 'v', 'w', 'x', 'y', 'z'];
    $voyelles = ['a', 'e', 'i', 'o', 'u'];
    $motDePasse = "";
    for ($i = 0; $i < 4; $i++) {
        $motDePasse .= $consonnes[array_rand($consonnes)];
        $motDePasse .= $voyelles[array_rand($voyelles)];
    }
    return $motDePasse;
}