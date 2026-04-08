# Agents — Plateforme Audiodescription

## Contexte du projet

La plateforme audiodescription est un service public numérique développé par l'Atelier Numérique du ministère de la Culture, incubé au sein du programme beta.gouv.fr. Elle vise à rendre le cinéma accessible aux personnes malvoyantes ou aveugles en centralisant l'offre de films audiodécrits disponibles sur les plateformes de streaming.

Le site propose :
- Un catalogue interrogeable de films audiodécrits (par titre, genre, réalisateur, année, prix)
- Des ressources documentaires sur l'audiodescription (cinéma, spectacle vivant, etc.)
- Des liens directs ou renvois vers les plateformes partenaires (Canal, France TV, Arte, TF1, Orange, LaCinétek)

---

## Architecture générale

Le projet repose sur **deux applications distinctes** communiquant via une API REST, orchestrées par Docker Compose.

```
audiodescription/
├── drupal/          # CMS public (interface utilisateur)
├── patrimony/       # API Symfony (catalogue films, backend)
├── docker/          # Dockerfiles par service
├── cypress/         # Tests end-to-end
├── docs/            # Documentation technique et installation
├── scripts/         # Crontab, scripts de restauration BDD
├── infra/           # Infrastructure (Traefik)
├── compose.yml      # Stack Docker dev
├── compose.prod.yml # Stack Docker production
├── Makefile         # Interface CLI principale
└── package.json     # Dépendances Node (Cypress, Lighthouse)
```

---

## Drupal (CMS public)

**Rôle** : Interface publique du site, gestion de contenu éditorial, moteur de recherche films.

