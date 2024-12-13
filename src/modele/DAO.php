<?php
// Projet TraceGPS
// fichier : modele/DAO.php   (DAO : Data Access Object)
// Rôle : fournit des méthodes d'accès à la bdd tracegps (projet TraceGPS) au moyen de l'objet PDO
// modifié par dP le 12/8/2021

// liste des méthodes déjà développées (dans l'ordre d'apparition dans le fichier) :

// __construct() : le constructeur crée la connexion $cnx à la base de données
// __destruct() : le destructeur ferme la connexion $cnx à la base de données
// getNiveauConnexion($login, $mdp) : fournit le niveau (0, 1 ou 2) d'un utilisateur identifié par $login et $mdp
// existePseudoUtilisateur($pseudo) : fournit true si le pseudo $pseudo existe dans la table tracegps_utilisateurs, false sinon
// getUnUtilisateur($login) : fournit un objet Utilisateur à partir de $login (son pseudo ou son adresse mail)
// getTousLesUtilisateurs() : fournit la collection de tous les utilisateurs (de niveau 1)
// creerUnUtilisateur($unUtilisateur) : enregistre l'utilisateur $unUtilisateur dans la bdd
// modifierMdpUtilisateur($login, $nouveauMdp) : enregistre le nouveau mot de passe $nouveauMdp de l'utilisateur $login daprès l'avoir hashé en SHA1
// supprimerUnUtilisateur($login) : supprime l'utilisateur $login (son pseudo ou son adresse mail) dans la bdd, ainsi que ses traces et ses autorisations
// envoyerMdp($login, $nouveauMdp) : envoie un mail à l'utilisateur $login avec son nouveau mot de passe $nouveauMdp

// liste des méthodes restant à développer :

// existeAdrMailUtilisateur($adrmail) : fournit true si l'adresse mail $adrMail existe dans la table tracegps_utilisateurs, false sinon
// getLesUtilisateursAutorises($idUtilisateur) : fournit la collection  des utilisateurs (de niveau 1) autorisés à suivre l'utilisateur $idUtilisateur
// getLesUtilisateursAutorisant($idUtilisateur) : fournit la collection  des utilisateurs (de niveau 1) autorisant l'utilisateur $idUtilisateur à voir leurs parcours
// autoriseAConsulter($idAutorisant, $idAutorise) : vérifie que l'utilisateur $idAutorisant) autorise l'utilisateur $idAutorise à consulter ses traces
// creerUneAutorisation($idAutorisant, $idAutorise) : enregistre l'autorisation ($idAutorisant, $idAutorise) dans la bdd
// supprimerUneAutorisation($idAutorisant, $idAutorise) : supprime l'autorisation ($idAutorisant, $idAutorise) dans la bdd
// getLesPointsDeTrace($idTrace) : fournit la collection des points de la trace $idTrace
// getUneTrace($idTrace) : fournit un objet Trace à partir de identifiant $idTrace
// getToutesLesTraces() : fournit la collection de toutes les traces
// getLesTraces($idUtilisateur) : fournit la collection des traces de l'utilisateur $idUtilisateur
// getLesTracesAutorisees($idUtilisateur) : fournit la collection des traces que l'utilisateur $idUtilisateur a le droit de consulter
// creerUneTrace(Trace $uneTrace) : enregistre la trace $uneTrace dans la bdd
// terminerUneTrace($idTrace) : enregistre la fin de la trace d'identifiant $idTrace dans la bdd ainsi que la date de fin
// supprimerUneTrace($idTrace) : supprime la trace d'identifiant $idTrace dans la bdd, ainsi que tous ses points
// creerUnPointDeTrace(PointDeTrace $unPointDeTrace) : enregistre le point $unPointDeTrace dans la bdd


// certaines méthodes nécessitent les classes suivantes :
use modele\Point;

include_once ('Utilisateur.php');
include_once ('Trace.php');
include_once ('PointDeTrace.php');
include_once ('Point.php');
include_once ('Outils.php');

// inclusion des paramètres de l'application
include_once('parametres.php');

// début de la classe DAO (Data Access Object)
class DAO
{
    // ------------------------------------------------------------------------------------------------------
    // ---------------------------------- Membres privés de la classe ---------------------------------------
    // ------------------------------------------------------------------------------------------------------
    
    private $cnx;				// la connexion à la base de données
    
