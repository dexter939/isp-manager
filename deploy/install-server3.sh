#!/usr/bin/env bash
# ==============================================================================
# install-server3.sh — Server TERZO (Infrastruttura HA)
# Ruolo: PostgreSQL REPLICA 2 + Redis REPLICA 2 + Sentinel 3 + Backup scheduler
#
# Nessuna applicazione su questo server — solo infrastruttura dati.
# Fornisce: quorum Sentinel, seconda replica DB, failover automatico Redis.
#
# Prerequisito: Server 1 deve essere già in esecuzione.
#
# Esecuzione:
#   sudo bash install-server3.sh
#   oppure
#   MAIN_IP=10.0.0.1 SECONDARY_IP=10.0.0.2 THIRD_IP=10.0.0.3 \
#   DB_PASSWORD=xxx REDIS_PASSWORD=xxx REPL_PASSWORD=xxx \
#   sudo bash install-server3.sh
# ==============================================================================
set -euo pipefail

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
BLUE='\033[0;34m'; CYAN='\033[0;36m'; BOLD='\033[1m'; NC='\033[0m'

info()    { echo -e "${GREEN}[✓]${NC} $1"; }
warn()    { echo -e "${YELLOW}[!]${NC} $1"; }
error()   { echo -e "${RED}[✗]${NC} $1"; exit 1; }
section() { echo -e "\n${BLUE}${BOLD}━━━ $1 ━━━${NC}"; }
ask()     { echo -e "${CYAN}[?]${NC} $1"; }

