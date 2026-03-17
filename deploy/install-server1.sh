#!/usr/bin/env bash
# ==============================================================================
# install-server1.sh — Server PRINCIPALE
# Ruolo: App (Octane) + Horizon + PostgreSQL PRIMARY + Redis MASTER + Sentinel 1
#        + MinIO + Mailpit
#
# Esecuzione:
#   sudo bash install-server1.sh
#   oppure
#   MAIN_IP=10.0.0.1 SECONDARY_IP=10.0.0.2 THIRD_IP=10.0.0.3 \
#   DB_PASSWORD=xxx REDIS_PASSWORD=xxx REPL_PASSWORD=xxx \
#   sudo bash install-server1.sh
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
  SERVER 1 — PRINCIPALE
  App (Octane) · PostgreSQL Primary · Redis Master · Sentinel · MinIO
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

prompt_with_default MAIN_IP      "IP privato di questo server (Server 1)"    "$(hostname -I | awk '{print $1}')"
prompt_with_default SECONDARY_IP "IP privato del Server 2 (secondario)"     "10.0.0.2"
prompt_with_default THIRD_IP     "IP privato del Server 3 (terzo)"          "10.0.0.3"
prompt_with_default APP_URL      "URL pubblico dell'app"                     "http://${MAIN_IP}:8000"
prompt_with_default DB_PASSWORD  "Password PostgreSQL"                       "$(openssl rand -base64 16 | tr -d '/+=')"
prompt_with_default REDIS_PASSWORD "Password Redis"                          "$(openssl rand -base64 16 | tr -d '/+=')"
prompt_with_default REPL_PASSWORD  "Password utente 'replicator' (PostgreSQL HA)" "$(openssl rand -base64 16 | tr -d '/+=')"
prompt_with_default MINIO_SECRET   "Password MinIO (min. 12 char)"           "$(openssl rand -base64 12 | tr -d '/+=')"
prompt_with_default GIT_REPO       "URL repository Git (lascia vuoto se copi manualmente)" ""
prompt_with_default PROJECT_DIR    "Directory installazione"                 "/var/www/ispmanager"

echo ""
echo -e "${YELLOW}${BOLD}Riepilogo configurazione:${NC}"
echo "  Server 1 (principale):  ${MAIN_IP}"
echo "  Server 2 (secondario):  ${SECONDARY_IP}"
echo "  Server 3 (terzo):       ${THIRD_IP}"
echo "  App URL:                ${APP_URL}"
echo "  Directory:              ${PROJECT_DIR}"
echo ""
ask "Conferma e procedi? [s/N]:"
read -r CONFIRM
[[ "$CONFIRM" =~ ^[sS]$ ]] || { warn "Installazione annullata."; exit 0; }

# ── Docker ────────────────────────────────────────────────────────────────────
section "Installazione Docker"

if command -v docker &>/dev/null; then
    info "Docker già installato: $(docker --version)"
else
    info "Installazione Docker..."
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
    info "Docker installato: $(docker --version)"
fi

# ── Progetto ──────────────────────────────────────────────────────────────────
section "Copia codice sorgente"

if [ -n "$GIT_REPO" ]; then
    if [ -d "$PROJECT_DIR/.git" ]; then
        info "Repository già presente — pull."
        git -C "$PROJECT_DIR" pull
    else
        info "Clone da ${GIT_REPO}..."
        git clone "$GIT_REPO" "$PROJECT_DIR"
    fi
elif [ ! -f "$PROJECT_DIR/artisan" ]; then
    warn "Repository non trovato in ${PROJECT_DIR}."
    warn "Copia manualmente il progetto in ${PROJECT_DIR} e riesegui lo script."
    warn "  scp -r ./ispmanager user@${MAIN_IP}:${PROJECT_DIR}"
    exit 1
else
    info "Progetto trovato in ${PROJECT_DIR}."
fi

cd "$PROJECT_DIR"

