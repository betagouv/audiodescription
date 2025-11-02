# France TV

## Informations générales
* **Format :** API
* **URLs :** 
  * https://gatewayvf.webservices.francetelevisions.fr/v1/videos?category.id=films&audio_tracks.id=audiodescription&platforms.ftv.status=available&per_page=100&created_by=france.tv@francetv.fr
  * https://gatewayvf.webservices.francetelevisions.fr/v1/videos?category.id=films&audio_tracks.id=audiodescription&platforms.ftv.status=available&per_page=100&channel.tags[in]=premium
* **Authentification :** Aucune
* **Fréquence d'appel :** Quotidienne

## Paramètres
* `category_id = films` : récupérer uniquement les films.
* `audio_tracks.id = audiodescription` : récupérer uniquement les contenus audiodécrits.
* `platforms.ftv.status = available` : récupérer uniquement les contenus sous droits.
* `per_page = 100` : récupérer 100 contenus par page (20 par défaut).
* `created_by=france.tv@francetv.fr` : récupérer les contenus 100% numériques.
* `channel.tags[in]=premium` : récupérer les contenus des chaînes premium (France 2 à France 5).

Il faut faire 2 requêtes : 
* une pour filtrer les contenus 100% numériques
* une pour filtrer les films audiodécrits diffusés sur les chaînes premium (France 2 à France 5).

## Mapping des champs
| # | Nom du flux | Type        | URL                                  |
|---|-------------|-------------|--------------------------------------|
| 1 | France TV   | API         | https://gatewayvf.wetags[in]=premium |