    // ------------------------------------------------------------------------------------------------------
    // ---------------------------------- Constructeur et destructeur ---------------------------------------
    // ------------------------------------------------------------------------------------------------------
    public function __construct() {
        global $PARAM_HOTE, $PARAM_PORT, $PARAM_BDD, $PARAM_USER, $PARAM_PWD;
        echo $PARAM_HOTE;
        try
        {	$this->cnx = new PDO ("mysql:host=" . $PARAM_HOTE . ";port=" . $PARAM_PORT . ";dbname=" . $PARAM_BDD,
            $PARAM_USER,
            $PARAM_PWD);
        return true;
        }
        catch (Exception $ex)
        {	echo ("Echec de la connexion a la base de donnees <br>");
        echo ("Erreur numero : " . $ex->getCode() . "<br />" . "Description : " . $ex->getMessage() . "<br>");
        echo ("PARAM_HOTE = " . $PARAM_HOTE);
        return false;
        }
    }



    public function __destruct() {
        // ferme la connexion à MySQL :
        unset($this->cnx);
    }
    
    // ------------------------------------------------------------------------------------------------------
    // -------------------------------------- Méthodes d'instances ------------------------------------------
    // ------------------------------------------------------------------------------------------------------
    
    // fournit le niveau (0, 1 ou 2) d'un utilisateur identifié par $pseudo et $mdpSha1
    // cette fonction renvoie un entier :
    //     0 : authentification incorrecte
    //     1 : authentification correcte d'un utilisateur (pratiquant ou personne autorisée)
    //     2 : authentification correcte d'un administrateur
    // modifié par dP le 11/1/2018
    public function getNiveauConnexion($pseudo, $mdpSha1) {
        // préparation de la requête de recherche
        $txt_req = "Select niveau from tracegps_utilisateurs";
        $txt_req .= " where pseudo = :pseudo";
        $txt_req .= " and mdpSha1 = :mdpSha1";
        $req = $this->cnx->prepare($txt_req);
        // liaison de la requête et de ses paramètres
        $req->bindValue("pseudo", $pseudo, PDO::PARAM_STR);
        $req->bindValue("mdpSha1", $mdpSha1, PDO::PARAM_STR);
        // extraction des données
        $req->execute();
        $uneLigne = $req->fetch(PDO::FETCH_OBJ);
        // traitement de la réponse
        $reponse = 0;
        if ($uneLigne) {
        	$reponse = $uneLigne->niveau;
        }
        // libère les ressources du jeu de données
        $req->closeCursor();
        // fourniture de la réponse
        return $reponse;
    }
    
    
    // fournit true si le pseudo $pseudo existe dans la table tracegps_utilisateurs, false sinon
    // modifié par dP le 27/12/2017
    public function existePseudoUtilisateur($pseudo) {
        // préparation de la requête de recherche
        $txt_req = "Select count(*) from tracegps_utilisateurs where pseudo = :pseudo";
        $req = $this->cnx->prepare($txt_req);
        // liaison de la requête et de ses paramètres
        $req->bindValue("pseudo", $pseudo, PDO::PARAM_STR);
        // exécution de la requête
        $req->execute();
        $nbReponses = $req->fetchColumn(0);
        // libère les ressources du jeu de données
        $req->closeCursor();
        
        // fourniture de la réponse
        if ($nbReponses == 0) {
            return false;
        }
        else {
            return true;
        }
    }
    
    
    // fournit un objet Utilisateur à partir de son pseudo $pseudo
    // fournit la valeur null si le pseudo n'existe pas
    // modifié par dP le 9/1/2018
    public function getUnUtilisateur($pseudo) {
        // préparation de la requête de recherche
        $txt_req = "Select id, pseudo, mdpSha1, adrMail, numTel, niveau, dateCreation, nbTraces, dateDerniereTrace";
        $txt_req .= " from tracegps_vue_utilisateurs";
        $txt_req .= " where pseudo = :pseudo";
        $req = $this->cnx->prepare($txt_req);
        // liaison de la requête et de ses paramètres
        $req->bindValue("pseudo", $pseudo, PDO::PARAM_STR);
        // extraction des données
        $req->execute();
        $uneLigne = $req->fetch(PDO::FETCH_OBJ);
        // libère les ressources du jeu de données
        $req->closeCursor();
        
        // traitement de la réponse
        if ( ! $uneLigne) {
            return null;
        }
        else {
            // création d'un objet Utilisateur
            $unId = $uneLigne->id !== null ? mb_convert_encoding($uneLigne->id, 'UTF-8', 'UTF-8') : "";
            $unPseudo = $uneLigne->pseudo !== null ? mb_convert_encoding($uneLigne->pseudo, 'UTF-8', 'UTF-8') : "";
            $unMdpSha1 = $uneLigne->mdpSha1 !== null ? mb_convert_encoding($uneLigne->mdpSha1, 'UTF-8', 'UTF-8') : "";
            $uneAdrMail = $uneLigne->adrMail !== null ? mb_convert_encoding($uneLigne->adrMail, 'UTF-8', 'UTF-8') : "";
            $unNumTel = $uneLigne->numTel !== null ? mb_convert_encoding($uneLigne->numTel, 'UTF-8', 'UTF-8') : "";
            $unNiveau = $uneLigne->niveau !== null ? mb_convert_encoding($uneLigne->niveau, 'UTF-8', 'UTF-8') : "";
            $uneDateCreation = $uneLigne->dateCreation !== null ? mb_convert_encoding($uneLigne->dateCreation, 'UTF-8', 'UTF-8') : "";
            $unNbTraces = $uneLigne->nbTraces !== null ? mb_convert_encoding($uneLigne->nbTraces, 'UTF-8', 'UTF-8') : "";
            $uneDateDerniereTrace = $uneLigne->dateDerniereTrace !== null ? mb_convert_encoding($uneLigne->dateDerniereTrace, 'UTF-8', 'UTF-8') : "";
            
            $unUtilisateur = new Utilisateur($unId, $unPseudo, $unMdpSha1, $uneAdrMail, $unNumTel, $unNiveau, $uneDateCreation, $unNbTraces, $uneDateDerniereTrace);
            return $unUtilisateur;
        }
    }
    
    
    // fournit la collection  de tous les utilisateurs (de niveau 1)
    // le résultat est fourni sous forme d'une collection d'objets Utilisateur
    // modifié par dP le 27/12/2017
    public function getTousLesUtilisateurs() {
        // préparation de la requête de recherche
        $txt_req = "Select id, pseudo, mdpSha1, adrMail, numTel, niveau, dateCreation, nbTraces, dateDerniereTrace";
        $txt_req .= " from tracegps_vue_utilisateurs";
        $txt_req .= " where niveau = 1";
        $txt_req .= " order by pseudo";
        
        $req = $this->cnx->prepare($txt_req);
        // extraction des données
        $req->execute();
        $uneLigne = $req->fetch(PDO::FETCH_OBJ);
        
        // construction d'une collection d'objets Utilisateur
        $lesUtilisateurs = array();
        // tant qu'une ligne est trouvée :
        while ($uneLigne) {
            // création d'un objet Utilisateur
            $unId = $uneLigne->id !== null ? mb_convert_encoding($uneLigne->id, 'UTF-8', 'UTF-8') : "";
            $unPseudo = $uneLigne->pseudo !== null ? mb_convert_encoding($uneLigne->pseudo, 'UTF-8', 'UTF-8') : "";
            $unMdpSha1 = $uneLigne->mdpSha1 !== null ? mb_convert_encoding($uneLigne->mdpSha1, 'UTF-8', 'UTF-8') : "";
            $uneAdrMail = $uneLigne->adrMail !== null ? mb_convert_encoding($uneLigne->adrMail, 'UTF-8', 'UTF-8') : "";
            $unNumTel = $uneLigne->numTel !== null ? mb_convert_encoding($uneLigne->numTel, 'UTF-8', 'UTF-8') : "";
            $unNiveau = $uneLigne->niveau !== null ? mb_convert_encoding($uneLigne->niveau, 'UTF-8', 'UTF-8') : "";
            $uneDateCreation = $uneLigne->dateCreation !== null ? mb_convert_encoding($uneLigne->dateCreation, 'UTF-8', 'UTF-8') : "";
            $unNbTraces = $uneLigne->nbTraces !== null ? mb_convert_encoding($uneLigne->nbTraces, 'UTF-8', 'UTF-8') : "";
            $uneDateDerniereTrace = $uneLigne->dateDerniereTrace !== null ? mb_convert_encoding($uneLigne->dateDerniereTrace, 'UTF-8', 'UTF-8') : "";
            
            $unUtilisateur = new Utilisateur($unId, $unPseudo, $unMdpSha1, $uneAdrMail, $unNumTel, $unNiveau, $uneDateCreation, $unNbTraces, $uneDateDerniereTrace);
            // ajout de l'utilisateur à la collection
            $lesUtilisateurs[] = $unUtilisateur;
            // extrait la ligne suivante
            $uneLigne = $req->fetch(PDO::FETCH_OBJ);
        }
        // libère les ressources du jeu de données
        $req->closeCursor();
        // fourniture de la collection
        return $lesUtilisateurs;
    }

    
    // enregistre l'utilisateur $unUtilisateur dans la bdd
    // fournit true si l'enregistrement s'est bien effectué, false sinon
    // met à jour l'objet $unUtilisateur avec l'id (auto_increment) attribué par le SGBD
    // modifié par dP le 9/1/2018
    public function creerUnUtilisateur($unUtilisateur) {
        // on teste si l'utilisateur existe déjà
        if ($this->existePseudoUtilisateur($unUtilisateur->getPseudo())) return false;
        
        // préparation de la requête
        $txt_req1 = "insert into tracegps_utilisateurs (pseudo, mdpSha1, adrMail, numTel, niveau, dateCreation)";
        $txt_req1 .= " values (:pseudo, :mdpSha1, :adrMail, :numTel, :niveau, :dateCreation)";
        $req1 = $this->cnx->prepare($txt_req1);
        // liaison de la requête et de ses paramètres
        $req1->bindValue("pseudo", $unUtilisateur->getPseudo() !== null ? mb_convert_encoding($unUtilisateur->getPseudo(), 'UTF-8', 'UTF-8') : "", PDO::PARAM_STR);
        $req1->bindValue("mdpSha1", $unUtilisateur->getMdpsha1() !== null ? mb_convert_encoding(sha1($unUtilisateur->getMdpsha1()), 'UTF-8', 'UTF-8') : "", PDO::PARAM_STR);
        $req1->bindValue("adrMail", $unUtilisateur->getAdrMail() !== null ? mb_convert_encoding($unUtilisateur->getAdrmail(), 'UTF-8', 'UTF-8') : "", PDO::PARAM_STR);
        $req1->bindValue("numTel", $unUtilisateur->getNumTel() !== null ? mb_convert_encoding($unUtilisateur->getNumTel(), 'UTF-8', 'UTF-8') : "", PDO::PARAM_STR);
        $req1->bindValue("niveau", $unUtilisateur->getNiveau() !== null ? mb_convert_encoding($unUtilisateur->getNiveau(), 'UTF-8', 'UTF-8') : "", PDO::PARAM_INT);
        $req1->bindValue("dateCreation", $unUtilisateur->getDateCreation() !== null ? mb_convert_encoding($unUtilisateur->getDateCreation(), 'UTF-8', 'UTF-8') : "", PDO::PARAM_STR);
        // exécution de la requête
        $ok = $req1->execute();
        // sortir en cas d'échec
        if ( ! $ok) { return false; }
        
        // recherche de l'identifiant (auto_increment) qui a été attribué à la trace
        $unId = $this->cnx->lastInsertId();
        $unUtilisateur->setId($unId);
        return true;
    }
    
    
    // enregistre le nouveau mot de passe $nouveauMdp de l'utilisateur $pseudo daprès l'avoir hashé en SHA1
    // fournit true si la modification s'est bien effectuée, false sinon
    // modifié par dP le 9/1/2018
    // modifie le mot de passe de l'utilisateur $pseudo avec le nouveau mot de passe $nouveauMdp
    // fournit true si la modification s'est bien effectuée, false sinon
    public function modifierMdpUtilisateur($pseudo, $nouveauMdp)
    {
        // préparation de la requête
        $txt_req = "update tracegps_utilisateurs set mdpSha1 = :nouveauMdp";
        $txt_req .= " where pseudo = :pseudo";
        $req = $this->cnx->prepare($txt_req);
        // liaison de la requête et de ses paramètres
        $req->bindValue("nouveauMdp", $nouveauMdp !== null ? mb_convert_encoding(sha1($nouveauMdp), 'UTF-8', 'UTF-8') : "", PDO::PARAM_STR);
        $req->bindValue("pseudo", $pseudo !== null ? mb_convert_encoding($pseudo, 'UTF-8', 'UTF-8') : "", PDO::PARAM_STR);
        // exécution de la requête
        $ok = $req->execute();
        return $ok;
    }
    
    
    // supprime l'utilisateur $pseudo dans la bdd, ainsi que ses traces et ses autorisations
    // fournit true si l'effacement s'est bien effectué, false sinon
    // modifié par dP le 9/1/2018
    public function supprimerUnUtilisateur($pseudo) {
        $unUtilisateur = $this->getUnUtilisateur($pseudo);
        if ($unUtilisateur == null) {
            return false;
        } else {
            $idUtilisateur = $unUtilisateur->getId();

            // suppression des traces de l'utilisateur (et des points correspondants)
            $lesTraces = $this->getLesTraces($idUtilisateur);
            if ($lesTraces != null) {
                foreach ($lesTraces as $uneTrace) {
                    $this->supprimerUneTrace($uneTrace->getId());
                }
            }
            // préparation de la requête de suppression des autorisations
            $txt_req1 = "delete from tracegps_autorisations";
            $txt_req1 .= " where idAutorisant = :idUtilisateur or idAutorise = :idUtilisateur";
            $req1 = $this->cnx->prepare($txt_req1);
            // liaison de la requête et de ses paramètres
            $req1->bindValue("idUtilisateur", $idUtilisateur !== null ? mb_convert_encoding($idUtilisateur, 'UTF-8', 'UTF-8') : "", PDO::PARAM_INT);
            // exécution de la requête
            $ok = $req1->execute();

            // préparation de la requête de suppression de l'utilisateur
            $txt_req2 = "delete from tracegps_utilisateurs";
            $txt_req2 .= " where pseudo = :pseudo";
            $req2 = $this->cnx->prepare($txt_req2);
            // liaison de la requête et de ses paramètres
            $req2->bindValue("pseudo", $pseudo !== null ? mb_convert_encoding($pseudo, 'UTF-8', 'UTF-8') : "", PDO::PARAM_STR);
            // exécution de la requête
            $ok = $req2->execute();
            return $ok;
        }
    }
    
    
    // envoie un mail à l'utilisateur $pseudo avec son nouveau mot de passe $nouveauMdp
    // retourne true si envoi correct, false en cas de problème d'envoi
    // modifié par dP le 9/1/2018
    public function envoyerMdp($pseudo, $nouveauMdp) {
        global $ADR_MAIL_EMETTEUR;
        // si le pseudo n'est pas dans la table tracegps_utilisateurs :
        if ($this->existePseudoUtilisateur($pseudo) == false) return false;

        // recherche de l'adresse mail
        $utilisateur = $this->getUnUtilisateur($pseudo);
        $adrMail = $utilisateur !== null && $utilisateur->getAdrMail() !== null ? mb_convert_encoding($utilisateur->getAdrMail(), 'UTF-8', 'UTF-8') : "";

        // envoie un mail à l'utilisateur avec son nouveau mot de passe
        $sujet = "Modification de votre mot de passe d'accès au service TraceGPS";
        $message = "Cher(chère) " . ($pseudo !== null ? mb_convert_encoding($pseudo, 'UTF-8', 'UTF-8') : "") . "\n\n";
        $message .= "Votre mot de passe d'accès au service TraceGPS a été modifié.\n\n";
        $message .= "Votre nouveau mot de passe est : " . ($nouveauMdp !== null ? mb_convert_encoding($nouveauMdp, 'UTF-8', 'UTF-8') : "");
        $ok = Outils::envoyerMail($adrMail, $sujet, $message, $ADR_MAIL_EMETTEUR);
        return $ok;
    }
    
    
    // Le code restant à développer va être réparti entre les membres de l'équipe de développement.
    // Afin de limiter les conflits avec GitHub, il est décidé d'attribuer une zone de ce fichier à chaque développeur.
    // Développeur 1 : lignes 350 à 549
    // Développeur 2 : lignes 550 à 749
    // Développeur 3 : lignes 750 à 949
    // Développeur 4 : lignes 950 à 1150
    
