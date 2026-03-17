#!/usr/bin/env bash
# ==============================================================================
# entrypoint.sh — PostgreSQL Replica (Hot Standby)
# Se PGDATA è vuoto, esegue pg_basebackup dal primary e configura standby mode.
# Poi avvia postgres normalmente.
# ==============================================================================
set -e

PGDATA="${PGDATA:-/var/lib/postgresql/data}"
PRIMARY_HOST="${POSTGRES_PRIMARY_HOST:?POSTGRES_PRIMARY_HOST non impostato}"
PRIMARY_PORT="${POSTGRES_PRIMARY_PORT:-5432}"
REPL_USER="${POSTGRES_REPLICATION_USER:-replicator}"
REPL_PASS="${POSTGRES_REPLICATION_PASSWORD:?POSTGRES_REPLICATION_PASSWORD non impostato}"
REPLICA_SLOT="${POSTGRES_REPLICA_SLOT:-}"

log() { echo "[replica-init] $*"; }

# ── Se PGDATA è già inizializzato, avvia senza fare nulla ────────────────────
if [ -s "$PGDATA/PG_VERSION" ]; then
    log "PGDATA già inizializzato — avvio standby."
    exec docker-entrypoint.sh postgres \
        -c hot_standby=on \
        -c wal_level=replica
fi

# ── Prima inizializzazione: pg_basebackup dal primary ────────────────────────
log "PGDATA vuoto — inizializzo replica da ${PRIMARY_HOST}:${PRIMARY_PORT}..."

# Attendi che il primary sia raggiungibile
RETRIES=30
until PGPASSWORD="$REPL_PASS" pg_isready -h "$PRIMARY_HOST" -p "$PRIMARY_PORT" -U "$REPL_USER" -q; do
    RETRIES=$((RETRIES - 1))
    if [ "$RETRIES" -le 0 ]; then
        echo "[replica-init] ERROR: primary non raggiungibile dopo 60s. Esco."
        exit 1
    fi
    log "Attendo primary ($RETRIES tentativi rimasti)..."
    sleep 2
done

log "Primary raggiungibile. Eseguo pg_basebackup..."

mkdir -p "$PGDATA"
chown postgres:postgres "$PGDATA"

SLOT_OPT=""
if [ -n "$REPLICA_SLOT" ]; then
    SLOT_OPT="--slot=${REPLICA_SLOT} --create-slot"
fi

PGPASSWORD="$REPL_PASS" pg_basebackup \
    -h "$PRIMARY_HOST" \
    -p "$PRIMARY_PORT" \
    -U "$REPL_USER" \
    -D "$PGDATA" \
    -Fp -Xs -P -R \
    $SLOT_OPT

# ── Configura primary_conninfo in postgresql.auto.conf ──────────────────────
log "Configuro primary_conninfo..."
cat >> "$PGDATA/postgresql.auto.conf" <<EOF

# Streaming replication (generato da entrypoint.sh)
primary_conninfo = 'host=${PRIMARY_HOST} port=${PRIMARY_PORT} user=${REPL_USER} password=${REPL_PASS} application_name=replica'
hot_standby = on
EOF

log "Replica inizializzata. Avvio standby..."
exec docker-entrypoint.sh postgres \
    -c hot_standby=on \
    -c wal_level=replica
