<?php
// Projet TraceGPS - services web
// fichier : api/services/GetUnParcoursEtSesPoints.php
// Dernière mise à jour : 3/7/2021 par dP

// Rôle : ce service permet à un utilisateur d'obtenir le détail d'un de ses parcours ou d'un parcours d'un utilisateur qui l'autorise
// Le service web doit recevoir 4 paramètres :
//     pseudo : le pseudo de l'utilisateur
//     mdp : le mot de passe de l'utilisateur hashé en sha1
//     idTrace : l'identifiant de la trace
//     lang : le langage du flux de données retourné ("xml" ou "json") ; "xml" par défaut si le paramètre est absent ou incorrect
// Le service retourne un flux de données XML ou JSON contenant un compte-rendu d'exécution

// Les paramètres doivent être passés par la méthode GET :
//     http://<hébergeur>/tracegps/api/GetUnParcoursEtSesPoints?pseudo=callisto&mdp=13e3668bbee30b004380052b086457b014504b3e&idTrace=1&lang=xml

//include_once ('../../modele/DAO.php');
// connexion du serveur web à la base MySQL
$dao = new DAO();

// Récupération des données transmises
$pseudo = ( empty($this->request['pseudo'])) ? "" : $this->request['pseudo'];
$mdpSha1 = ( empty($this->request['mdp'])) ? "" : $this->request['mdp'];
$idTrace = ( empty($this->request['idTrace'])) ? "" : $this->request['idTrace'];
$lang = ( empty($this->request['lang'])) ? "" : $this->request['lang'];

// "xml" par défaut si le paramètre lang est absent ou incorrect
if ($lang != "json") $lang = "xml";