    // Quelques conseils pour le travail collaboratif :
    // avant d'attaquer un cycle de développement (début de séance, nouvelle méthode, ...), faites un Pull pour récupérer 
    // la dernière version du fichier.
    // Après avoir testé et validé une méthode, faites un commit et un push pour transmettre cette version aux autres développeurs.
    
    
    
    
    
    // --------------------------------------------------------------------------------------
    // début de la zone attribuée au développeur 1 (Tom) : lignes 350 à 549
    // --------------------------------------------------------------------------------------




    //12. getLesTracesAutorisees
    // fournit la collection des traces que l'utilisateur $idUtilisateur a le droit de consulter
    // le résultat est fourni sous forme d'une collection d'objets Trace
    public function getLesTracesAutorisees($idUtilisateur) {
        $txt_req = "SELECT t.id, t.dateDebut, t.dateFin, t.terminee, t.idUtilisateur
                FROM tracegps_traces t
                JOIN tracegps_autorisations a ON t.idUtilisateur = a.idAutorisant
                WHERE a.idAutorise = :idUtilisateur
                ORDER BY t.dateDebut DESC";
        $req = $this->cnx->prepare($txt_req);
        $req->bindValue("idUtilisateur", $idUtilisateur, PDO::PARAM_INT);
        $req->execute();

        $lesTraces = [];
        while ($uneLigne = $req->fetch(PDO::FETCH_OBJ)) {
            $uneTrace = new Trace(
                $uneLigne->id,
                $uneLigne->dateDebut,
                $uneLigne->dateFin,
                $uneLigne->terminee,
                $uneLigne->idUtilisateur
            );
            $lesPoints = $this->getLesPointsDeTrace($uneTrace->getId());
            foreach ($lesPoints as $unPoint) {
                $uneTrace->ajouterPoint($unPoint);
            }
            $lesTraces[] = $uneTrace;
        }
        $req->closeCursor();
        return $lesTraces;
    }