# Assicura che i file deploy siano presenti
DEPLOY_SRC="$(dirname "$(realpath "$0")")"
if [ "$DEPLOY_SRC" != "$PROJECT_DIR/deploy" ]; then
    cp -r "$DEPLOY_SRC/docker" "$PROJECT_DIR/deploy/" 2>/dev/null || true
fi

# ── .env ──────────────────────────────────────────────────────────────────────
section "Configurazione .env"

cp .env.example .env

sed -i "s|APP_ENV=.*|APP_ENV=production|" .env
sed -i "s|APP_DEBUG=.*|APP_DEBUG=false|" .env
sed -i "s|APP_URL=.*|APP_URL=${APP_URL}|" .env
sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD}|" .env
sed -i "s|REDIS_PASSWORD=.*|REDIS_PASSWORD=${REDIS_PASSWORD}|" .env
sed -i "s|MINIO_SECRET_KEY=.*|MINIO_SECRET_KEY=${MINIO_SECRET}|" .env
sed -i "s|TELESCOPE_ENABLED=.*|TELESCOPE_ENABLED=false|" .env

# Aggiungi variabili HA che non sono nell'esempio
cat >> .env <<EOF

# ── HA / Replication ──────────────────────────────────────────────────────────
SERVER_ROLE=primary
MAIN_SERVER_IP=${MAIN_IP}
SECONDARY_SERVER_IP=${SECONDARY_IP}
THIRD_SERVER_IP=${THIRD_IP}
POSTGRES_REPLICATION_PASSWORD=${REPL_PASSWORD}

# Redis Sentinel (quando server2/3 sono attivi)
REDIS_SENTINEL_HOST=${MAIN_IP}
REDIS_SENTINEL_PORT=26379
REDIS_SENTINEL_MASTER=ispmaster
EOF

info ".env generato."

# ── docker-compose override per server 1 ─────────────────────────────────────
section "Generazione docker-compose.override.yml"

cat > docker-compose.override.yml <<EOF
# Auto-generato da install-server1.sh — Server Principale
version: '3.8'

services:
  postgres:
    volumes:
      - ./deploy/docker/postgres-primary/init-replication.sh:/docker-entrypoint-initdb.d/99-init-replication.sh
      - ./docker/postgres/init.sql:/docker-entrypoint-initdb.d/01-init.sql
    environment:
      POSTGRES_REPLICATION_PASSWORD: "${REPL_PASSWORD}"
    command: >
      postgres
        -c wal_level=replica
        -c max_wal_senders=10
        -c max_replication_slots=5
        -c wal_keep_size=1GB
        -c hot_standby=on
        -c synchronous_commit=local
    ports:
      - "5432:5432"

  redis:
    command: >
      redis-server
        --requirepass ${REDIS_PASSWORD}
        --maxmemory 512mb
        --maxmemory-policy allkeys-lru
        --appendonly yes
        --save 900 1
        --save 300 10
    ports:
      - "6379:6379"

  # Sentinel 1 (su questo server)
  redis-sentinel-1:
    image: redis:7.2-alpine
    container_name: ispmanager-sentinel-1
    restart: unless-stopped
    ports:
      - "26379:26379"
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

  minio:
    ports:
      - "9000:9000"
      - "9001:9001"

  app:
    environment:
      OCTANE_SERVER: swoole
EOF

info "docker-compose.override.yml generato."

# ── Build e avvio ─────────────────────────────────────────────────────────────
section "Build immagine Docker"
docker compose build app
info "Build completata."

section "Avvio servizi infrastruttura"
docker compose up -d postgres redis minio mailpit

info "Attesa PostgreSQL..."
until docker compose exec -T postgres pg_isready -U ispmanager -d ispmanager &>/dev/null; do
    echo -n "."; sleep 2
done; echo ""
info "PostgreSQL pronto."

info "Attesa Redis..."
until docker compose exec -T redis redis-cli -a "${REDIS_PASSWORD}" ping &>/dev/null; do
    echo -n "."; sleep 1
done; echo ""
info "Redis pronto."

# ── Setup applicazione ────────────────────────────────────────────────────────
section "Setup applicazione Laravel"

