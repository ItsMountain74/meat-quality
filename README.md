# MeatScan (Meat-Quality) — Fullstack Documentation

MeatScan is a fullstack (web + API) application that classifies meat freshness as **fresh**, **spoiled**, or **uncertain** from an uploaded photo. It includes:

- **Backend**: Laravel 12 REST API (`/api/v1`) + database persistence
- **Frontend**: a modern single-page landing & scanner UI served at `/` (Blade view with inlined UI + JS)
- **AI/Detector layer**: swappable detectors with safe **failover to mock** for demos

This README is written so **anyone can set up and present the application confidently**.

---

## What to demo (2–4 minute presenter script)

1. Open the web UI: `GET /`  
   - Show the landing sections (About / Dataset / Methodology).
2. Scroll to **Scanner** → upload an image → click **Run Scan**.
3. Explain the output:
   - **Label** (fresh/spoiled/uncertain)
   - **Confidence** (0–100)
   - **Explanation** and **Recommendations**
4. Show the API quickly:
   - `GET /api/health`
   - `POST /api/v1/auth/login`
   - `POST /api/v1/scans/upload` → `POST /api/v1/scans/{id}/analyze`
   - `GET /api/v1/scans/history`

If external AI keys are missing or the network is unavailable, the app still demonstrates end‑to‑end behavior because detectors are wrapped by a **failover detector** that falls back to **Mock**.

---

## Tech stack

- **PHP**: 8.2+ (works on 8.4)
- **Laravel**: 12.58
- **Database**: MySQL (configured by default)
- **Frontend build tooling**: Vite + Tailwind (standard Laravel assets; the MeatScan page itself is inlined in Blade)

---

## Project structure (high-level)

- **Web UI**
  - `routes/web.php` → `/` → `resources/views/meatscan.blade.php`
  - UI uploads an image and calls the API at `window.__MEATSCAN_API_BASE__` (defaults to `/api/v1`)
- **API**
  - `routes/api.php`
  - Controllers: `app/Http/Controllers/Api/V1/*`
  - Resources: `app/Http/Resources/*`
  - Postman collection: `docs/MeatScan-API.postman_collection.json`
- **MeatScan domain**
  - Service: `app/Services/MeatScan/MeatScanService.php`
  - Contract: `app/Services/MeatScan/Contracts/MeatDetector.php`
  - Detectors: `app/Services/MeatScan/Detectors/*`
  - Detector configuration: `config/meatscan.php`
  - DI binding / failover: `app/Providers/AppServiceProvider.php`
- **Data**
  - Table: `meat_scans` (see `database/migrations/2026_05_10_*`)
  - Stored images: `storage/app/public/meat-scans/*` (served via `php artisan storage:link`)

---

## Requirements

- **PHP** 8.2+ and Composer
- **Node.js** and npm (for Vite/Tailwind build; optional for pure API demos)
- **MySQL** (or change `DB_CONNECTION` to another supported driver)

---

## Setup (backend + frontend)

### 1) Install dependencies

```bash
composer install
npm install
```

### 2) Environment file

```bash
copy .env.example .env
php artisan key:generate
```

Update `.env` database settings (defaults in `.env.example`):

- `DB_DATABASE=meatscan`
- `DB_USERNAME=root`
- `DB_PASSWORD=root`

### 3) Migrate database

```bash
php artisan migrate
```

### 4) Enable public image URLs

The API returns `image_url` using Laravel Storage, so you must create the public storage symlink:

```bash
php artisan storage:link
```

### 5) Run the app (recommended)

Laravel’s `composer dev` runs the server, queue listener, logs, and Vite together:

```bash
composer dev
```

Then open:

- Web UI: `GET /` (typically `http://127.0.0.1:8000`)
- Health: `GET /api/health`

### Alternative: run only the API (no Node)

```bash
php artisan serve
```

---

## Configuration: MeatScan detector (AI layer)

The detector is configured via `.env` and `config/meatscan.php`.

### Detector driver

Set:

- `MEATSCAN_DETECTOR=roboflow_workflow` (default)
- `MEATSCAN_DETECTOR=roboflow_universe`
- `MEATSCAN_DETECTOR=openai_vision`
- `MEATSCAN_DETECTOR=mock`

### Failover behavior (important for demos)

The app binds `MeatDetector` to a driver-specific detector wrapped in `FailoverMeatDetector`:

- If the primary detector fails (missing keys, timeout, bad response), it **falls back to `MockMeatDetector`** automatically.

This means you can present the full workflow even without external AI credentials.

### Roboflow Workflows (serverless)

Required `.env` keys:

- `ROBOFLOW_API_KEY`
- `ROBOFLOW_WORKSPACE`
- `ROBOFLOW_WORKFLOW`

Common optional keys:

- `ROBOFLOW_IMAGE_INPUT_TYPE=base64` (recommended for local uploads)
- `ROBOFLOW_TIMEOUT=45`
- `ROBOFLOW_MAX_IMAGE_BYTES=3000000`
- `ROBOFLOW_LABEL_MAP_JSON={...}` (map workflow labels → `fresh|spoiled|uncertain`)

### Roboflow Universe hosted classification

Required `.env` keys:

- `ROBOFLOW_API_KEY`
- `ROBOFLOW_MODEL`
- `ROBOFLOW_VERSION`

### OpenAI Vision (optional)

Required `.env` keys:

- `OPENAI_API_KEY`

Optional keys:

- `OPENAI_VISION_MODEL` (default: `gpt-4o-mini`)
- `OPENAI_ENDPOINT`
- `OPENAI_TIMEOUT`

---

## API documentation

Import the ready-to-use Postman collection:

```
docs/MeatScan-API.postman_collection.json
```

In Postman: **Import → File →** select the JSON above. Set `api_root` / `base_url` if your server is not `http://127.0.0.1:8000`. Run **Register** or **Login** first — the collection auto-saves the Bearer token and scan ID.

**Base URL:** `{APP_URL}/api/v1`  
**Auth:** Laravel Sanctum Bearer token (`Authorization: Bearer {token}`)

---

### Response envelope

All API responses follow:

```json
{
  "success": true,
  "message": "…",
  "data": {}
}
```

Errors return `"success": false` with an appropriate HTTP status (401, 404, 422, etc.).

Validation errors (422) also include Laravel's standard `errors` object.

---

### Health

#### `GET /api/health`

No authentication required.

```json
{ "ok": true }
```

---

### Authentication

#### `POST /api/v1/auth/register`

No authentication required.

**Body (JSON):**

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `name` | string | yes | Max 255 chars |
| `email` | string | yes | Must be unique |
| `password` | string | yes | Min 8 chars |
| `password_confirmation` | string | yes | Must match `password` |
| `device_name` | string | no | Defaults to `"mobile"` |

**Response (201):**

```json
{
  "success": true,
  "message": "Registered successfully.",
  "data": {
    "token": "1|abc...",
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "created_at": "2026-06-23T10:00:00.000000Z",
      "updated_at": "2026-06-23T10:00:00.000000Z"
    }
  }
}
```

#### `POST /api/v1/auth/login`

No authentication required.

**Body (JSON):**

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `email` | string | yes | |
| `password` | string | yes | |
| `device_name` | string | no | Defaults to `"mobile"` |

**Response (200):** Same shape as register (`token` + `user`).

**Error (422):** `"Invalid credentials."`

---

### Profile

Requires `Authorization: Bearer {token}`.

#### `GET /api/v1/profile`

Returns the authenticated user.

**Response (200):**

```json
{
  "success": true,
  "message": "Profile fetched successfully.",
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "created_at": "...",
    "updated_at": "..."
  }
}
```

#### `PUT /api/v1/profile` or `PATCH /api/v1/profile`

Update profile. All fields are optional.

| Field | Type | Notes |
|-------|------|-------|
| `name` | string | Max 255 chars |
| `email` | string | Must be unique |
| `password` | string | Min 8 chars |
| `password_confirmation` | string | Required when changing password |

---

### Scans

All scan endpoints require `Authorization: Bearer {token}`. Scans are scoped to the authenticated user.

#### Scan object shape

```json
{
  "id": 12,
  "status": "completed",
  "image_url": "http://127.0.0.1:8000/storage/meat-scans/abc.jpg",
  "label": "fresh",
  "confidence": 92.5,
  "explanation": "The meat appears bright red with firm texture.",
  "recommendations": ["Store below 4°C", "Use within 2 days"],
  "scanned_at": "2026-06-23T10:00:05.000000Z",
  "created_at": "2026-06-23T10:00:00.000000Z"
}
```

