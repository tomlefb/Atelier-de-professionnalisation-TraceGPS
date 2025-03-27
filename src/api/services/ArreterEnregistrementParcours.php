<?php
// Projet TraceGPS - services web
// fichier : api/services/ArreterEnregistrementParcours.php
// Dernière mise à jour : 13/12/2024

// Connexion à la base de données
$dao = new DAO();

// Récupération des données transmises
$pseudo = empty($this->request['pseudo']) ? "" : $this->request['pseudo'];
$mdpSha1 = empty($this->request['mdp']) ? "" : $this->request['mdp'];
$idTrace = empty($this->request['idTrace']) ? "" : $this->request['idTrace'];
$lang = empty($this->request['lang']) ? "xml" : $this->request['lang'];

// "xml" par défaut si le paramètre lang est absent ou incorrect
if ($lang != "json") {
    $lang = "xml";
}

// Vérification de la méthode HTTP
if ($this->getMethodeRequete() != "GET") {
    $msg = "Erreur : méthode HTTP incorrecte.";
    $code_reponse = 406;
} else {
    // Vérification des paramètres transmis
    if (empty($pseudo) || empty($mdpSha1) || empty($idTrace)) {
        $msg = "Erreur : données incomplètes.";
        $code_reponse = 400;
    } else {
        // Vérification de l'authentification
        $niveauConnexion = $dao->getNiveauConnexion($pseudo, $mdpSha1);
        if ($niveauConnexion == 0) {
            $msg = "Erreur : authentification incorrecte.";
            $code_reponse = 401;
        } else {
            // Vérification de l'existence de la trace
            $laTrace = $dao->getUneTrace($idTrace);
            if ($laTrace == null) {
                $msg = "Erreur : parcours inexistant.";
                $code_reponse = 400;
            } else {
                // Vérification si la trace appartient à l'utilisateur
                if ($laTrace->getIdUtilisateur() != $dao->getUnUtilisateur($pseudo)->getId()) {
                    $msg = "Erreur : le numéro de trace ne correspond pas à cet utilisateur.";
                    $code_reponse = 400;
                } else {
                    // Vérification si la trace est déjà terminée
                    if ($laTrace->getTerminee()) {
                        // Au lieu de renvoyer une erreur, on renvoie un message de succès
                        $msg = "Enregistrement terminé.";
                        $code_reponse = 200;
                    } else {
                        // Mise à jour de la trace
                        $dateFin = $laTrace->getDateHeureFin() ?: date('Y-m-d H:i:s');
                        $ok = $dao->terminerUneTrace($idTrace);
                        if (!$ok) {
                            $msg = "Erreur : problème lors de la fin de l'enregistrement de la trace.";
                            $code_reponse = 500;
                        } else {
                            $msg = "Enregistrement terminé.";
                            $code_reponse = 200;
                        }
                    }
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
function creerFluxXML($msg)
{
    $doc = new DOMDocument();
    $doc->version = '1.0';
    $doc->encoding = 'UTF-8';

    $elt_commentaire = $doc->createComment('Service web ArreterEnregistrementParcours - BTS SIO - Lycée De La Salle - Rennes');
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
function creerFluxJSON($msg)
{
    return json_encode(["data" => ["reponse" => $msg]], JSON_PRETTY_PRINT);
}
