# Play2TV Backend API

Productie-klare REST API backend voor de Play2TV Android-app.  
Gebouwd met **PHP 8.2**, **CodeIgniter 4**, **MySQL**, **Redis** en **JWT-authenticatie**.

---

## Stack

| Onderdeel | Technologie |
|---|---|
| Runtime | PHP 8.2 |
| Framework | CodeIgniter 4 |
| Database | MySQL 8.0+ |
| Cache | File cache of Redis |
| Sessies | Database, files of Redis (env-driven) |
| Webserver | Apache2 + mod_rewrite |
| Auth (API) | JWT Bearer token (firebase/php-jwt) |
| Auth (Admin) | Session-based + CSRF |
| Admin UI | Bootstrap 5 + Chart.js |

---

## Mappenstructuur

```
play2tv/
├── app/
│   ├── Config/
│   │   ├── Filters.php          ← Filter registraties (jwt, adminauth, ratelimit)
│   │   ├── Routes.php           ← Alle API + admin routes
│   │   ├── Session.php          ← Env-driven sessie driver (database/file/redis)
│   │   └── Cache.php            ← Env-driven cache driver (file/redis)
│   ├── Controllers/
│   │   ├── Api/
│   │   │   ├── AuthController.php         ← register/login/logout/user
│   │   │   ├── SettingsController.php     ← settings opslaan/ophalen
│   │   │   ├── HistoryController.php      ← kijkgeschiedenis
│   │   │   ├── StorePointsController.php  ← punten systeem
│   │   │   └── PlaylistController.php     ← M3U playlist (premium)
│   │   └── Admin/
│   │       ├── AdminAuthController.php    ← admin login/logout
│   │       ├── DashboardController.php    ← statistieken dashboard
│   │       ├── RedisController.php        ← Redis admin dashboard + acties
│   │       ├── UserController.php         ← gebruikersbeheer
│   │       └── PlaylistController.php     ← playlist beheer
│   ├── Services/
│   │   └── RedisService.php               ← Redis metrics, scans en veilige admin acties
│   ├── Database/
│   │   ├── Migrations/          ← 7 database migraties
│   │   └── Seeds/
│   │       └── AdminSeeder.php  ← maakt eerste admin aan
│   ├── Filters/
│   │   ├── JwtFilter.php        ← JWT Bearer token validatie
│   │   ├── AdminAuthFilter.php  ← Admin sessie validatie
│   │   ├── RateLimitFilter.php  ← Login rate limiting
│   │   └── SecureHeadersFilter.php ← Security response headers
│   ├── Libraries/
│   │   └── JwtLibrary.php       ← JWT genereren/decoderen
│   ├── Models/
│   │   ├── UserModel.php
│   │   ├── UserDeviceModel.php
│   │   ├── UserSettingsModel.php
│   │   ├── WatchHistoryModel.php
│   │   ├── StorePointsModel.php
│   │   ├── PlaylistModel.php
│   │   ├── UserIpsLogModel.php
│   │   └── AdminModel.php
│   └── Views/
│       └── admin/
│           ├── layout.php       ← Gedeelde sidebar layout
│           ├── login.php        ← Admin login pagina
│           ├── dashboard.php    ← Statistieken + Chart.js
│           ├── redis_dashboard.php ← Redis dashboard met tabs + realtime updates
│           ├── users/
│           │   ├── index.php   ← Gebruikerslijst + zoek/filter
│           │   ├── view.php    ← Gebruikersdetail + IP/devices
│           │   └── edit.php    ← Gebruiker bewerken
│           └── playlists/
│               ├── index.php   ← Playlist overzicht
│               ├── add.php     ← Playlist uploaden
│               └── edit.php    ← Playlist bewerken
├── tools/
│   └── redis-admin-ws/
│       ├── package.json         ← Node WebSocket broadcaster
│       └── server.js            ← Realtime Redis snapshot service
├── public/                      ← DocumentRoot (Apache)
│   └── .htaccess
├── play2tv-apache.conf          ← Apache VirtualHost voorbeeld
├── .env                         ← Jouw configuratie (NIET committen!)
├── .env.example                 ← Template voor .env
├── composer.json
└── package.json                 ← Root npm scripts voor Redis WebSocket service
```

---

## Installatie

### 1. Composer installeren

