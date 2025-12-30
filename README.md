# Comments SPA

_Advanced threaded comments app with Laravel & Vue_

---

## Table of Contents
- [Description](#description)
- [Features](#features)
- [Technology Stack](#technology-stack)
- [Project Structure](#project-structure)
- [Installation & Quick Start](#installation--quick-start)
- [CI/CD & Deployment](#cicd--deployment)
- [Manual Deployment](#manual-deployment)
- [Docker Integration](#docker-integration)
- [Api documentation](#api-docs)
- [CAPTCHA System](#captcha-system)
- [Elasticsearch, Redis & Realtime Flow](#elasticsearch-redis--realtime-flow)
- [Database Design](#database-design)
- [Testing](#testing)

---

## Description
A test Single Page Application for managing comments with threaded replies. The project demonstrates clean backend–frontend separation, robust validation, file handling (attachments), pagination, sorting, search, and optional real-time updates.

---

## Features
- Threaded comments (create replies, tree structure)
- Safe HTML rendering (XSS-protected)
- Comment preview before submit
- Search, pagination, sorting (name, email, date)
- Image & text file attachments:
  - JPG / PNG / GIF (with processing)
  - TXT previews (≤ 100KB)
- Client-side & server-side validation (incl. CAPTCHA)
- Optional real-time updates (Laravel Echo)

---

## Technology Stack

### Backend
- **Laravel 12** (API + MVC)
- **MySQL 8.4** (database)
- **Elasticsearch** (full-text search/indexing via jobs)
- **Redis** (queues/caching & broadcast events)
- **Sanctum** (SPA API security)
- **Mews Purifier** (HTML sanitization/XSS protection)
- **Intervention Image** (image validation/processing)
- **Dedoc Scramble** (automatic OpenAPI docs)
- **PHPUnit** (testing)
- **Pusher** (via Laravel Echo, real-time web socket events)

### Frontend
- **Vue 3** (SPA, comments tree, forms, preview)
- **Pinia** (state management)
- **Vite** (frontend build tool)
- **Axios** (API HTTP client)
- **Tailwind CSS** (UI, minimal CSS)
- **Ziggy** (route helper, backend/frontend consistency)

### DevOps / Tooling
- **Docker & Docker Compose** (all services containerized)
- **Nginx** (webserver, static assets)
- **Node.js & NPM** (frontend tooling)
- **Breeze** (Laravel SPA kit)
- **Pint** (code style)
- **Debugbar** (dev debugging)
- **Pail** (log viewer)

---

## Project Structure
```text
app/
├── Console/Commands/
│   ├── ElasticCommentsCreateIndex.php
│   └── ElasticCommentsSync.php
├── Events/
├── Http/Controllers/Api/CommentController.php
├── Http/Middleware/
├── Http/Requests/
│   ├── CommentIndexRequest.php
│   ├── CommentPreviewRequest.php
│   ├── CommentSearchRequest.php
│   └── CommentStoreRequest.php
├── Jobs/IndexCommentToElastic.php
├── Models/
│   ├── Captcha.php
│   └── Comment.php
├── Providers/AppServiceProvider.php
├── Services/
│   ├── Captcha/TextCaptcha.php
│   ├── Comment/AttachmentService.php
│   ├── Comment/CommentListService.php
│   ├── Comment/CommentSearchService.php
│   ├── Comment/CommentSanitizer.php
│   ├── Comment/CommentService.php
│   └── Elastic/ElasticCommentIndexer.php
```

---

## Installation & Quick Start
This project uses Docker (Laravel Sail) for simple setup.

```bash
bash deploy/install.sh
```
- Copies `.env.example` to `.env` if not present
- Starts Docker & Sail containers
- Installs backend & frontend dependencies
- Builds frontend
- Runs migrations
- Syncs Elasticsearch index (if enabled)

> For full local setup details, see `deploy/install.sh` and Docker sections below.

---

## Manual Deployment
You can also deploy the application manually (without Docker/Sail) as follows:

1. Clone the repository & install PHP dependencies:
   ```bash
   git clone <repo_url>
   cd <project_dir>
   cp .env.example .env
   composer install
   ```
2. Install frontend dependencies:
   ```bash
   npm install
   npm run build
   ```
3. Generate an application key:
   ```bash
   php artisan key:generate
   ```
4. Set up your database in `.env`, then run migrations:
   ```bash
   php artisan migrate
   ```
5. (Optional) Create the Elasticsearch index and sync:
   ```bash
   php artisan elastic:comments-create-index --force
   php artisan elastic:comments-sync
   ```
6. Start the queue worker and (optionally) websocket server:
   ```bash
   php artisan queue:work
   # and for local websocket (if not using Pusher/Soketi managed service):
   # php artisan websockets:serve
   ```
7. Serve the application:
   ```bash
   php artisan serve
   ```

---

## CI/CD & Deployment

### GitHub Actions CI/CD

Проект настроен с автоматическим CI/CD через GitHub Actions:

- **CI (Continuous Integration):**
  - Автоматически запускается при каждом push и pull request
  - Запускает тесты PHP (`php artisan test`)
  - Проверяет сборку фронтенда (`npm run build`)
  - Использует MySQL и Redis как services в GitHub Actions

- **CD (Continuous Deployment):**
  - **Railway.app** (рекомендуется): автоматически деплоит при push в `main`/`master`
  - Railway определяет `compose.yaml` и запускает все сервисы автоматически
  - Для VPS: можно настроить SSH деплой через GitHub Secrets (см. DEPLOYMENT.md)

### Настройка деплоя

**Рекомендуемый вариант: Railway.app** ⭐

Railway автоматически деплоит при каждом push в `main`/`master`. Просто:

1. Зайдите на https://railway.app
2. Sign up через GitHub
3. New Project → Deploy from GitHub repo → выберите репозиторий
4. Railway автоматически определит `compose.yaml` и задеплоит
5. Настройте переменные окружения в Railway Dashboard
6. Добавьте MySQL и Redis через Railway (New → Database)

**Для других платформ:**
- См. подробные инструкции в [`DEPLOYMENT.md`](DEPLOYMENT.md)
- Oracle Cloud (VPS), Render, Fly.io - все варианты описаны

### Файлы CI/CD

- `.github/workflows/ci.yml` - основной CI workflow (тесты + сборка)
- `railway.json` - конфигурация для Railway.app (опционально)
- `.github/workflows/ci-flyio.yml.example` - пример для Fly.io
- `.github/workflows/ci-railway.yml.example` - пример для Railway (не нужен, Railway работает автоматически)

Подробнее: [`DEPLOYMENT.md`](DEPLOYMENT.md)

---

## Docker Integration
Key containers:
- **laravel.test**: PHP/Nginx app container
  - Exposes: `APP_PORT` (80), `VITE_PORT` (5173)
  - Uses custom Nginx config from `docker/nginx/default.conf`
- **queue**: Laravel queue worker (background jobs, e.g. Elasticsearch, notifications)
- **mysql**: MySQL database
- **redis**: Redis (queue/cache backend, broadcasting)
- **elasticsearch**: Full-text search/indexing

Helper scripts:
- `docker/ensure-storage-link.sh` (guarantees correct storage symlink)

---

## Api documentation
Doc url /docs/api#/
Swagger documentation with endpoints and schemas

Helper scripts:
- `docker/ensure-storage-link.sh` (guarantees correct storage symlink)

---

## Public access via Tailscale Funnel + Working WebSockets (Soketi)

This project uses Laravel + Soketi (Pusher-compatible) for realtime events.
To make it work from the internet via **Tailscale Funnel**, we must:

1) expose only **one public entrypoint** (Nginx)
2) proxy `/app/` (WebSocket endpoint) to Soketi with proper Upgrade headers
3) configure frontend Echo to connect via **wss** on port **443** and **path `/app`**
4) keep backend broadcasting pointed to `soketi:6001` inside Docker network

---

### 1) Docker Compose: Nginx reverse proxy in front

We run Laravel using `php artisan serve` inside `laravel.test` (no nginx inside that container),
so we add a separate `nginx` container as the public entrypoint.

Ports:
- `nginx` exposes `8088:80` (public entrypoint)
- `laravel.test` moves to `8089:80` (internal / optional direct access)
- `soketi` exposes `6001:6001`

Nginx routes:
- `/` -> `laravel.test:80`
- `/app/` -> `soketi:6001` (WebSockets)

---

### 2) Nginx config for WebSockets

`docker/nginx/default.conf`:

```nginx
map $http_upgrade $connection_upgrade {
    default upgrade;
    ''      close;
}

server {
    listen 80;
    server_name _;

    # WebSockets -> Soketi
    location ^~ /app/ {
        proxy_pass http://soketi:6001;
        proxy_http_version 1.1;

        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection $connection_upgrade;

        proxy_set_header Host $host;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;

        proxy_read_timeout 3600s;
        proxy_send_timeout 3600s;
        proxy_buffering off;
    }

    # App -> Laravel HTTP
    location / {
        proxy_pass http://laravel.test:80;
        proxy_http_version 1.1;

        proxy_set_header Host $host;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    }
}


## CAPTCHA System
- Custom backend-generated CAPTCHA using a random code per session, validated fully on the server.
- See `App\Services\Captcha\TextCaptcha` for internals.

---

## Elasticsearch, Redis & Realtime Flow

### Elasticsearch (Full-Text Search)
- Used to index and search comments for fast, flexible, typo-tolerant queries.
- When comments are created/edited, jobs are queued to update the Elasticsearch index.
- Indexing jobs are processed asynchronously using **Redis** as queue backend for maximum HTTP responsiveness.
  - Command to (re-)build index:
    ```bash
    php artisan elastic:comments-create-index --force
    php artisan elastic:comments-sync
    ```
- Elasticsearch is run locally in Docker, with mappings for fields (user_name, email, content, etc.), supporting rich search and sorting.

### Redis (Queue & Broadcast Backbone)
- **Primary queue driver** for background job processing in Laravel (via Sail).
- Powers deferred jobs like Elasticsearch indexing & other workflow-heavy tasks.
- Also used as cache backend for fast data retrieval (sessions, etc.).
- Enables fast async communication between containers (especially queue and broadcast events).

### Realtime & WebSockets
- Realtime updates use **Laravel Echo** with **Pusher** (or compatible WS driver like Soketi) for instant frontend refresh without reloads.
#### Flow:
1. **Comment Created/Updated** event is fired in backend (e.g. `App\Events\CommentCreated`).
2. Event is broadcast (`ShouldBroadcastNow`) on the `comments` public channel (via Redis).
3. Pusher (or compatible) transports this as a WebSocket message.
4. Frontend SPA (Vue app) listens on `comments` channel, receives event data (`comment_id`, `parent_id`).
5. UI fetches latest comments/tree and refreshes display.
- Health-check broadcast event (`App\Events\PingBroadcast`) lets you easily test the WebSocket connection configuration.

---

## Database Design
### Als default tables (users, migrations, jobs)

(docs/database/workbench-diagram.jpg)


### captchas
**Table `captchas`:**
- `id` (bigint, PK)
- `token` (varchar(64), UNIQUE): Random token for lookup
- `value` (varchar(10)): CAPTCHA value/answer
- `expires_at` (timestamp): TTL/expiration
- `created_at`, `updated_at` (timestamps)

**Indexes:**
- `UNIQUE(token)`
- `INDEX(expires_at)`

---

### comments
**Table `comments`:**
- `id` (bigint, PK)
- `parent_id` (bigint, nullable, FK → comments.id): Tree/thread reply structure
- `user_name` (varchar(70)): Author, validated
- `email` (varchar(255)): Author email
- `home_page` (varchar(255), nullable): Optional URL
- `text_html` (longtext): Sanitized HTML for rendering
- `text_raw` (longtext, nullable): Original for search/indexing
- `attachment_type` (enum: 'image'|'text', nullable)
- `attachment_path`, `attachment_original_name` (varchar, nullable): For file upload
- `ip` (varchar(64)), `user_agent` (varchar(255)), nullable: Client meta/auditing
- `created_at`, `updated_at`

**Indexes:**
- `INDEX(parent_id, created_at)` (replies by tree/time)
- `INDEX(created_at)` (pagination)
- `INDEX(user_name)` / `INDEX(email)` (sorting/filtering)

---

## Testing
Run all tests:
```bash
php artisan test
# Or in Docker/Sail:
./vendor/bin/sail test
```
Run a single test:
```bash
./vendor/bin/sail test --filter CommentPreviewTest
```
