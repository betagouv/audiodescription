# Architecture système

Ce document décrit l'architecture générale du Portail d'audiodescription.
---

## Architecture double application

Le système implémente une **architecture double application** où deux applications PHP distinctes ont des
responsabilités séparées :

1. **Backend Patrimony** (Symfony)
    - Import des données depuis les API et fichiers partenaires
    - Canonicalisation des données
    - Génération et envoi de la newsletter hebdomadaire
2. **Frontend Drupal** - Site web public
    - Moteur de recherche
    - Affichage des fiches films
    - Accessibilité (conformité RGAA)

Les deux applications partagent une **base de données PostgreSQL** mais maintiennent des schémas séparés (`patrimony` et
`drupal`).
L'application Patrimony écrit dans le schéma `patrimony`, et Drupal synchronise ces données dans son propre schéma à des
fins de présentation.

---

## Composants applicatifs

### Composants du backend Patrimony

Le backend Patrimony est une application Symfony responsable de l'acquisition et de la canonicalisation des données :

| Composant                  | Classe/Fichier                                                                                                                                                       | Rôle                                                                                |
|----------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------|-------------------------------------------------------------------------------------|
| **Factory d'importeurs**   | `App\Importer\Movie\ImporterFactory`                                                                                                                                 | Crée des importeurs spécifiques aux partenaires basés sur l'enum `ImportSourceType` |
| **Importeurs partenaires** | `ArteTvApiImporter`, `CanalReplayApiImporter`, `CanalVodApiImporter`, `FranceTvApiImporter`, `LaCinetekApiImporter`, `OrangeVodApiImporter`, `Tf1ApiImporter`        | Récupèrent et transforment les données depuis les API externes                      |
| **Entity Managers**        | `ActorManager`, `DirectorManager`, `GenreManager`, `LanguageManager`, `MovieManager`, `NationalityManager`, `PublicManager`, `SolutionManager`, `SourceMovieManager` | Gèrent les opérations CRUD et la logique métier                                     |
| **Entités**                | `Actor`, `ActorMovie`, `Director`, `Genre`, `Language`, `Movie`, `Nationality`, `Offer`, `Partner`, `PublicRestriction`, `SourceMovie`, `Solution`                   | Entités Doctrine ORM dans le schéma `patrimony`                                     |
| **Repositories**           | `GenreRepository`, `MovieRepository`, `SolutionRepository`, `SourceMovieRepository`, `UserRepository`                                                                | Abstraction des requêtes de base de données                                         |

L'`ImporterFactory` suit le pattern Factory pour instancier les importeurs :

```php
public function create(ImportSourceType $source): MovieImporterInterface
{
    return match($source) {
        ImportSourceType::FRANCE_TV => $this->franceTvApiImporter,
        ImportSourceType::TF1 => $this->tf1ApiImporter,
        ImportSourceType::ARTE_TV => $this->arteTvApiImporter,
        // ... autres partenaires
    };
}
```

---

### Composants du frontend Drupal

L'applicatif Drupal utilise un module (`audiodescription`) et un thème (`ad_theme`) pour fournir toutes les
fonctionnalités personnalisées :

| Composant                     | Nom du service                              | Classe                                                                                                                             | Rôle                                                    |
|-------------------------------|---------------------------------------------|------------------------------------------------------------------------------------------------------------------------------------|---------------------------------------------------------|
| **Importeurs Patrimony**      | `audiodescription.movie_patrimony_importer` | `MoviePatrimonyImporter`                                                                                                           | Synchronise les données patrimony vers les nœuds Drupal |
| **Entity Managers**           | `audiodescription.manager.movie`            | `MovieManager`                                                                                                                     | Gère les opérations sur les nœuds films                 |
| **Gestionnaire de recherche** | `audiodescription.manager.movie_search`     | `MovieSearchManager`                                                                                                               | Interroge Elasticsearch via Search API                  |
| **Contrôleurs**               | N/A                                         | `Error410Controller`, `ErrorController`, `GenresController`, `HomepageController`, `MovieSearchController`, ``NewsletterController | Gèrent les routes personnalisées                        |
| **Breadcrumb Builders**       | `audiodescription.xx.breadcrumb`            | `MovieBreadcrumbBuilder`                                                                                                           | Navigation contextuelle                                 |
| **Event Subscribers**         | `audiodescription.exception_subscriber`     | `MovieExceptionSubscriber`                                                                                                         | Gère les erreurs 410 pour les films non publiés         |

---

## Pipeline de flux de données

Le Portail de l'audiodescription implémente un pipeline de transformation des données en trois étapes :

```
┌─────────────────┐      ┌──────────────────┐      ┌─────────────────┐
│ Étape 1:        │─────▶│ Étape 2:         │─────▶│ Étape 3:        │
│ Import          │      │ Synchronisation  │      │ Indexation      │
│ partenaires     │      │ Drupal           │      │ recherche       │
└─────────────────┘      └──────────────────┘      └─────────────────┘
     Patrimony                 Drupal                Elasticsearch
```

### Étape 1 : Import des données partenaires

Chaque importeur partenaire suit un pattern commun défini par `MovieImporterInterface` :

