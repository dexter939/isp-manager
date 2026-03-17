#!/usr/bin/env bash
# ==============================================================================
# install-server2.sh — Server SECONDARIO
# Ruolo: App (Octane) + PostgreSQL REPLICA 1 + Redis REPLICA 1 + Sentinel 2
#
# Prerequisito: Server 1 deve essere già in esecuzione.
#
# Esecuzione:
#   sudo bash install-server2.sh
#   oppure
#   MAIN_IP=10.0.0.1 SECONDARY_IP=10.0.0.2 THIRD_IP=10.0.0.3 \
#   DB_PASSWORD=xxx REDIS_PASSWORD=xxx REPL_PASSWORD=xxx \
#   sudo bash install-server2.sh
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
  SERVER 2 — SECONDARIO
  App (Octane) · PostgreSQL Replica 1 · Redis Replica 1 · Sentinel 2
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
prompt_with_default SECONDARY_IP   "IP di questo server (Server 2)"          "$(hostname -I | awk '{print $1}')"
prompt_with_default THIRD_IP       "IP del Server 3 (terzo)"                "10.0.0.3"
prompt_with_default APP_URL        "URL pubblico dell'app (questo server)"   "http://${SECONDARY_IP}:8000"
prompt_with_default DB_PASSWORD    "Password PostgreSQL (uguale a server 1)" ""
prompt_with_default REDIS_PASSWORD "Password Redis (uguale a server 1)"      ""
prompt_with_default REPL_PASSWORD  "Password utente replicator"              ""
prompt_with_default MINIO_SECRET   "Password MinIO (uguale a server 1)"      ""
prompt_with_default GIT_REPO       "URL repository Git (o lascia vuoto)"     ""
prompt_with_default PROJECT_DIR    "Directory installazione"                 "/var/www/ispmanager"

[ -z "$DB_PASSWORD" ]    && error "DB_PASSWORD obbligatoria (deve corrispondere al server 1)"
[ -z "$REDIS_PASSWORD" ] && error "REDIS_PASSWORD obbligatoria"
[ -z "$REPL_PASSWORD" ]  && error "REPL_PASSWORD obbligatoria"

echo ""
echo -e "${YELLOW}${BOLD}Riepilogo:${NC}"
echo "  Server 1 (primary):  ${MAIN_IP}"
echo "  Server 2 (questo):   ${SECONDARY_IP}"
echo "  Server 3:            ${THIRD_IP}"
echo ""
ask "Conferma e procedi? [s/N]:"
read -r CONFIRM
[[ "$CONFIRM" =~ ^[sS]$ ]] || { warn "Installazione annullata."; exit 0; }

# ── Verifica raggiungibilità server 1 ────────────────────────────────────────
section "Verifica connessione a Server 1"

if ! nc -z -w5 "${MAIN_IP}" 5432 &>/dev/null; then
    warn "PostgreSQL su Server 1 (${MAIN_IP}:5432) non raggiungibile."
    warn "Assicurati che Server 1 sia in esecuzione e che il firewall consenta la connessione."
    ask "Continuare comunque? [s/N]:"
    read -r CONT
    [[ "$CONT" =~ ^[sS]$ ]] || exit 1
else
    info "PostgreSQL su Server 1 raggiungibile."
fi

# ── Docker ────────────────────────────────────────────────────────────────────
section "Installazione Docker"

if command -v docker &>/dev/null; then
    info "Docker già installato: $(docker --version)"
else
    info "Installazione Docker..."
    apt-get update -qq
    apt-get install -y -qq ca-certificates curl gnupg netcat-openbsd
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

apt-get install -y -qq netcat-openbsd 2>/dev/null || true

# ── Progetto ──────────────────────────────────────────────────────────────────
section "Copia codice sorgente"

if [ -n "$GIT_REPO" ]; then
    if [ -d "$PROJECT_DIR/.git" ]; then
        git -C "$PROJECT_DIR" pull
    else
        git clone "$GIT_REPO" "$PROJECT_DIR"
    fi
