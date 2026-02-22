# Play2TV Backend API

Productie-klare REST API backend voor de Play2TV Android-app.  
Gebouwd met **PHP 8.2**, **CodeIgniter 4**, **MySQL** en **JWT-authenticatie**.

---

## Stack

| Onderdeel | Technologie |
|---|---|
| Runtime | PHP 8.2 |
| Framework | CodeIgniter 4 |
| Database | MySQL 8.0+ |
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
│   │   └── Session.php          ← DatabaseHandler sessie config
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
│   │       ├── UserController.php         ← gebruikersbeheer
│   │       └── PlaylistController.php     ← playlist beheer
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
│           ├── users/
│           │   ├── index.php   ← Gebruikerslijst + zoek/filter
│           │   ├── view.php    ← Gebruikersdetail + IP/devices
│           │   └── edit.php    ← Gebruiker bewerken
│           └── playlists/
│               ├── index.php   ← Playlist overzicht
│               ├── add.php     ← Playlist uploaden
│               └── edit.php    ← Playlist bewerken
├── public/                      ← DocumentRoot (Apache)
│   └── .htaccess
├── play2tv-apache.conf          ← Apache VirtualHost voorbeeld
├── .env                         ← Jouw configuratie (NIET committen!)
├── .env.example                 ← Template voor .env
└── composer.json
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

Vul in:
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

### 7. Rechten instellen

```bash
chown -R www-data:www-data /var/www/play2tv/writable
chmod -R 775 /var/www/play2tv/writable
```

---

## API Endpoints

Base URL: `https://api.play2tv.nl`

### Authenticatie (geen JWT vereist)

| Methode | Endpoint | Beschrijving |
|---|---|---|
| POST | `/api/register` | Nieuw account aanmaken |
| POST | `/api/login` | Inloggen, JWT token ontvangen |
| POST | `/api/logout` | Uitloggen (client verwijdert token) |

### Gebruiker (JWT vereist)

| Methode | Endpoint | Beschrijving |
|---|---|---|
| GET | `/api/user` | Huidig gebruikersprofiel |
| POST | `/api/settings` | App-instellingen opslaan |
| GET | `/api/settings` | App-instellingen ophalen |
| POST | `/api/history` | Kijkgeschiednis opslaan |
| GET | `/api/history` | Kijkgeschiednis ophalen |
| POST | `/api/store-points` | Punten toevoegen/aftrekken |
| GET | `/api/store-points` | Punten saldo + geschiedenis |
| GET | `/api/playlist` | M3U playlist (alleen premium) |

### JWT Token Structure

```json
{
  "iss": "play2tv-api",
  "iat": 1700000000,
  "exp": 1700604800,
  "user_id": 42,
  "premium": true
}
```

Geldigheid: **7 dagen**. Stuur mee in elke beveiligde request:

```
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
    "user_id": 1,
    "email": "user@example.com",
    "premium": true,
    "premium_until": "2025-12-31 00:00:00"
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
  "message": "Te veel inlogpogingen. Probeer het opnieuw na 287 seconden.",
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
// Interface definities
interface Play2TVApi {
    @POST("api/login")
    suspend fun login(@Body body: LoginRequest): Response<LoginResponse>

    @POST("api/register")
    suspend fun register(@Body body: RegisterRequest): Response<LoginResponse>

    @GET("api/user")
    suspend fun getUser(@Header("Authorization") token: String): Response<UserResponse>

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

// Data klassen
data class LoginRequest(val email: String, val password: String, val device_id: String? = null)
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

- ✅ JWT Bearer authenticatie (HS256, 7 dagen)
- ✅ Bcrypt password hashing (cost 12)
- ✅ Rate limiting op login (5 pogingen / 5 minuten per IP)
- ✅ CSRF bescherming op admin panel (uitgesloten voor /api/*)
- ✅ Session-based admin authenticatie
- ✅ SQL injection bescherming via CI4 Query Builder
- ✅ XSS filtering op alle inputs
- ✅ Secure HTTP headers op alle responses (HSTS, X-Frame-Options, etc.)
- ✅ HTTPS-only via Apache2
- ✅ Gebruiker deactivering controle bij elke JWT request

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

# Ontwikkelserver starten (alleen dev)
php spark serve --port=8080
```
More information can be found at the [official site](https://codeigniter.com).

This repository holds a composer-installable app starter.
It has been built from the
[development repository](https://github.com/codeigniter4/CodeIgniter4).

More information about the plans for version 4 can be found in [CodeIgniter 4](https://forum.codeigniter.com/forumdisplay.php?fid=28) on the forums.

You can read the [user guide](https://codeigniter.com/user_guide/)
corresponding to the latest version of the framework.

## Installation & updates

`composer create-project codeigniter4/appstarter` then `composer update` whenever
there is a new release of the framework.

When updating, check the release notes to see if there are any changes you might need to apply
to your `app` folder. The affected files can be copied or merged from
`vendor/codeigniter4/framework/app`.

## Setup

Copy `env` to `.env` and tailor for your app, specifically the baseURL
and any database settings.

## Important Change with index.php

`index.php` is no longer in the root of the project! It has been moved inside the *public* folder,
for better security and separation of components.

This means that you should configure your web server to "point" to your project's *public* folder, and
not to the project root. A better practice would be to configure a virtual host to point there. A poor practice would be to point your web server to the project root and expect to enter *public/...*, as the rest of your logic and the
framework are exposed.

**Please** read the user guide for a better explanation of how CI4 works!

## Repository Management

We use GitHub issues, in our main repository, to track **BUGS** and to track approved **DEVELOPMENT** work packages.
We use our [forum](http://forum.codeigniter.com) to provide SUPPORT and to discuss
FEATURE REQUESTS.

This repository is a "distribution" one, built by our release preparation script.
Problems with it can be raised on our forum, or as issues in the main repository.

## Server Requirements

PHP version 8.2 or higher is required, with the following extensions installed:

- [intl](http://php.net/manual/en/intl.requirements.php)
- [mbstring](http://php.net/manual/en/mbstring.installation.php)

> [!WARNING]
> - The end of life date for PHP 7.4 was November 28, 2022.
> - The end of life date for PHP 8.0 was November 26, 2023.
> - The end of life date for PHP 8.1 was December 31, 2025.
> - If you are still using below PHP 8.2, you should upgrade immediately.
> - The end of life date for PHP 8.2 will be December 31, 2026.

Additionally, make sure that the following extensions are enabled in your PHP:

- json (enabled by default - don't turn it off)
- [mysqlnd](http://php.net/manual/en/mysqlnd.install.php) if you plan to use MySQL
- [libcurl](http://php.net/manual/en/curl.requirements.php) if you plan to use the HTTP\CURLRequest library
