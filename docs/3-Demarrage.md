# Démarrage

Cette page fournit des instructions détaillées pour configurer un environnement de développement local pour le Portail d'audiodescription. 

Elle couvre la configuration Docker, l'installation des projets Drupal et Patrimony, ainsi que l'initialisation des données..

---

## Prérequis

Avant de commencer, assurez-vous d'avoir installé les éléments suivants sur votre machine de développement :

| Prérequis                | Rôle |
|--------------------------|------|
| **Docker** (v28.5+)      | Orchestration des conteneurs |
| **Docker Compose** (v2+) | Gestion multi-conteneurs |
| **Make**                 | Outil d'automatisation de build |
| **Git**                  | Contrôle de version |

---

## Architecture des services Docker

L'application s'exécute dans un environnement Docker multi-conteneurs avec 4 services principaux :

1. **drupal** - Frontend Drupal (PHP 8.3 + Caddy)
2. **patrimony** - Backend Symfony (PHP 8.3 + Caddy)
3. **db** - Base de données PostgreSQL 16 avec deux schémas
4. **elasticsearch** - Moteur de recherche (8.14.3) en mode nœud unique

---

## Configuration de l'environnement

Créez un fichier `.env` à la racine du projet avec les paramètres suivants :

```bash
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=

DATABASE_URL="postgresql://"

CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'

PATRIMONY_API_KEY=""

OIDC_AD_BASE_URL=""
OIDC_AD_PUBLIC_URL=""
OIDC_AD_CLIENT_ID=""
OIDC_AD_CLIENT_SECRET=""
OIDC_AD_REDIRECT_BASE_URL=""
OIDC_AD_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----
-----END PUBLIC KEY-----"

CANAL_VOD_API_URL=''
CANAL_REPLAY_API_URL=''
CANAL_VOD_API_AUTH_TOKEN=''

ARTE_TV_API_URL=''
ORANGE_VOD_FILENAME=''
FRANCE_TV_API_URL_100_NUM=''
FRANCE_TV_API_URL_PREMIUM=''

TF1_API_USER=''
TF1_API_PASSWORD=''
TF1_API_URL=''

LACINETEK_API_URL=''
LACINETEK_API_AUTH_TOKEN=''

BREVO_API_KEY=
BREVO_LIST_ID=
```

---

## Installation initiale

### Étape 1 : Démarrer les services Docker

Démarre les services Docker grâce à l'alias :
```bash
make up
```

**OU** grâce à la commande complète :

```bash
docker compose up -d --build --remove-orphans -t 0
```

Cette commande démarre :

- Service `patrimony` (PHP 8.3 + Caddy) sur le port 8083
- Service `drupal` (PHP 8.3 + Caddy) sur le port 8080
- Service `db` (PostgreSQL 16) avec deux schémas grâce au script disponible dans `docker/postgres/init-patrimony-db.sh`
- Service `elasticsearch` (8.14.3) en mode nœud unique

---

### Étape 2 : Installer le backend Patrimony

Le backend Patrimony importe toutes les données depuis les API partenaires et maintient la base de données des films.

Exécutez l'installation grâce à l'alias :
```bash
make pt-install
```

**OU** grâce à la commande complète :

```bash
docker compose exec patrimony composer install
docker compose exec patrimony php bin/console doctrine:database:drop --force --env=test
docker compose exec patrimony php bin/console doctrine:database:create --if-not-exists --env=test
docker compose exec patrimony php bin/console doctrine:migrations:migrate --env=test --no-interaction
docker compose exec patrimony php bin/console doctrine:fixtures:load --env=test --group=test --no-interaction
```

Cette cible exécute les commandes suivantes :

1. `composer install` - Installe les dépendances Symfony
2. Crée la base de données de test pour Codeception

---

### Étape 3 : Importer les données initiales Patrimony

Importez les données de référence depuis les fichiers disponibles dans `patrimony/data` et les API partenaires grâce à l'alias :
```bash
make pt-import-all
```

**OU** grâce à la commande complète :

