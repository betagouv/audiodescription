# Introduction

## Documentation

Cette documentation fournit les informations générales du Portail de l'audiodescription, dont :

- ses fonctionnalités métiers
- son architecture
- ses principaux composants

Elle constitue un point d'entrée pour les développeurs qui ont besoin de comprendre comment l'application est structurée
et comment ses grandes parties interagissent.

### Finalité et périmètre

Le portail du ministère de la Culture dédié à l’audiodescription permet aux usagers en situation de handicap visuel et à
leurs proches de découvrir :

- des films disponibles en audiodescription (AD) sur plusieurs plateformes partenaires
- un ensemble de ressources informatives sur l’audiodescription

Le Portail permet de :

- agrèger les données de disponibilité des films à partir des données fournies par les partenaires (arte.tv, CANALVOD,
  france.tv, LaCinetek, LaCinetek VOD, myCANAL, TF1+, VOD Orange)
- maintenir une base de données de films
- présenter ces informations via une interface publique avec un moteur de recherche.

Le dépôt implémente une architecture double application où :

- Patrimony (Symfony) gère l'import, la transformation et l'agrégation des données
- Drupal fournit le système de gestion de contenu et le site web destiné au public

