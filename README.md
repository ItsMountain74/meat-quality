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
   - `POST /api/v1/meat-scans` (upload image)
   - `GET /api/v1/meat-scans` (history)

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
  - Controller: `app/Http/Controllers/Api/V1/MeatScanController.php`
  - Resource: `app/Http/Resources/MeatScanResource.php`
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

### Response envelope

All API responses follow:

```json
{
  "success": true,
  "message": "…",
  "data": {}
}
```

### `GET /api/health`

Returns:

```json
{ "ok": true }
```

### `POST /api/v1/meat-scans` (run a scan)

- **Content-Type**: `multipart/form-data`
- **Body**:
  - `image` (required, image file, max 10MB)

Example (PowerShell):

```powershell
$file = Get-Item ".\sample.jpg"
curl.exe -X POST "http://127.0.0.1:8000/api/v1/meat-scans" `
  -H "Accept: application/json" `
  -F "image=@$($file.FullName)"
```

Successful response includes:

- `label`: `fresh | spoiled | uncertain`
- `confidence`: float (0–100)
- `explanation`: string
- `recommendations`: array of strings
- `image_url`: public URL (requires `storage:link`)
- `scanned_at`: ISO timestamp

### `GET /api/v1/meat-scans` (history)

Returns a paginated list (15 per page) ordered by `scanned_at` descending.

### `GET /api/v1/meat-scans/{id}` (single scan)

Returns one scan resource.

### `DELETE /api/v1/meat-scans/{id}` (delete)

Deletes the scan row (uploaded image file is not currently deleted automatically).

---

## Frontend (web UI) documentation

### Where the UI lives

- Route: `GET /`
- View: `resources/views/meatscan.blade.php`

### How it calls the backend

The page sets:

- `window.__MEATSCAN_API_BASE__ = url('/api/v1')`

Then it calls:

- `POST /meat-scans` with `FormData` containing the uploaded image

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