    //13. creerUneTrace
    // enregistre la trace $uneTrace dans la table tracegps_traces
    // met à jour l'objet $uneTrace avec l'identifiant attribué par le SGBD
    // retourne true si l'enregistrement a réussi, false sinon
    public function creerUneTrace($uneTrace) {
        $txt_req = "INSERT INTO tracegps_traces (dateDebut, dateFin, terminee, idUtilisateur)
                VALUES (:dateDebut, :dateFin, :terminee, :idUtilisateur)";
        $req = $this->cnx->prepare($txt_req);
        $req->bindValue("dateDebut", $uneTrace->getDateHeureDebut(), PDO::PARAM_STR);
        $req->bindValue("dateFin", $uneTrace->getDateHeureFin() ?? null, PDO::PARAM_NULL);
        $req->bindValue("terminee", $uneTrace->getTerminee(), PDO::PARAM_BOOL);
        $req->bindValue("idUtilisateur", $uneTrace->getIdUtilisateur(), PDO::PARAM_INT);

        $ok = $req->execute();
        if ($ok) {
            $uneTrace->setId($this->cnx->lastInsertId());
        }
        $req->closeCursor();
        return $ok;
    }



    //14. supprimerUneTrace
    // supprime la trace d'identifiant $idTrace dans la table tracegps_traces ainsi que tous ses points
    // retourne true si la suppression a réussi, false sinon
    public function supprimerUneTrace($idTrace) {
        $txt_req1 = "DELETE FROM tracegps_points WHERE idTrace = :idTrace";
        $req1 = $this->cnx->prepare($txt_req1);
        $req1->bindValue("idTrace", $idTrace, PDO::PARAM_INT);
        $ok1 = $req1->execute();

        $txt_req2 = "DELETE FROM tracegps_traces WHERE id = :idTrace";
        $req2 = $this->cnx->prepare($txt_req2);
        $req2->bindValue("idTrace", $idTrace, PDO::PARAM_INT);
        $ok2 = $req2->execute();

        return $ok1 && $ok2;
    }



