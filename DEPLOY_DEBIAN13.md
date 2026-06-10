# Play2TV Deployment Handleiding — Debian 13 (Trixie) met Nginx

Deze handleiding beschrijft stap voor stap hoe je de Play2TV backend API uitrolt op een verse Debian 13 (Trixie) server met **Nginx**, **PHP-FPM 8.4**, **MySQL 8.4** / **MariaDB 10.11+** en **Redis 7.x**.

> **Belangrijk:** Deze handleiding gaat uit van een schone Debian 13 installatie. Pas commando's aan als je server afwijkt.

---

## 0. Server Basis Setup

### 0.1 Systeem updaten en basispakketten installeren

```bash
apt update && apt upgrade -y
apt install -y curl wget gnupg ca-certificates lsb-release unzip git acl sudo
```

### 0.2 Tijdzone instellen

```bash
timedatectl set-timezone Europe/Amsterdam
```

### 0.3 Firewall (UFW) — als je die gebruikt

```bash
apt install -y ufw
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw enable
```

### 0.4 Fail2ban (aanbevolen voor productie)

```bash
apt install -y fail2ban
systemctl enable fail2ban --now
```

---

## 1. Nginx Installeren

```bash
apt install -y nginx
systemctl enable nginx --now
```

Na installatie, controleer:

```bash
nginx -v
# nginx version: nginx/1.26.x (of hoger op Debian 13)
curl -I http://localhost
# HTTP/1.1 200 OK
```

---

## 2. PHP 8.4 + Extensies Installeren

Debian 13 (Trixie) wordt geleverd met PHP 8.4. Deze applicatie werkt met PHP 8.2+ dus PHP 8.4 is volledig compatibel.

```bash
apt install -y \
  php-fpm \
  php-cli \
  php-common \
  php-mysql \
  php-mbstring \
  php-intl \
  php-curl \
  php-json \
  php-xml \
  php-zip \
  php-gd \
  php-bcmath \
  php-opcache \
  php-readline
```

Controleer PHP versie:

```bash
php -v
# PHP 8.4.x
php -m | grep -E 'intl|mbstring|json|mysqlnd|curl|redis'
```

> **Let op:** De applicatie vereist `intl`, `mbstring`, `json`, `mysqlnd` en `curl` als minimale extensies.

---

## 3. MySQL / MariaDB Installeren

Debian 13 heeft standaard MariaDB in de repositories. Je kunt ook MySQL 8.4 installeren.

### 3.1 MariaDB (aanbevolen, eenvoudiger)

```bash
apt install -y mariadb-server mariadb-client
```

### 3.2 MySQL 8.4 (alternatief)

```bash
# Alleen als je liever MySQL gebruikt:
apt install -y default-mysql-server default-mysql-client
```

### 3.3 Database securen en configureren

```bash
mysql_secure_installation
```

Volg de prompts:
- Enter current password for root: (leeg, druk Enter)
- Switch to unix_socket authentication? **Y**
- Change the root password? **N** (tenzij je een wachtwoord wilt instellen)
- Remove anonymous users? **Y**
- Disallow root login remotely? **Y**
- Remove test database and access to it? **Y**
- Reload privilege tables now? **Y**

### 3.4 Database en gebruiker aanmaken

```bash
mysql -u root
```

Voer in de MySQL shell uit:

```sql
CREATE DATABASE play2tv CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'play2tv_user'@'localhost' IDENTIFIED BY 'STERK_WACHTWOORD_HIER';
GRANT ALL PRIVILEGES ON play2tv.* TO 'play2tv_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

> ⚠️ **Vervang `STERK_WACHTWOORD_HIER` door een echt wachtwoord. Gebruik minimaal 32 tekens.**

---

## 4. Redis Installeren en Beveiligen

```bash
apt install -y redis-server
systemctl enable redis-server --now
```

### 4.1 Redis beveiligen

Bewerk `/etc/redis/redis.conf`:

```bash
nano /etc/redis/redis.conf
```

Controleer of zet deze regels:

```conf
bind 127.0.0.1
protected-mode yes
requirepass JOUW_STERKE_REDIS_WACHTWOORD
```

Herstart Redis:

```bash
systemctl restart redis-server
```

### 4.2 PHP Redis extensie installeren

```bash
apt install -y php-redis
```

### 4.3 Test Redis verbinding

```bash
redis-cli -a "JOUW_STERKE_REDIS_WACHTWOORD" ping
# PONG
```

```bash
php -m | grep redis
# redis
```

---

## 5. Node.js Installeren (voor WebSocket service)

De Redis Admin WebSocket service draait op Node.js.

```bash
# Node.js 22 LTS via NodeSource
curl -fsSL https://deb.nodesource.com/setup_22.x -o nodesource_setup.sh
bash nodesource_setup.sh
apt install -y nodejs

# Controleer versies
node -v
npm -v
```

> **Let op:** Controleer op [nodejs.org](https://nodejs.org/) wat de huidige LTS versie is. Vervang `22` indien nodig.

Verwijder het setup script:

```bash
rm nodesource_setup.sh
```

---

## 6. Applicatie Uitrollen

### 6.1 Code naar de server kopiëren

Je kunt de code op meerdere manieren op de server krijgen:

#### Optie A: Git clone

```bash
mkdir -p /var/www
cd /var/www
git clone https://github.com/ecannemeijer/play2tv.git
cd play2tv
```

#### Optie B: SCP / RSYNC vanaf je lokale machine

```bash
# Op je lokale machine:
rsync -avz --exclude '.git' --exclude 'node_modules' --exclude 'vendor' \
  /pad/naar/play2tv/ user@server:/var/www/play2tv/
```

### 6.2 PHP dependencies installeren (Composer)

```bash
apt install -y composer
```

Of installeer Composer handmatig:

```bash
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
```

Installeer dependencies:

```bash
cd /var/www/play2tv
composer install --no-dev --optimize-autoloader
```

### 6.3 Node.js dependencies installeren (WebSocket service)

```bash
npm run redis:ws:install
```

Of direct:

```bash
cd /var/www/play2tv/tools/redis-admin-ws
npm install
cd /var/www/play2tv
```

### 6.4 Omgevingsconfiguratie (.env)

```bash
cp .env.example .env
nano .env
```

**Minimale aanpassingen in `.env`:**

```ini
# Basis
CI_ENVIRONMENT = production
app.baseURL = 'https://api.velixatv.com/'
app.forceGlobalSecureRequests = true
app.appTimezone = Europe/Amsterdam

# Database
database.default.hostname = localhost
database.default.database = play2tv
database.default.username = play2tv_user
database.default.password = "JOUW_DATABASE_WACHTWOORD"

# JWT (genereer met: openssl rand -base64 64)
jwt.secret = "GEGENEREERDE_JWT_SECRET_HIER"

# Rate limiting
rateLimit.loginMaxAttempts = 5
rateLimit.loginWindowSeconds = 300

# CORS
cors.allowedOrigins = *

# Logging
logger.threshold = 4

# Diagnostics
diagnostics.uploadApiKey = "DIAGNOSTICS_API_KEY"
diagnostics.maxUploadBytes = 1048576

# Telemetry
telemetry.enabled = true
telemetry.sampleRate = 0.3

# Redis Dashboard
redis.host = 127.0.0.1
redis.port = 6379
redis.password = "JOUW_REDIS_WACHTWOORD"
redis.prefix = play2tv:
redis.websocket.url = wss://api.velixatv.com/redis-ws
redis.websocket.secret = "LANG_RANDOM_WS_SECRET"
redis.websocket.allowedOrigins = https://api.velixatv.com,https://dashboard.play2tv.nl
redis.websocket.bindHost = 127.0.0.1
redis.websocket.bindPort = 8082
redis.websocket.path = /redis-ws