| Field | Values / notes |
|-------|----------------|
| `status` | `pending` · `completed` · `failed` |
| `label` | `fresh` · `spoiled` · `uncertain` (null until analyzed) |
| `confidence` | 0–100 (null until analyzed) |

#### `POST /api/v1/scans/upload`

Upload a meat image (step 1 of 2).

- **Content-Type:** `multipart/form-data`
- **Body:** `image` (required, image file, max 10 MB)

**Response (201):** Scan object with `status: "pending"` and null analysis fields.

#### `POST /api/v1/scans/{id}/analyze`

Run AI analysis on a previously uploaded scan (step 2 of 2). No request body.

**Response (200):** Scan object with `status: "completed"` and analysis results.

**Error (422):** Analysis failed (scan `status` set to `failed`).

#### `GET /api/v1/scans/history`

Paginated list of completed scans for the current user, newest first.

**Query params:**

| Param | Default | Notes |
|-------|---------|-------|
| `per_page` | 15 | Items per page |
| `page` | 1 | Page number |

**Response (200):** Paginated collection inside `data`:

```json
{
  "success": true,
  "message": "Scan history fetched successfully.",
  "data": {
    "data": [ /* scan objects */ ],
    "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
    "meta": { "current_page": 1, "last_page": 3, "per_page": 15, "total": 42 }
  }
}
```

#### `GET /api/v1/scans/{id}`

Fetch a single scan by ID. Returns 404 if the scan does not belong to the user.

#### `DELETE /api/v1/scans/{id}`

Delete a scan and its stored image file. Returns 404 if the scan does not belong to the user.

---

### Typical mobile flow

```
1. POST /auth/register  (or /auth/login)  →  save token
2. POST /scans/upload   (multipart image)  →  save scan id
3. POST /scans/{id}/analyze                →  show results
4. GET  /scans/history                     →  list past scans
```

**Example (PowerShell) — login + upload + analyze:**

```powershell
# Login
$login = Invoke-RestMethod -Method POST `
  -Uri "http://127.0.0.1:8000/api/v1/auth/login" `
  -ContentType "application/json" `
  -Body '{"email":"demo@meatscan.test","password":"password123"}'
$token = $login.data.token

# Upload
$file = Get-Item ".\sample.jpg"
$upload = curl.exe -s -X POST "http://127.0.0.1:8000/api/v1/scans/upload" `
  -H "Accept: application/json" `
  -H "Authorization: Bearer $token" `
  -F "image=@$($file.FullName)" | ConvertFrom-Json
$scanId = $upload.data.id

# Analyze
Invoke-RestMethod -Method POST `
  -Uri "http://127.0.0.1:8000/api/v1/scans/$scanId/analyze" `
  -Headers @{ Authorization = "Bearer $token"; Accept = "application/json" }
```

---

## Frontend (web UI) documentation

### Where the UI lives

- Route: `GET /`
- View: `resources/views/meatscan.blade.php`

### How it calls the backend

The page sets:

- `window.__MEATSCAN_API_BASE__ = url('/api/v1')`

Then it calls (with Bearer token after login):

- `POST /scans/upload` with `FormData` containing the uploaded image
- `POST /scans/{id}/analyze` to run the AI detector

### Demo tips (for best results)

- Use well-lit images with the meat surface in focus.
- If you’re presenting offline, set `MEATSCAN_DETECTOR=mock` to guarantee deterministic results.

---

## Troubleshooting

- **Images URLs return 404**
  - Run `php artisan storage:link`
  - Ensure `FILESYSTEM_DISK` / `public` disk is configured and `APP_URL` matches your host.

- **Database errors on migrate**
  - Confirm MySQL is running and `.env` credentials are correct.
  - Create the database (e.g., `meatscan`) before running migrations.

- **Roboflow/OpenAI detector errors**
  - Verify required `.env` keys are set.
  - If keys are missing or network is blocked, the app should still work via **Mock failover**.

- **Vite/Tailwind not building**
  - Run `npm install`, then `npm run dev` (or `composer dev`).

---

## Notes for evaluators / future work

- The detector layer is intentionally abstracted (`MeatDetector`) so a real model (FastAPI/TensorFlow, OpenAI Vision, or Roboflow) can be swapped in without changing controllers or API contracts.
- The UI currently focuses on the scan journey and presentation; the API already persists scan history in `meat_scans`.

---

## License

This project is built on Laravel (MIT). Project-specific licensing should be added here if required.
