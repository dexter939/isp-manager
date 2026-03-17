#!/usr/bin/env bash
# ==============================================================================
# init-replication.sh
# Eseguito da postgres all'avvio (una volta sola, in /docker-entrypoint-initdb.d)
# Crea l'utente "replicator" e abilita accesso replicazione da tutte le reti.
# ==============================================================================
set -e

REPL_PASSWORD="${POSTGRES_REPLICATION_PASSWORD:-replicator_secret}"

echo "[init-replication] Creo utente replicator..."
psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
    CREATE USER replicator WITH REPLICATION ENCRYPTED PASSWORD '${REPL_PASSWORD}';
    COMMENT ON ROLE replicator IS 'Streaming replication user for ISP Manager HA';
EOSQL

echo "[init-replication] Aggiorno pg_hba.conf per la replicazione..."
echo "host replication replicator 0.0.0.0/0 scram-sha-256" >> "${PGDATA}/pg_hba.conf"

echo "[init-replication] Ricarico configurazione PostgreSQL..."
pg_ctl reload -D "${PGDATA}" 2>/dev/null || true

echo "[init-replication] Completato."