elif [ ! -f "$PROJECT_DIR/artisan" ]; then
    warn "Progetto non trovato in ${PROJECT_DIR}."
    warn "Copia dal server 1:"
    warn "  rsync -avz --exclude='.env' --exclude='storage/logs' user@${MAIN_IP}:${PROJECT_DIR}/ ${PROJECT_DIR}/"
    exit 1
else
    info "Progetto trovato."
fi

cd "$PROJECT_DIR"

# ── .env ──────────────────────────────────────────────────────────────────────
section "Configurazione .env"

cp .env.example .env

sed -i "s|APP_ENV=.*|APP_ENV=production|" .env
sed -i "s|APP_DEBUG=.*|APP_DEBUG=false|" .env
sed -i "s|APP_URL=.*|APP_URL=${APP_URL}|" .env
sed -i "s|DB_HOST=.*|DB_HOST=postgres-replica|" .env
sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD}|" .env
sed -i "s|REDIS_HOST=.*|REDIS_HOST=redis-replica|" .env
sed -i "s|REDIS_PASSWORD=.*|REDIS_PASSWORD=${REDIS_PASSWORD}|" .env
sed -i "s|MINIO_SECRET_KEY=.*|MINIO_SECRET_KEY=${MINIO_SECRET}|" .env
sed -i "s|MINIO_ENDPOINT=.*|MINIO_ENDPOINT=http://${MAIN_IP}:9000|g" .env 2>/dev/null || true
sed -i "s|TELESCOPE_ENABLED=.*|TELESCOPE_ENABLED=false|" .env

cat >> .env <<EOF

# ── HA / Replication ──────────────────────────────────────────────────────────
SERVER_ROLE=secondary
MAIN_SERVER_IP=${MAIN_IP}
SECONDARY_SERVER_IP=${SECONDARY_IP}
THIRD_SERVER_IP=${THIRD_IP}
POSTGRES_REPLICATION_PASSWORD=${REPL_PASSWORD}

# Letture dal replica locale, scritture al primary via DB::connection('primary')
DB_HOST=postgres-replica
DB_HOST_READ=postgres-replica

# Sentinel
REDIS_SENTINEL_HOST=${MAIN_IP}
REDIS_SENTINEL_PORT=26379
REDIS_SENTINEL_MASTER=ispmaster

# Storage su MinIO del server principale
FILESYSTEM_DISK=s3
AWS_ENDPOINT=http://${MAIN_IP}:9000
EOF

info ".env generato."

# ── docker-compose per server 2 ──────────────────────────────────────────────
section "Generazione docker-compose.server2.yml"

cat > docker-compose.server2.yml <<EOF
# Auto-generato da install-server2.sh — Server Secondario
version: '3.8'

services:

  # ── Applicazione (replica del server 1) ─────────────────────────────────
  app:
    build:
      context: .
      dockerfile: docker/app/Dockerfile
    image: ispmanager-app
    container_name: ispmanager-app
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - .:/var/www
      - ./docker/app/php.ini:/usr/local/etc/php/conf.d/custom.ini
    ports:
      - "8000:8000"
    environment:
      OCTANE_SERVER: swoole
    depends_on:
      - postgres-replica
      - redis-replica
    networks:
      - ispmanager

  # ── PostgreSQL Replica 1 ─────────────────────────────────────────────────
  postgres-replica:
    build:
      context: ./deploy/docker/postgres-replica
      dockerfile: Dockerfile
    image: ispmanager-postgres-replica
    container_name: ispmanager-postgres-replica
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
      POSTGRES_REPLICA_SLOT: replica_slot_1
    volumes:
      - postgres_replica_data:/var/lib/postgresql/data
    networks:
      - ispmanager
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U ispmanager -d ispmanager"]
      interval: 15s
      timeout: 10s
      retries: 10

  # ── Redis Replica 1 ──────────────────────────────────────────────────────
  redis-replica:
    image: redis:7.2-alpine
    container_name: ispmanager-redis-replica-1
    restart: unless-stopped
    command: >
      redis-server
        --requirepass ${REDIS_PASSWORD}
        --masterauth ${REDIS_PASSWORD}
        --replicaof ${MAIN_IP} 6379
        --replica-read-only yes
    volumes:
      - redis_replica_data:/data
    networks:
      - ispmanager
    healthcheck:
      test: ["CMD", "redis-cli", "-a", "${REDIS_PASSWORD}", "ping"]
      interval: 10s
      timeout: 5s
      retries: 5

  # ── Redis Sentinel 2 (su questo server) ─────────────────────────────────
  redis-sentinel-2:
    image: redis:7.2-alpine
    container_name: ispmanager-sentinel-2
    restart: unless-stopped
    ports:
      - "26380:26379"
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
      - ispmanager

