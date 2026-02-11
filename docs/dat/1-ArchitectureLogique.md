# Architecture logique

## Schéma d'architecture logique

Ce schéma présente l'architecture logique du Portail de l'audiodescription, composée de deux applications principales : **Drupal** (portail public) et **Patrimony** (backend).

![schéma technique](../assets/AD-schema-technique.jpg)

## Blocs fonctionnels

### Application Drupal (Portail public)

Application Drupal 11 servant de portail public pour la découverte de films en audiodescription. L'interface utilisateur est accessible et intégrée grâce au Design System de l'État Français (DSFR).

#### Page d'accueil
- Bloc de recherche full-text via Search API (et ElasticSearch)
- Bloc "Derniers films gratuits ajoutés"
- Bloc "Films gratuits qui expirent bientôt"
- Bloc de redirection vers la page d'inscription à la newsletter
- Bloc d'informaton sur l'audiodescription (et redirection vers les pages éditoriales)
- Bloc d'informaton sur le Portail de l'audiodescription (et moyen de contact)

#### Catalogue de films
- Affichage des fiches films avec des métadonnées (synopsis, poster, durée, année...)
- Affichage des offres partenaires avec dates de disponibilité (droits de diffusion) si les données sont fournies par le partenaire
- Gestion des relations : genres, réalisations, public

* Les champs Durée, Nationalités, Acteurs, Affiche ne sont pas encore utilisés dans l'application Drupal.

#### Moteur de recherche
- Recherche full-text via Search API (et ElasticSearch)
- Filtrage multi-critères en Ajax (sans rechargement de page) : films gratuits uniquement, plateformes, genres
- Pagination des résultats (20 items/page)

#### Navigation par taxonomies
- Page "Films par genre" (liste des films par catégorie)
- Pages collections thématiques comme "Films lauréats du Marius de l'audiodescription"

#### Newsletter
Le module de newsletter n'utilise pas les formulaires fournis par Brevo, car ils ne sont pas suffisamment accessibles.
L'application Drupal utilise l'API de Brevo et ses propres pages.

- Formulaire d'inscription
- Page de confirmation d'inscription
- Formulaire de désinscription
- Page de confirmation de désinscription

#### Import de données (depuis l'API Patrimony)
- Commande drush adia : import tous les films depuis Patrimony (tous les partenaires)
- Command drush adum : dépublier les films qui n'ont pas de solutions.

---

### Application Patrimony (Backend API)

Application Symfony 7.1 avec API Platform servant de backend pour l'agrégation et la gestion du catalogue de films en audiodescription.

#### API REST
- Endpoints RESTful exposés via API Platform (`/api/v1/`)
- Ressources : actors, actors_movies, directors, genres, genres/main, movies, nationality, offers, partners, public_restrictions, solutions
- Authentification par tokens API (JWT Bearer)
- Format JSON-LD

#### Administration (EasyAdmin)
- Interface CRUD pour la gestion du catalogue
- Gestion des films, solutions, genres, partenaires, offres, publics, source films

#### Import multi-sources
Connecteurs vers les plateformes partenaires pour l'agrégation automatique du catalogue :
- **ARTE TV** : Import via API
- **France TV** : Import via API
- **Canal+ VOD/Replay** : Import via API
- **Orange VOD** : Import via CSV
- **La Cinétek** : Import via API
- **TF1** : Import via API
- **Public** : Import des publics via CSV

#### Fusion automatique (MovieAutoMerger)
Ce composant est appelé lors des imports de films depuis les sources partenaires.

- Réconciliation des films provenant de sources multiples
- Unification par identifiants externes (Allociné, IMDB, ISAN, etc.)
- Merge des métadonnées et des solutions d'accès

#### Newsletter hebdomadaire
- Génération automatique du contenu (films récents, fin de droits, sélection aléatoire)
- Intégration avec l'API Brevo pour l'envoi des campagnes email
- Programmation et tests d'envoi

---

### Blocs externes

#### Bases de données PostgreSQL
* Base de données relationnelle hébergeant les données Patrimony (schéma `patrimony`).
* Base de données relationnelle hébergeant les données de l'application Drupal (schéma `drupal`).

#### API Brevo
Service externe d'emailing utilisé pour l'inscription / désinscription des abonnés et l'envoi des newsletters hebdomadaires.

#### APIs Partenaires VOD
Services externes des plateformes de diffusion (ARTE, France TV, Canal+, Orange, La Cinétek, TF1) fournissant les données de catalogue et de disponibilité des films en audiodescription.