    //15. terminerUneTrace
    // termine la trace d'identifiant $idTrace dans la table tracegps_traces
    // enregistre la date de fin et met le champ terminee à 1
    // retourne true si la modification a réussi, false sinon
    public function terminerUneTrace($idTrace) {
        $lesPoints = $this->getLesPointsDeTrace($idTrace);
        $dateFin = $lesPoints ? end($lesPoints)->getDateHeure() : date('Y-m-d H:i:s');

        $txt_req = "UPDATE tracegps_traces
                SET dateFin = :dateFin, terminee = 1
                WHERE id = :idTrace";
        $req = $this->cnx->prepare($txt_req);
        $req->bindValue("dateFin", $dateFin, PDO::PARAM_STR);
        $req->bindValue("idTrace", $idTrace, PDO::PARAM_INT);

        $ok = $req->execute();
        $req->closeCursor();
        return $ok;
    }









































































































































































































    // --------------------------------------------------------------------------------------
    // début de la zone attribuée au développeur 2 (Nael) : lignes 550 à 749
    // --------------------------------------------------------------------------------------


    public function autoriseAConsulter($idAutorisant, $idAutorise) {
        // Prépare une requête SQL pour vérifier l'existence d'une autorisation dans la table tracegps_autorisations
        $txtReq = "SELECT COUNT(*) FROM tracegps_autorisations WHERE idAutorisant = :idAutorisant AND idAutorise = :idAutorise";
        $req = $this->cnx->prepare($txtReq);
        // Lie les paramètres
        $req->bindValue(":idAutorisant", $idAutorisant, PDO::PARAM_INT);
        $req->bindValue(":idAutorise", $idAutorise, PDO::PARAM_INT);
        // Exécute la requête
        $req->execute();
        // Récupère le résultat
        $nbLignes = $req->fetchColumn();
        // Retourne true si au moins une autorisation existe, sinon false
        return ($nbLignes > 0);
    }

