<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Production Setup (Scouting App)

Gebruik deze stappen om de app op production werkend te krijgen, inclusief:
- uitnodigen van gebruikers via e-mail
- e-mailverificatie bij eerste account-aanmaak
- web push notificaties (iPhone + Android)

### 1) Vereisten op server

- PHP 8.3+
- Composer
- Node.js + npm
- MySQL/MariaDB
- HTTPS (verplicht voor Web Push en iPhone PWA)

### 2) Deploy en dependencies

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

### 3) Environment instellen

Zet minimaal deze variabelen in `.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://jouwdomein.nl

DB_CONNECTION=mysql
DB_HOST=...
DB_PORT=3306
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=info@fn12.nl
MAIL_FROM_NAME="Scouting App"

VITE_WEBPUSH_VAPID_PUBLIC_KEY=...
WEBPUSH_VAPID_PUBLIC_KEY=...
WEBPUSH_VAPID_PRIVATE_KEY=...
WEBPUSH_VAPID_SUBJECT=mailto:beheer@jouwdomein.nl
```

### 4) VAPID keys genereren (eenmalig)

```bash
npx web-push generate-vapid-keys
```

Plak de output in:
- `WEBPUSH_VAPID_PUBLIC_KEY`
- `WEBPUSH_VAPID_PRIVATE_KEY`
- `VITE_WEBPUSH_VAPID_PUBLIC_KEY` (zelfde waarde als public key)

### 5) Laravel productie-commando’s

```bash
php artisan key:generate --force
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 6) Rechten voor storage/cache

Zorg dat webserver kan schrijven naar:
- `storage/`
- `bootstrap/cache/`

### 7) Belangrijk voor uitnodigingen + verificatie

- Alleen `admin` en `bestuurslid` kunnen uitnodigingen versturen via `Gebruikers`.
- Uitgenodigde gebruiker vult eerst profielgegevens in via uitnodigingslink.
- Daarna wordt verificatiemail verstuurd.
- Zonder e-mailverificatie krijgt gebruiker geen toegang tot beschermde app-routes.

### 8) Push notificaties gedrag

- **iPhone**: gebruiker moet app toevoegen aan beginscherm en meldingen toestaan.
- **Android**: meldingen werken na toestemming, ook zonder homescreen-install.
- Voor beide geldt: de site moet op HTTPS draaien.

### 9) Snelle post-deploy check

1. Login als `admin` of `bestuurslid`.
2. Open `Gebruikers` en verstuur een uitnodiging.
3. Controleer of uitnodigingsmail aankomt.
4. Maak account via link aan en klik verificatielink in mail.
5. Ga in profiel naar push en zet meldingen aan.
6. Verstuur test-push en controleer ontvangst op toestel.

### 10) Automatische push-reminders (taken/opkomsten)

De app verstuurt automatisch:
- taakmelding **1 week voor deadline**
- taakmelding **op de dag van deadline**
- op de **dag van de geplande opkomst** een melding als je je niet afwezig of juist aanwezig hebt gemeld.

Hiervoor moet de Laravel scheduler draaien op productie:

```bash
* * * * * cd /pad/naar/scouting-app && php artisan schedule:run >> /dev/null 2>&1
```

Intern draait dan dagelijks om 08:00:
- `php artisan app:send-scheduled-push-notifications`

### 11) Rollenmodel (bestuur + speltakken)

- Globale rollen:
  - `admin`
  - `bestuurslid`
  - `penningmeester`
  - `secretaresse`
  - `voorzitter`
- Lokale rollen per speltak:
  - `teamleider`
  - `leiding`
  - `ouder_contact`
  - `lid`
- Bestuursrollen hebben globale toegang zoals bestuurslid.
- Teamleider kan in `Admin > Rechtenbeheer` per speltak rollen aan/uit zetten.
- Uitgeschakelde rollen:
  - zijn niet meer zichtbaar in rolkeuzes voor die speltak,
  - zijn niet meer zichtbaar in rechtenbeheer voor die speltak,
  - worden verwijderd van gebruikers in die speltak.

### 12) Declaratie-goedkeuringsflow

- Nieuwe declaratie krijgt status: `submitted` (wacht op goedkeuring).
- Beoordeling van declaraties kan door:
  - `penningmeester` (primair)
  - `admin` (fallback)
- Mogelijke uitkomst:
  - `approved` (goedgekeurd)
  - `needs_changes` (aanpassen nodig, met verplichte opmerking)
- Bij `needs_changes` ziet de indiener de opmerking in het declaratie-overzicht.
- Financiële afboeking op potje gebeurt alleen bij `approved`.

