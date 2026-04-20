# Liste des commandes Make

Préfixes :
* prd : Production
* pt : Patrimony
* d : Drupal

Ces commandes Make sont utilisées par des tâches cron.

| Cible | Rôle |
|---|---|
| `cron-import` | Importer tous les films depuis les sources partenaires dans Patrimony et synchroniser avec Drupal |
| `pt-send-newsletter` | Envoyer la newsletter mensuelle |

Il existe d'autres commandes Make utilisées principalement en développement.

| Cible                   | Rôle                                                                                           | Commandes                                             |
|-------------------------|------------------------------------------------------------------------------------------------|-------------------------------------------------------|
| `help`                  | Liste toutes les commandes Make                                                                |                                                       |
| `up`                    | Démarre les conteneurs Docker                                                                  |                                                       |
| `down`                  | Éteint les conteneurs Docker                                                                   |                                                       |
| ---                     | ---                                                                                            | ---                                                   |
| `prd-up`                | Démarre les conteneurs Docker en production (avec le bon fichier compose.yml)                  |                                                       |
| `prd-down`              | Éteint les conteneurs Docker (avec le bon fichier compose.yml)                                 |                                                       |
| `prd-install`           | Installe Patrimony & Drupal                                                                    |                                                       |
| `prd-install-patrimony` | Installe Patrimony                                                                             |                                                       |
| `prd-install-drupal`    | Installe Drupal                                                                                |                                                       |
| `prd-install-drupal`    | Installe Drupal                                                                                |                                                       |
| ---                     | ---                                                                                            | ---                                                   |
| `pt-sh`                 | Exécute `bash` dans le conteneur Patrimony pour lancer ensuite d'autres commandes manuellement |                                                       |
| `pt-install`            | Exécute `composer install` et initialise l'environnement de tests. | |
| `pt-quality`            | Exécute les outils de qualité                                                                  |                                                       |
| `pt-phpcs`              | Exécute les outils PHPCBF & PHP CodeSniffer                                                    |                                                       |
| `pt-phpmd`              | Exécute l'outil PHPMD (analyse statique du code PHP)                                           |                                                       |
| `pt-phpstan`            | Exécute l'outil PHPStan (analyse statique du code PHP)                                         |                                                       |
| `pt-schema-reset`       | Réinitialise le schéma de la base de données                                                   | | 
| `pt-migrate-diff`       | Crée les migrations si nécessaire | |
| `pt-migrate`            | Exécute les migrations | |
| `pt-import-all`         | Importe les films de tous les partenaires (et les publics fournis par le CNC)                  | `ad:import:cnc-public` + tous les imports partenaires |
| `pt-import-artetv`      | Importe les films Arte TV uniquement                                                           | `ad:import:artetv-api --create-movies=true`           |
| `pt-import-canalvod`    | Importe les films Canal VOD uniquement                                                         | `ad:import:canalvod-api --create-movies=true`         |
| `pt-import-canalreplay` | Importe les films Canal Replay uniquement                                                      | `ad:import:canalreplay-api --create-movies=true`      |
| `pt-import-francetv`    | Importe les films France TV uniquement                                                         | `ad:import:francetv-api --create-movies=true`         |
| `pt-import-lacinetek`   | Importe les films LaCinetek uniquement                                                         | `ad:import:lacinetek-api --create-movies=true`        |
| `pt-import-orange`      | Importe les films Orange VOD (CSV)                                                             | `ad:import:orangevod-csv --create-movies=true`        |
| `pt-import-tf1`         | Importe les films TF1+ uniquement                                                              | `ad:import:tf1-api --create-movies=true`              |
| ---                     | ---                                                                                            | ---                                                   |
| `d-sh`                  | Exécute `bash` dans le conteneur Drupal pour lancer ensuite d'autres commandes manuellement    |                                                       |
| `d-quality`             | Exécute les outils de qualité                                                                  |                                                       |
| `d-phpcs`               | Exécute les outils PHPCBF & PHP CodeSniffer                                                    |                                                       |
| `d-phpmd`               | Exécute l'outil PHPMD (analyse statique du code PHP)                                           |                                                       |
| `d-phpstan`             | Exécute l'outil PHPStan (analyse statique du code PHP)                                         |                                                       |
| `d-reset-db`            | Exécute l'outil PHPStan (analyse statique du code PHP)                                         |                                                       |
| `d-import`              | Synchronise Drupal avec les données de Patrimony et dépublie les films sans audiodescription   |                                                       |
| `d-cr`                  | Vide le cache                                                                                  |                                                       |
| `drush`                 | Exécute une commande Drush passée en argument                                                  | Exemple : `make drush cr`                              |