    public function creerUneAutorisation($idAutorisant, $idAutorise) {
        // Vérifie d'abord si l'autorisation existe déjà
        if ($this->autoriseAConsulter($idAutorisant, $idAutorise)) {
            return false; // Retourne false si l'autorisation existe déjà
        }

        // Prépare la requête d'insertion
        $txtReq = "INSERT INTO tracegps_autorisations (idAutorisant, idAutorise) VALUES (:idAutorisant, :idAutorise)";
        $req = $this->cnx->prepare($txtReq);
        // Lie les paramètres
        $req->bindValue(":idAutorisant", $idAutorisant, PDO::PARAM_INT);
        $req->bindValue(":idAutorise", $idAutorise, PDO::PARAM_INT);

        // Exécute la requête et retourne le résultat
        try {
            $req->execute();
            return true; // Retourne true si l'insertion a réussi
        } catch (Exception $ex) {
            // En cas d'erreur, retourne false
            return false;
        }
    }

    public function supprimerUneAutorisation($idAutorisant, $idAutorise) {
        // Prépare la requête de suppression
        $txtReq = "DELETE FROM tracegps_autorisations WHERE idAutorisant = :idAutorisant AND idAutorise = :idAutorise";
        $req = $this->cnx->prepare($txtReq);
        // Lie les paramètres
        $req->bindValue(":idAutorisant", $idAutorisant, PDO::PARAM_INT);
        $req->bindValue(":idAutorise", $idAutorise, PDO::PARAM_INT);

        // Exécute la requête et retourne true si une ligne a été affectée
        try {
            $req->execute();
            return ($req->rowCount() > 0); // rowCount retourne le nombre de lignes supprimées
        } catch (Exception $ex) {
            // En cas d'erreur, retourne false
            return false;
        }
    }