docker compose run --rm app composer install --no-dev --optimize-autoloader --no-interaction
docker compose run --rm app php artisan key:generate --force --ansi
docker compose run --rm app php artisan migrate --force
docker compose run --rm app php artisan db:seed --force
docker compose run --rm app php artisan storage:link
docker compose run --rm app php artisan config:cache
docker compose run --rm app php artisan route:cache
docker compose run --rm app php artisan view:cache
docker compose run --rm app php artisan event:cache

info "Setup Laravel completato."

# ── Avvio completo ────────────────────────────────────────────────────────────
section "Avvio completo"
docker compose up -d
docker compose ps

# ── Salva credenziali ─────────────────────────────────────────────────────────
section "Salvataggio credenziali"

CRED_FILE="${PROJECT_DIR}/.deploy-credentials-server1.txt"
cat > "$CRED_FILE" <<EOF
# ISP Manager — Credenziali Server 1 (PRINCIPALE)
# Generato: $(date)
# MANTIENI QUESTO FILE SICURO E RIMUOVILO DOPO AVER SALVATO LE CREDENZIALI

MAIN_IP=${MAIN_IP}
SECONDARY_IP=${SECONDARY_IP}
THIRD_IP=${THIRD_IP}

DB_PASSWORD=${DB_PASSWORD}
REDIS_PASSWORD=${REDIS_PASSWORD}
REPL_PASSWORD=${REPL_PASSWORD}
MINIO_SECRET=${MINIO_SECRET}

APP_URL=${APP_URL}
HORIZON_URL=${APP_URL}/horizon
MINIO_CONSOLE=http://${MAIN_IP}:9001
MAILPIT=http://${MAIN_IP}:8025

# ── Credenziali per install-server2.sh e install-server3.sh ──────────────────
# Esegui sul server 2:
#   MAIN_IP=${MAIN_IP} SECONDARY_IP=${SECONDARY_IP} THIRD_IP=${THIRD_IP} \
#   DB_PASSWORD=${DB_PASSWORD} REDIS_PASSWORD=${REDIS_PASSWORD} REPL_PASSWORD=${REPL_PASSWORD} \
#   sudo bash install-server2.sh
#
# Esegui sul server 3:
#   MAIN_IP=${MAIN_IP} SECONDARY_IP=${SECONDARY_IP} THIRD_IP=${THIRD_IP} \
#   DB_PASSWORD=${DB_PASSWORD} REDIS_PASSWORD=${REDIS_PASSWORD} REPL_PASSWORD=${REPL_PASSWORD} \
#   sudo bash install-server3.sh
EOF

chmod 600 "$CRED_FILE"
info "Credenziali salvate in: ${CRED_FILE}"

# ── Riepilogo finale ──────────────────────────────────────────────────────────
echo ""
echo -e "${GREEN}${BOLD}╔══════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}${BOLD}║   SERVER 1 INSTALLATO CON SUCCESSO!                  ║${NC}"
echo -e "${GREEN}${BOLD}╚══════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "  ${CYAN}App:${NC}           ${APP_URL}"
echo -e "  ${CYAN}Horizon:${NC}       ${APP_URL}/horizon"
echo -e "  ${CYAN}MinIO Console:${NC} http://${MAIN_IP}:9001"
echo -e "  ${CYAN}Mailpit:${NC}       http://${MAIN_IP}:8025"
echo ""
echo -e "  ${YELLOW}Prossimi passi:${NC}"
echo -e "  1. Leggi ${CRED_FILE} per le password"
echo -e "  2. Installa server 2: sudo bash install-server2.sh"
echo -e "  3. Installa server 3: sudo bash install-server3.sh"
echo -e "  4. Crea il primo utente admin:"
echo -e "     ${BLUE}docker compose exec app php artisan tinker${NC}"
echo ""
warn "Configura il firewall: apri solo 8000 (app), 9000/9001 (MinIO), 22 (SSH)"
warn "PostgreSQL (5432) e Redis (6379) devono essere accessibili da server 2/3"