// La méthode HTTP utilisée doit être GET
if ($this->getMethodeRequete() != "GET")
{	$msg = "Erreur : méthode HTTP incorrecte.";
    $code_reponse = 406;
}
else {
    $uneTrace=null;
    // Les paramètres doivent être présents
    if ( $pseudo == "" || $mdpSha1 == "" || $idTrace == "" )
    {	$msg = "Erreur : données incomplètes.";
        $code_reponse = 400;
    }
    else
    {	if ( $dao->getNiveauConnexion($pseudo, $mdpSha1) == 0 ) {
            $msg = "Erreur : authentification incorrecte.";
            $code_reponse = 401;
        }
        else 
        {	// récupération de la trace et de ses points
            $uneTrace = $dao->getUneTrace($idTrace);
            if ($uneTrace == null) {
                $msg = "Erreur : parcours inexistant.";
                $code_reponse = 400;
            }
            else {
                $lesPointsDeTrace = $dao->getLesPointsDeTrace($idTrace);
                if ($lesPointsDeTrace == null) {
                    $msg = "Erreur : aucun point pour ce parcours.";
                    $code_reponse = 400;
                }
                else {
                    $msg = "Données du parcours et de ses points.";
                    $code_reponse = 200;
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
    $donnees = creerFluxXML($msg, $uneTrace, $lesPointsDeTrace);
}
else {
    $content_type = "application/json; charset=utf-8";      // indique le format Json pour la réponse
    $donnees = creerFluxJSON($msg, $uneTrace, $lesPointsDeTrace);
}

// envoi de la réponse HTTP
$this->envoyerReponse($code_reponse, $content_type, $donnees);

// fin du programme (pour ne pas enchainer sur les 2 fonctions qui suivent)
exit;

// ================================================================================================

// création du flux XML en sortie
function creerFluxXML($msg, $uneTrace, $lesPointsDeTrace)
{	// crée une instance de DOMdocument (DOM : Document Object Model)
    $doc = new DOMDocument();
    
    // specifie la version et le type d'encodage
    $doc->version = '1.0';
    $doc->encoding = 'UTF-8';
    
    // crée un commentaire et l'encode en UTF-8
    $elt_commentaire = $doc->createComment('Service web GetUnParcoursEtSesPoints - BTS SIO - Lycée De La Salle - Rennes');
    // place ce commentaire à la racine du document XML
    $doc->appendChild($elt_commentaire);
    
    // crée l'élément 'data' à la racine du document XML
    $elt_data = $doc->createElement('data');
    $doc->appendChild($elt_data);
    
    // place l'élément 'reponse' dans l'élément 'data'
    $elt_reponse = $doc->createElement('reponse', $msg);
    $elt_data->appendChild($elt_reponse);
    
    if ($uneTrace != null) {
        // place l'élément 'donnees' dans l'élément 'data'
        $elt_donnees = $doc->createElement('donnees');
        $elt_data->appendChild($elt_donnees);
        
        // place l'élément 'trace' dans l'élément 'donnees'
        $elt_trace = $doc->createElement('trace');
        $elt_donnees->appendChild($elt_trace);
        
        // crée les éléments enfants de l'élément 'trace'
        $elt_id = $doc->createElement('id', $uneTrace->getId());
        $elt_trace->appendChild($elt_id);
        
        $elt_dateHeureDebut = $doc->createElement('dateHeureDebut', $uneTrace->getDateHeureDebut());
        $elt_trace->appendChild($elt_dateHeureDebut);
        
        $elt_dateHeureFin = $doc->createElement('dateHeureFin', $uneTrace->getDateHeureFin());
        $elt_trace->appendChild($elt_dateHeureFin);
        
        $elt_terminee = $doc->createElement('terminee', $uneTrace->getTerminee());
        $elt_trace->appendChild($elt_terminee);
        
        $elt_idUtilisateur = $doc->createElement('idUtilisateur', $uneTrace->getIdUtilisateur());
        $elt_trace->appendChild($elt_idUtilisateur);
        
        // place l'élément 'lesPointsDeTrace' dans l'élément 'trace'
        $elt_lesPointsDeTrace = $doc->createElement('lesPointsDeTrace');
        $elt_trace->appendChild($elt_lesPointsDeTrace);
        
        // crée les éléments enfants de l'élément 'lesPointsDeTrace'
        foreach ($lesPointsDeTrace as $unPointDeTrace)
        {
            // crée un élément vide 'point'
            $elt_point = $doc->createElement('point');	    
            // place l'élément 'point' dans l'élément 'lesPointsDeTrace'
            $elt_lesPointsDeTrace->appendChild($elt_point);
        
            // crée les éléments enfants de l'élément 'point'
            $elt_id = $doc->createElement('id', $unPointDeTrace->getId());
            $elt_point->appendChild($elt_id);
            
            $elt_latitude = $doc->createElement('latitude', $unPointDeTrace->getLatitude());
            $elt_point->appendChild($elt_latitude);
            
            $elt_longitude = $doc->createElement('longitude', $unPointDeTrace->getLongitude());
            $elt_point->appendChild($elt_longitude);
            
            $elt_altitude = $doc->createElement('altitude', $unPointDeTrace->getAltitude());
            $elt_point->appendChild($elt_altitude);
            
            $elt_dateHeure = $doc->createElement('dateHeure', $unPointDeTrace->getDateHeure());
            $elt_point->appendChild($elt_dateHeure);
            
            $elt_rythmeCardio = $doc->createElement('rythmeCardio', $unPointDeTrace->getRythmeCardio());
            $elt_point->appendChild($elt_rythmeCardio);
            
            $elt_tempsCumule = $doc->createElement('tempsCumule', $unPointDeTrace->getTempsCumule());
            $elt_point->appendChild($elt_tempsCumule);
            
            $elt_distanceCumulee = $doc->createElement('distanceCumulee', $unPointDeTrace->getDistanceCumulee());
            $elt_point->appendChild($elt_distanceCumulee);
            
            $elt_vitesse = $doc->createElement('vitesse', $unPointDeTrace->getVitesse());
            $elt_point->appendChild($elt_vitesse);
        }
    }
    
    // Mise en forme finale
    $doc->formatOutput = true;
    
    // renvoie le contenu XML
    return $doc->saveXML();
}

// ================================================================================================

// création du flux JSON en sortie
function creerFluxJSON($msg, $uneTrace, $lesPointsDeTrace)
{
    /* Exemple de code JSON
        {
            "data": {
                "reponse": "Données du parcours et de ses points.",
                "donnees": {
                    "trace": {
                        "id": "1",
                        "dateHeureDebut": "2021-07-03 10:00:00",
                        "dateHeureFin": "2021-07-03 12:00:00",
                        "terminee": "1",
                        "idUtilisateur": "2",
                        "lesPointsDeTrace": [
                            {
                                "id": "1",
                                "latitude": "48.8566",
                                "longitude": "2.3522",
                                "altitude": "35",
                                "dateHeure": "2021-07-03 10:00:00",
                                "rythmeCardio": "80",
                                "tempsCumule": "0",
                                "distanceCumulee": "0",
                                "vitesse": "0"
                            },
                            ...
                        ]
                    }
                }
            }
        }
     */
    
    // construction de l'élément "data"
    $elt_data = ["reponse" => $msg];
    
    if ($uneTrace != null) {
        // construction de l'élément "trace"
        $elt_trace = [
            "id" => $uneTrace->getId(),
            "dateHeureDebut" => $uneTrace->getDateHeureDebut(),
            "dateHeureFin" => $uneTrace->getDateHeureFin(),
            "terminee" => $uneTrace->getTerminee(),
            "idUtilisateur" => $uneTrace->getIdUtilisateur(),
            "lesPointsDeTrace" => []
        ];
        
        // construction des éléments "point"
        foreach ($lesPointsDeTrace as $unPointDeTrace) {
            $elt_point = [
                "id" => $unPointDeTrace->getId(),
                "latitude" => $unPointDeTrace->getLatitude(),
                "longitude" => $unPointDeTrace->getLongitude(),
                "altitude" => $unPointDeTrace->getAltitude(),
                "dateHeure" => $unPointDeTrace->getDateHeure(),
                "rythmeCardio" => $unPointDeTrace->getRythmeCardio(),
                "tempsCumule" => $unPointDeTrace->getTempsCumule(),
                "distanceCumulee" => $unPointDeTrace->getDistanceCumulee(),
                "vitesse" => $unPointDeTrace->getVitesse()
            ];
            $elt_trace["lesPointsDeTrace"][] = $elt_point;
        }
        
        // ajout de l'élément "trace" dans "donnees"
        $elt_donnees = ["trace" => $elt_trace];
        $elt_data["donnees"] = $elt_donnees;
    }
    
    // construction de la racine
    $elt_racine = ["data" => $elt_data];
    
    // retourne le contenu JSON (l'option JSON_PRETTY_PRINT gère les sauts de ligne et l'indentation)
    return json_encode($elt_racine, JSON_PRETTY_PRINT);
}
?>