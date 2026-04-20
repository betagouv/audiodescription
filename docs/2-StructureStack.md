# Structure & Stack technique

## Structure du projet

Le dépôt contient deux applications principales dans des répertoires séparés :

| Répertoire   | Rôle                      | Framework      | Fonction                                                  |
|--------------|---------------------------|----------------|-----------------------------------------------------------|
| `patrimony/` | Backend et données        | Symfony        | Intégration API partenaires, qualité des données          |
| `drupal/`    | CMS et site public        | Drupal 11      | Présentation du contenu, recherche, interface utilisateur |
| `docker/`    | Configurations Docker     | Docker Compose | Orchestration des services                                |
| `scripts/`   | Scripts et configurations | Shell/Cron     | Planification des imports, rotation des logs              |

## Stack technique

### Backend Patrimony

| Composant       | Technologie            | Version   | Rôle                            |
|-----------------|------------------------|-----------|---------------------------------|
| Framework       | Symfony / API Platform | 7.1 / 3.3 | Framework applicatif            |
| ORM             | Doctrine               | -         | Gestion des entités             |
| Client HTTP     | Symfony HTTP Client    | -         | Intégration des API partenaires |
| Base de données | PostgreSQL             | 17        | Persistance des données         |

### Frontend Drupal

| Composant              | Technologie                          | Version          | Rôle                                  |
|------------------------|--------------------------------------|------------------|---------------------------------------|
| CMS                    | Drupal                               | 11.x             | Gestion de contenu                    |
| Système de thèmes      | Twig                                 | -                | Rendu des templates                   |
| Système de design      | DSFR (Système de Design de l'État)   | 1.11.2           | Standards UI du gouvernement français |
| Recherche              | Search API + Elasticsearch Connector | 1.35 / 8.0-alpha | Couche d'abstraction de recherche     |
| OAuth                  | Simple OAuth                         | 6.0-beta         | Authentification API                  |
| Exécuteur de commandes | Drush                                | 13.3             | Administration en ligne de commande   |


### Infrastructure

| Composant               | Technologie    | Version     | Fichier de config          |
|-------------------------|----------------|-------------|----------------------------|
| Conteneurisation        | Docker Compose | -           | `compose.prod.yml`         |
| Serveur web             | Caddy          | -           | Intégré aux conteneurs PHP |
| Reverse proxy           | Traefik        | -           | Réseau web externe         |
| Moteur de recherche     | Elasticsearch  | 8.14.3      | Cluster en nœud unique     |
| Base de données         | PostgreSQL     | 17-bookworm | Partagée avec deux schémas |
| Planificateur de tâches | Cron           | -           | `scripts/crontab`          |