# georef-api

## Pré-requis

PostgreSQL > 9.2
Postgis
Serveur web
Composer

## Installation BDD

Installation postgreSQL / PostGis
Création d'un role + mdp
Création d'une DB avec propriétaire le role
Création de l'extension Postgis
Modification du pg_hba.conf
Reload de postgres
Tester l'accès avec le login/mdp

## Installation Web
Installation serveur web
Récupération des sources git
Installation de composer
Composer update dans libs/

## Configuration

Reprendre le fichier private/conf/conf.sample.json
Créer un conf.json
Modifier bdd, login et mdp

## Les données

Un jeu de données est disponible dans private/install
Le fichier xml private/conf/layers.xml décrit les données disponibles dans l'API
Le point d'accès de l'API est api.php/layers/nom_de_la_couche?y=47.71969&x=-2.92285

## Tests

*   Welcome : http://your_host/georef-api/api.php/
*   Accès à la BDD : http://your_host/georef-api/api.php/test/accessBdd
*   Couches dispos : http://your_host/georef-api/api.php/layersAvailable
*   Couches dispos : http://your_host/georef-api/api.php/layersAvailable