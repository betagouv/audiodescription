# Arte TV

## Informations générales
* **Format :** API
* **URL :** Variable d'environnement `ARTE_TV_API_URL`
* **Authentification :** Aucune (URL pré-signée)
* **Fréquence d'appel :** Quotidienne

## Paramètres

L'API ARTE retourne directement la liste des films audiodécrits disponibles. Aucun paramètre de filtrage n'est nécessaire côté client.

---

**⚠️ Filtre appliqué à l'import :**
* Les contenus de type `genrePresse = "Téléfilm"` sont exclus de l'import.

## Structure de la réponse

```json
{
  "programs": [
    {
      "durationSeconds": 90,
      "public": "",
      "externalIds": {
        "plurimedia": "",
        "ISAN": null
      },
      "videoRightsBegin": "",
      "videoRightsEnd": "",
      "programId": "",
      "title": "",
      "shortDescription": "",
      "productionYear": 2000,
      "mainImage": {
        "url": "",
        "copyright": null
      },
      "director": "",
      "casting": [
        {
          "name": "",
          "activity": "",
          "activityCode": "",
          "characterName": ""
        },
        {
          "name": "",
          "activity": "",
          "activityCode": "",
          "characterName": ""
        }
      ],
      "case": "",
      "genrePresse": "Film",
      "url": "",
      "productionCountries": [
        {
          "arteCode": "FRA",
          "isoAlpha2Code": "FR",
          "label": "France"
        }
      ]
    }
  ]
}
```

## Mapping des champs
| Donnée                                 | Champ                   | Format   | Commentaire                                      |
|----------------------------------------|-------------------------|----------|--------------------------------------------------|
| ID ARTE                                | `['programId']`         | string   | Identifiant unique du programme ARTE             |
| Titre                                  | `['title']`             | string   |                                                  |
| Synopsis                               | `['shortDescription']`  | string   |                                                  |
| Année de production                    | `['productionYear']`    | int      |                                                  |
| Genres                                 | ~~`['genre']`~~  | array    | Non utilisé (données non pertinentes)            |
| Date de début de droits d'exploitation | `['videoRightsBegin']`  | datetime |                                                  |
| Date de fin de droits d'exploitation   | `['videoRightsEnd']`    | datetime |                                                  |
| Lien                                   | `['url']`               | string   | URL de visionnage sur arte.tv                    |
| Réalisateur                            | `['director']`          | string   | Nom complet du réalisateur                       |
| Casting (acteurs)                      | `['casting']`           | array    | Tableau d'objets `{name, characterName}`         |
| Durée                                  | `['durationSeconds']`   | int      | Durée en secondes, convertie en minutes à l'import |
| Nationalités                           | `['productionCountries']` | array    | Tableau d'objets `{label}`                       |
| Type de contenu                        | `['genrePresse']`       | string   | Utilisé pour filtrer les téléfilms              |

## Commande d'import

```bash
bin/console ad:import:artetv-api
bin/console ad:import:artetv-api --create-movies=true
```

### Options
| Option            | Valeur par défaut | Description                                      |
|-------------------|-------------------|--------------------------------------------------|
| `--create-movies` | `false`           | Si `true`, crée les films dans la table Movie    |

## Fichier source

`patrimony/src/Importer/Movie/ArteTvApiImporter.php`