```bash
cd /var/www/play2tv
composer install --no-dev --optimize-autoloader
```

### 2. Omgeving configureren

```bash
cp .env.example .env
nano .env
```

Vul minimaal in:
- `database.*` → MySQL credentials
- `jwt.secret` → genereer via `openssl rand -base64 64`
- `app.baseURL` → jouw domein

### 3. Database aanmaken

```sql
CREATE DATABASE play2tv CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'play2tv_user'@'localhost' IDENTIFIED BY 'STERK_WACHTWOORD';
GRANT ALL PRIVILEGES ON play2tv.* TO 'play2tv_user'@'localhost';
FLUSH PRIVILEGES;
```

### 4. Migraties uitvoeren

```bash
php spark migrate
```

### 5. Admin aanmaken

```bash
php spark db:seed AdminSeeder
```

> ⚠️ Wijzig het standaard admin wachtwoord DIRECT na de eerste login!

### 6. Apache configureren

```bash
cp play2tv-apache.conf /etc/apache2/sites-available/play2tv.conf
a2ensite play2tv
a2enmod rewrite ssl
systemctl reload apache2
```

### 7. Redis installeren en activeren (optioneel, aanbevolen voor productie)

Redis is vooral nuttig voor:
- snellere caching van playlist- en Xtream metadata
- minder databasebelasting bij hoge API-load
- Redis-gebaseerde sessies voor admin/login

#### Debian / Ubuntu

```bash
apt update
apt install -y redis-server php-redis
systemctl enable redis-server
systemctl restart redis-server
```

#### AlmaLinux / Rocky / RHEL

```bash
dnf install -y redis php-pecl-redis
systemctl enable redis
systemctl restart redis
```

#### Redis beveiligen

Open het Redis configuratiebestand:

```bash
nano /etc/redis/redis.conf
```

Controleer of zet minimaal:

```conf
bind 127.0.0.1
protected-mode yes
requirepass JOUW_STERKE_REDIS_WACHTWOORD
```

Herstart daarna Redis:

```bash
systemctl restart redis-server
```

Of op RHEL-achtige systemen:

```bash
systemctl restart redis
```

### 8. Redis in `.env` configureren

Voor cache:

```ini
cache.handler = redis
cache.backupHandler = file
cache.prefix = play2tv:
cache.ttl = 120

cache.redis.host = 127.0.0.1
cache.redis.port = 6379
cache.redis.password = "JOUW_STERKE_REDIS_WACHTWOORD"
cache.redis.database = 0
```

Voor sessies:

```ini
session.driver = redis
session.redisSavePath = "tcp://127.0.0.1:6379?database=1&prefix=play2tv_session:&auth=JOUW_STERKE_REDIS_WACHTWOORD"
session.expiration = 7200
session.timeToUpdate = 300
session.regenerateDestroy = true
```

Belangrijk:
- gebruik voor `cache.redis.password` het normale Redis wachtwoord
- gebruik voor `session.redisSavePath` een URL-encoded wachtwoord als het speciale tekens bevat
- voorbeelden: `!` wordt `%21`, `@` wordt `%40`, `#` wordt `%23`

Voorbeeld met encoded wachtwoord:

```ini
session.redisSavePath = "tcp://127.0.0.1:6379?database=1&prefix=play2tv_session:&auth=MijnWachtwoord%21"
```

Als Redis niet beschikbaar is of de PHP Redis extensie ontbreekt, valt de applicatie terug op een veiligere fallback-configuratie.

### 9a. Redis Admin Dashboard configureren

Naast cache en sessies ondersteunt Play2TV een aparte adminpagina op `/admin/redis`.

Deze implementatie bestaat uit twee delen:
- een CI4 controller + service voor initial load, key search, admin acties en fallback data
- een Node.js WebSocket broadcaster voor realtime updates zonder polling

#### Vereiste `.env` variabelen

Gebruik in productie minimaal deze Redis-sectie:

```ini
#--------------------------------------------------------------------
# REDIS DASHBOARD
#--------------------------------------------------------------------
redis.host = 127.0.0.1
redis.port = 6379
redis.password = JOUW_STERKE_REDIS_WACHTWOORD
redis.database = 0
redis.connectTimeout = 1.5
redis.readTimeout = 1.5
redis.prefix = play2tv:
redis.scanCount = 200
redis.scanSampleLimit = 500
redis.keySearchLimit = 100
redis.slowlogLimit = 25
redis.keyDeleteEnabled = true
redis.admin.allowedCommands = ping,info,ttl,pttl,type,exists,get,hgetall,hget,llen,scard,zcard,memory_usage
redis.admin.flushablePrefixes = play2tv:,cache:,epg:,vod:
redis.iptv.userPrefixes = play2tv:session:,play2tv:user:session:,iptv:user:
redis.iptv.streamPrefixes = play2tv:stream:active:,iptv:stream:,stream:active:
redis.iptv.epgHitKeys = play2tv:metrics:epg:hits,metrics:epg:hits,cache:epg:hits
redis.iptv.vodHitKeys = play2tv:metrics:vod:hits,metrics:vod:hits,cache:vod:hits
redis.websocket.url = wss://api.velixatv.com/redis-ws
redis.websocket.secret = VERVANG_MET_LANG_RANDOM_SECRET
redis.websocket.allowedOrigins = https://api.velixatv.com,https://dashboard.play2tv.nl,https://user.velixatv.com
redis.websocket.intervalMs = 2000
redis.websocket.bindHost = 127.0.0.1
redis.websocket.bindPort = 8082
redis.websocket.path = /redis-ws
```

Belangrijk:
- `redis.websocket.url` is het publieke browser-endpoint
- `redis.websocket.bindHost` en `redis.websocket.bindPort` zijn alleen voor de interne Node listener
- `redis.websocket.allowedOrigins` bepaalt welke admin frontends de live feed mogen openen
- als `redis.websocket.allowedOrigins` leeg is, valt de service terug op `cors.allowedOrigins` en `app.baseURL`
- gebruik `wss://` op HTTPS-productieomgevingen
- gebruik `memory_usage` in `.env`, niet `memory usage`, om DotEnv parsing fouten te voorkomen

#### Live status uitleg

De badge rechtsboven op `/admin/redis` heeft nu drie duidelijke statussen:
- `Live Feed Active` betekent: Redis is bereikbaar en de realtime WebSocket feed werkt.
- `Redis OK, Live Feed Offline` betekent: Redis snapshot werkt, maar de browser krijgt geen live updates via WebSocket.
- `Redis Unreachable` betekent: de backend kan geen Redis snapshot ophalen.

Als je op de Redis pagina wel data ziet maar de badge niet groen wordt, controleer dan eerst:
- of de Node service onder `tools/redis-admin-ws` draait
- of `redis.websocket.url` publiek bereikbaar is
- of het admin domein voorkomt in `redis.websocket.allowedOrigins`
- of reverse proxy of Cloudflare de WebSocket upgrade doorlaat

#### Dashboard functionaliteit

De Redis adminpagina bevat tabs voor:
- Overview
- Performance
- Memory
- Keys
- Slowlog
- IPTV
- Admin

De pagina ondersteunt:
- realtime statistieken via WebSocket
- automatische reconnect bij verbrekingen
- initial snapshot via CI4 als fallback
- Chart.js grafieken voor memory, commands/sec, clients en hit/miss ratio
- SCAN-gebaseerde key search met TTL en memory usage
- prefix-based cache flush
- veilige Redis admin commands via allowlist
- alerting voor hoge memory usage, evictions en latency

#### Beveiliging

De Redis dashboard implementatie is alleen bedoeld voor admins.

Beveiligingsmaatregelen:
- routebeveiliging via `AdminAuthFilter`
- signed WebSocket token vanuit de admin sessie
- admin acties worden gelogd via `SecurityEventService`
- gevaarlijke Redis commands zijn geblokkeerd
- `KEYS *` wordt nergens gebruikt; alleen `SCAN`
- flush is alleen toegestaan op expliciet toegestane prefixes

#### Beschikbare admin routes

| Methode | Endpoint | Beschrijving |
|---|---|---|
| GET | `/admin/redis` | Dashboard pagina |
| GET | `/admin/redis/initial` | Initial JSON snapshot fallback |
| GET | `/admin/redis/keys?pattern=...` | Zoekt keys via `SCAN` |
| POST | `/admin/redis/keys/delete` | Verwijdert 1 key |
| POST | `/admin/redis/admin/flush-prefix` | Flusht toegestane prefix |
| POST | `/admin/redis/admin/execute` | Voert veilig allowlisted command uit |

