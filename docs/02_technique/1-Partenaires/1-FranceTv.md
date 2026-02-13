# France TV

## Informations générales
* **Format :** API
* **URLs :** 
  * Variable d'environnement `FRANCE_TV_API_URL_100_NUM`
  * Variable d'environnement `FRANCE_TV_API_URL_PREMIUM`
* **Authentification :** Aucune
* **Fréquence d'appel :** Quotidienne

## Paramètres
* `category_id = films` : récupérer uniquement les films.
* `audio_tracks.id = audiodescription` : récupérer uniquement les contenus audiodécrits.
* `platforms.ftv.status = available` : récupérer uniquement les contenus sous droits.
* `per_page = 100` : récupérer 100 contenus par page (20 par défaut).
* `created_by=france.tv@francetv.fr` : récupérer les contenus 100% numériques.
* `channel.tags[in]=premium` : récupérer les contenus des chaînes premium (France 2 à France 5).

---

**⚠️ Il faut faire 2 requêtes :**
* une pour filtrer les contenus 100% numériques
* une pour filtrer les films audiodécrits diffusés sur les chaînes premium (France 2 à France 5).

## Mapping des champs
| Donnée                                 | Champ                                                      | Format            | Commentaire                                                                                                             |
|----------------------------------------|------------------------------------------------------------|-------------------|-------------------------------------------------------------------------------------------------------------------------|
| ID France TV                           | `['id']`                                                   | UUID              |                                                                                                                         |
| Titre                                  | `['title']`                                                | string            |                                                                                                                         |
| Synopsis                               | `['description']`                                          | string            |                                                                                                                         |
| Année de production                    | `['produced_at']`                                          | string            |                                                                                                                         |
| Genres                                 | `['tags']`                                                 | array             | Prendre tous les tags ayant le `type = genre`.                                                                          |
| Date de début de droits d'exploitation | `['platforms']['ftv']['exploitation_windows'][0]['start']` | datetime ISO 8601 |                                                                                                                         |
| Date de fin de doits d'exploitation    | `['platforms']['ftv']['exploitation_windows'][0]['end']`   | datetime ISO 8601 |                                                                                                                         |
| Lien                                   | `['ftv_raw_url']`                                          | string            | Le champ "ftv_raw_url" est à privilégié.                                                                                |
| Lien *(alternative)*                   | `['deep_links_v2']`                                        | array             | La bonne URL à prendre se trouve dans `type = google` ET `targets[].offer = ftv`ET `targets[].platforms[] = androidTV`. |
| Casting (acteurs & réalisateurs)       | `['credits']`                                              | array             |                                                                                                                         |
| Public                                 | `['rating']`                                               | string            |                                                                                                                         |
| Duration                               | `['duration']` (si vide prendre `expected_duration`)       | datetime ISO 8601 |                                                                                                                         |
| Affiche (poster)                       | `['images']`                                               | string            | Prendre l'url de l'image avec le `ration = 3:4`.                                                                        |
| ID IMDB                                | `['imdb']['episode_id']`                                   | string            |                                                                                                                         |
| ID Plurimedia                          | `['plurimedia_broadcast_id']`                              | string            |                                                                                                                         |
| ID Allociné                            | `['allocine']['movie_id']`                                 | string            |                                                                                                                         |

## Commande d'import

```bash
bin/console ad:import:francetv-api
bin/console ad:import:francetv-api --create-movies=true
```

### Options
| Option            | Valeur par défaut | Description                                      |
|-------------------|-------------------|--------------------------------------------------|
| `--create-movies` | `false`           | Si `true`, crée les films dans la table Movie    |

## Fichier source

`patrimony/src/Importer/Movie/FranceTvApiImporter.php`