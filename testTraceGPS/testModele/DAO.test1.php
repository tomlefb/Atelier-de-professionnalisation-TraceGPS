<?php
// Projet TraceGPS
// fichier : modele/DAO.test1.php
// Rôle : test de la classe DAO.php
// Dernière mise à jour : xxxxxxxxxxxxxxxxx par xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

// Le code des tests restant à développer va être réparti entre les membres de l'équipe de développement.
// Afin de limiter les conflits avec GitHub, il est décidé d'attribuer un fichier de test à chaque développeur.
// Développeur 1 : fichier DAO.test1.php
// Développeur 2 : fichier DAO.test2.php
// Développeur 3 : fichier DAO.test3.php
// Développeur 4 : fichier DAO.test4.php

// Quelques conseils pour le travail collaboratif :
// avant d'attaquer un cycle de développement (début de séance, nouvelle méthode, ...), faites un Pull pour récupérer
// la dernière version du fichier.
// Après avoir testé et validé une méthode, faites un commit et un push pour transmettre cette version aux autres développeurs.
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


// Test de la méthode getLesTracesAutorisees ----------------------------------------------------------
echo "<h3>Test de getLesTracesAutorisees : </h3>";
$lesTraces = $dao->getLesTracesAutorisees(2);
echo "<p>Nombre de traces autorisées à l'utilisateur 2 : " . count($lesTraces) . "</p>";
foreach ($lesTraces as $uneTrace) {
    echo $uneTrace->toString() . "<br>";
}
$lesTraces = $dao->getLesTracesAutorisees(3);
echo "<p>Nombre de traces autorisées à l'utilisateur 3 : " . count($lesTraces) . "</p>";
foreach ($lesTraces as $uneTrace) {
    echo $uneTrace->toString() . "<br>";
}

// Test de la méthode creerUneTrace ----------------------------------------------------------
echo "<h3>Test de creerUneTrace : </h3>";
$trace1 = new Trace(0, "2017-12-18 14:00:00", "2017-12-18 14:10:00", true, 3);
$ok = $dao->creerUneTrace($trace1);
if ($ok) {
    echo "<p>Trace bien enregistrée !</p>";
    echo $trace1->toString();
} else {
    echo "<p>Echec lors de l'enregistrement de la trace !</p>";
}

$trace2 = new Trace(0, date('Y-m-d H:i:s'), null, false, 3);
$ok = $dao->creerUneTrace($trace2);
if ($ok) {
    echo "<p>Trace bien enregistrée !</p>";
    echo $trace2->toString();
} else {
    echo "<p>Echec lors de l'enregistrement de la trace !</p>";
}

// Test de la méthode supprimerUneTrace ----------------------------------------------------------
echo "<h3>Test de supprimerUneTrace : </h3>";
$ok = $dao->supprimerUneTrace($trace1->getId());
if ($ok) {
    echo "<p>Trace bien supprimée !</p>";
} else {
    echo "<p>Echec lors de la suppression de la trace !</p>";
}

// Test de la méthode terminerUneTrace ----------------------------------------------------------
echo "<h3>Test de terminerUneTrace : </h3>";
// On choisit une trace non terminée
$unIdTrace = $trace2->getId();
$laTrace = $dao->getUneTrace($unIdTrace);
if ($laTrace) {
    echo "<h4>L'objet laTrace avant l'appel de la méthode terminerUneTrace :</h4>";
    echo $laTrace->toString() . "<br>";

    $dao->terminerUneTrace($unIdTrace);

    $laTrace = $dao->getUneTrace($unIdTrace);
    echo "<h4>L'objet laTrace après l'appel de la méthode terminerUneTrace :</h4>";
    echo $laTrace->toString() . "<br>";
} else {
    echo "<p>La trace avec l'ID $unIdTrace n'existe pas !</p>";
}








// ferme la connexion à MySQL :
unset($dao);
?>

</body>
</html>