networks:
  ispmanager:
    driver: bridge

volumes:
  postgres_replica_data:
    driver: local
  redis_replica_data:
    driver: local
EOF

info "docker-compose.server2.yml generato."

# ── Crea symlink .env per docker-compose ─────────────────────────────────────
ln -sf .env .env.server2 2>/dev/null || true

# ── Build e avvio ─────────────────────────────────────────────────────────────
section "Build immagini Docker"

docker compose -f docker-compose.server2.yml build
info "Build completata."

section "Avvio servizi"
docker compose -f docker-compose.server2.yml up -d

info "Attesa inizializzazione replica PostgreSQL (può richiedere 2-3 minuti)..."
ATTEMPTS=0
until docker compose -f docker-compose.server2.yml exec -T postgres-replica \
      pg_isready -U ispmanager -d ispmanager &>/dev/null; do
    ATTEMPTS=$((ATTEMPTS + 1))
    if [ "$ATTEMPTS" -gt 60 ]; then
        warn "Replica PostgreSQL non pronta dopo 2 minuti."
        warn "Verifica: docker compose -f docker-compose.server2.yml logs postgres-replica"
        break
    fi
    echo -n "."; sleep 3
done; echo ""
info "Replica PostgreSQL pronta."

# ── Setup Laravel (usa key del server 1) ─────────────────────────────────────
section "Configurazione Laravel (server 2)"

# Non rigeneriamo la APP_KEY — deve essere uguale al server 1
if ! grep -q "^APP_KEY=base64:" .env; then
    warn "APP_KEY non trovata in .env."
    warn "Copia la APP_KEY dal server 1 (.env del server 1) e inseriscila qui:"
    ask "APP_KEY (base64:...):"
    read -r APP_KEY_INPUT
    if [ -n "$APP_KEY_INPUT" ]; then
        sed -i "s|APP_KEY=.*|APP_KEY=${APP_KEY_INPUT}|" .env
    else
        warn "APP_KEY non impostata. L'app non funzionerà correttamente."
    fi
fi

docker compose -f docker-compose.server2.yml run --rm app \
    composer install --no-dev --optimize-autoloader --no-interaction

docker compose -f docker-compose.server2.yml run --rm app php artisan config:cache
docker compose -f docker-compose.server2.yml run --rm app php artisan route:cache
docker compose -f docker-compose.server2.yml run --rm app php artisan view:cache
docker compose -f docker-compose.server2.yml run --rm app php artisan storage:link

# ── Riavvio finale ─────────────────────────────────────────────────────────────
docker compose -f docker-compose.server2.yml up -d
docker compose -f docker-compose.server2.yml ps

# ── Riepilogo ─────────────────────────────────────────────────────────────────
echo ""
echo -e "${GREEN}${BOLD}╔══════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}${BOLD}║   SERVER 2 INSTALLATO CON SUCCESSO!                  ║${NC}"
echo -e "${GREEN}${BOLD}╚══════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "  ${CYAN}App:${NC}             ${APP_URL}"
echo -e "  ${CYAN}PostgreSQL:${NC}      replica in sola lettura da ${MAIN_IP}"
echo -e "  ${CYAN}Redis:${NC}           replica da ${MAIN_IP}"
echo -e "  ${CYAN}Sentinel 2:${NC}      ${SECONDARY_IP}:26380"
echo ""
echo -e "  ${YELLOW}Comandi utili:${NC}"
echo -e "  docker compose -f docker-compose.server2.yml logs -f"
echo -e "  docker compose -f docker-compose.server2.yml restart app"
echo ""
warn "Aggiungi il Sentinel 2 al server 1:"
warn "  Aggiungi ${SECONDARY_IP}:26380 a REDIS_SENTINELS nel .env del server 1"
