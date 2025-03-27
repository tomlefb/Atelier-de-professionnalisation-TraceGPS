<?php
// Projet TraceGPS - services web
// fichier : api/services/DemarrerEnregistrementParcours.php
// Dernière mise à jour : 13/12/2024

// Connexion à la base de données
$dao = new DAO();

// Récupération des données transmises
$pseudo = empty($this->request['pseudo']) ? "" : $this->request['pseudo'];
$mdpSha1 = empty($this->request['mdp']) ? "" : $this->request['mdp'];
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
    if (empty($pseudo) || empty($mdpSha1)) {
        $msg = "Erreur : données incomplètes.";
        $code_reponse = 400;
    } else {
        // Vérification de l'authentification
        $niveauConnexion = $dao->getNiveauConnexion($pseudo, $mdpSha1);
        if ($niveauConnexion == 0) {
            $msg = "Erreur : authentification incorrecte.";
            $code_reponse = 401;
        } else {
            // Création d'une nouvelle trace
            $idUtilisateur = $dao->getUnUtilisateur($pseudo)->getId();
            $nouvelleTrace = new Trace(null, date('Y-m-d H:i:s'), null, false, $idUtilisateur);
            $ok = $dao->creerUneTrace($nouvelleTrace);

            if (!$ok) {
                $msg = "Erreur : problème lors de la création de la trace.";
                $code_reponse = 500;
            } else {
                $msg = "Trace créée.";
                $code_reponse = 200;
                $donneesTrace = [
                    "id" => $nouvelleTrace->getId(),
                    "dateHeureDebut" => $nouvelleTrace->getDateHeureDebut(),
                    "terminee" => $nouvelleTrace->getTerminee(),
                    "idUtilisateur" => $nouvelleTrace->getIdUtilisateur()
                ];
            }
        }
    }
}

// Fermeture de la connexion à la base de données
unset($dao);

// Création du flux en sortie
if ($lang == "xml") {
    $content_type = "application/xml; charset=utf-8";
    if (isset($donneesTrace)) {
        $donnees = creerFluxXML($msg, $donneesTrace);
    } else {
        $donnees = creerFluxXML($msg);
    }
} else {
    $content_type = "application/json; charset=utf-8";
    if (isset($donneesTrace)) {
        $donnees = creerFluxJSON($msg, $donneesTrace);
    } else {
        $donnees = creerFluxJSON($msg);
    }
}

// Envoi de la réponse HTTP
$this->envoyerReponse($code_reponse, $content_type, $donnees);
exit;

// ================================================================================================

// Création du flux XML en sortie
function creerFluxXML($msg, $donneesTrace = null)
{
    $doc = new DOMDocument();
    $doc->version = '1.0';
    $doc->encoding = 'UTF-8';

    $elt_commentaire = $doc->createComment('Service web DemarrerEnregistrementParcours - BTS SIO - Lycée De La Salle - Rennes');
    $doc->appendChild($elt_commentaire);

    $elt_data = $doc->createElement('data');
    $doc->appendChild($elt_data);

    $elt_reponse = $doc->createElement('reponse', $msg);
    $elt_data->appendChild($elt_reponse);

    if ($donneesTrace) {
        $elt_donnees = $doc->createElement('donnees');
        $elt_data->appendChild($elt_donnees);

        $elt_trace = $doc->createElement('trace');
        $elt_donnees->appendChild($elt_trace);

        foreach ($donneesTrace as $key => $value) {
            $elt = $doc->createElement($key, $value);
            $elt_trace->appendChild($elt);
        }
    }

    $doc->formatOutput = true;
    return $doc->saveXML();
}

// ================================================================================================

// Création du flux JSON en sortie
function creerFluxJSON($msg, $donneesTrace = null)
{
    $data = ["reponse" => $msg];
    if ($donneesTrace) {
        $data["donnees"] = ["trace" => $donneesTrace];
    }
    return json_encode(["data" => $data], JSON_PRETTY_PRINT);
}
