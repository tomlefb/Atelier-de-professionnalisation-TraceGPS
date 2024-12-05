<?php
// Projet TraceGPS - services web
// fichier : api/services/DemanderMdp.php
// Dernière mise à jour : 28/11/2024

// Rôle : ce service permet à un utilisateur de demander un nouveau mot de passe
// Le service web doit recevoir 2 paramètres :
//     pseudo : le pseudo de l'utilisateur
//     lang : le langage du flux de données retourné ("xml" ou "json") ; "xml" par défaut si le paramètre est absent ou incorrect
// Le service retourne un flux de données XML ou JSON contenant un compte-rendu d'exécution

include_once (__DIR__ . '/../../modele/DAO.php');
include_once (__DIR__ . '/../../modele/Outils.php');

// connexion du serveur web à la base MySQL
$dao = new DAO();

// Récupération des données transmises
$pseudo = (empty($this->request['pseudo'])) ? "" : $this->request['pseudo'];
$lang = (empty($this->request['lang'])) ? "" : $this->request['lang'];

// "xml" par défaut si le paramètre lang est absent ou incorrect
if ($lang != "json") $lang = "xml";

// La méthode HTTP utilisée doit être GET
if ($this->getMethodeRequete() != "GET") {
    $msg = "Erreur : méthode HTTP incorrecte.";
    $code_reponse = 406;
} else {
    // Vérification des données
    if (empty($pseudo)) {
        $msg = "Erreur : données incomplètes.";
        $code_reponse = 400;
    } else {
        // Vérification de l'existence du pseudo
        if (!$dao->existePseudoUtilisateur($pseudo)) {
            $msg = "Erreur : pseudo inexistant.";
            $code_reponse = 400;
        } else {
            // Génération d'un nouveau mot de passe
            $nouveauMdp = Outils::creerMdp();
            $nouveauMdpSha1 = sha1($nouveauMdp);

            // Mise à jour du mot de passe dans la base de données
            $ok = $dao->modifierMdpUtilisateur($pseudo, $nouveauMdpSha1);
            if (!$ok) {
                $msg = "Erreur : problème lors de l'enregistrement du mot de passe.";
                $code_reponse = 500;
            } else {
                // Récupération de l'adresse email de l'utilisateur
                $adrMail = $dao->existeAdrMailUtilisateur($pseudo);

                // Envoi d'un courriel avec le nouveau mot de passe
                $sujet = "Votre nouveau mot de passe";
                $contenuMail = "Bonjour,\n\nVoici votre nouveau mot de passe : $nouveauMdp\n\n";
                $contenuMail .= "Pensez à le changer lors de votre prochaine connexion.\n\nL'équipe TraceGPS.";
                global $ADR_MAIL_EMETTEUR;

                if (!Outils::envoyerMail($adrMail, $sujet, $contenuMail, $ADR_MAIL_EMETTEUR)) {
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

// Fermeture de la connexion à la base de données
unset($dao);

// Création du flux en sortie
if ($lang == "xml") {
    $content_type = "application/xml; charset=utf-8";
    $donnees = creerFluxXML($msg);
} else {
    $content_type = "application/json; charset=utf-8";
    $donnees = creerFluxJSON($msg);
}

// Envoi de la réponse HTTP
$this->envoyerReponse($code_reponse, $content_type, $donnees);
exit;

// ================================================================================================

// Création du flux XML en sortie
function creerFluxXML($msg) {
    $doc = new DOMDocument();
    $doc->version = '1.0';
    $doc->encoding = 'UTF-8';

    $elt_commentaire = $doc->createComment('Service web DemanderMdp - BTS SIO - Lycée De La Salle - Rennes');
    $doc->appendChild($elt_commentaire);

    $elt_data = $doc->createElement('data');
    $doc->appendChild($elt_data);

    $elt_reponse = $doc->createElement('reponse', $msg);
    $elt_data->appendChild($elt_reponse);

    $doc->formatOutput = true;
    return $doc->saveXML();
}

// ================================================================================================

// Création du flux JSON en sortie
function creerFluxJSON($msg) {
    $elt_data = ["reponse" => $msg];
    $elt_racine = ["data" => $elt_data];
    return json_encode($elt_racine, JSON_PRETTY_PRINT);
}
?>