#### Veilige Redis commands

Standaard allowlist:
- `ping`
- `info`
- `ttl`
- `pttl`
- `type`
- `exists`
- `get`
- `hgetall`
- `hget`
- `llen`
- `scard`
- `zcard`
- `memory usage` via env alias `memory_usage`

Expliciet geblokkeerd:
- `flushall`
- `flushdb`
- `eval`
- `evalsha`
- `script`
- `config`
- `keys`
- `shutdown`
- `debug`
- `migrate`
- `restore`
- `replicaof`
- `slaveof`
- `monitor`

#### Prefix flush gedrag

Prefix-flush gebruikt altijd `SCAN` en verwijdert alleen keys onder deze toegestane prefixes:
- `play2tv:`
- `cache:`
- `epg:`
- `vod:`

Pas deze lijst alleen aan als je Redis key naming in productie anders is.

### 9b. WebSocket service installeren

De realtime feed draait als aparte Node.js service onder `tools/redis-admin-ws`.

Installeren vanaf de project root:

```bash
npm run redis:ws:install
```

Of direct in de map:

```bash
cd /var/www/html/play2tv/tools/redis-admin-ws
npm install
```

Starten vanaf project root:

```bash
npm start
```

Of direct:

```bash
cd /var/www/html/play2tv/tools/redis-admin-ws
npm start
```

Bij succesvolle startup zie je ongeveer:

```text
[redis-admin-ws] listening on ws://127.0.0.1:8082/redis-ws
[redis-admin-ws] public endpoint wss://api.velixatv.com/redis-ws
```

### 9c. nginx / Nginx Proxy Manager WebSocket proxy

De Node service hoort niet publiek op dezelfde poort te luisteren als nginx.

Correct productiepad:
- browser gebruikt `wss://api.velixatv.com/redis-ws`
- nginx proxyt dit intern naar `127.0.0.1:8082`
- Node luistert alleen lokaal op `127.0.0.1:8082`

Voor een standaard nginx vhost:

```nginx
location /redis-ws {
  proxy_pass http://127.0.0.1:8082/redis-ws;
  proxy_http_version 1.1;
  proxy_set_header Upgrade $http_upgrade;
  proxy_set_header Connection "upgrade";
  proxy_set_header Host $host;
  proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
  proxy_set_header X-Forwarded-Proto $scheme;
  proxy_read_timeout 60s;
  proxy_send_timeout 60s;
}
```

Gebruik je Nginx Proxy Manager, voeg dan equivalent WebSocket proxy gedrag toe aan dezelfde host die `api.velixatv.com` afhandelt.

### 9d. Realtime status “Disconnected” debuggen

Als de Redis pagina laadt maar rechtsboven `Disconnected` blijft staan, controleer dan in deze volgorde:

1. Klopt `redis.websocket.url` en gebruikt deze `wss://` op HTTPS?
2. Is `redis.websocket.path` gelijk aan het nginx proxy pad, bijvoorbeeld `/redis-ws`?
3. Draait de Node service werkelijk op `127.0.0.1:8082`?
4. Proxyt nginx of NPM WebSocket upgrades correct door?
5. Klopt `app.baseURL` met het publieke domein zodat origin validatie slaagt?
6. Is `redis.websocket.secret` exact gelijk voor CI4 en de Node service?

Veelvoorkomende fouten:
- `ws://127.0.0.1:8081` in productie gebruiken
- browser probeert naar localhost te verbinden in plaats van naar het publieke domein
- poortconflict omdat nginx al op `8081` luistert
- `memory usage` met spatie in `.env`, wat CI4 DotEnv laat crashen
- WebSocket proxy wel op nginx backend zetten, maar niet op de publieke Proxy Manager host

Handige productiechecks:

```bash
ss -ltnp | grep 8082
curl -I https://api.velixatv.com/
```

Voor Node logs:

```bash
cd /var/www/html/play2tv/tools/redis-admin-ws
npm start
```

### 9e. Redis dashboard uitrollen na wijzigingen

Na wijzigingen aan `.env`, nginx proxying of de WebSocket service:

```bash
systemctl reload nginx
systemctl restart php8.2-fpm
```

En herstart daarna de Node service opnieuw.

