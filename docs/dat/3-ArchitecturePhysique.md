# Architecture physique

Le Portail de l'audiodescription est déployé sur un serveur dédié via Docker Compose, avec Traefik comme reverse proxy pour la gestion du routage et des certificats SSL.

## Schéma d'architecture physique

Le schéma suivant présente l'architecture physique de l'application.

*@TODO : schéma d'architecture physique*

## Infrastructure de déploiement

### Serveur hôte

L'application est déployée sur un serveur Linux avec Docker. L'ensemble des services sont orchestrés via Docker Compose.

### Réseau Docker

Deux réseaux Docker sont configurés :
- **web** : Réseau externe partagé avec Traefik pour l'exposition des services
- **ad-network** : Réseau interne pour la communication entre les containers de l'application

### Reverse Proxy (Traefik v3.3)

Traefik assure les fonctions suivantes :
- Routage des requêtes HTTP/HTTPS vers les containers appropriés
- Terminaison TLS avec certificats Let's Encrypt (renouvellement automatique)
- Redirection HTTP vers HTTPS
- Redirection www vers non-www

Configuration des entrypoints :
- Port 80 (HTTP) : Redirection automatique vers HTTPS
- Port 443 (HTTPS) : Point d'entrée principal avec TLS

## Inventaire des ressources

| Composant     | Instances | CPU   | Mémoire | Stockage persistant |
|---------------|-----------|-------|---------|---------------------|
| Traefik       | 1         | 0.25  | 128Mo   | acme.json (certificats) |
| Drupal        | 1         | 1     | 512Mo   | N/A                 |
| Patrimony     | 1         | 1     | 512Mo   | N/A                 |
| PostgreSQL    | 1         | 1     | 1Go     | 10Go (db-data)      |
| ElasticSearch | 1         | 1     | 2Go     | 5Go (elastic-data)  |

### Volumes persistants

| Volume        | Composant     | Description                              |
|---------------|---------------|------------------------------------------|
| db-data       | PostgreSQL    | Données de la base de données            |
| elastic-data  | ElasticSearch | Index de recherche                       |
| acme.json     | Traefik       | Certificats SSL Let's Encrypt            |

## Noms de domaine

| Domaine                                     | Service   | Description              |
|---------------------------------------------|-----------|--------------------------|
| audiodescription.beta.gouv.fr               | Drupal    | Portail public           |
| www.audiodescription.beta.gouv.fr           | Drupal    | Redirection vers non-www |
| patrimony.audiodescription.beta.gouv.fr     | Patrimony | API et administration    |
| www.patrimony.audiodescription.beta.gouv.fr | Patrimony | Redirection vers non-www    |

---

# Exploitabilité

## Supervision

### Logs

Les logs des containers sont accessibles via Docker :
```bash
docker logs <container_name>
```

Traefik génère des logs d'accès au format JSON pour faciliter l'intégration avec des outils de monitoring.

### Healthchecks

Les containers peuvent être surveillés via les commandes Docker standard :
```bash
docker ps
docker stats
```

## Paramétrage

Le paramétrage de l'application se fait via :
- **Variables d'environnement** : Définies dans les fichiers `.env` et `compose.*.yml`
- **Fichiers de configuration** : Caddyfile, php.ini, traefik.yml

### Fichiers de configuration principaux

| Fichier                                | Description                          |
|----------------------------------------|--------------------------------------|
| compose.prod.yml                       | Configuration Docker Compose (prod)  |
| compose.staging.yml                    | Configuration Docker Compose (staging) |
| infra/compose.yml                      | Configuration Traefik                |
| infra/traefik.yml                      | Configuration du reverse proxy       |
| docker/drupal/prod/php/php.ini         | Configuration PHP Drupal             |
| docker/patrimony/prod/php/php.ini      | Configuration PHP Patrimony          |
| docker/drupal/prod/caddy/Caddyfile     | Configuration Caddy Drupal           |
| docker/patrimony/prod/caddy/Caddyfile  | Configuration Caddy Patrimony        |

## Mise à jour

### Procédure de déploiement

1. Pull des dernières modifications du code :
   ```bash
   git pull origin main
   ```

2. Rebuild des images Docker :
   ```bash
   docker compose -f compose.prod.yml build
   ```

3. Redémarrage des services :
   ```bash
   docker compose -f compose.prod.yml up -d
   ```

4. Exécution des migrations (si nécessaire) :
   ```bash
   # Drupal
   docker compose -f compose.prod.yml exec drupal drush updb -y
   docker compose -f compose.prod.yml exec drupal drush cim -y
   docker compose -f compose.prod.yml exec drupal drush cr

   # Patrimony
   docker compose -f compose.prod.yml exec patrimony bin/console doctrine:migrations:migrate --no-interaction
   ```

### Rollback

En cas de problème, rollback vers la version précédente :
```bash
git checkout <commit_precedent>
docker compose -f compose.prod.yml build
docker compose -f compose.prod.yml up -d
```

## Sauvegardes / Restauration

### Sauvegarde de la base de données

```bash
# Export de la base PostgreSQL
docker compose -f compose.prod.yml exec db pg_dump -U audiodescription drupal > backup_drupal_$(date +%Y%m%d).sql
docker compose -f compose.prod.yml exec db pg_dump -U audiodescription -n patrimony patrimony > backup_patrimony_$(date +%Y%m%d).sql
```

### Sauvegarde des volumes

*@TODO : Procédure à tester*

```bash
# Sauvegarde du volume PostgreSQL
docker run --rm -v audiodescription_db-data:/data -v $(pwd):/backup alpine tar czf /backup/db-data-backup.tar.gz /data

# Sauvegarde du volume ElasticSearch
docker run --rm -v audiodescription_elastic-data:/data -v $(pwd):/backup alpine tar czf /backup/elastic-data-backup.tar.gz /data
```

### Restauration
*@TODO : Procédure à tester*

```bash
# Restauration de la base PostgreSQL
cat backup_drupal_YYYYMMDD.sql | docker compose -f compose.prod.yml exec -T db psql -U audiodescription drupal

# Restauration d'un volume
docker run --rm -v audiodescription_db-data:/data -v $(pwd):/backup alpine tar xzf /backup/db-data-backup.tar.gz -C /
```

### Fréquence recommandée

| Élément       | Fréquence        | Rétention       |
|---------------|------------------|-----------------|
| Base de données | Quotidienne    | 30 jours        |
| Configuration | À chaque modification | Versionnée (Git) |

[<< Page précédente - Architecture technique](2-ArchitectureTechnique.md) - [Page suivante - Annexes >>](4-Annexes.md)