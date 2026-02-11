# Orange VOD

## Informations générales
* **Format :** CSV
* **Fichier :** Variable d'environnement `ORANGE_VOD_FILENAME` (dans le dossier `patrimony/data/`)
* **Authentification :** Aucune (fichier local)
* **Fréquence de mise à jour :** Annuelle (demander un fichier mis à jour à Orange)
* **Type d'offre :** TVOD (location à l'acte)
* **Séparateur CSV :** `;`

---

**⚠️ Filtre appliqué à l'import :**
* `Category = "film"` : Seuls les films sont importés

## Structure du fichier CSV

Seules les colonnes pertinentes sont listées dans le tableau ci-dessous.

| Colonne             | Description                          |
|---------------------|--------------------------------------|
| Product code        | Identifiant unique du produit        |
| Title               | Titre du film                        |
| Category            | Type de contenu (film, série, etc.)  |
| Production year     | Année de production                  |
| Genre               | Genre du film                        |
| Product duration    | Durée au format `HH:MM`              |
| Réalisateur         | Nom du réalisateur                   |
| Actor 1             | Nom de l'acteur 1                    |
| Actor 1 role        | Rôle de l'acteur 1                   |
| Actor 2             | Nom de l'acteur 2                    |
| Actor 2 role        | Rôle de l'acteur 2                   |
| Actor 3             | Nom de l'acteur 3                    |
| Actor 3 role        | Rôle de l'acteur 3                   |
| Default punchline 3 | Synopsis                             |
| Production regions  | Nationalités (séparées par virgules) |
| Parental rating     | Classification d'âge                 |
| URL                 | Lien de visionnage                   |
| Isan                | Identifiant ISAN                     |

## Mapping des champs
| Donnée                                 | Colonne CSV             | Format   | Commentaire                                |
|----------------------------------------|-------------------------|----------|--------------------------------------------|
| ID Orange VOD                          | `Product code`          | string   | Identifiant unique du produit              |
| Titre                                  | `Title`                 | string   |                                            |
| Synopsis                               | `Default punchline 3`   | string   |                                            |
| Année de production                    | `Production year`       | int      |                                            |
| Genre                                  | `Genre`                 | string   |                                            |
| Date de début de droits d'exploitation | -                       | -        | Non fourni                                 |
| Date de fin de droits d'exploitation   | -                       | -        | Non fourni                                 |
| Lien                                   | `URL`                   | string   | URL de visionnage                          |
| Réalisateur                            | `Réalisateur`           | string   | Nom complet                                |
| Acteurs                                | `Actor 1/2/3`           | string   | Jusqu'à 3 acteurs avec leurs rôles         |
| Durée                                  | `Product duration`      | string   | Format `HH:MM`, converti en minutes        |
| Nationalités                           | `Production regions`    | string   | Liste séparée par virgules                 |
| ID ISAN                                | `Isan`                  | string   |                                            |
| Public                                 | `Parental rating`       | string   | Voir tableau de conversion                 |

### Conversion du public (Parental rating)

| Valeur CSV         | Code stocké |
|--------------------|-------------|
| tous publics       | TP          |
| déc. -10           | 10          |
| déc. -12 / int. -12 | 12          |
| déc. -16 / int. -16 | 16          |

## Commande d'import

```bash
bin/console ad:import:orange-vod-csv
bin/console ad:import:orange-vod-csv --create-movies=true
```

### Options
| Option            | Valeur par défaut | Description                                      |
|-------------------|-------------------|--------------------------------------------------|
| `--create-movies` | `false`           | Si `true`, crée les films dans la table Movie    |

## Fichier source

`patrimony/src/Importer/Movie/OrangeVodCsvImporter.php`
