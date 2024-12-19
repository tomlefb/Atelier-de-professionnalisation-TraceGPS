<?php
// Projet TraceGPS
// fichier : modele/DAO.test.php.php
// Rôle : test.php de la classe DAO.php
// Dernière mise à jour : 7/7/2021 par dPlanchet
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Test de la classe DAO</title>
	<style type="text/css">body {font-family: Arial, Helvetica, sans-serif; font-size: small;}</style>
</head>
<body>

<?php
// connexion du serveur web à la base MySQL
include_once ('../../src/modele/DAO.php');
$dao = new DAO();


// test.php de la méthode getNiveauConnexion ----------------------------------------------------------
// modifié par dP le 12/8/2018
echo "<h3>Test de getNiveauConnexion : </h3>";
$niveau = $dao->getNiveauConnexion("admin", sha1("mdpadmin"));
echo "<p>Niveau de ('admin', 'mdpadmin') : " . $niveau . "</br>";

$niveau = $dao->getNiveauConnexion("europa", sha1("mdputilisateur"));
echo "<p>Niveau de ('europa', 'mdputilisateur') : " . $niveau . "</br>";

$niveau = $dao->getNiveauConnexion("europa", sha1("123456"));
echo "<p>Niveau de ('europa', '123456') : " . $niveau . "</br>";

$niveau = $dao->getNiveauConnexion("toto", sha1("mdputilisateur"));
echo "<p>Niveau de ('toto', 'mdputilisateur') : " . $niveau . "</br>";




// test.php de la méthode existePseudoUtilisateur -----------------------------------------------------
// modifié par dP le 12/8/2018
echo "<h3>Test de existePseudoUtilisateur : </h3>";
if ($dao->existePseudoUtilisateur("admin")) $existe = "oui"; else $existe = "non";
echo "<p>Existence de l'utilisateur 'admin' : <b>" . $existe . "</b><br>";
if ($dao->existePseudoUtilisateur("europa")) $existe = "oui"; else $existe = "non";
echo "Existence de l'utilisateur 'europa' : <b>" . $existe . "</b></br>";
if ($dao->existePseudoUtilisateur("toto")) $existe = "oui"; else $existe = "non";
echo "Existence de l'utilisateur 'toto' : <b>" . $existe . "</b></p>";




// test.php de la méthode getUnUtilisateur -----------------------------------------------------------
// modifié par dP le 12/8/2018
echo "<h3>Test de getUnUtilisateur : </h3>";
$unUtilisateur = $dao->getUnUtilisateur("admin");
if ($unUtilisateur) {
    echo "<p>L'utilisateur admin existe : <br>" . $unUtilisateur->toString() . "</p>";
}
else {
    echo "<p>L'utilisateur admin n'existe pas !</p>";
}
$unUtilisateur = $dao->getUnUtilisateur("europa");
if ($unUtilisateur) {
    echo "<p>L'utilisateur europa existe : <br>" . $unUtilisateur->toString() . "</p>";
}
else {
    echo "<p>L'utilisateur europa n'existe pas !</p>";
}
$unUtilisateur = $dao->getUnUtilisateur("admon");
if ($unUtilisateur) {
    echo "<p>L'utilisateur admon existe : <br>" . $unUtilisateur->toString() . "</p>";
}
else {
    echo "<p>L'utilisateur admon n'existe pas !</p>";
}




// test.php de la méthode getTousLesUtilisateurs ------------------------------------------------------
// modifié par dP le 12/8/2018
echo "<h3>Test de getTousLesUtilisateurs : </h3>";
$lesUtilisateurs = $dao->getTousLesUtilisateurs();
$nbReponses = sizeof($lesUtilisateurs);
echo "<p>Nombre d'utilisateurs : " . $nbReponses . "</p>";
// affichage des utilisateurs
foreach ($lesUtilisateurs as $unUtilisateur)
{	echo ($unUtilisateur->toString());
    echo ('<br>');
}




// test.php de la méthode creerUnUtilisateur ----------------------------------------------------------
// modifié par dP le 12/8/2018
echo "<h3>Test de creerUnUtilisateur : </h3>";
$unUtilisateur = new Utilisateur(0, "toto", "mdputilisateur", "delasalle.sio.xxxx@gmail.com", "5566778899", 1, date('Y-m-d H:i:s', time()), 0, null);
$ok = $dao->creerUnUtilisateur($unUtilisateur);
if ($ok)
{   echo "<p>Utilisateur bien enregistré !</p>";
    echo $unUtilisateur->toString();
}
else {
    echo "<p>Echec lors de l'enregistrement de l'utilisateur !</p>";
}