# Cache (Redis)
cache.handler = redis
cache.backupHandler = file
cache.prefix = play2tv:
cache.ttl = 120
cache.redis.host = 127.0.0.1
cache.redis.port = 6379
cache.redis.password = "JOUW_REDIS_WACHTWOORD"
cache.redis.database = 0

# Sessions (Redis)
session.driver = redis
session.redisSavePath = "tcp://127.0.0.1:6379?database=1&prefix=play2tv_session:&auth=JOUW_REDIS_WACHTWOORD_URLENCODED"
session.expiration = 7200
session.timeToUpdate = 300
session.regenerateDestroy = true
```

> **Belangrijke .env tips:**
> - Genereer JWT secret: `openssl rand -base64 64`
> - Genereer diagnostics API key: `openssl rand -hex 32`
> - Genereer Redis WS secret: `openssl rand -hex 32`
> - Als Redis wachtwoord speciale tekens bevat, URL-encode deze in `session.redisSavePath` (bv. `!` → `%21`, `@` → `%40`)

### 6.5 Database migraties uitvoeren

```bash
cd /var/www/play2tv
php spark migrate
```

### 6.6 Admin gebruiker aanmaken

```bash
php spark db:seed AdminSeeder
```

> ⚠️ **Wijzig het standaard admin wachtwoord DIRECT na je eerste login!**

### 6.7 Rechten instellen

```bash
chown -R www-data:www-data /var/www/play2tv/writable
chmod -R 775 /var/www/play2tv/writable
```

---

## 7. Nginx Configureren

### 7.1 Nginx configuratiebestand plaatsen

De repository bevat een kant-en-klare nginx configuratie: `play2tv-nginx.conf`

```bash
cp /var/www/play2tv/play2tv-nginx.conf /etc/nginx/sites-available/play2tv
```

> **Let op:** Pas indien nodig het `server_name` aan naar jouw domeinnaam in het configuratiebestand.

### 7.2 Site activeren

```bash
ln -s /etc/nginx/sites-available/play2tv /etc/nginx/sites-enabled/
```

### 7.3 Standaard site uitschakelen

```bash
rm -f /etc/nginx/sites-enabled/default
```

### 7.4 Nginx configuratie testen

```bash
nginx -t
```

Output moet zijn:

```
nginx: the configuration file /etc/nginx/nginx.conf syntax is ok
nginx: configuration file /etc/nginx/nginx.conf test is successful
```

**Als de test faalt, verbeter de fouten dan eerst voordat je verder gaat!**

### 7.5 PHP-FPM socket pad controleren

Controleer of het PHP-FPM socket pad in de nginx config klopt met je systeem:

```bash
ls -la /var/run/php/php-fpm.sock
```

Als het socket niet bestaat, zoek het juiste pad:

```bash
find /var/run -name "*.sock" 2>/dev/null
```

Pas indien nodig het `fastcgi_pass` pad aan in `/etc/nginx/sites-available/play2tv`.

### 7.6 Nginx herladen

```bash
systemctl reload nginx
```

---

## 8. SSL Certificaat (Let's Encrypt)

### 8.1 Certbot installeren

```bash
apt install -y certbot python3-certbot-nginx
```

### 8.2 Certificaat verkrijgen

```bash
certbot --nginx -d api.velixatv.com
```

Volg de prompts:
- Email: jouw emailadres
- Terms of Service: A (agree)
- Share email met EFF: N (optioneel)

Certbot past automatisch de nginx config aan om SSL goed te zetten.

### 8.3 Auto-renewal testen

```bash
certbot renew --dry-run
```

Certbot maakt automatisch een systemd timer aan die dagelijks draait.

```bash
systemctl list-timers | grep certbot
```

---

## 9. WebSocket Service (systemd)

### 9.1 Systemd service aanmaken

```bash
nano /etc/systemd/system/play2tv-redis-ws.service
```

Plak het volgende:

```ini
[Unit]
Description=Play2TV Redis Admin WebSocket
After=network.target redis-server.service

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/play2tv/tools/redis-admin-ws
Environment=NODE_ENV=production
ExecStart=/usr/bin/node server.js
Restart=always
RestartSec=3
StandardOutput=journal
StandardError=journal

