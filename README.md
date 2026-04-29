# Shipping Label Manager

A full-stack web application that lets authenticated users generate, store, and print USPS shipping labels via the [EasyPost API](https://www.easypost.com/).

**Stack:** Laravel 12 · React 18 · MySQL 8 · Nginx · Docker

---

## Quick Start

### Prerequisites

| Tool | Version |
|---|---|
| Docker | 24+ |
| Docker Compose | v2.20+ |

No local PHP, Node, or MySQL installation is needed — everything runs inside containers.

---

### 1 — Clone and configure

```bash
git clone <repo-url>
cd fullstack-project
```

Copy the example env file and set your EasyPost API key:

```bash
cp .env.example .env
```

Then open `.env` and fill in `EASYPOST_API_KEY` (get a free test key at easypost.com/account/api-keys). The values in `.env` are picked up by `docker-compose.yml` automatically.

> Use a **test key** (begins with `EZAK` for test mode) during development.  
> Test-mode labels are free and print real-looking PDFs — no postage is actually charged.

---

### 2 — Build and start

```bash
docker compose up --build
```

First boot takes a few minutes. The backend container will:

1. Install Composer dependencies (`laravel/sanctum`, `easypost/easypost-php`)
2. Generate an application key
3. Wait for MySQL to become healthy
4. Run all database migrations automatically

Once you see `Apache/2.4.x ... resuming normal operations` in the logs, the app is ready.

---

### 3 — Open the app

| Service | URL |
|---|---|
| Web app (React) | http://localhost:8080 |
| API | http://localhost:8080/api |
| MySQL (external) | `localhost:3306` |

Register an account at http://localhost:8080/register, then create labels from the dashboard.

---

### Database

The schema is managed entirely through Laravel migrations — no manual SQL required.
Migrations run automatically on every container start (`php artisan migrate --force`).

| Table | Purpose |
|---|---|
| `users` | Registered accounts (name, email, hashed password) |
| `personal_access_tokens` | Sanctum API tokens (one per login session) |
| `shipping_labels` | Every generated label, linked to its owner user |

**Credentials (local only):**

| | Value |
|---|---|
| Database | `shipping_labels` |
| User | `laravel` |
| Password | `secret` |
| Root password | `rootsecret` |

Data persists in a named Docker volume (`mysql_data`). To wipe and start fresh:

```bash
docker compose down -v   # removes the volume
docker compose up
```

---

### Running the test suite

Tests use an in-memory SQLite database — no running containers needed:

```bash
docker exec fullstack_backend php artisan test
```

| Suite | Tests |
|---|---|
| `AuthTest` | 14 — register, login, logout, /me |
| `ShippingLabelTest` | 18 — create (EasyPost mocked), list, show, download, ownership |

All EasyPost calls are mocked in tests; no real API key is needed to run them.

---

### Before pushing to GitHub

#### What is already gitignored

| Path | Reason |
|---|---|
| `backend/laravel/vendor/` | Composer dependencies — restored by `composer install` on boot |
| `backend/laravel/.env` | Contains `APP_KEY` and the real `EASYPOST_API_KEY` — never commit |
| `backend/laravel/storage/api-docs/` | Generated Swagger spec — rebuilt on every container start |
| `backend/laravel/storage/*.key` | Laravel encryption keys |
| `frontend/node_modules/` | npm dependencies |
| `frontend/dist/` | Production build output |

#### What you must set locally (not in the repo)

`docker-compose.yml` ships with `EASYPOST_API_KEY: ""`. Fill it in on your machine before running the stack — it is intentionally blank in the repository:

```yaml
environment:
  EASYPOST_API_KEY: "EZAK..."   # your test key — never commit a real value here
```

#### Local-only credentials

The database passwords in `docker-compose.yml` (`secret` / `rootsecret`) are throwaway values that only work inside the Docker network. They are safe to leave in the file for a development repository, but must be changed for any shared or production environment.

---

### Re-building after code changes

| What changed | Command |
|---|---|
| PHP / Laravel code (`backend/laravel/`) | No rebuild needed — volume is live-mounted |
| React code (`frontend/src/`) | No rebuild needed — Vite HMR |
| `backend/Dockerfile` or `entrypoint.sh` | `docker compose build backend && docker compose up -d backend` |
| `frontend/package.json` (new npm packages) | `docker compose build frontend && docker compose up -d frontend` |

---

## API Documentation (Swagger UI)

Interactive documentation is available via Swagger UI once the stack is running. It is generated from OpenAPI 3.0 annotations in the PHP source code using [`darkaonline/l5-swagger`](https://github.com/DarkaOnLine/L5-Swagger) (the Laravel wrapper around [`zircote/swagger-php`](https://github.com/zircote/swagger-php)).

| | |
|---|---|
| **URL** | http://localhost:8080/api/documentation |
| **Spec (JSON)** | http://localhost:8080/docs?api-docs.json |

### Authenticating in Swagger UI

1. Call **POST /api/register** or **POST /api/login** directly in the UI to obtain a token.
2. Click the **Authorize** button (top right, lock icon).
3. In the **bearerAuth** field enter your token and click **Authorize**.
4. All subsequent requests in the UI will include the `Authorization: Bearer <token>` header automatically.

### Re-generating the spec

The spec is generated automatically on every container start. To regenerate it manually without restarting:

```bash
docker exec fullstack_backend php artisan l5-swagger:generate
```

The output file is stored at `storage/api-docs/api-docs.json` inside the backend container.

---

## API Reference

All endpoints are prefixed `/api`. Protected routes require `Authorization: Bearer <token>`.

| Method | Path | Auth | Description |
|---|---|---|---|
| `POST` | `/register` | — | Create account, returns token |
| `POST` | `/login` | — | Authenticate, returns token |
| `POST` | `/logout` | ✓ | Revoke current token |
| `GET` | `/me` | ✓ | Return authenticated user |
| `GET` | `/labels` | ✓ | List the authenticated user's labels |
| `POST` | `/labels` | ✓ | Generate a USPS label via EasyPost |
| `GET` | `/labels/{id}` | ✓ | Retrieve a single label |
| `GET` | `/labels/{id}/download` | ✓ | Redirect to the EasyPost label PDF |

---

## Assumptions

- **US addresses only.** Both the from and to address state fields are validated against the full list of US state/territory codes. The EasyPost call always passes `"country": "US"`.
- **USPS carrier only.** The service picks the cheapest available USPS rate via `lowestRate(['USPS'])`. No other carriers are offered.
- **One token per session.** Each login issues a new Sanctum token. Logout deletes only the current token; other active sessions are unaffected.
- **Label storage is a URL pointer.** The `label_url` field stores the EasyPost-hosted PDF URL. The file itself is not downloaded and stored locally.
- **Test keys for development.** EasyPost test-mode labels are fully functional for printing and UI testing without any real postage cost.
- **Single-tenant isolation.** All `GET` requests for labels enforce `user_id` ownership. A 403 is returned — not a 404 — so the existence of another user's label is not leaked.

---

## What I Would Do Next

### 1 — EasyPost request/response logging

Every outbound EasyPost call should be logged for debugging, auditing, and cost tracking.

**Implementation:**

- Create a `LoggingEasyPostService` decorator that wraps `EasyPostService`, logs the request payload and the full API response (or exception), then delegates to the real service.
- Store logs in a dedicated `easypost_logs` table:

```
easypost_logs
  id, user_id, shipment_id, action (create|buy),
  request_payload (json), response_payload (json),
  duration_ms, created_at
```

- Bind the decorator in `AppServiceProvider` so the rest of the application is unaware of logging.
- This also provides a full audit trail for billing disputes and carrier claims.

---

### 2 — Address book (contacts table)

Users repeatedly ship from the same warehouse or to the same customers. Forcing them to retype addresses every time is poor UX and error-prone.

**New table:**

```
contacts
  id, user_id,
  label (string, e.g. "Home Office", "Client A"),
  name, company, street1, street2, city, state, zip,
  is_default_sender (boolean),
  created_at, updated_at
```

**Changes:**

- `GET /contacts`, `POST /contacts`, `PUT /contacts/{id}`, `DELETE /contacts/{id}` endpoints.
- On the label creation form, add a **"Saved addresses"** dropdown that pre-fills the from/to fields when a contact is selected.
- Store `from_contact_id` and `to_contact_id` (nullable) on `shipping_labels` so the history view can show the contact name instead of raw address text.

---

### 3 — Webhook handler for tracking updates

EasyPost sends `tracker.updated` webhook events whenever a shipment status changes. Without them, tracking info is stale the moment the label is created.

**Implementation:**

- `POST /api/webhooks/easypost` endpoint (excluded from `auth:sanctum`).
- Verify the EasyPost HMAC signature on every incoming request.
- Dispatch a queued `TrackingUpdated` job that updates `shipping_labels.status` and `tracking_code`.
- Display live status on the label detail page ("Pre-Transit", "In Transit", "Delivered").

---

### 4 — Queue EasyPost calls

Currently `POST /api/labels` is synchronous — the HTTP request hangs for the full EasyPost round-trip (typically 1–3 s). Under load this ties up PHP workers.

**Implementation:**

- Dispatch a `CreateShipmentJob` from the controller and return `202 Accepted` with a label stub.
- The job calls EasyPost, updates the label record (including `label_url`), and fires a `LabelReady` event.
- The frontend polls `GET /api/labels/{id}` until `status !== 'pending'`, then shows the print button.

---

### 5 — Store label PDFs locally (S3 / local disk)

EasyPost label URLs can expire. Storing a local copy ensures labels remain printable after the URL lapses.

**Implementation:**

- After buying the shipment, download the PDF using `Http::get($labelUrl)` and store it with Laravel's `Storage` facade (`s3` driver in production, `local` in development).
- Save the internal storage path in a new `label_storage_path` column.
- The `/download` endpoint streams from storage instead of redirecting to EasyPost.

---

### 6 — Production hardening

| Item | Detail |
|---|---|
| Move secrets to `.env` file | Never commit API keys; use a `.env` file excluded from git and injected via CI/CD secrets |
| HTTPS | Add a TLS-terminating reverse proxy (Caddy or AWS ALB) in front of Nginx |
| Rate limiting | Apply stricter rate limits on `/register` and `/login` to prevent credential stuffing |
| Database backups | Schedule automated MySQL dumps to S3 with a retention policy |
| Sanctum token expiry | Set `personal_access_tokens` expiry in `config/sanctum.php` (e.g. 30 days) |
| Stricter CORS | Lock `config/cors.php` to the production domain instead of `*` |
