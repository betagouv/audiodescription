# Drupal

---

## Objectif et périmètre

L'application Drupal est la partie publique du Portail de l'audiodescription. 
Il affiche les informations sur les films, gère la recherche et le filtrage, et assure l'affichage des contenus. 

Le Drupal consomme les données des films de Patrimony (Symfony) et les restitue aux utilisateurs finaux.

---

## Stack technique

Le Drupal est en version 11 avec les dépendances principales suivantes :

| Composant         | Package | Rôle |
|-------------------|---|---|
| **Core**          | `drupal/core-recommended: ^11.0` | Plateforme CMS Drupal |
| **DSFR**          | `drupal/ui_suite_dsfr: ^1.0` / `gouv/dsfr: ^1.11` | Système de design gouvernemental DSFR |
| **Recherche**     | `drupal/search_api: ^1.35` / `drupal/elasticsearch_connector: ^8.0@alpha` / `drupal/search_api_elasticsearch: dev-7.x-1.x` | Intégration Elasticsearch |
| **OAuth**         | `drupal/simple_oauth: ^6.0@beta` / `drupal/externalauth: ^2.0` | Authentification API |
| **Développement** | `drush/drush: ^13.3` / `drupal/devel: ^5.3` | Outils en ligne de commande |


---

## Architecture du système

Le frontend Drupal suit une architecture en couches :

1. **Couche Core** : le routage Drupal, l'API des entités et le conteneur de services constituent la fondation.
2. **Couche Module personnalisé** : le module `audiodescription` contient la logique métier.
3. **Couche Thème** : le thème `ad_theme` gère l'affichage avec des templates Twig et un style basé sur le DSFR.
4. **Couche Données** : les contenus sont stockés dans PostgreSQL et indexés dans Elasticsearch.

---

## Modèle de contenu

Le frontend Drupal stocke les contenus dans les types d'entités suivants :

### Types de contenu

| Bundle | Machine name | Rôle |
|---|---|---|
| Film | `movie` | Informations sur les films audiodécrits |
| Page | `page` | Pages de contenu statique |

### Taxonomies

| Vocabulaire | Machine name | Rôle |
|---|---|---|
| Genre | `genre` | Genres cinématographiques (Action, Drame, etc.) |
| Réalisateur | `director` | Réalisateurs de films |
| Collection | `collection` | Listes de films curatées (mises en avant sur l'accueil) |
| Partenaire | `partner` | Plateformes de streaming (France TV, Arte, etc.) |
| Offre | `offer` | Types de disponibilité (Gratuit, Abonnement, Achat) |
| Nationalité | `nationality` | Pays de production |
| Public | `public` | Classifications par âge |

---

## Organisation du module

Le module `audiodescription` suit la structure de répertoires standard de Drupal :

```
drupal/web/modules/custom/audiodescription/
├── audiodescription.info.yml          # Métadonnées du module
├── audiodescription.module            # Implémentations des hooks
├── audiodescription.routing.yml       # Définitions des routes
├── audiodescription.services.yml      # Conteneur de services
├── src/
│   ├── Breadcrumb/                    # Builders de fil d'Ariane
│   ├── Controller/                    # Contrôleurs de pages
│   ├── EntityManager/                 # Managers CRUD des entités
│   ├── EventSubscriber/               # Abonnés aux événements
│   ├── Form/                          # Classes de formulaires
│   ├── Importer/                      # Importers de synchronisation Patrimony
│   ├── Manager/                       # Services de logique métier
│   ├── Parser/                        # Parsing CSV
│   └── TwigExtension/                 # Filtres Twig personnalisés
└── templates/                         # Suggestions de templates Twig
```

### Répertoires clés

- **Controller/** : gère les requêtes HTTP et construit les tableaux de rendu pour les pages
- **EntityManager/** : fournit une couche d'abstraction pour les opérations sur les entités (création, lecture, mise à jour)
- **Importer/** : synchronise les données de PostgreSQL Patrimony vers les entités Drupal via des commandes Drush
- **Breadcrumb/** : sept builders de fil d'Ariane spécialisés avec des priorités pour différents types de pages
- **EventSubscriber/** : gère les erreurs 410 Gone pour les films dépubliés

---

## Modules activés et thème

### Modules de la communauté

| Module | Rôle |
|---|---|
| `config_pages` | Pages de configuration singleton (paramètres de l'accueil) |
| `config_split` | Configuration spécifique à l'environnement |
| `elasticsearch_connector` | Connexion Elasticsearch |
| `externalauth` | Mapping d'authentification OAuth |
| `field_group` | Regroupement de champs dans les formulaires d'entités |
| `layout_paragraphs` | Constructeur visuel de layout pour paragraphes |
| `metatag` | Gestion des balises meta |
| `paragraphs` | Composants de contenu structuré |
| `pathauto` | Génération automatique d'alias d'URL |
| `search_api` | Couche d'abstraction pour la recherche |
| `simple_oauth` | Serveur OAuth 2.0 |
| `simple_sitemap` | Génération de sitemap XML |
| `ui_patterns` | Bibliothèque de patterns |
| `ui_suite_dsfr` | Intégration des patterns DSFR |

### Modules personnalisés

- `audiodescription` : logique métier principale (ce module)

### Thèmes

| Thème | Rôle |
|---|---|
| `ui_suite_dsfr` | Thème de base DSFR |
| `ad_theme` | Sous-thème personnalisé avec les templates spécifiques au projet |

Le thème `ad_theme` étend `ui_suite_dsfr` pour fournir des templates personnalisés, du SCSS et du JavaScript tout en maintenant la conformité DSFR.

---

## Gestion des erreurs

Le module implémente une gestion personnalisée des erreurs pour les contenus indisponibles.

### 410 Gone pour les films dépubliés

Lorsqu'un utilisateur tente d'accéder à un nœud film dépublié, le système renvoie un statut **410 Gone** plutôt qu'un 403 Forbidden :

1. **MovieExceptionSubscriber** intercepte les `AccessDeniedHttpException` sur les nœuds films
2. Vérifie si le nœud est dépublié
3. Restitue le template `error_410` avec le contexte du film
4. Retourne le code de statut HTTP 410

Cela fournit un statut HTTP plus sémantique pour les films qui étaient précédemment disponibles mais ne le sont plus (droits expirés).

---

## Résumé

L'application publique est basée **sur le CMS Drupal 11** qui sert d'interface publique au Portail de l'audiodescription.

Le système suit les bonnes pratiques Drupal avec injection de dépendances, architecture orientée services et séparation claire entre logique métier (module) et présentation (thème).