#!/bin/bash
set -e

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" -v user="$POSTGRES_USER" <<-'EOSQL'
    CREATE DATABASE patrimony;
    GRANT ALL PRIVILEGES ON DATABASE patrimony TO :"user";
EOSQL