# Security hardening
NoNewPrivileges=yes
PrivateTmp=yes
ProtectSystem=strict
ProtectHome=yes
ReadWritePaths=/var/log

[Install]
WantedBy=multi-user.target
```

### 9.2 Service activeren en starten

```bash
systemctl daemon-reload
systemctl enable play2tv-redis-ws --now
```

### 9.3 Service status controleren

```bash
systemctl status play2tv-redis-ws
```

Verwachte output bevat:

```
[redis-admin-ws] listening on ws://127.0.0.1:8082/redis-ws
[redis-admin-ws] public endpoint wss://api.velixatv.com/redis-ws
```

### 9.4 Logs bekijken

```bash
journalctl -u play2tv-redis-ws -f
```

---

## 10. PHP-FPM Optimalisatie voor Productie

### 10.1 PHP-FPM pool configuratie

```bash
nano /etc/php/8.4/fpm/pool.d/www.conf
```

Pas aan:

```ini
# Dynamische process management
pm = dynamic
pm.max_children = 20
pm.start_servers = 4
pm.min_spare_servers = 2
pm.max_spare_servers = 8
pm.max_requests = 500

# Owner
user = www-data
group = www-data
```

> Pas `pm.max_children` aan op basis van je server RAM:
> - 1 GB RAM: ~10
> - 2 GB RAM: ~20
> - 4 GB RAM: ~40
> - 8 GB RAM: ~80

PHP-FPM herstarten:

```bash
systemctl restart php8.4-fpm
```

### 10.2 PHP OPcache configuratie

```bash
nano /etc/php/8.4/fpm/conf.d/10-opcache.ini
```

Zorg voor deze instellingen:

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
opcache.enable_cli=0
```

PHP-FPM herstarten:

```bash
systemctl restart php8.4-fpm
```

---

## 11. MySQL / MariaDB Optimalisatie

### 11.1 Basis buffer configuratie

```bash
nano /etc/mysql/mariadb.conf.d/50-server.cnf
```

Voeg toe of pas aan in de `[mysqld]` sectie:

```ini
[mysqld]
innodb_buffer_pool_size = 512M
innodb_log_file_size = 128M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci
```

> Pas `innodb_buffer_pool_size` aan: 50-70% van beschikbaar RAM is een goede richtlijn.

Herstart:

```bash
systemctl restart mariadb
```

---

## 12. Applicatie Testen

### 12.1 Nginx bereikbaarheid

```bash
curl -I https://api.velixatv.com/
# HTTP/2 200 (of 302 redirect naar login als je root route checkt)
```

### 12.2 API endpoints testen

```bash
# Health check
curl https://api.velixatv.com/health
# OK

# Login test
curl -X POST https://api.velixatv.com/api/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"admin@velixatv.com","password":"password"}'
```

### 12.3 WebSocket testen

```bash
# Controleer of de WebSocket service draait
systemctl status play2tv-redis-ws

# Controleer of poort 8082 lokaal luistert
ss -ltnp | grep 8082
# 127.0.0.1:8082 ... node / server.js
```

### 12.4 Admin dashboard

Open in de browser:

```
https://api.velixatv.com/admin
```

Log in met de credentials uit `AdminSeeder` en wijzig direct het wachtwoord.

---

## 13. Automatisch Opstarten Controleren

```bash
systemctl is-enabled nginx
systemctl is-enabled mariadb     # of mysql
systemctl is-enabled redis-server
systemctl is-enabled php8.4-fpm
systemctl is-enabled play2tv-redis-ws
systemctl is-enabled certbot.timer

# Elke moet "enabled" tonen
```

---

## 14. Backup en Onderhoud

### 14.1 Database backup script

Maak een cron job aan voor dagelijkse backups:

```bash
nano /etc/cron.daily/play2tv-backup
```

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/play2tv"
mkdir -p "$BACKUP_DIR"
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump --single-transaction --routines --triggers play2tv | gzip > "$BACKUP_DIR/play2tv_$DATE.sql.gz"
# Houd alleen de laatste 14 backups
find "$BACKUP_DIR" -name "*.sql.gz" -mtime +14 -delete
```

```bash
chmod +x /etc/cron.daily/play2tv-backup
```

### 14.2 Applicatie onderhoud commando's

```bash
# Oude data opschonen
php spark maintenance:prune-data

# Routes bekijken
php spark routes

# Cache legen (indien nodig)
php spark cache:clear
```

---

## 15. Troubleshooting

### 15.1 Nginx error: 502 Bad Gateway

```bash
# Controleer of PHP-FPM draait
systemctl status php8.4-fpm

# Controleer socket
ls -la /var/run/php/php-fpm.sock

# Check nginx error log
tail -f /var/log/nginx/play2tv_error.log
```

### 15.2 Nginx error: 403 Forbidden

```bash
# Controleer permissies
ls -la /var/www/play2tv/public

# Controleer of www-data kan lezen
sudo -u www-data cat /var/www/play2tv/public/index.php
```

### 15.3 Redis connectie problemen

```bash
# Test Redis
redis-cli -a "WACHTWOORD" ping

# Check of Redis luistert
ss -ltnp | grep 6379

# Check PHP Redis extensie
php -m | grep redis
```

### 15.4 Database connectie problemen

```bash
# Test MySQL connectie
mysql -u play2tv_user -p -h localhost play2tv -e "SELECT 1;"

# Check MySQL status
systemctl status mariadb
```

### 15.5 WebSocket niet verbonden

Controleer in deze volgorde:

1. Draait de Node service? `systemctl status play2tv-redis-ws`
2. Luistert poort 8082? `ss -ltnp | grep 8082`
3. Werkt de nginx proxy? `curl -i -N -H "Connection: Upgrade" -H "Upgrade: websocket" -H "Host: api.velixatv.com" https://api.velixatv.com/redis-ws`
4. Staat het publieke domein in `redis.websocket.allowedOrigins`?
5. Is `redis.websocket.url` ingesteld op `wss://api.velixatv.com/redis-ws`?

### 15.6 PHP-FPM herstarten bij .env wijzigingen

```bash
systemctl restart php8.4-fpm
```

**Let op:** In tegenstelling tot Apache `mod_php` moet je bij nginx + PHP-FPM expliciet FPM herstarten na .env wijzigingen.

---

## 16. Samenvatting Installatie Commando's (Kopiëren en Plakken)

Hier is een overzicht van alle commando's in volgorde:

```bash
# 1. Basis
apt update && apt upgrade -y
apt install -y curl wget gnupg ca-certificates lsb-release unzip git acl sudo
timedatectl set-timezone Europe/Amsterdam

# 2. Nginx
apt install -y nginx
systemctl enable nginx --now

# 3. PHP 8.4
apt install -y php-fpm php-cli php-common php-mysql php-mbstring php-intl php-curl php-json php-xml php-zip php-gd php-bcmath php-opcache php-readline

# 4. MariaDB
apt install -y mariadb-server mariadb-client
systemctl enable mariadb --now
mysql_secure_installation

# 4b. Database aanmaken (MySQL shell)
# mysql -u root
# CREATE DATABASE play2tv CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
# CREATE USER 'play2tv_user'@'localhost' IDENTIFIED BY 'STERK_WACHTWOORD';
# GRANT ALL PRIVILEGES ON play2tv.* TO 'play2tv_user'@'localhost';
# FLUSH PRIVILEGES;
# EXIT;

# 5. Redis
apt install -y redis-server php-redis
systemctl enable redis-server --now
# nano /etc/redis/redis.conf  (bind 127.0.0.1, requirepass)

# 6. Node.js
curl -fsSL https://deb.nodesource.com/setup_22.x -o nodesource_setup.sh
bash nodesource_setup.sh
apt install -y nodejs
rm nodesource_setup.sh

# 7. Applicatie
mkdir -p /var/www
cd /var/www
git clone https://github.com/ecannemeijer/play2tv.git
cd play2tv
composer install --no-dev --optimize-autoloader
npm run redis:ws:install

# 8. Configuratie
cp .env.example .env
# nano .env (vul alle waarden in)
php spark migrate
php spark db:seed AdminSeeder
chown -R www-data:www-data /var/www/play2tv/writable
chmod -R 775 /var/www/play2tv/writable

# 9. Nginx
cp play2tv-nginx.conf /etc/nginx/sites-available/play2tv
ln -s /etc/nginx/sites-available/play2tv /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx

# 10. SSL
apt install -y certbot python3-certbot-nginx
certbot --nginx -d api.velixatv.com

# 11. WebSocket service
# nano /etc/systemd/system/play2tv-redis-ws.service
systemctl daemon-reload
systemctl enable play2tv-redis-ws --now

# 12. PHP-FPM optimalisatie
# nano /etc/php/8.4/fpm/pool.d/www.conf
# nano /etc/php/8.4/fpm/conf.d/10-opcache.ini
systemctl restart php8.4-fpm

# 13. Testen
curl https://api.velixatv.com/health
systemctl status play2tv-redis-ws
ss -ltnp | grep 8082
```