**Stack** :
- Drupal 11 (PHP)
- Thème custom `ad_theme` avec Vite 6, SASS, Lit (web components), Fuse.js
- DSFR (Design System de l'État français) pour l'accessibilité
- Elasticsearch 8.14 via `search_api` + `elasticsearch_connector`
- Modules clés : `ui_suite_dsfr`, `layout_paragraphs`, `simple_oauth`, `config_pages`, `metatag`, `pathauto`

**Répertoire** : `drupal/`

**Points d'entrée** :
- HTTP : `drupal/web/index.php`
- Module custom : `drupal/web/modules/custom/audiodescription/`
- Thème : `drupal/web/themes/ad_theme/`
- Config : `drupal/config/sync/` (export), `drupal/config/prod/`, `drupal/config/local/`

**Build du thème** :
```bash
npm run dev    # Watch (développement)
npm run build  # Production
```

**Fonctionnalités du module custom `audiodescription`** :
- Formulaires de recherche simple et avancée de films (filtres : genre, réalisateur, année, thématique)
- Affichage des fiches films et résultats de recherche via l'API Patrimony
- Gestion des abonnements/désabonnements à la newsletter
- Breadcrumb contextuel et navigation dynamique
- Intégration Elasticsearch pour la recherche facettée

---

## Patrimony (API Symfony)

**Rôle** : Backend API REST exposant le catalogue de films audiodécrits, gestion des imports multi-sources, interface d'administration.

**Stack** :
- Symfony 7.1 (PHP 8.3+)
- API Platform 3.3 (JSON-LD, OpenAPI)
- Doctrine ORM 3.2 avec PostgreSQL 16
- FrankPHP (serveur HTTP)
- EasyAdmin 4 (interface d'administration)
- JWT pour l'authentification API, OIDC pour l'IHM admin

**Répertoire** : `patrimony/`

**Point d'entrée HTTP** : `patrimony/public/index.php`

### Entités principales

| Entité | Description |
|--------|-------------|
| `Movie` | Film audiodécrit (titre, année, IDs plateformes externes) |
| `Offer` | Offre de streaming associée à un film |
| `Director` | Réalisateur |
| `Actor` | Acteur |
| `Genre` | Genre cinématographique |
| `Language` | Langue |
| `Nationality` | Nationalité |
| `Solution` | Plateforme de diffusion (Canal, France TV, etc.) |
| `Partner` | Partenaire institutionnel |
| `SourceMovie` | Suivi de l'origine et de l'état de sync des films par source |

Les entités sont dans `patrimony/src/Entity/Patrimony/` (entités principales) et `patrimony/src/Entity/Source/` (entités sources).

### Imports multi-sources

Le système importe automatiquement les films depuis 6+ partenaires :

| Source | Type |
|--------|------|
| CNC | Données publiques |
| Canal VOD | API |
| Canal Replay | API |
| Orange VOD | CSV |
| LaCinétek | API |
| Arte.tv | API |
| TF1 | API |
| France TV | API |

Tous les importeurs implémentent `MovieImporterInterface` (pattern Strategy). Une factory sélectionne dynamiquement l'importeur approprié. Les commandes CLI déclenchent les imports : `patrimony/src/Command/`.

**Imports planifiés** (via `scripts/crontab`) :
- Imports quotidiens des films à 1h05
- Newsletter hebdomadaire le lundi à 10h00

### Authentification

- **API** (stateless) : JWT tokens via `POST /api/v1/login`
- **IHM Admin** (stateful) : OIDC (OpenID Connect)
- Deux firewalls Symfony distincts : `api` et `ihm`

---

## Infrastructure Docker

**Services** (définis dans `compose.yml`) :

| Service | Rôle | Port |
|---------|------|------|
| `drupal` | Application Drupal | 8080 |
| `patrimony` | API Symfony (FrankPHP) | 8083 |
| `db` | PostgreSQL 16 | 5432 |
| `elasticsearch` | Moteur de recherche | 9200, 9300 |
| `node` | Build thème Drupal | — |
| `a11y` | Tests accessibilité (Lighthouse) | — |

Commandes principales :
```bash
make up    # Démarrer tous les services
make down  # Arrêter tous les services
```

---

## Tests et qualité

### Patrimony
- **Tests** : Codeception (unitaires, API, intégration) → `make pt-tests`
- **Analyse statique** : PHPStan 1.12+ → `make pt-phpstan`
- **Standards** : PHP CodeSniffer, PHPMD
- **Hooks Git** : GrumPHP (pre-commit)

### Drupal / frontend
- **E2E** : Cypress 14 (`cypress/e2e/`) → `make cypress`
- **Performance & accessibilité** : Lighthouse CI → `make lighthouse`

---

## Commandes Makefile importantes

```bash
make up                  # Démarrer Docker
make pt-import-all       # Importer depuis toutes les sources
make pt-import-orange    # Import Orange VOD
make pt-import-francetv  # Import France TV
make pt-tests            # Lancer les tests Patrimony
make pt-phpstan          # Analyse statique
make lighthouse          # Audit performance
make cypress             # Tests E2E
```

---

## Points d'attention pour les LLMs

- **Deux applications PHP indépendantes** : ne pas confondre le code Drupal (`drupal/`) et le code Symfony (`patrimony/`). Ils ont chacun leur `composer.json`, leur base de code, et leurs conventions.
- **API Platform** : les ressources API dans Patrimony sont déclarées via attributs PHP sur les entités ou dans `patrimony/src/ApiResource/`. Les routes sont auto-générées.
- **DSFR obligatoire** : toute modification de l'interface doit respecter le Design System de l'État. Les composants Twig utilisent les classes DSFR.
- **Accessibilité prioritaire** : le projet cible les personnes malvoyantes. Chaque modification frontend doit être testée en accessibilité (Lighthouse, navigation clavier, lecteurs d'écran).
- **Imports idempotents** : les importeurs ne doublonnent pas les films — ils utilisent les IDs externes pour matcher les entrées existantes avant de créer de nouvelles.
- **Environnements** : `compose.yml` (dev), `compose.staging.yml` (staging), `compose.prod.yml` (production). Les variables d'environnement sensibles sont dans `.env.local` (non versionné), basé sur `.env.dist` et `patrimony/.env.local.dist`.
