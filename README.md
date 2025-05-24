# Gestion de Matériel - Projet Test Symfony

Ce projet est une implémentation des fonctionnalités demandées pour le test technique Symfony de ManaTime.

## Fonctionnalités implémentées

* **Gestion des entités `Equipment` et `Employee`** :
    * UUID pour les identifiants.
    * `number` unique pour `Equipment`, `email` unique pour `Employee`.
    * Timestamps `createdAt`, `updatedAt` gérés automatiquement.
    * Soft delete pour `Equipment` via le champ `deletedAt`.
    * Relation `OneToMany` entre `Employee` et `Equipment`.
* **CRUD pour les équipements** :
    * Création, modification, affichage et liste.
    * Suppression logique (soft delete).
* **CRUD pour les employés** :
    * Création, modification, affichage et liste.
    * La suppression d'un employé désassigne ses équipements.
* **Association/Désassociation d'équipement à un employé** :
    * Via le formulaire d'équipement.
    * Bouton de désassignation rapide dans la liste des équipements.
* **Filtrage des équipements** :
    * Par catégorie, employé, et plage de dates de création.
* **Export des équipements** :
    * Formats CSV et JSON.
* **API REST pour les équipements** :
    * `GET /api/equipments` : Liste tous les équipements (non supprimés).
    * `POST /api/equipments` : Crée un nouvel équipement.

## Pré-requis

* PHP 8.1+
* Composer
* Symfony CLI
* MySQL 8.0+

## Installation

1.  **Clonez le dépôt Git** (si ce n'est pas déjà fait) :
    ```bash
    git clone https://github.com/angelolockdev/gestion-equipements.git
    cd gestion-equipements
    ```
2.  **Installez les dépendances Composer** :
    ```bash
    composer install
    ```
3.  **Configurez votre base de données MySQL** :
    * Copiez le fichier `.env` : `cp .env .env.local`
    * Éditez `.env.local` et mettez à jour la variable `DATABASE_URL` avec vos identifiants MySQL :
        ```dotenv
        DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/db_name?serverVersion=8.0.32&charset=utf8mb4"
        ```
4.  **Créez la base de données et exécutez les migrations Doctrine** :
    ```bash
    php bin/console doctrine:database:create
    php bin/console make:migration
    php bin/console doctrine:migrations:migrate
    ```
    *Note : `make:migration` générera un fichier de migration basé sur vos entités. Vous devrez l'exécuter avec `doctrine:migrations:migrate`.*
5.  **Installez le package `symfony/uid` pour les UUID** :
    ```bash
    composer require symfony/uid
    ```

## Lancement de l'application

Démarrez le serveur web Symfony :
```bash
symfony serve
```
L'application sera accessible à l'adresse indiquée dans votre terminal (généralement `https://127.0.0.1:8000`).

## Utilisation de l'API REST

L'API est accessible via le préfixe `/api`.

### Lister les équipements

* **Endpoint** : `GET /api/equipments`
* **Description** : Retourne une liste de tous les équipements actifs (non supprimés) au format JSON.
* **Exemple de requête (avec `curl`)** :
    ```bash
    curl -X GET http://127.0.0.1:8000/api/equipments -H "Accept: application/json"
    ```
* **Exemple de réponse** :
    ```json
    [
        {
            "id": "a1b2c3d4-e5f6-7890-1234-567890abcdef",
            "name": "Ordinateur portable",
            "category": "Informatique",
            "number": "SN12345",
            "description": "Laptop pour le développement",
            "createdAt": "2023-01-01T10:00:00+01:00",
            "updatedAt": "2023-01-01T10:00:00+01:00",
            "deletedAt": null,
            "employee": {
                "id": "b1c2d3e4-f5a6-7890-1234-567890abcdef",
                "firstName": "Jean",
                "lastName": "Dupont",
                "email": "jean.dupont@example.com",
                "hiredAt": "2022-01-01T00:00:00+01:00"
            }
        },
        // ... autres équipements
    ]
    ```

### Créer un équipement

* **Endpoint** : `POST /api/equipments`
* **Description** : Crée un nouvel équipement avec les données fournies.
* **Headers** : `Content-Type: application/json`
* **Exemple de requête (avec `curl`)** :
    ```bash
    curl -X POST http://127.0.0.1:8000/api/equipments \
         -H "Content-Type: application/json" \
         -d '{
               "name": "Clavier mécanique",
               "category": "Périphérique",
               "number": "KM98765",
               "description": "Clavier pour la saisie rapide",
               "employee": {
                   "id": "b1c2d3e4-f5a6-7890-1234-567890abcdef"
               }
             }'
    ```
    *Note : L'`id` de l'employé est optionnel. Si fourni, l'équipement sera assigné à cet employé.*
* **Exemple de réponse (succès - 201 Created)** :
    ```json
    {
        "id": "nouvel-uuid-genere",
        "name": "Clavier mécanique",
        "category": "Périphérique",
        "number": "KM98765",
        "description": "Clavier pour la saisie rapide",
        "createdAt": "2024-05-24T15:30:00+02:00",
        "updatedAt": "2024-05-24T15:30:00+02:00",
        "deletedAt": null,
        "employee": {
            "id": "b1c2d3e4-f5a6-7890-1234-567890abcdef",
            "firstName": "Jean",
            "lastName": "Dupont",
            "email": "jean.dupont@example.com",
            "hiredAt": "2022-01-01T00:00:00+01:00"
        }
    }
    ```
* **Exemple de réponse (erreur - 400 Bad Request ou 422 Unprocessable Entity)** :
    ```json
    {
        "errors": {
            "number": "Le numéro d'équipement est obligatoire."
        }
    }
    ```
    Ou si l'employé n'est pas trouvé :
    ```json
    {
        "message": "Employé non trouvé pour l'ID fourni."
    }
    ```

## Qualité de code et outils

* **PHPStan (niveau 5+)** :
    ```bash
    composer require --dev phpstan/phpstan
    # Créez un fichier phpstan.neon ou phpstan.neon.dist à la racine du projet
    # Exemple de contenu pour phpstan.neon.dist:
    # parameters:
    #     level: 5
    #     paths:
    #         - src/
    ```
    Exécutez : `vendor/bin/phpstan analyse`

* **PHP-CS-Fixer** :
    ```bash
    composer require --dev friendsofphp/php-cs-fixer
    # Créez un fichier .php-cs-fixer.dist.php à la racine du projet
    # Exemple de contenu pour .php-cs-fixer.dist.php:
    # <?php
    # return (new PhpCsFixer\Config())
    #     ->setRules([
    #         '@Symfony' => true,
    #         'array_syntax' => ['syntax' => 'short'],
    #     ])
    #     ->setFinder(
    #         PhpCsFixer\Finder::create()
    #             ->in(__DIR__.'/src')
    #     )
    # ;
    ```
    Exécutez : `vendor/bin/php-cs-fixer fix`

## Tests

* **PHPUnit (couverture min. 80%)** :
    ```bash
    composer require --dev symfony/test-pack
    ```
    Exécutez les tests : `php bin/phpunit`
    Pour la couverture de code (nécessite Xdebug ou PCOV) : `php bin/phpunit --coverage-html var/coverage`

### Structure des tests

* **`tests/Unit/`**: Contient les tests pour la logique métier des entités (`EquipmentTest.php`, `EmployeeTest.php`).
* **`tests/Functional/`**: Contient les tests d'intégration et fonctionnels pour les contrôleurs (`EquipmentControllerTest.php`, `EmployeeControllerTest.php`, `ApiControllerTest.php`). Ces tests démarrent le noyau Symfony et interagissent avec la base de données.