---

## Vereisten Overzicht

| Component | Vereiste | Debian 13 Default |
|-----------|----------|-------------------|
| OS | Debian/Linux | Debian 13 ✓ |
| Webserver | Nginx | 1.26+ ✓ |
| PHP | ≥ 8.2 | 8.4 ✓ |
| Database | MySQL 8.0+ / MariaDB 10.11+ | MariaDB 10.11+ ✓ |
| Cache | Redis 6+ | Redis 7.x ✓ |
| WebSocket | Node.js 18+ | 22 LTS via NodeSource |
| PHP extensies | intl, mbstring, json, mysqlnd, curl, redis | Allemaal beschikbaar ✓ |

---

## Bestandsstructuur na Installatie

```
/var/www/play2tv/                 ← Applicatie root
├── app/
├── public/                       ← Nginx document root
├── writable/                     ← Schrijfrechten voor www-data
├── tools/redis-admin-ws/         ← Node.js WebSocket service
├── .env                          ← Jouw productie configuratie
├── play2tv-nginx.conf            ← Nginx configuratie template
└── vendor/                       ← Composer dependencies

/etc/nginx/sites-available/play2tv  ← Actieve nginx config
/etc/nginx/sites-enabled/play2tv    ← Symlink

/etc/systemd/system/play2tv-redis-ws.service  ← WebSocket systemd service
```

---

## Let op: nginx vs Apache Verschillen

In tegenstelling tot Apache met `mod_php` waar `.htaccess` bestanden automatisch worden toegepast:

1. **URL rewriting:** Nginx gebruikt `try_files` in plaats van `.htaccess` mod_rewrite. De nginx configuratie (`play2tv-nginx.conf`) bevat de juiste `try_files` directive voor CodeIgniter 4.

2. **Directory bescherming:** Nginx gebruikt `location` blocks om gevoelige mappen te blokkeren in plaats van Apache `DirectoryMatch` directives.

3. **Authorization header:** Nginx heeft expliciete `fastcgi_param HTTP_AUTHORIZATION $http_authorization;` nodig om JWT Bearer tokens door te geven aan PHP-FPM. Dit is opgenomen in de nginx config.

4. **PHP herstarten:** Bij `.env` wijzigingen moet je PHP-FPM herstarten (`systemctl restart php8.4-fpm`), niet nginx.

5. **.htaccess bestanden:** Deze worden genegeerd door nginx. Alle beveiligingsregels zijn omgezet naar nginx `location` directives in `play2tv-nginx.conf`.

6. **CSP headers:** De Content Security Policy is geconfigureerd in de nginx config. Als je admin dashboard extra externe bronnen nodig heeft (CDN scripts, etc.), pas dan de `Content-Security-Policy` header aan in de nginx config.