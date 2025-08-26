#!/bin/bash
set -e

COMPOSE="-f compose.staging.yml"
DBSVC="db"
DBUSER="audiodescription"
DBNAME="audiodescription"
SQL="drupal.sql"

echo "⏳ Restauration de la base $DBNAME depuis $SQL ..."

# 1) Terminer les sessions ouvertes
docker compose $COMPOSE exec -T $DBSVC psql -U $DBUSER -d postgres \
  -c "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname='$DBNAME' AND pid <> pg_backend_pid();"

# 2) Drop & recreate
docker compose $COMPOSE exec -T $DBSVC dropdb   -U $DBUSER --if-exists $DBNAME
docker compose $COMPOSE exec -T $DBSVC createdb -U $DBUSER $DBNAME

# 3) Import
docker compose $COMPOSE exec -T $DBSVC psql -U $DBUSER -d $DBNAME -v ON_ERROR_STOP=1 < "$SQL"

echo "✅ Base $DBNAME restaurée avec succès."