```bash
docker compose exec patrimony php bin/console ad:import:cnc-public
docker compose exec patrimony php bin/console ad:import:canalvod-api --create-movies=true
docker compose exec patrimony php bin/console ad:import:canalreplay-api --create-movies=true
docker compose exec patrimony php bin/console ad:import:orangevod-csv --create-movies=true
docker compose exec patrimony php bin/console ad:import:lacinetek-api --create-movies=true
docker compose exec patrimony php bin/console ad:import:artetv-api --create-movies=true
docker compose exec patrimony php bin/console ad:import:tf1-api --create-movies=true
docker compose exec patrimony php bin/console ad:import:francetv-api --create-movies=true
```

Ces commandes permettent d'importer les films de tous les partenaires dans cet ordre :

| Ordre | Partenaire | Commande | Crée des films ? |
|-------|-----------|----------|------------------|
| 1 | CNC | `ad:import:cnc-public` | Non              |
| 2 | Canal VOD | `ad:import:canalvod-api` | Oui              |
| 3 | Canal Replay | `ad:import:canalreplay-api` | Oui              |
| 4 | Orange VOD | `ad:import:orangevod-csv` | Oui              |
| 5 | LaCinetek | `ad:import:lacinetek-api` | Oui              |
| 6 | Arte TV | `ad:import:artetv-api` | Oui              |
| 7 | TF1+ | `ad:import:tf1-api` | Oui              |
| 8 | France TV | `ad:import:francetv-api` | Oui              |

Chaque importeur crée :

- Des entités `SourceMovie` avec les données spécifiques au partenaire
- Des entités `Solution` représentant la disponibilité dans le temps
- Des entités `Movie` (si `--create-movies=true`)

---

### Étape 4 : Installer le frontend Drupal

Le frontend Drupal fournit le site web public et synchronise les données depuis la base Patrimony.

Exécutez l'installation grâce à l'alias:
```bash
make prd-install
```

**OU** grâce aux commandes suivantes : 
```bash
docker compose exec drupal composer install
docker compose exec drupal vendor/bin/drush si -y
docker compose exec drupal vendor/bin/drush cset system.site uuid e6c1838c-d5b1-4d0c-8c20-9405ca9991f6 -y
docker compose exec drupal vendor/bin/drush cr
docker compose exec drupal vendor/bin/drush entity:delete shortcut
docker compose exec drupal vendor/bin/drush cim -y || true
docker compose exec drupal vendor/bin/drush cim -y
docker compose exec drupal vendor/bin/drush updb -y
docker compose exec drupal vendor/bin/drush cr
docker compose exec drupal vendor/bin/drush locale:check
docker compose exec drupal vendor/bin/drush locale:update
docker compose exec drupal vendor/bin/drush cr
docker compose exec drupal vendor/bin/drush adia
docker compose exec drupal vendor/bin/drush adum
docker compose exec drupal vendor/bin/drush cr
```

Cela effectue les opérations suivantes :