# ── Banner ────────────────────────────────────────────────────────────────────
echo -e "${BLUE}${BOLD}"
cat <<'EOF'
  _____ ____  ____  __  __
 |_   _/ ___||  _ \|  \/  | __ _ _ __   __ _  __ _  ___ _ __
   | | \___ \| |_) | |\/| |/ _` | '_ \ / _` |/ _` |/ _ \ '__|
   | |  ___) |  __/| |  | | (_| | | | | (_| | (_| |  __/ |
   |_||____/|_|   |_|  |_|\__,_|_| |_|\__,_|\__, |\___|_|
                                              |___/
  SERVER 3 — INFRASTRUTTURA HA
  PostgreSQL Replica 2 · Redis Replica 2 · Sentinel 3 · Backup Scheduler
EOF
echo -e "${NC}"

[ "$(id -u)" -ne 0 ] && error "Esegui come root: sudo bash $0"

# ── Raccolta configurazione ────────────────────────────────────────────────────
section "Configurazione"

prompt_with_default() {
    local var="$1" prompt="$2" default="$3"
    if [ -z "${!var:-}" ]; then
        ask "$prompt [default: $default]:"
        read -r INPUT
        declare -g "$var=${INPUT:-$default}"
    fi
}

prompt_with_default MAIN_IP        "IP del Server 1 (principale)"           "10.0.0.1"
prompt_with_default SECONDARY_IP   "IP del Server 2 (secondario)"           "10.0.0.2"
prompt_with_default THIRD_IP       "IP di questo server (Server 3)"         "$(hostname -I | awk '{print $1}')"
prompt_with_default DB_PASSWORD    "Password PostgreSQL (uguale a server 1)" ""
prompt_with_default REDIS_PASSWORD "Password Redis (uguale a server 1)"      ""
prompt_with_default REPL_PASSWORD  "Password utente replicator"              ""
prompt_with_default BACKUP_DIR     "Directory backup PostgreSQL"             "/opt/ispmanager-backups"
prompt_with_default GIT_REPO       "URL repository Git (per i Dockerfile)"   "https://github.com/dexter939/isp-manager.git"
prompt_with_default PROJECT_DIR    "Directory progetto (per build immagini)"  "/var/www/ispmanager"

[ -z "$DB_PASSWORD" ]    && error "DB_PASSWORD obbligatoria"
[ -z "$REDIS_PASSWORD" ] && error "REDIS_PASSWORD obbligatoria"
[ -z "$REPL_PASSWORD" ]  && error "REPL_PASSWORD obbligatoria"

echo ""
echo -e "${YELLOW}${BOLD}Riepilogo:${NC}"
echo "  Server 1 (primary):  ${MAIN_IP}"
echo "  Server 2:            ${SECONDARY_IP}"
echo "  Server 3 (questo):   ${THIRD_IP}"
echo "  Backup dir:          ${BACKUP_DIR}"
echo ""
ask "Conferma e procedi? [s/N]:"
read -r CONFIRM
[[ "$CONFIRM" =~ ^[sS]$ ]] || { warn "Installazione annullata."; exit 0; }

# ── Verifica connessione a Server 1 ──────────────────────────────────────────
section "Verifica connessione a Server 1"

apt-get install -y -qq netcat-openbsd 2>/dev/null || true

if ! nc -z -w5 "${MAIN_IP}" 5432 &>/dev/null; then
    warn "PostgreSQL su Server 1 (${MAIN_IP}:5432) non raggiungibile."
    ask "Continuare comunque? [s/N]:"
    read -r CONT
    [[ "$CONT" =~ ^[sS]$ ]] || exit 1
else
    info "Connessione a Server 1 OK."
fi

# ── Docker ────────────────────────────────────────────────────────────────────
section "Installazione Docker"

if command -v docker &>/dev/null; then
    info "Docker già installato: $(docker --version)"
else
    apt-get update -qq
    apt-get install -y -qq ca-certificates curl gnupg
    install -m 0755 -d /etc/apt/keyrings
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg | \
        gpg --dearmor -o /etc/apt/keyrings/docker.gpg
    chmod a+r /etc/apt/keyrings/docker.gpg
    echo \
        "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] \
        https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
        tee /etc/apt/sources.list.d/docker.list
    apt-get update -qq
    apt-get install -y -qq docker-ce docker-ce-cli containerd.io docker-compose-plugin
    systemctl enable --now docker
    info "Docker installato."
fi

# ── Copia Dockerfile replica (necessario per build) ──────────────────────────
section "Preparazione Dockerfile replica"

WORK_DIR="/opt/ispmanager-infra"
mkdir -p "${WORK_DIR}/docker/postgres-replica"
mkdir -p "${BACKUP_DIR}"

if [ -n "$GIT_REPO" ]; then
    if [ -d "$PROJECT_DIR/.git" ]; then
        git -C "$PROJECT_DIR" pull 2>/dev/null || true
    else
        git clone "$GIT_REPO" "$PROJECT_DIR" --depth=1
    fi
fi

if [ -f "${PROJECT_DIR}/deploy/docker/postgres-replica/Dockerfile" ]; then
    cp "${PROJECT_DIR}/deploy/docker/postgres-replica/"* "${WORK_DIR}/docker/postgres-replica/"
    info "Dockerfile replica copiato da ${PROJECT_DIR}."
else
    # Genera il Dockerfile inline se il progetto non è disponibile
    warn "Progetto non trovato — genero Dockerfile inline."
    cat > "${WORK_DIR}/docker/postgres-replica/Dockerfile" <<'DOCKERFILE'
FROM postgis/postgis:16-3.4
COPY entrypoint.sh /usr/local/bin/replica-entrypoint.sh
RUN chmod +x /usr/local/bin/replica-entrypoint.sh
ENTRYPOINT ["/usr/local/bin/replica-entrypoint.sh"]
DOCKERFILE

    cat > "${WORK_DIR}/docker/postgres-replica/entrypoint.sh" <<'ENTRYPOINT_SCRIPT'
#!/usr/bin/env bash
set -e
PGDATA="${PGDATA:-/var/lib/postgresql/data}"
PRIMARY_HOST="${POSTGRES_PRIMARY_HOST:?}"
PRIMARY_PORT="${POSTGRES_PRIMARY_PORT:-5432}"
REPL_USER="${POSTGRES_REPLICATION_USER:-replicator}"
REPL_PASS="${POSTGRES_REPLICATION_PASSWORD:?}"
REPLICA_SLOT="${POSTGRES_REPLICA_SLOT:-}"

if [ -s "$PGDATA/PG_VERSION" ]; then
    exec docker-entrypoint.sh postgres -c hot_standby=on -c wal_level=replica
fi

echo "[replica-init] Primo avvio — pg_basebackup da ${PRIMARY_HOST}:${PRIMARY_PORT}..."
RETRIES=30
until PGPASSWORD="$REPL_PASS" pg_isready -h "$PRIMARY_HOST" -p "$PRIMARY_PORT" -U "$REPL_USER" -q; do
    RETRIES=$((RETRIES - 1))
    [ "$RETRIES" -le 0 ] && echo "ERROR: primary non raggiungibile" && exit 1
    echo -n "."; sleep 2
done

mkdir -p "$PGDATA"
chown postgres:postgres "$PGDATA"

SLOT_OPT=""
[ -n "$REPLICA_SLOT" ] && SLOT_OPT="--slot=${REPLICA_SLOT} --create-slot"

PGPASSWORD="$REPL_PASS" pg_basebackup \
    -h "$PRIMARY_HOST" -p "$PRIMARY_PORT" -U "$REPL_USER" \
    -D "$PGDATA" -Fp -Xs -P -R $SLOT_OPT

cat >> "$PGDATA/postgresql.auto.conf" <<EOF
primary_conninfo = 'host=${PRIMARY_HOST} port=${PRIMARY_PORT} user=${REPL_USER} password=${REPL_PASS} application_name=replica2'
hot_standby = on
EOF

exec docker-entrypoint.sh postgres -c hot_standby=on -c wal_level=replica
ENTRYPOINT_SCRIPT
    chmod +x "${WORK_DIR}/docker/postgres-replica/entrypoint.sh"
fi

# ── docker-compose per server 3 ──────────────────────────────────────────────
section "Generazione docker-compose.server3.yml"

cat > "${WORK_DIR}/docker-compose.server3.yml" <<EOF
# Auto-generato da install-server3.sh — Server Terzo (Infrastruttura HA)
version: '3.8'

services:

  # ── PostgreSQL Replica 2 ─────────────────────────────────────────────────
  postgres-replica-2:
    build:
      context: ./docker/postgres-replica
      dockerfile: Dockerfile
    image: ispmanager-postgres-replica
    container_name: ispmanager-postgres-replica-2
    restart: unless-stopped
    environment:
      PGDATA: /var/lib/postgresql/data
      POSTGRES_USER: ispmanager
      POSTGRES_PASSWORD: ${DB_PASSWORD}
      POSTGRES_DB: ispmanager
      POSTGRES_PRIMARY_HOST: ${MAIN_IP}
      POSTGRES_PRIMARY_PORT: 5432
      POSTGRES_REPLICATION_USER: replicator
      POSTGRES_REPLICATION_PASSWORD: ${REPL_PASSWORD}
      POSTGRES_REPLICA_SLOT: replica_slot_2
    volumes:
      - postgres_replica2_data:/var/lib/postgresql/data
    networks:
      - ispmanager-infra
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U ispmanager -d ispmanager"]
      interval: 15s
      timeout: 10s
      retries: 10

  # ── Redis Replica 2 ──────────────────────────────────────────────────────
  redis-replica-2:
    image: redis:7.2-alpine
    container_name: ispmanager-redis-replica-2
    restart: unless-stopped
    command: >
      redis-server
        --requirepass ${REDIS_PASSWORD}
        --masterauth ${REDIS_PASSWORD}
        --replicaof ${MAIN_IP} 6379
        --replica-read-only yes
        --appendonly yes
    volumes:
      - redis_replica2_data:/data
    networks:
      - ispmanager-infra
    healthcheck:
      test: ["CMD", "redis-cli", "-a", "${REDIS_PASSWORD}", "ping"]
      interval: 10s
      timeout: 5s
      retries: 5

  # ── Redis Sentinel 3 (quorum — voto decisivo) ────────────────────────────
  redis-sentinel-3:
    image: redis:7.2-alpine
    container_name: ispmanager-sentinel-3
    restart: unless-stopped
    ports:
      - "26381:26379"
    command: >
      sh -c "
        echo 'port 26379' > /tmp/sentinel.conf &&
        echo 'daemonize no' >> /tmp/sentinel.conf &&
        echo 'sentinel monitor ispmaster ${MAIN_IP} 6379 2' >> /tmp/sentinel.conf &&
        echo 'sentinel auth-pass ispmaster ${REDIS_PASSWORD}' >> /tmp/sentinel.conf &&
        echo 'sentinel down-after-milliseconds ispmaster 5000' >> /tmp/sentinel.conf &&
        echo 'sentinel failover-timeout ispmaster 60000' >> /tmp/sentinel.conf &&
        echo 'sentinel parallel-syncs ispmaster 1' >> /tmp/sentinel.conf &&
        redis-sentinel /tmp/sentinel.conf
      "
    networks:
      - ispmanager-infra

  # ── Backup scheduler ─────────────────────────────────────────────────────
  pg-backup:
    image: postgis/postgis:16-3.4
    container_name: ispmanager-pg-backup
    restart: unless-stopped
    environment:
      PGPASSWORD: ${DB_PASSWORD}
      PRIMARY_HOST: ${MAIN_IP}
      BACKUP_DIR: /backups
    volumes:
      - ${BACKUP_DIR}:/backups
    entrypoint: >
      sh -c "
        echo '# ISP Manager backup cron' > /etc/cron.d/pg-backup &&
        echo '0 2 * * * postgres pg_dump -h \$\${PRIMARY_HOST} -U ispmanager -d ispmanager -Fc -f /backups/ispmanager_\$\$(date +%Y%m%d_%H%M).dump && find /backups -name \"*.dump\" -mtime +7 -delete' >> /etc/cron.d/pg-backup &&
        crontab /etc/cron.d/pg-backup &&
        crond -f
      "
    networks:
      - ispmanager-infra

networks:
  ispmanager-infra:
    driver: bridge

volumes:
  postgres_replica2_data:
    driver: local
  redis_replica2_data:
    driver: local
EOF

info "docker-compose.server3.yml generato in ${WORK_DIR}."

# ── Build e avvio ─────────────────────────────────────────────────────────────
section "Build immagine replica PostgreSQL"
cd "${WORK_DIR}"
docker compose -f docker-compose.server3.yml build postgres-replica-2
info "Build completata."

section "Avvio servizi infrastruttura"
docker compose -f docker-compose.server3.yml up -d

info "Attesa inizializzazione replica PostgreSQL (può richiedere 3-5 minuti su DB grandi)..."
ATTEMPTS=0
until docker compose -f docker-compose.server3.yml exec -T postgres-replica-2 \
      pg_isready -U ispmanager -d ispmanager &>/dev/null; do
    ATTEMPTS=$((ATTEMPTS + 1))
    if [ "$ATTEMPTS" -gt 100 ]; then
        warn "Replica non pronta dopo 5 minuti."
        warn "Verifica: docker compose -f docker-compose.server3.yml logs postgres-replica-2"
        break
    fi
    echo -n "."; sleep 3
done; echo ""

docker compose -f docker-compose.server3.yml ps

# ── Configura backup cron sull'host ──────────────────────────────────────────
section "Configurazione backup automatico"

mkdir -p "${BACKUP_DIR}"
cat > /etc/cron.d/ispmanager-backup <<EOF
# ISP Manager — Backup PostgreSQL su Server 3
# Eseguito alle 02:00 ogni notte
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin

0 2 * * * root PGPASSWORD="${DB_PASSWORD}" pg_dump \
    -h ${MAIN_IP} -p 5432 -U ispmanager -d ispmanager \
    -Fc -f ${BACKUP_DIR}/ispmanager_\$(date +\%Y\%m\%d_\%H\%M).dump \
    && find ${BACKUP_DIR} -name "*.dump" -mtime +7 -delete \
    && echo "Backup completato: \$(date)" >> /var/log/ispmanager-backup.log
EOF
chmod 644 /etc/cron.d/ispmanager-backup
info "Backup automatico configurato (ogni notte alle 02:00)."
info "Dump in: ${BACKUP_DIR}"

# ── Riepilogo ─────────────────────────────────────────────────────────────────
echo ""
echo -e "${GREEN}${BOLD}╔══════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}${BOLD}║   SERVER 3 INSTALLATO CON SUCCESSO!                  ║${NC}"
echo -e "${GREEN}${BOLD}╚══════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "  ${CYAN}PostgreSQL Replica 2:${NC}  hot standby da ${MAIN_IP}"
echo -e "  ${CYAN}Redis Replica 2:${NC}        replica da ${MAIN_IP}"
echo -e "  ${CYAN}Sentinel 3:${NC}             ${THIRD_IP}:26381 (voto quorum)"
echo -e "  ${CYAN}Backup:${NC}                 ${BACKUP_DIR} (ogni notte ore 02:00)"
echo ""
echo -e "  ${YELLOW}Architettura HA completa:${NC}"
echo -e "  ┌─ Server 1 (${MAIN_IP}) ─ App + PG Primary + Redis Master + Sentinel 1"
echo -e "  ├─ Server 2 (${SECONDARY_IP}) ─ App + PG Replica 1 + Redis Replica + Sentinel 2"
echo -e "  └─ Server 3 (${THIRD_IP}) ─ PG Replica 2 + Redis Replica 2 + Sentinel 3 + Backup"
echo ""
echo -e "  ${YELLOW}Comandi utili:${NC}"
echo -e "  cd ${WORK_DIR}"
echo -e "  docker compose -f docker-compose.server3.yml logs -f"
echo -e "  docker compose -f docker-compose.server3.yml ps"
echo ""
warn "Firewall: apri solo la porta 26381 (Sentinel) su questo server."
warn "PostgreSQL e Redis su questo server NON devono essere esposti all'esterno."
