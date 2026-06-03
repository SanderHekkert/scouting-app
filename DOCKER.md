# Docker

Productie-image met multi-stage build: Node (Vite), Composer, PHP 8.3-FPM en Nginx.

## Snel starten (lokaal)

```bash
cp .env.docker.example .env
# Vul APP_KEY in: php artisan key:generate --show

docker compose build
docker compose up -d
```

App: http://localhost:8080  
Healthcheck: http://localhost:8080/up

## Alleen image bouwen

```bash
docker build --target php -t scouting-app:php .
docker build --target web -t scouting-app:web .
```

## Services

| Service   | Rol                                      |
|-----------|------------------------------------------|
| `mysql`   | Database                                 |
| `php`     | Laravel (PHP-FPM)                        |
| `nginx`   | Webserver (static assets + PHP proxy)    |
| `queue`   | `php artisan queue:work`                 |
| `scheduler` | `php artisan schedule:run` (elke minuut) |

## Productie

- Zet `APP_ENV=production`, `APP_DEBUG=false` en een echte `APP_URL` (HTTPS).
- Vul mail- en Web Push-variabelen in `.env` (zie README).
- Voor Vite-build in de image: geef `VITE_WEBPUSH_VAPID_PUBLIC_KEY` mee als build-arg (staat in `docker-compose.yml`).
- Persistent volumes: `app_storage`, `app_bootstrap_cache`, `mysql_data`.

## Handige commando’s

```bash
docker compose exec php php artisan migrate --force
docker compose exec php php artisan db:seed
docker compose logs -f php queue scheduler
```