1. **Authentification** : Configure le client HTTP avec les credentials depuis les paramètres `patrimony.yaml`
2. **Requête API** : Récupère les données films via les endpoints spécifiques au partenaire
3. **Transformation des données** : Extrait les champs pertinents (titre, réalisateurs, synopsis, etc.)
4. **Création d'entité** : Crée/met à jour les entités `SourceMovie` avec les métadonnées partenaire
5. **Création de Solution** : Crée les entités `Solution` avec les fenêtres de disponibilité et liens
6. **Matching de films** : Utilise `MovieFetcher` pour faire correspondre les films sources aux entités `Movie`
   canoniques via les IDs externes
---

### Étape 2 : Synchronisation Drupal

Le `MoviePatrimonyImporter` utilise l'API Rest fournie par Patrimony pour importer les données et créer des entités Drupal :

1. Appel l'API Rest fournie par Patrimony
2. Crée/met à jour les nœuds Drupal de type `movie`
3. Crée/met à jour les termes de taxonomie (genres, réalisateurs)
4Crée des paragraphes pour les offres et partenaires

La clé de synchronisation est le champ `field_code` dans Drupal, qui correspond à la colonne `code` dans la table
`movie` de Patrimony. Cela garantit des imports idempotents.

---

### Étape 3 : Indexation de recherche

Après la synchronisation Drupal, la commande drush `adia` (alias de `ad:import:all`) déclenche l'indexation
Elasticsearch :

1. Le module Search API indexe tous les nœuds films
2. Les champs sont mappés selon la configuration Search API
3. Elasticsearch stocke les documents indexés pour une récupération rapide

---

## Infrastructure des services

### Orchestration Docker

L'environnement de production utilise Docker Compose pour orchestrer quatre services principaux :

**Détails de configuration :**

| Service         | Image/Build                           | Réseaux             | Routes exposées                           |
|-----------------|---------------------------------------|---------------------|-------------------------------------------|
| `patrimony`     | Dockerfile personnalisé (Caddy + PHP) | `ad-network`, `web` | `patrimony.audiodescription.beta.gouv.fr` |
| `drupal`        | Dockerfile personnalisé (Caddy + PHP) | `ad-network`, `web` | `audiodescription.beta.gouv.fr`           |
| `db`            | `postgres:16-bookworm`                | `ad-network`        | Interne uniquement                        |
| `elasticsearch` | `elasticsearch:8.14.3`                | `ad-network`        | Interne uniquement                        |

---

### Configuration de la base de données

L'instance PostgreSQL héberge deux bases de données initialisées via `docker/postgres/init-patrimony-db.sh` :

1. **`drupal_db`** (schéma public) - Tables Drupal core et personnalisées
2. **`patrimony_db`** (schéma patrimony) - Entités Symfony/Doctrine

L'application Drupal se connecte à `drupal_db` mais interroge directement le schéma `patrimony` pour les opérations de
synchronisation.

---

### Configuration Elasticsearch

Elasticsearch fonctionne comme un cluster à nœud unique avec les paramètres suivants :

- **Type de découverte** : `single-node` (pas de clustering)
- **Sécurité** : Désactivée (`xpack.security.enabled=false`)
- **Taille du tas** : 2GB (`-Xms2048m -Xmx2048m`)
- **Persistance des données** : `/usr/share/elasticsearch/data` mappé vers le volume `elastic-data`

---

## Automatisation Makefile

Le `Makefile` fournit des commandes de haut niveau qui abstraient la complexité :

**Commandes clés :**

*@TODO : à remplir*

---

## Points d'intégration

### Patrimony vers Drupal

L'intégration se fait via une API Rest fournie par Patrimony.

---

### Drupal vers Elasticsearch

L'intégration se fait via le module Search API :

1. **Configuration d'index** : Search API définit les champs à indexer
2. **Connecteur** : Le module connecteur Elasticsearch gère la communication
3. **Indexation** : Déclenchée via `drush adia` ou automatiquement lors des changements de contenu
4. **Interrogation** : `MovieSearchManager` utilise Search API pour interroger Elasticsearch

---

### Gestion des erreurs (410 Gone)

Le système implémente une gestion d'erreur personnalisée pour les films indisponibles :

**Composants :**

- **Event Subscriber** : `MovieExceptionSubscriber` - Intercepte les exceptions pour les films non publiés
- **Contrôleur** : `Error410Controller` - Rend la page d'erreur 410
- **Template** : `error-410.html.twig` - Template personnalisé pour l'erreur 410
- **Route** : `audiodescription.error_410` - Route dédiée à l'erreur

Workflow :

1. L'utilisateur accède à `/node/{nid}` pour un film non publié
2. `MovieExceptionSubscriber` détecte l'exception
3. Redirige vers la route `audiodescription.error_410`
4. `Error410Controller` rend le template avec code HTTP 410
5. Le template affiche un message convivial et suggère des alternatives

---

## Planification cron

Le système exécute un import automatisé quotidien :

**Entrée crontab :**

```cron
5 1 * * * cd /opt/audiodescription && make cron-import >> /opt/audiodescription/logs/cron-import.log
0 10 * * 1 cd /opt/application/audiodescription && make pt-send-newsletter >> /opt/application/audiodescription/logs/cron-newsletter.log
```

**Rotation des logs :**

- Les logs tournent quotidiennement via logrotate
- Rétention : 15 jours
- Compression : Activée avec délai
- Emplacement : `/var/log/audiodescription/cron-import.log`

Configuration logrotate :

```
/var/log/audiodescription/*.log {
    daily
    rotate 15
    compress
    delaycompress
    missingok
    notifempty
}
```