1. `composer install` - Installe Drupal core et les modules
2. `drush si -y` - Installation du site (crée le schéma drupal)
3. `drush cset system.site uuid` - Définit l'UUID correct du site
4. `drush entity:delete shortcut` - Supprimer les entités inutiles
4. `drush cim -y` - Importe la configuration (peut s'exécuter deux fois pour gérer les dépendances)
5. `drush updb -y` - Exécute les mises à jour de base de données
6. `drush locale:check` et `drush locale:update` - Met à jour les traductions françaises
7. `drush adia` - Importe toutes les données depuis Patrimony (alias de `ad:import:patrimony:all`)
8. `drush adum` - Dépublie les films sans solution (alias de `ad:unpublish-movies`)
9. `drush cr` - Reconstruit tous les caches

---

## Commandes Drush clés

L'installation Drupal fournit des commandes Drush personnalisées pour la synchronisation des données :

| Commande | Alias | Rôle                                                                 |
|----------|-------|----------------------------------------------------------------------|
| `ad:import:patrimony:all` | `adia` | Importe tous les types d'entités depuis la base de données Patrimony |
| `ad:unpublish-movies` | `adum` | Met à jour les métatags sur tous les nœuds films                     |

---

## Accès à l'application

Une fois l'installation terminée, accédez aux services :

| Service | URL                   | Rôle |
|---------|-----------------------|------|
| **Frontend Drupal** | http://localhost:8080 | Site web public |
| **Backend Patrimony** | http://localhost:8083 | API et interface d'administration |
| **Elasticsearch** | http://localhost:9200 | Index de recherche (sans auth en dev) |

---

## Workflow de développement

### Cibles Make courantes
```bash
make up              # Démarre tous les conteneurs
make down            # Arrête tous les conteneurs
make pt-sh        # Accède au shell du conteneur Patrimony
make sh         # Accède au shell du conteneur Drupal
make pt-import-all   # Importe depuis tous les partenaires
make d-import        # Synchronise Patrimony vers Drupal
```

### Tâches quotidiennes de développement

**Importer les données d'un partenaire spécifique :**
```bash
make pt-import-francetv   # Import France TV uniquement
make pt-import-artetv       # Import Arte uniquement
make pt-import-tf1        # Import TF1+ uniquement
make pt-import-orange        # Import VOD ORANGE uniquement
make pt-import-canal        # Import CANALVOD uniquement
make pt-import-canal-replay        # Import myCanal uniquement
make pt-import-lacinetek        # Import LaCinetek uniquement
```

**Accéder aux shells des conteneurs :**
```bash
make pt-sh         # Shell Symfony (composer, bin/console)
make sh          # Shell Drupal (drush, composer)
```

---

## Tâches planifiées (Production)

En production, les imports s'exécutent automatiquement via cron :
```cron
# Crontab : /opt/audiodescription/scripts/crontab
5 1 * * * cd /opt/audiodescription && make cron-import >> /opt/audiodescription/logs/cron-import.log
0 10 * * 1 cd /opt/application/audiodescription && make pt-send-newsletter >> /opt/application/audiodescription/logs/cron-newsletter.log
```

L'alias `cron-import` utilise le flag `-T` pour éviter les erreurs TTY dans cron :
```bash
make cron-import      # Exécute pt-import-all puis d-import sans TTY
```

Les logs sont tournés quotidiennement avec une rétention de 15 jours via logrotate.

---

## Outils de qualité du code

Exécutez les vérifications de qualité du code avant de commiter :

**Patrimony (Symfony) avec l'alias **
```bash
make pt-quality        # Corrige le style du code (PHP-CS-Fixer) & Analyse statique
```
** OU les commandes :**
```bash
docker compose exec patrimony php vendor/bin/phpcbf
docker compose exec patrimony vendor/bin/phpcs
docker compose exec patrimony php vendor/bin/phpstan analyse -c phpstan.neon
docker compose exec patrimony php vendor/bin/phpmd src ansi phpmd.xml
```

**Drupal :**
```bash
make quality        # Corrige le style du code (PHP-CS-Fixer) & Analyse statique
```

```bash
docker compose exec drupal vendor/bin/phpcbf --standard=Drupal --extensions=php,module,inc,install,test,profile,theme,css,info,txt,md,yml web/modules/custom/ web/themes/ad_theme/ || true
docker compose exec drupal vendor/bin/phpcs --standard=Drupal --extensions=php,module,inc,install,test,profile,theme,css,info,txt,md,yml web/modules/custom/ web/themes/ad_theme/
docker compose exec drupal vendor/bin/phpstan.phar || true
docker compose exec drupal vendor/bin/phpmd web/modules/custom/ web/themes/ad_theme/ ansi phpmd.xml || true
```
---

## Étapes suivantes

Après avoir terminé la configuration initiale :

- **Comprendre l'architecture :** Voir *Architecture système* pour la conception double application
- **En savoir plus sur les intégrations partenaires :** Voir *Intégrations API partenaires* pour les détails des API
- **Explorer le modèle de données :** Voir *Modèle de données* pour les relations entre entités
- **Travailler avec le frontend :** Voir *Frontend Drupal* pour le développement de modules et thèmes
- **Ajouter de nouveaux partenaires :** Voir *Ajout de nouvelles intégrations partenaires* pour un guide pas à pas