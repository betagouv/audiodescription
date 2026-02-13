# LaCinetek

## Informations générales
* **Format :** API
* **URL :** Variable d'environnement `LACINETEK_API_URL`
* **Authentification :** Bearer Token (`LACINETEK_API_AUTH_TOKEN`)
* **Fréquence d'appel :** Quotidienne
* **Types d'offres :** TVOD et SVOD (deux partenaires distincts : `LACINETEK_TVOD` et `LACINETEK_SVOD`)

## Paramètres

| Paramètre | Valeur | Description                        |
|-----------|--------|------------------------------------|
| `size`    | 200    | Nombre de films par page           |
| `page`    | 1..n   | Numéro de page (pagination)        |

---

**⚠️ Filtre appliqué à l'import :**
* Seuls les films avec `audioDescription.fr = true` sont importés.

## Structure de la réponse

```json
{
  "total_page": 22,
  "data": [
    {
      "id": "",
      "ids": {
        "alloCine": ""
      },      
      "localizedTitle": [
        {
          "language": "fr",
          "value": ""
        }
      ],
      "year": "",
      "directors": [
        ""
      ],
      "origin": "",
      "urls": {
        "film": {
          "fr": ""
        },
        "svod": {
          "fr": ""
        }
      },
      "duration": "",
      "description": [
        {
          "language": "fr",
          "value": ""
        }
      ],
      "actors": [
        {
          "id": "",
          "name": "",
          "role": ""
        }
      ],
      "availability": {
        "svod": {
          "fr": false
        },
        "tvod": {
          "fr": true
        }
      },
      "audioDescription": {
        "fr": true
      }
    }
  ]
}
```

## Mapping des champs
| Donnée                                 | Champ                        | Format   | Commentaire                                              |
|----------------------------------------|------------------------------|----------|----------------------------------------------------------|
| ID La Cinétek                          | `['id']`                     | string   | Identifiant unique du film                               |
| Titre                                  | `['localizedTitle']`         | array    | Prendre l'objet avec `language = "fr"`                   |
| Synopsis                               | `['description']`            | array    | Prendre l'objet avec `language = "fr"`, HTML nettoyé     |
| Année de production                    | `['year']`                   | int      |                                                          |
| Lien TVOD                              | `['urls']['film']['fr']`     | string   | Préfixé par `https://www.lacinetek.com`                  |
| Lien SVOD                              | `['urls']['svod']['fr']`     | string   | Préfixé par `https://www.lacinetek.com`                  |
| Réalisateurs                           | `['directors']`              | array    | Tableau de noms                                          |
| Acteurs                                | `['actors']`                 | array    | Objets `{name, role}`                                    |
| Durée                                  | `['duration']`               | string   | Format `XhYY`, converti en minutes                       |
| Nationalités                           | `['origin']`                 | string   | Liste séparée par virgules                               |
| Disponibilité TVOD                     | `['availability']['tvod']['fr']` | bool |                                                          |
| Disponibilité SVOD                     | `['availability']['svod']['fr']` | bool |                                                          |
| Audiodescription FR                    | `['audioDescription']['fr']` | bool     | Filtre pour l'import                                     |
| ID Allociné                            | `['ids']['alloCine']`        | string   |                                                          |

## Commande d'import

```bash
bin/console ad:import:lacinetek-api
bin/console ad:import:lacinetek-api --create-movies=true
```

### Options
| Option            | Valeur par défaut | Description                                      |
|-------------------|-------------------|--------------------------------------------------|
| `--create-movies` | `false`           | Si `true`, crée les films dans la table Movie    |

## Fichier source

`patrimony/src/Importer/Movie/LaCinetekApiImporter.php`
