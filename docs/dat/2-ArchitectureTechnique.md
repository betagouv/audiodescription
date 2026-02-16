# Architecture technique

## Schéma d'architecture technique

Le schéma suivant présente l'architecture technique du Portail de l'audiodescription.

![schéma d'architecture technique](../assets/AD-schema-technique.jpg)

## Composants applicatifs

### Application Drupal (Portail public)

Ce composant est une application Web Drupal 11 servant de portail public. 
Elle utilise FrankenPHP comme serveur d'application combinant PHP-FPM et Caddy.

Le thème utilise le Design System de l'État Français (DSFR v1.11) via le module `ui_suite_dsfr`. Les assets frontend sont compilés avec Vite.

Les principales fonctionnalités sont :
- Affichage du catalogue de films avec audiodescription
- Moteur de recherche full-text via Search API et ElasticSearch
- Gestion des taxonomies (genres, réalisateurs, collections)
- Formulaires newsletter (inscription/désinscription via API Brevo)
- Import de données depuis l'API Patrimony

| Composant | Nb instances | besoin CPU | besoin RAM | Besoin disque |
|-----------|--------------|------------|------------|---------------|
| Drupal    | 1            | 1          | 512Mo      | N/A           |

Ce composant est packagé sous la forme d'une image Docker issue de **dunglas/frankenphp:1.4-php8.3-bookworm** et déployé sous la forme d'un container.

### Application Patrimony (Backend API)

Ce composant est une application Symfony 7.1 avec API Platform 3.3 servant de backend pour l'agrégation du catalogue de films.

Elle expose une API REST (`/api/v1/`) consommée par l'application Drupal et fournit une interface d'administration via EasyAdmin.

Les principales fonctionnalités sont :
- API REST pour l'accès au catalogue (movies, solutions, partners, genres, etc.)
- Interface d'administration CRUD pour la gestion des données
- Import automatisé depuis les partenaires VOD (ARTE, France TV, Canal+, Orange, TF1, La Cinétek)
- Fusion automatique des films provenant de sources multiples (MovieAutoMerger)
- Génération et envoi de newsletters hebdomadaires via Brevo

| Composant  | Nb instances | besoin CPU | besoin RAM | Besoin disque |
|------------|--------------|------------|------------|---------------|
| Patrimony  | 1            | 1          | 512Mo      | N/A           |

Ce composant est packagé sous la forme d'une image Docker issue de **dunglas/frankenphp:1.4-php8.3-bookworm** et déployé sous la forme d'un container.

### Base de données PostgreSQL

Ce composant est une base de données PostgreSQL 17 hébergeant les données des deux applications.

Deux schémas sont utilisés :
- `drupal` : données du CMS (nodes, taxonomies, configurations, cache)
- `patrimony` : catalogue de films, solutions, partenaires, historique des imports

| Composant  | Nb instances | besoin CPU | besoin RAM | Besoin disque |
|------------|--------------|------------|------------|---------------|
| PostgreSQL | 1            | 1          | 1Go        | 10Go          |

Ce composant est packagé sous la forme d'une image Docker issue de **postgres:17-bookworm** et déployé sous la forme d'un container.

### ElasticSearch

Ce composant est un moteur de recherche ElasticSearch 8.14 utilisé par Drupal pour la recherche full-text des films.

Il est configuré en mode single-node et indexe les contenus de type "movie" pour permettre une recherche performante avec filtrage multi-critères.

| Composant     | Nb instances | besoin CPU | besoin RAM | Besoin disque |
|---------------|--------------|------------|------------|---------------|
| ElasticSearch | 1            | 1          | 2Go        | 5Go           |

Ce composant est packagé sous la forme d'une image Docker issue de **docker.elastic.co/elasticsearch/elasticsearch:8.14.3** et déployé sous la forme d'un container.

### Traefik (Reverse Proxy)

Ce composant est un reverse proxy / load balancer Traefik gérant le routage des requêtes HTTPS vers les applications Drupal et Patrimony.

Il assure :
- La terminaison TLS (HTTPS)
- Le routage par nom de domaine
- La redirection www vers non-www

### API Brevo

Service externe d'emailing utilisé pour :
- L'inscription/désinscription des abonnés à la newsletter (via Drupal)
- La création et l'envoi de campagnes email hebdomadaires (via Patrimony)
- La gestion des listes de contacts

### APIs Partenaires VOD

Services externes des plateformes de diffusion fournissant les données de catalogue :
- **ARTE TV** : API REST pour les films avec audiodescription
- **France TV** : API REST pour le catalogue France Télévisions
- **Canal+ VOD/Replay** : API REST pour les contenus Canal+
- **Orange VOD** : Import via fichiers CSV
- **La Cinétek** : API REST (offres TVOD et SVOD)
- **TF1** : API REST pour les contenus TF1


## Socle technique

| Produit        | Version | Commentaires                              |
|----------------|---------|-------------------------------------------|
| PHP            | 8.3     | Drupal et Patrimony                       |
| Drupal         | 11      | Portail public                            |
| Symfony        | 7.1     | Backend Patrimony                         |
| API Platform   | 3.3     | API REST Patrimony                        |
| EasyAdmin      | 4.20    | Interface d'administration Patrimony      |
| PostgreSQL     | 17      | Base de données                           |
| ElasticSearch  | 8.14    | Moteur de recherche                       |
| FrankenPHP     | 1.4     | Serveur d'application (PHP + Caddy)       |
| Traefik        | 3.x     | Reverse proxy / Load balancer             |
| Node.js        | 20      | Build des assets frontend (Vite)          |
| DSFR           | 1.11    | Design System de l'État Français          |

### Images Docker

| Composant     | Image                                                   |
|---------------|---------------------------------------------------------|
| Drupal        | dunglas/frankenphp:1.4-php8.3-bookworm                  |
| Patrimony     | dunglas/frankenphp:1.4-php8.3-bookworm                  |
| PostgreSQL    | postgres:17-bookworm                                    |
| ElasticSearch | docker.elastic.co/elasticsearch/elasticsearch:8.14.3   |

## Synthèse dimensionnement

| Composant     | Nb instances | CPU | RAM  | Besoin disque |
|---------------|--------------|-----|------|---------------|
| Drupal        | 1            | 1   | 512Mo| N/A           |
| Patrimony     | 1            | 1   | 512Mo| N/A           |
| PostgreSQL    | 1            | 1   | 1Go  | 10Go          |
| ElasticSearch | 1            | 1   | 2Go  | 5Go           |

## Flux

### Flux techniques

| Émetteur      | Destinataire    | Détails                                                      | Protocole | Volumétrie            |
|---------------|-----------------|--------------------------------------------------------------|-----------|-----------------------|
| Drupal        | PostgreSQL      | Accès aux données Drupal                                     | TCP/5432  | Pour chaque requête   |
| Drupal        | ElasticSearch   | Recherche full-text des films                                | HTTP/9200 | Pour chaque recherche |
| Drupal        | Patrimony API   | Récupération du catalogue de films                           | HTTPS     | Import quotidien      |
| Drupal        | API Brevo       | Inscription/désinscription newsletter                        | HTTPS     | À la demande          |
| Patrimony     | PostgreSQL      | Accès aux données Patrimony                                  | TCP/5432  | Pour chaque requête   |
| Patrimony     | API Brevo       | Envoi des newsletters hebdomadaires                          | HTTPS     | Hebdomadaire          |
| Patrimony     | API ARTE        | Import du catalogue ARTE                                     | HTTPS     | Import quotidien      |
| Patrimony     | API France TV   | Import du catalogue France TV                                | HTTPS     | Import quotidien       |
| Patrimony     | API Canal+      | Import du catalogue Canal+ VOD/Replay                        | HTTPS     | Import quotidien       |
| Patrimony     | API TF1         | Import du catalogue TF1                                      | HTTPS     | Import quotidien       |
| Patrimony     | API La Cinétek  | Import du catalogue La Cinétek                               | HTTPS     | Import quotidien       |

### URLs

| Application | URL Production                              | Description              |
|-------------|---------------------------------------------|--------------------------|
| Drupal      | https://audiodescription.beta.gouv.fr       | Portail public           |
| Patrimony   | https://patrimony.audiodescription.beta.gouv.fr | API et administration |

## Sécurité

### Accès et protocoles

- L'accès public au portail Drupal se fait en HTTPS (port 443).
- L'accès à l'interface d'administration Patrimony se fait en HTTPS avec authentification.
- Les communications internes entre containers se font sur un réseau Docker isolé.

### Authentification

#### Portail public (Drupal)
Le portail public ne nécessite pas d'authentification pour la consultation du catalogue.

#### Administration Patrimony
L'authentification à l'interface d'administration Patrimony est réalisée via le protocole **OIDC** (OpenID Connect).
Le fournisseur est l'application Drupal.

Une fois authentifiés, les utilisateurs peuvent avoir les rôles suivants :

| Rôle          | Description                                                              |
|---------------|--------------------------------------------------------------------------|
| ROLE_ADMIN    | Administrateur fonctionnel avec accès complet à toutes les fonctionnalités |

#### API Patrimony
L'accès à l'API REST Patrimony est sécurisé par tokens JWT (Bearer Authentication).

### Protection des données

- Les mots de passe et tokens sont stockés de manière chiffrée
- Les clés API des partenaires sont gérées via variables d'environnement
- Les communications avec les services externes (Brevo, partenaires VOD) sont chiffrées (HTTPS)

## Paramétrage

L'ensemble du paramétrage se fait par variables d'environnement et fichiers de configuration.

Format du fichier .env :

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

[<< Page précédente - Architecture logique](1-ArchitectureLogique.md) - [Page suivante - Architecture physique >>](3-ArchitecturePhysique.md)