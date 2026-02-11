# Annexes

## Commandes utiles

### Drupal (Drush)

```bash
# Vider le cache
drush cr

# Importer la configuration
drush cim -y

# Exporter la configuration
drush cex -y

# Mettre à jour la base de données
drush updb -y

# Import des films depuis Patrimony
drush adia

# Dépublier les films sans solutions
drush adum

# Réindexer ElasticSearch
drush sapi-r
drush sapi-i
```

### Symfony / Patrimony

```bash
# Vider le cache
bin/console cache:clear

# Exécuter les migrations
bin/console doctrine:migrations:migrate --no-interaction

# Créer une migration
bin/console make:migration

# Import des films ARTE
bin/console app:import:arte-tv-api

# Import des films France TV
bin/console app:import:france-tv-api

# Import des films Canal+
bin/console app:import:canal-vod-api
bin/console app:import:canal-replay-api

# Import des films TF1
bin/console app:import:tf1-api

# Import des films La Cinétek
bin/console app:import:lacinetek-api

# Import des films Orange VOD (CSV)
bin/console app:import:orange-vod-csv

# Import des publics (CSV)
bin/console app:import:cnc-public

# Envoi de la newsletter hebdomadaire
bin/console app:send-weekly-newsletter
```

### Docker

```bash
# Démarrer les services (développement)
docker compose up -d

# Démarrer les services (production)
docker compose -f compose.prod.yml up -d

# Voir les logs
docker compose logs -f <service>

# Accéder à un container
docker compose exec drupal bash
docker compose exec patrimony bash

# Reconstruire les images
docker compose build --no-cache

# Arrêter les services
docker compose down
```

### Base de données

```bash
# Accéder à PostgreSQL
docker compose exec db psql -U audiodescription drupal

# Export de la base
docker compose exec db pg_dump -U audiodescription drupal > backup.sql

# Import d'une base
cat backup.sql | docker compose exec -T db psql -U audiodescription drupal
```

## Références et documentation

### Documentation officielle

| Ressource | URL |
|-----------|-----|
| Drupal 11 | https://www.drupal.org/docs |
| Symfony 7.1 | https://symfony.com/doc/7.1/index.html |
| API Platform | https://api-platform.com/docs/ |
| EasyAdmin | https://symfony.com/bundles/EasyAdminBundle/current/index.html |
| FrankenPHP | https://frankenphp.dev/docs/ |
| Traefik | https://doc.traefik.io/traefik/ |
| ElasticSearch | https://www.elastic.co/guide/en/elasticsearch/reference/8.14/index.html |
| DSFR | https://www.systeme-de-design.gouv.fr/ |

### Modules Drupal utilisés

| Module | Description | Documentation |
|--------|-------------|---------------|
| Search API | Recherche avancée | https://www.drupal.org/project/search_api |
| ElasticSearch Connector | Connecteur ElasticSearch | https://www.drupal.org/project/elasticsearch_connector |
| Config Pages | Pages de configuration | https://www.drupal.org/project/config_pages |
| UI Suite DSFR | Thème DSFR | https://www.drupal.org/project/ui_suite_dsfr |
| Pathauto | URLs automatiques | https://www.drupal.org/project/pathauto |
| Metatag | Métadonnées SEO | https://www.drupal.org/project/metatag |

### Bundles Symfony utilisés

| Bundle | Description |
|--------|-------------|
| api-platform/core | Framework API REST |
| easycorp/easyadmin-bundle | Interface d'administration |
| doctrine/orm | ORM base de données |
| firebase/php-jwt | Gestion des tokens JWT |
| getbrevo/brevo-php | Client API Brevo |
| nelmio/cors-bundle | Gestion CORS |

## Structure du projet

```
audiodescription/
├── docker/                    # Configuration Docker
│   ├── drupal/               # Dockerfiles Drupal (dev/prod)
│   ├── patrimony/            # Dockerfiles Patrimony (dev/prod)
│   └── postgres/             # Scripts init PostgreSQL
├── docs/                      # Documentation
│   ├── dat/                  # Dossier d'Architecture Technique
│   └── assets/               # Images et schémas
├── drupal/                    # Application Drupal
│   ├── config/               # Configuration exportée
│   ├── web/
│   │   ├── modules/custom/   # Module audiodescription
│   │   └── themes/           # Thème ad_theme
│   └── composer.json
├── patrimony/                 # Application Symfony
│   ├── config/               # Configuration Symfony
│   ├── src/
│   │   ├── Command/          # Commandes console
│   │   ├── Controller/       # Contrôleurs
│   │   ├── Entity/           # Entités Doctrine
│   │   ├── Importer/         # Importeurs partenaires
│   │   ├── Manager/          # Entity Managers
│   │   ├── Repository/       # Repositories
│   │   └── Service/          # Services métier
│   └── composer.json
├── infra/                     # Configuration Traefik
├── files/                     # Fichiers CSV d'import
├── compose.yml               # Docker Compose (dev)
├── compose.prod.yml          # Docker Compose (prod)
├── compose.staging.yml       # Docker Compose (staging)
└── Makefile                  # Commandes Make
```

## Contacts

| Rôle            | Contact |
|-----------------|---------|
| Contact produit | contact@audiodescription.beta.gouv.fr |

[<< Page précédente - Architecture physique](3-ArchitecturePhysique.md)
