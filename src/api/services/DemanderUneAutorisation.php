<?php

// Projet TraceGPS - services web
// Fichier : api/services/DemanderUneAutorisation.php
// Dernière mise à jour : [Votre Date]
// Rôle : Ce service permet à un utilisateur de demander une autorisation à un autre membre
//include_once('../../modele/DAO.php');

// Connexion au DAO
$dao = new DAO();

// Récupération des données transmises
$pseudo = (empty($this->request['pseudo'])) ? "" : $this->request['pseudo'];
$mdpSha1 = (empty($this->request['mdp'])) ? "" : $this->request['mdp'];
$pseudoDestinataire = (empty($this->request['pseudoDestinataire'])) ? "" : $this->request['pseudoDestinataire'];
$texteMessage = (empty($this->request['texteMessage'])) ? "" : $this->request['texteMessage'];
$nomPrenom = (empty($this->request['nomPrenom'])) ? "" : $this->request['nomPrenom'];
$lang = (empty($this->request['lang'])) ? "xml" : $this->request['lang'];

// Par défaut, on utilise XML si lang est absent ou incorrect
if ($lang != "json") $lang = "xml";

// La méthode HTTP utilisée doit être GET
if ($this->getMethodeRequete() != "GET") {
    $msg = "Erreur : méthode HTTP incorrecte.";
    $code_reponse = 406;
} else {
    // Vérification des paramètres obligatoires
    if (empty($pseudo) || empty($mdpSha1) || empty($pseudoDestinataire) || empty($texteMessage) || empty($nomPrenom)) {
        $msg = "Erreur : données incomplètes.";
        $code_reponse = 400;
    } else {

        // Vérification de l'authentification de l'utilisateur demandeur
        if ($dao->getNiveauConnexion($pseudo, $mdpSha1) == 0) {
            $msg = "Erreur : authentification incorrecte.";
            $code_reponse = 401;
        } else {
            // Vérification de l'existence du destinataire (changement ici)
            if (!$dao->existePseudoUtilisateur($pseudoDestinataire)) {
                $msg = "Erreur : pseudo utilisateur inexistant.";
                $code_reponse = 404;
            } else {
                // Création et envoi de la demande d'autorisation
                $ok = $dao->creerUneAutorisation($pseudo, $pseudoDestinataire, $texteMessage, $nomPrenom);
                if (!$ok) {
                    $msg = "Erreur : l'envoi du courriel de demande d'autorisation a rencontré un problème.";
                    $code_reponse = 500;
                } else {
                    $msg = "$pseudoDestinataire va recevoir un courriel avec votre demande.";
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

?>


