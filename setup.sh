#!/usr/bin/env bash

# =============================================================
# IspManager - Setup Script
# Eseguire dalla directory del progetto (ispmanager/)
# =============================================================

set -euo pipefail

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

info()  { echo -e "${GREEN}[INFO]${NC} $1"; }
warn()  { echo -e "${YELLOW}[WARN]${NC} $1"; }
error() { echo -e "${RED}[ERROR]${NC} $1"; exit 1; }

info "=== IspManager Setup ==="

# --- STEP 1: Verifica prerequisiti ---
info "Verifica prerequisiti..."
command -v docker  >/dev/null 2>&1 || error "Docker non trovato. Installa Docker Desktop."
command -v composer >/dev/null 2>&1 || error "Composer non trovato."
command -v php >/dev/null 2>&1 || error "PHP non trovato (richiesto PHP 8.3+)."

PHP_VERSION=$(php -r "echo PHP_VERSION;")
info "PHP Version: $PHP_VERSION"

# --- STEP 2: .env ---
if [ ! -f ".env" ]; then
    info "Creo .env da .env.example..."
    cp .env.example .env
fi

# --- STEP 3: Dipendenze Composer ---
info "Installazione dipendenze Composer..."
composer install --no-interaction --prefer-dist --optimize-autoloader

# --- STEP 4: App key ---
info "Generazione APP_KEY..."
php artisan key:generate --ansi

# --- STEP 5: Docker ---
info "Avvio servizi Docker (PostgreSQL, Redis, MinIO)..."
docker compose up -d postgres redis minio

info "Attesa PostgreSQL ready..."
until docker compose exec postgres pg_isready -U ispmanager -d ispmanager > /dev/null 2>&1; do
    echo -n "."
    sleep 2
done
echo ""
info "PostgreSQL pronto!"

info "Attesa Redis ready..."
until docker compose exec redis redis-cli -a secret ping > /dev/null 2>&1; do
    echo -n "."
    sleep 1
done
echo ""
info "Redis pronto!"

# --- STEP 6: Migrazioni ---
info "Esecuzione migrazioni..."
php artisan migrate --force

# --- STEP 7: Publish package config ---
info "Pubblicazione config Spatie Permission..."
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --ansi

info "Pubblicazione config Spatie Activitylog..."
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations" --ansi

info "Pubblicazione config Laravel Horizon..."
php artisan vendor:publish --provider="Laravel\Horizon\HorizonServiceProvider" --ansi

info "Pubblicazione config Laravel Octane..."
php artisan vendor:publish --provider="Laravel\Octane\OctaneServiceProvider" --ansi

# --- STEP 8: Seconda migrazione (post-publish) ---
info "Migrazione tabelle Spatie..."
php artisan migrate --force

# --- STEP 9: Seeder ---
info "Seeding ruoli e permessi..."
php artisan db:seed --force

# --- STEP 10: Modules setup ---
info "Setup moduli nwidart..."
php artisan module:enable Core
php artisan module:enable Coverage
php artisan module:enable Contracts
php artisan module:enable Provisioning
php artisan module:enable Billing
php artisan module:enable Network
php artisan module:enable Monitoring
php artisan module:enable AI
php artisan module:enable Maintenance

# --- STEP 11: Storage ---
info "Link storage..."
php artisan storage:link

# --- STEP 12: Ottimizzazione ---
info "Cache configurazione..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# --- STEP 13: Avvio app ---
info "Avvio Docker completo (app + horizon)..."
docker compose up -d

echo ""
info "=== Setup completato! ==="
info "App:        http://localhost:8000"
info "Horizon:    http://localhost:8000/horizon"
info "MinIO:      http://localhost:9001"
info "Mailpit:    http://localhost:8025"
info ""
warn "Login iniziale: admin@ispmanager.local / IspManager@2024!"
warn "CAMBIA LA PASSWORD IMMEDIATAMENTE!"
