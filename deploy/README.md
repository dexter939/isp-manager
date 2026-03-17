# ISP Manager — Deploy Multi-Server

## Architettura

```
┌─────────────────────────────────────────────────────────────────┐
│  SERVER 1 — PRINCIPALE              IP: es. 10.0.0.1            │
│  ├── App Laravel Octane (porta 8000)                            │
│  ├── Laravel Horizon (worker coda)                              │
│  ├── PostgreSQL 16 PRIMARY (porta 5432)                         │
│  ├── Redis MASTER (porta 6379)                                  │
│  ├── Redis Sentinel 1 (porta 26379)                             │
│  ├── MinIO Storage (porte 9000/9001)                            │
│  └── Mailpit dev mail (porte 1025/8025)                         │
├─────────────────────────────────────────────────────────────────┤
│  SERVER 2 — SECONDARIO              IP: es. 10.0.0.2            │
│  ├── App Laravel Octane (porta 8000)  ← stessa app, bilanciata  │
│  ├── PostgreSQL REPLICA 1 (hot standby, sola lettura)           │
│  ├── Redis REPLICA 1                                             │
│  └── Redis Sentinel 2 (porta 26380)                             │
├─────────────────────────────────────────────────────────────────┤
│  SERVER 3 — INFRASTRUTTURA HA        IP: es. 10.0.0.3           │
│  ├── PostgreSQL REPLICA 2 (hot standby, sola lettura)           │
│  ├── Redis REPLICA 2                                             │
│  ├── Redis Sentinel 3 (porta 26381)  ← voto quorum              │
│  └── Backup scheduler (dump notte ore 02:00)                    │
└─────────────────────────────────────────────────────────────────┘
```

**Failover automatico Redis**: con 3 Sentinel (1 per server), se il master Redis va down il Sentinel raggiunge il quorum (2/3) e promuove automaticamente una replica.

**Replica PostgreSQL**: hot standby in streaming. In caso di down del primary, promuovi manualmente con `pg_ctl promote` o usa Patroni per failover automatico.

---

## Ordine di installazione

> I server devono essere raggiunti via SSH. Copia prima gli script.

### 1. Prepara i server (tutti e tre)

```bash
# Su ogni server Ubuntu 24.04
sudo apt-get update && sudo apt-get upgrade -y
```

### 2. Copia il progetto sui server

```bash
# Dal tuo PC Windows — sostituisci con i tuoi IP
scp -r "c:/Users/Pasquale/fattrazione evo/ispmanager" user@10.0.0.1:/var/www/ispmanager
scp -r "c:/Users/Pasquale/fattrazione evo/ispmanager" user@10.0.0.2:/var/www/ispmanager
# Server 3 ha bisogno solo della cartella deploy/ per i Dockerfile
scp -r "c:/Users/Pasquale/fattrazione evo/ispmanager/deploy" user@10.0.0.3:/opt/ispmanager-deploy
```

### 3. Installa Server 1 (PRIMO — obbligatorio)

```bash
ssh user@10.0.0.1
cd /var/www/ispmanager/deploy
sudo bash install-server1.sh
```

Al termine lo script salva le password in `.deploy-credentials-server1.txt`.
**Annotale** — servono per i server 2 e 3.

### 4. Installa Server 2

```bash
ssh user@10.0.0.2
cd /var/www/ispmanager/deploy

# Passa le variabili dalla credenziale salvata al server 1
MAIN_IP=10.0.0.1 \
SECONDARY_IP=10.0.0.2 \
THIRD_IP=10.0.0.3 \
DB_PASSWORD=<da-server1> \
REDIS_PASSWORD=<da-server1> \
REPL_PASSWORD=<da-server1> \
MINIO_SECRET=<da-server1> \
sudo -E bash install-server2.sh
```

> Lo script chiederà la `APP_KEY` dal `.env` del server 1 — deve essere identica.

### 5. Installa Server 3

```bash
ssh user@10.0.0.3
# Copia lo script se non hai copiato tutto il progetto
scp user@10.0.0.1:/var/www/ispmanager/deploy/install-server3.sh .
scp -r user@10.0.0.1:/var/www/ispmanager/deploy/docker ./deploy-docker

MAIN_IP=10.0.0.1 \
SECONDARY_IP=10.0.0.2 \
THIRD_IP=10.0.0.3 \
DB_PASSWORD=<da-server1> \
REDIS_PASSWORD=<da-server1> \
REPL_PASSWORD=<da-server1> \
sudo -E bash install-server3.sh
```

---

## Firewall consigliato

| Server | Porta | Aperta a |
|--------|-------|----------|
| 1 | 22 | admin |
| 1 | 8000 | pubblico (o solo load balancer) |
| 1 | 5432 | Server 2 e 3 (IP privati) |
| 1 | 6379 | Server 2 e 3 (IP privati) |
| 1 | 26379 | Server 2 e 3 (IP privati) |
| 1 | 9000/9001 | Server 2, admin |
| 2 | 22 | admin |
| 2 | 8000 | pubblico (o solo load balancer) |
| 2 | 26380 | Server 1 e 3 (IP privati) |
| 3 | 22 | admin |
| 3 | 26381 | Server 1 e 2 (IP privati) |

```bash
# Esempio firewall Server 1
sudo ufw default deny incoming
sudo ufw allow 22/tcp
sudo ufw allow 8000/tcp
sudo ufw allow from 10.0.0.2 to any port 5432
sudo ufw allow from 10.0.0.3 to any port 5432
sudo ufw allow from 10.0.0.2 to any port 6379
sudo ufw allow from 10.0.0.3 to any port 6379
sudo ufw allow from 10.0.0.2 to any port 26379
sudo ufw allow from 10.0.0.3 to any port 26379
sudo ufw enable
```

---

## Comandi post-deploy

```bash
# Verifica replica PostgreSQL (da server 1)
docker compose exec -T postgres psql -U ispmanager -c "SELECT client_addr, state, sent_lsn, replay_lsn FROM pg_stat_replication;"

# Verifica stato Sentinel
docker exec ispmanager-sentinel-1 redis-cli -p 26379 sentinel masters

# Verifica replica Redis
docker exec ispmanager-redis-replica-1 redis-cli -a <PASSWORD> info replication

# Aggiornamento applicazione (tutti i server app)
cd /var/www/ispmanager
git pull
docker compose exec app composer install --no-dev --optimize-autoloader
docker compose exec app php artisan migrate --force
docker compose exec app php artisan config:cache && docker compose restart app
```

---

## File generati dagli installer

| File | Server | Scopo |
|------|--------|-------|
| `docker-compose.override.yml` | 1 | Override postgres+redis per replicazione |
| `docker-compose.server2.yml` | 2 | Compose completo server secondario |
| `/opt/ispmanager-infra/docker-compose.server3.yml` | 3 | Compose infrastruttura HA |
| `.deploy-credentials-server1.txt` | 1 | Password generate (elimina dopo uso) |