    public function getLesPointsDeTrace($idTrace) {
        $txtReq = "SELECT * FROM tracegps_points WHERE idTrace = :idTrace ORDER BY id";
        $req = $this->cnx->prepare($txtReq);
        $req->bindValue(":idTrace", $idTrace, PDO::PARAM_INT);

        $lesPoints = [];
        $tempsCumule = 0;          // Temps cumulé en secondes
        $distanceCumulee = 0.0;    // Distance cumulée en km
        $vitesse = 0;
        $precedentPoint = null;    // Stockage du point précédent pour calculs

        try {
            $req->execute();
            while ($uneLigne = $req->fetch(PDO::FETCH_ASSOC)) {

                $unPoint = new PointDeTrace(
                    $uneLigne['idTrace'],
                    $uneLigne['id'],
                    $uneLigne['latitude'],
                    $uneLigne['longitude'],
                    $uneLigne['altitude'],
                    $uneLigne['dateHeure'],
                    $uneLigne['rythmeCardio'],
                    $tempsCumule,
                    $distanceCumulee,
                    $vitesse
                );

                // Calcul de distance et de temps
                if (count($lesPoints) >0) {

                    $distanceCumulee += Point::getDistance($precedentPoint, $unPoint);
                    $unPoint->setDistanceCumulee($distanceCumulee);
                    $tempsCumule += strtotime($unPoint->getDateHeure()) - strtotime($precedentPoint->getDateHeure());
                    $unPoint->setTempsCumule($tempsCumule);
                    // Calcul de la vitesse en km/h (distance / temps)
                    $vitesse = $tempsCumule > 0 ? ($distanceCumulee / ($tempsCumule / 3600)) : 0;
                    $unPoint->setVitesse($vitesse);

                }


                $lesPoints[] = $unPoint;
                $precedentPoint = $unPoint; // Mise à jour du point précédent
            }
        } catch (Exception $ex) {
            return [];
        }
        return $lesPoints;
    }


































































































































































































    // --------------------------------------------------------------------------------------
    // début de la zone attribuée au développeur 3 (xxxxxxxxxxxxxxxxxxxx) : lignes 750 à 949
    // --------------------------------------------------------------------------------------
    
    
    
    
    
    
    
    
    
    
    
    
    
    
        
    
    
    
    
    
    
    
    
    
    
    
    
        
    
    
    
    
    
    
    
    
    
    
    
    
        
    
    
    
    
    
    
    
    
    
    
    
    
        
    
    
    
    
    
    
    
    
    
    
    
    
        
    
    
    
    
    
    
    
    
    
    
    
    
        
    
    
    
    
    
    
    
    
    
    
    
    
        
    
    
    
    
    
    
    
    
    
    
    
    
        
    
    
    
    
    
    
    
    
    
    
    
    
        
    
    
    
    
    
    
    
    
    
    
    
    
        
    
    
    
    
    
    
    
    
    
    
    
    
        
    
    
    
    
    
    
    
    
    
    
    
    
        
    
    
    
    
    
    
    
    
    
    
    
    
        
    
    
    
    
    
    
    
    
    
    
    
    
        
    
    
    
    
    
    
    
    
    
    
    
    
   
    // --------------------------------------------------------------------------------------
    // début de la zone attribuée test au développeur 4 (Lohann) : lignes 950 à 1150
    // --------------------------------------------------------------------------------------
    //1.    existeAdrMailUtilisateur
    // Vérifie si une adresse e-mail existe dans la table tracegps_utilisateurs
    // Retourne true si l'adresse e-mail existe, false sinon
    public function existeAdrMailUtilisateur ($adrMail) {
        $txt_req = "SELECT count(*) from tracegps_utilisateurs where adrMail = :adrMail";
        $req = $this->cnx->prepare($txt_req);
        // liaison de la requête et de ses paramètres
        $req->bindValue("adrMail", $adrMail, PDO::PARAM_STR);
        // execution de la requête
        $req->execute();
        $nbReponses = $req->fetchColumn(0);
        //libère les ressources du jeu de données
        $req->closeCursor();

        if ($nbReponses == 0) {
            return false;
        }
        else {
            return true;
        }
    }

