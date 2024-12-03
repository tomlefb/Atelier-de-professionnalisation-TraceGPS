<?php

namespace api\services;

class DemanderMdp
{
    public function demanderNouveauMotDePasse($pseudo) {
        // Vérification que le pseudo existe
        $txt_req = "SELECT adrMail FROM tracegps_utilisateurs WHERE pseudo = :pseudo";
        $req = $this->cnx->prepare($txt_req);
        $req->bindValue("pseudo", $pseudo, PDO::PARAM_STR);
        $req->execute();
        $result = $req->fetch(PDO::FETCH_ASSOC);
        $req->closeCursor();

        if (!$result) {
            return "Erreur : pseudo inexistant.";
        }

        $email = $result['adrMail'];

        // Génération d'un nouveau mot de passe
        $nouveauMotDePasse = $this->genererMotDePasse();

        // Hashage du mot de passe en SHA1
        $motDePasseSha1 = sha1($nouveauMotDePasse);

        // Mise à jour du mot de passe dans la base de données
        $txt_req_update = "UPDATE tracegps_utilisateurs SET mdpSha1 = :mdpSha1 WHERE pseudo = :pseudo";
        $req_update = $this->cnx->prepare($txt_req_update);
        $req_update->bindValue("mdpSha1", $motDePasseSha1, PDO::PARAM_STR);
        $req_update->bindValue("pseudo", $pseudo, PDO::PARAM_STR);
        $success = $req_update->execute();
        $req_update->closeCursor();

        if (!$success) {
            return "Erreur : problème lors de l'enregistrement du mot de passe.";
        }

        // Envoi de l'email
        $sujet = "Votre nouveau mot de passe";
        $message = "Bonjour, voici votre nouveau mot de passe : " . $nouveauMotDePasse;
        $headers = "From: no-reply@tracegps.com";

        if (!mail($email, $sujet, $message, $headers)) {
            return "Enregistrement effectué ; l'envoi du courriel de confirmation a rencontré un problème.";
        }

        return "Vous allez recevoir un courriel avec votre nouveau mot de passe.";
    }

// Méthode privée pour générer un mot de passe
    private function genererMotDePasse() {
        $consonnes = ['b', 'c', 'd', 'f', 'g', 'h', 'j', 'k', 'l', 'm', 'n', 'p', 'r', 's', 't', 'v', 'w', 'x', 'y', 'z'];
        $voyelles = ['a', 'e', 'i', 'o', 'u'];

        $motDePasse = '';
        for ($i = 0; $i < 4; $i++) {
            $motDePasse .= $consonnes[array_rand($consonnes)];
            $motDePasse .= $voyelles[array_rand($voyelles)];
        }

        return $motDePasse;
    }
}