### 10. Controleren of Redis werkt

Test eerst Redis zelf:

```bash
redis-cli -a "JOUW_STERKE_REDIS_WACHTWOORD" ping
```

Verwachte output:

```text
PONG
```

Controleer daarna of de PHP Redis extensie geladen is:

```bash
php -m | grep redis
```

Controleer tenslotte of sessiesleutels verschijnen:

```bash
redis-cli -a "JOUW_STERKE_REDIS_WACHTWOORD"
```

In de Redis shell:

```text
SELECT 1
KEYS play2tv_session:*
```

### 11. Rechten instellen

```bash
chown -R www-data:www-data /var/www/play2tv/writable
chmod -R 775 /var/www/play2tv/writable
```

### 12. PHP / webserver herstarten na `.env` wijzigingen

Bij Apache mod_php:

```bash
systemctl restart apache2
```

Bij PHP-FPM:

```bash
systemctl restart php8.2-fpm
systemctl restart apache2
```

---

## API Endpoints

Base URL: `https://api.velixatv.com`

### Authenticatie (geen access token vereist)

| Methode | Endpoint | Beschrijving |
|---|---|---|
| POST | `/api/register` | Nieuw account aanmaken en token-paar ontvangen |
| POST | `/api/login` | Inloggen en token-paar ontvangen |
| POST | `/api/refresh` | Access token vernieuwen via refresh token |
| POST | `/api/logout` | Refresh token / token family intrekken |

### Gebruiker (JWT access token vereist)

| Methode | Endpoint | Beschrijving |
|---|---|---|
| GET | `/api/user` | Huidig gebruikersprofiel |
| GET | `/api/category-prefs` | Categorievoorkeuren ophalen |
| POST | `/api/category-prefs` | Categorievoorkeuren opslaan |
| POST | `/api/settings` | App-instellingen opslaan |
| GET | `/api/settings` | App-instellingen ophalen |
| POST | `/api/history` | Kijkgeschiedenis opslaan |
| GET | `/api/history` | Kijkgeschiedenis ophalen |
| POST | `/api/store-points` | Punten toevoegen/aftrekken |
| GET | `/api/store-points` | Punten saldo + geschiedenis |
| GET | `/api/playlist` | Actieve M3U playlist ophalen (alleen premium) |

### Access Token Structure

```json
{
  "iss": "play2tv-api",
  "aud": "play2tv-clients",
  "iat": 1700000000,
  "nbf": 1700000000,
  "exp": 1700000900,
  "typ": "access",
  "jti": "random-jwt-id",
  "sub": 42,
  "user_id": 42,
  "role": "user",
  "premium": true,
  "av": 3,
  "fp": "fingerprint-hash",
  "fam": "token-family-id"
}
```

Standaard geldigheid:
- access token: `900` seconden (15 minuten)
- refresh token: `2592000` seconden (30 dagen)

Gebruik voor beveiligde requests:

```text
Authorization: Bearer eyJhbGciOiJIUzI1NiJ9...
```

---

## Voorbeeld JSON Responses

### POST /api/login → 200 OK
```json
{
  "success": true,
  "message": "Inloggen geslaagd.",
  "data": {
    "token": "eyJhbGciOiJIUzI1NiJ9...",
    "access_token": "eyJhbGciOiJIUzI1NiJ9...",
    "token_type": "Bearer",
    "expires_in": 900,
    "refresh_token": "selector.validator",
    "refresh_expires_in": 2592000,
    "refresh_expires_at": "2026-05-03 12:00:00",
    "user_id": 1,
    "email": "user@example.com",
    "role": "user",
    "premium": true,
    "premium_until": "2025-12-31 00:00:00"
  }
}
```

### POST /api/refresh → 200 OK
```json
{
  "success": true,
  "message": "Token vernieuwd.",
  "data": {
    "token": "eyJhbGciOiJIUzI1NiJ9...",
    "access_token": "eyJhbGciOiJIUzI1NiJ9...",
    "token_type": "Bearer",
    "expires_in": 900,
    "refresh_token": "selector.validator",
    "refresh_expires_in": 2592000,
    "refresh_expires_at": "2026-05-03 12:00:00"
  }
}
```

### POST /api/login → 401 Unauthorized
```json
{
  "success": false,
  "message": "Ongeldige inloggegevens."
}
```