    //2. getLesUtilisateursAutorisant($idUtilisateur)
    //fournit la collection des utilisateurs (de niveau 1) autorisant l'utilisateur $idUtilisateur à voir leurs parcours
    //Retourne la collection des utilisateurs qui ont donné l'autorisation à $idUtilisateur
    public function getLesUtilisateursAutorisant($idUtilisateur) {
        // Requête pour récupérer les informations des utilisateurs autorisants
        $txt_req = "SELECT u.id, u.pseudo, u.mdpSha1, u.adrMail, u.numTel, u.niveau, u.dateCreation,
                       (SELECT COUNT(*) FROM tracegps_traces t WHERE t.idUtilisateur = u.id) AS nbTraces,
                       (SELECT MAX(t.dateDebut) FROM tracegps_traces t WHERE t.idUtilisateur = u.id) AS dateDerniereTrace
                FROM tracegps_utilisateurs u
                JOIN tracegps_autorisations a ON u.id = a.idAutorisant
                WHERE a.idAutorise = :idUtilisateur";

        // Préparation de la requête
        $req = $this->cnx->prepare($txt_req);

        // Liaison de l'idUtilisateur au paramètre
        $req->bindValue("idUtilisateur", $idUtilisateur, PDO::PARAM_INT);

        // Exécution de la requête
        $req->execute();

        // Tableau pour stocker les utilisateurs récupérés
        $lesUtilisateurs = [];

        // Parcours des résultats et création des objets Utilisateur
        while ($row = $req->fetch(PDO::FETCH_ASSOC)) {
            $unUtilisateur = new Utilisateur(
                $row['id'],
                $row['pseudo'],
                $row['mdpSha1'],
                $row['adrMail'],
                $row['numTel'],
                $row['niveau'],
                $row['dateCreation'],
                $row['nbTraces'],           // Nombre de traces
                $row['dateDerniereTrace']   // Date de la dernière trace
            );

            // Ajout de l'utilisateur au tableau
            $lesUtilisateurs[] = $unUtilisateur;
        }

        // Libération des ressources
        $req->closeCursor();

        // Retourne la collection d'utilisateurs
        return $lesUtilisateurs;
    }

    //3. fournit la collection des utilisateurs (de niveau 1) autorisés à voir les parcours de l'utilisateur $idUtilisateur
    //   Retourne la collection des utilisateurs qui sont autorisés à voir les parcours de l'utilisateur $idUtilisateur
    public function getLesUtilisateursAutorises($idUtilisateur) {
        // Requête SQL pour récupérer les utilisateurs autorisés
        $txt_req = "SELECT u.id, u.pseudo, u.mdpSha1, u.adrMail, u.numTel, u.niveau, u.dateCreation,
                   (SELECT COUNT(*) FROM tracegps_traces t WHERE t.idUtilisateur = u.id) AS nbTraces,
                   (SELECT MAX(t.dateDebut) FROM tracegps_traces t WHERE t.idUtilisateur = u.id) AS dateDerniereTrace
            FROM tracegps_utilisateurs u
            JOIN tracegps_autorisations a ON u.id = a.idAutorise
            WHERE a.idAutorisant = :idUtilisateur";


        // Préparation de la requête
        $req = $this->cnx->prepare($txt_req);

        // Liaison des paramètres
        $req->bindValue("idUtilisateur", $idUtilisateur, PDO::PARAM_INT);

        // Exécution de la requête
        $req->execute();

        // Tableau pour stocker les utilisateurs récupérés
        $lesUtilisateurs = [];

        // Parcours des résultats et instanciation des objets Utilisateur
        while ($row = $req->fetch(PDO::FETCH_ASSOC)) {
            $unUtilisateur = new Utilisateur(
                $row['id'],
                $row['pseudo'],
                $row['mdpSha1'],
                $row['adrMail'],
                $row['numTel'],
                $row['niveau'],
                $row['dateCreation'],
                $row['nbTraces'],           // Nombre de traces
                $row['dateDerniereTrace']   // Date de la dernière trace
            );

            // Ajout de l'utilisateur à la collection
            $lesUtilisateurs[] = $unUtilisateur;
        }

        // Libération des ressources
        $req->closeCursor();

        // Retourne la collection d'utilisateurs autorisés
        return $lesUtilisateurs;
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    



    
} // fin de la classe DAO
// ATTENTION : on ne met pas de balise de fin de script pour ne pas prendre le risque
// d'enregistrer d'espaces après la balise de fin de script !!!!!!!!!!!!