// test.php de la méthode modifierMdpUtilisateur ------------------------------------------------------
// modifié par dP le 12/8/2018
echo "<h3>Test de modifierMdpUtilisateur : </h3>";
$unUtilisateur = $dao->getUnUtilisateur("toto");
if ($unUtilisateur) {
    echo "<p>Ancien mot de passe de l'utilisateur toto : <b>" . $unUtilisateur->getMdpSha1() . "</b><br>";
    $dao->modifierMdpUtilisateur("toto", "mdpadmin");
    $unUtilisateur = $dao->getUnUtilisateur("toto");
    echo "Nouveau mot de passe de l'utilisateur toto : <b>" . $unUtilisateur->getMdpSha1() . "</b><br>";
    
    $niveauDeConnexion = $dao->getNiveauConnexion('toto', sha1('mdputilisateur'));
    echo "Niveau de connexion de ('toto', 'mdputilisateur') : <b>" . $niveauDeConnexion . "</b><br>";
    
    $niveauDeConnexion = $dao->getNiveauConnexion('toto', sha1('mdpadmin'));
    echo "Niveau de connexion de ('toto', 'mdpadmin') : <b>" . $niveauDeConnexion . "</b></p>";
}
else {
    echo "<p>L'utilisateur toto n'existe pas !</p>";
}




// test.php de la méthode supprimerUnUtilisateur ------------------------------------------------------
// modifié par dP le 12/8/2018
echo "<h3>Test de supprimerUnUtilisateur : </h3>";
$ok = $dao->supprimerUnUtilisateur("toto");
if ($ok) {
    echo "<p>Utilisateur toto bien supprimé !</p>";
}
else {
    echo "<p>Echec lors de la suppression de l'utilisateur toto !</p>";
}
$ok = $dao->supprimerUnUtilisateur("toto");
if ($ok) {
    echo "<p>Utilisateur toto bien supprimé !</p>";
}
else {
    echo "<p>Echec lors de la suppression de l'utilisateur toto !</p>";
}




// test.php de la méthode envoyerMdp ------------------------------------------------------------------
// modifié par dP le 12/8/2018
echo "<h3>Test de envoyerMdp : </h3>";
// pour ce test.php, une adresse mail que vous pouvez consulter
$unUtilisateur = new Utilisateur(0, "toto", "mdputilisateur", "delasalle.sio.xxxxx@gmail.com", "5566778899", 2, date('Y-m-d H:i:s', time()), 0, null);
$ok = $dao->creerUnUtilisateur($unUtilisateur);
$dao->modifierMdpUtilisateur("toto", "mdpadmin");
$ok = $dao->envoyerMdp("toto", "mdpadmin");
if ($ok) {
    echo "<p>Mail bien envoyé !</p>";
}
else {
    echo "<p>Echec lors de l'envoi du mail !</p>";
}
// supprimer le compte créé
$ok = $dao->supprimerUnUtilisateur("toto");
if ($ok) {
    echo "<p>Utilisateur toto bien supprimé !</p>";
}
else {
    echo "<p>Echec lors de la suppression de l'utilisateur toto !</p>";
}


// Test de la méthode existeAdrMailUtilisateur
echo "<h3>Test de existeAdrMailUtilisateur : </h3>";
if ($dao->existeAdrMailUtilisateur("admin@example.com")) $existe = "oui"; else $existe = "non";
echo "<p>Existence de l'adresse mail 'admin@example.com' : <b>" . $existe . "</b><br>";
if ($dao->existeAdrMailUtilisateur("inexistant@example.com")) $existe = "oui"; else $existe = "non";
echo "Existence de l'adresse mail 'inexistant@example.com' : <b>" . $existe . "</b></p>";


// Le code des tests restant à développer va être réparti entre les membres de l'équipe de développement.
// Afin de limiter les conflits avec GitHub, il est décidé d'attribuer un fichier de test.php à chaque développeur.
// Développeur 1 : fichier DAO.test1.php
// Développeur 2 : fichier DAO.test2.php
// Développeur 3 : fichier DAO.test3.php
// Développeur 4 : fichier DAO.test4.php

// Quelques conseils pour le travail collaboratif :
// avant d'attaquer un cycle de développement (début de séance, nouvelle méthode, ...), faites un Pull pour récupérer
// la dernière version du fichier.
// Après avoir testé et validé une méthode, faites un commit et un push pour transmettre cette version aux autres développeurs.


// ferme la connexion à MySQL :
unset($dao);
?>

</body>
</html>