### POST /api/login → 429 Too Many Requests
```json
{
  "success": false,
  "message": "Te veel inlogpogingen. Wacht voordat je opnieuw probeert.",
  "retry_after": 287
}
```

### GET /api/playlist → 403 (geen premium)
```json
{
  "success": false,
  "message": "Premium abonnement vereist voor toegang tot de playlist."
}
```

---

## Voorbeeld Android Retrofit Calls

```kotlin
interface Play2TVApi {
    @POST("api/login")
    suspend fun login(@Body body: LoginRequest): Response<LoginResponse>

    @POST("api/register")
    suspend fun register(@Body body: RegisterRequest): Response<LoginResponse>

  @POST("api/refresh")
  suspend fun refresh(@Body body: RefreshRequest): Response<LoginResponse>

  @POST("api/logout")
  suspend fun logout(@Body body: LogoutRequest): Response<ApiResponse>

    @GET("api/user")
    suspend fun getUser(@Header("Authorization") token: String): Response<UserResponse>

  @GET("api/category-prefs")
  suspend fun getCategoryPrefs(@Header("Authorization") token: String): Response<CategoryPrefsResponse>

  @POST("api/category-prefs")
  suspend fun saveCategoryPrefs(
    @Header("Authorization") token: String,
    @Body body: CategoryPrefsRequest
  ): Response<ApiResponse>

    @POST("api/settings")
    suspend fun saveSettings(
        @Header("Authorization") token: String,
        @Body settings: Map<String, Any>
    ): Response<ApiResponse>

    @GET("api/settings")
    suspend fun getSettings(@Header("Authorization") token: String): Response<SettingsResponse>

    @POST("api/history")
    suspend fun saveHistory(
        @Header("Authorization") token: String,
        @Body body: WatchHistoryRequest
    ): Response<ApiResponse>

    @POST("api/store-points")
    suspend fun addPoints(
        @Header("Authorization") token: String,
        @Body body: StorePointsRequest
    ): Response<PointsResponse>

    @GET("api/playlist")
    suspend fun getPlaylist(
        @Header("Authorization") token: String
    ): Response<ResponseBody> // Raw M3U text
}

data class LoginRequest(val email: String, val password: String, val device_id: String? = null)
data class RefreshRequest(val refresh_token: String, val device_id: String? = null)
data class LogoutRequest(val refresh_token: String? = null)
data class CategoryPrefsRequest(val hidden_category_ids: List<String>)
data class WatchHistoryRequest(
    val content_type: String, // "movie" of "series"
    val content_id: String,
    val season: Int? = null,
    val episode: Int? = null,
    val progress_seconds: Int
)
data class StorePointsRequest(val points: Int, val reason: String = "watch_reward")
```

---

## Beveiliging

- ✅ Korte access tokens met refresh-token rotatie
- ✅ Refresh-token hashing in database
- ✅ Replay-detectie en revoke van complete token families
- ✅ Device/user-agent fingerprinting voor token-validatie
- ✅ API rate limiting en login backoff met tijdelijke account lock
- ✅ Strikte JSON payload-validatie voor API requests
- ✅ Session-based admin authenticatie met CSRF bescherming
- ✅ Secure headers op responses, inclusief HSTS, CSP en X-Frame-Options
- ✅ HTTPS-only productie-opzet
- ✅ Gebruiker-, rol- en auth-version controle bij protected requests
- ✅ Security event logging voor verdachte auth-events

## Performance

- Redis-ready cache en sessieconfiguratie via `.env`
- Gerichte caching voor playlist- en Xtream metadata endpoints
- Extra database-indexen voor high-growth tabellen
- Cleanup command voor refresh tokens, security events, IP logs, watch history en sessies

## Server Requirements

PHP 8.2 of hoger is vereist, met minimaal deze extensies:

- intl
- mbstring
- json
- mysqlnd
- curl

Aanbevolen voor productie:

- redis

---

## Nuttige Commando's

```bash
# Migraties uitvoeren
php spark migrate

# Migraties terugdraaien
php spark migrate:rollback

# Admin aanmaken
php spark db:seed AdminSeeder

# Routes bekijken
php spark routes

# Oude operationele data opschonen
php spark maintenance:prune-data

# Ontwikkelserver starten (alleen dev)
php spark serve --port=8080
```
