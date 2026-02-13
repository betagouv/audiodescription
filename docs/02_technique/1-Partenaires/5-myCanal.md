# MyCanal (Canal Replay)

## Informations générales
* **Format :** API
* **URL :** Variable d'environnement `CANAL_REPLAY_API_URL`
* **Authentification :** Basic Auth (`CANAL_VOD_API_AUTH_TOKEN`)
* **Fréquence d'appel :** Quotidienne
* **Type d'offre :** SVOD (abonnement)

## Fonctionnement

L'API retourne d'abord une liste d'URLs (`locations`) pointant vers les fiches détaillées de chaque programme. L'importeur effectue ensuite une requête pour chaque URL.

---

**⚠️ Filtres appliqués à l'import :**
* `replay` : Seuls les films disponibles en SVOD.
* `contentType = "film"` : Seuls les films sont importés.
* `language.qad` contient `"fr"` : Seuls les films avec audiodescription française sont importés.

## Structure de la réponse (index)

```json
{
  "locations": [
    "",
    "",
    ""
    ]
}
```

## Structure de la réponse (programme)

```json
{
  "id": "",
  "title": "",
  "synopsis": {
    "large": ""
  },
  "productionYear": "",
  "productionNationalities": [
    {
      "id": "",
      "name": ""
    }
  ],
  "duration": 90,
  "parentalRatings": [
    {
      "value": "",
      "authority": ""
    }
  ],
  "pictures": [
    {
      "JAQCANAL": ""
    }
  ],
  "castings": [
    {
      "job": {
        "type": "Réalisateur"
      },
      "persons": [
        {
          "firstName": "",
          "lastName": "",
          "role": ""
        }
      ]
    },
    {
      "job": {
        "type": "Acteur"
      },
      "persons": [
        {
          "firstName": "",
          "lastName": "",
          "role": ""
        }
      ]
    }
  ],
  "externalIds": {
    "ALLOCINE": [
      ""
    ]
  },
  "contentType": "film",
  "genre": {
    "secondary": ""
  },
  "deeplink": [
    {
      "url": ""
    }
  ],
  "availability": {
    "startDate": "",
    "endDate": ""
  },

  "language": {
    "qad": [
      "fr"
    ]
  }
}
```

## Mapping des champs
| Donnée                                 | Champ                              | Format   | Commentaire                                           |
|----------------------------------------|------------------------------------|----------|-------------------------------------------------------|
| ID Canal                               | `['id']`                           | string   | Identifiant unique du programme                       |
| Titre                                  | `['title']`                        | string   |                                                       |
| Synopsis                               | `['synopsis']['large']`            | string   |                                                       |
| Année de production                    | `['productionYear']`               | int      |                                                       |
| Genre                                  | `['genre']['secondary']`           | string   | Format `CINEMA-Genre`, on extrait la partie après `-` |
| Date de début de droits d'exploitation | `['availability']['startDate']`    | datetime |                                                       |
| Date de fin de droits d'exploitation   | `['availability']['endDate']`      | datetime |                                                       |
| Lien                                   | `['deeplink'][0]['url']`           | string   | URL de visionnage                                     |
| Réalisateurs                           | `['castings']`                     | array    | Objets avec `job.type = "Réalisateur"`                |
| Acteurs                                | `['castings']`                     | array    | Objets avec `job.type = "Acteur"`                     |
| Durée                                  | `['duration']`                     | int      | Durée en minutes                                      |
| Affiche (poster)                       | `['pictures']`                     | array    | Chercher la clé `JAQCANAL`                            |
| Nationalités                           | `['productionNationalities']`      | array    | Objets `{name}`                                       |
| ID Allociné                            | `['externalIds']['ALLOCINE'][0]`   | string   |                                                       |
| Public                                 | `['parentalRatings'][0]['value']`  | string   | Converti : 1=TP, 2=10, 3=12, 4=16, 5=18               |

### Structure du casting

```json
{
  "castings": [
    {
      "job": {
        "type": "Réalisateur"
      },
      "persons": [
        {
          "firstName": "",
          "lastName": "",
          "role": ""
        }
      ]
    },
    {
      "job": {
        "type": "Acteur"
      },
      "persons": [
        {
          "firstName": "",
          "lastName": "",
          "role": ""
        }
      ]
    }
  ]
}
```

### Conversion du public (parentalRatings)

| Valeur API | Signification                  | Code stocké |
|------------|--------------------------------|-------------|
| 1          | Tous publics                   | TP          |
| 2          | Déconseillé aux moins de 10 ans | 10          |
| 3          | Déconseillé aux moins de 12 ans | 12          |
| 4          | Déconseillé aux moins de 16 ans | 16          |
| 5          | Déconseillé aux moins de 18 ans | 18          |

## Commande d'import

```bash
bin/console ad:import:canal-replay-api
bin/console ad:import:canal-replay-api --create-movies=true
```

### Options
| Option            | Valeur par défaut | Description                                      |
|-------------------|-------------------|--------------------------------------------------|
| `--create-movies` | `false`           | Si `true`, crée les films dans la table Movie    |

## Fichier source

`patrimony/src/Importer/Movie/CanalReplayApiImporter.php`
