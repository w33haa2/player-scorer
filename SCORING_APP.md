# DBBL Player Scorer

A companion scoring app for a **round-robin tournament** that is already organized in
[Challonge](https://challonge.com/). Challonge handles the bracket/schedule; this app exists
purely to **track per-game scores** so we can compute the tournament's custom **awards**.

Built on the Laravel + Inertia (Vue 3) starter kit, using **PrimeVue v4** for the scoring UI.

---

## Tech Stack

| Layer      | Technology                                             |
| ---------- | ------------------------------------------------------ |
| Backend    | PHP 8.4, Laravel 13, Laravel Fortify (auth)            |
| Frontend   | Vue 3, Inertia v3, PrimeVue v4 (Aura theme), Tailwind 4 |
| Assets     | PrimeVue, PrimeIcons, PrimeFlex                        |
| Routing    | Laravel Wayfinder (typed route/action helpers)         |
| Database   | MySQL 8 (Docker) / SQLite in-memory (tests)            |
| Tooling    | Pest, PHPStan (Larastan), Pint, ESLint, Prettier       |

PrimeVue is registered in `resources/js/app.ts` via Inertia's `withApp` hook. To keep Tailwind
utilities able to override PrimeVue's styled theme, the CSS layer order is declared at the top of
`resources/css/app.css` (`@layer theme, base, primevue, components, utilities;`) and PrimeVue is
configured with a matching `cssLayer`.

---

## Running with Docker (recommended)

The stack is defined in `docker-compose.yml` as two services on a shared bridge network
(`player-scorer`):

- **`app`** — PHP 8.4 + Node 22. Runs `php artisan serve` (`:8000`) and the Vite dev server (`:5173`).
- **`mysql`** — MySQL 8 with a persistent named volume.

```bash
# Build images and start the stack (first boot installs deps, migrates & seeds automatically)
docker compose up -d --build

# Follow app logs
docker compose logs -f app

# Open a shell in the app container
docker compose exec app bash

# Run artisan commands
docker compose exec app php artisan migrate:status
docker compose exec app php artisan test

# Stop the stack (keep data)
docker compose down

# Stop and wipe the database volume
docker compose down -v
```

Then open **http://localhost:8000**.

The `app` container manages its own `vendor/` and `node_modules/` (Docker volumes) so host
dependencies — including platform-specific Node binaries — never leak into the container. The
`docker/entrypoint.sh` script waits for MySQL, installs dependencies if missing, runs
`migrate --seed`, then starts both dev servers.

> **Note:** `.env` sets `DB_HOST=mysql` for the Docker network. If you run the app directly on
> your host (without Docker), point `DB_HOST` at your local database instead.

---

## Seeded Accounts

Two tournament admins are seeded (`UserSeeder`):

| Email             | Password   |
| ----------------- | ---------- |
| `admin1@dbbl.com` | `dbbl123!` |
| `admin2@dbbl.com` | `dbbl123!` |

The six awards are seeded via `AwardSeeder`.

---

## Data Model

| Table           | Columns                                                                 |
| --------------- | ----------------------------------------------------------------------- |
| `players`       | `id`, `name` (required), `date_started` (nullable), timestamps          |
| `awards`        | `id`, `name` (required), timestamps                                     |
| `player_scores` | `id`, `player_id` (FK), `score` (1–3), `is_burst` (bool), timestamps    |
| `action_logs`   | `id`, `user_id` (FK, nullable), `changes` (JSON), timestamps            |

### Score rules

- `score` must be an integer between **1 and 3** (never 0).
- If `is_burst` is `true`, the `score` **must be exactly 2** (enforced in the form requests and in
  the UI, where the score input is disabled and locked to 2 when burst is checked).

### Audit logging

`App\Observers\PlayerScoreObserver` logs every score **create / update / delete** into
`action_logs`. The `changes` JSON stores the action type, player reference, and the old/new score
snapshot. Human-readable messages are derived on the frontend (Audit Logs page).

---

## Endpoints

**PUBLIC** — no authentication required:

| Method | URI          | Name              | Description                        |
| ------ | ------------ | ----------------- | ---------------------------------- |
| GET    | `/standings` | `standings.index` | Aggregated award standings (public)|

**GUARDED** — requires an authenticated (and verified) admin:

| Method | URI                        | Name                  | Description                      |
| ------ | -------------------------- | --------------------- | -------------------------------- |
| GET    | `/players`                 | `players.index`       | List players                     |
| POST   | `/players`                 | `players.store`       | Create a player                  |
| PUT    | `/players/{player}`        | `players.update`      | Update a player                  |
| DELETE | `/players/{player}`        | `players.destroy`     | Delete a player (+ their scores) |
| GET    | `/scores`                  | `scores.index`        | List all scores                  |
| POST   | `/players/{player}/scores` | `players.scores.store`| Add one or more scores           |
| PUT    | `/scores/{score}`          | `scores.update`       | Update a single score            |
| GET    | `/audit-logs`              | `audit-logs.index`    | Recent admin activity            |

> Scores can be **created and updated** but **not deleted** through the app.

---

## Award Rulings

Computed by `App\Services\StandingsService`. Each award independently finds its winner; an award
with no qualifying entries shows "Not awarded yet".

This is a **Beyblade X** tournament, where each finish type is worth points: **Spin Finish = 1**,
**Over Finish = 2**, **Burst Finish = 2**, **Extreme Finish = 3**.

| Award                    | Ruling                                                                    |
| ------------------------ | ------------------------------------------------------------------------- |
| **Finals MVP**           | Highest **total accumulated score**.                                      |
| **Rookie of the Season** | Best newcomer in the scene — the player with the **most recent `date_started`** who has scored points. If no player has a start date, the award is **not calculated**. |
| **Stamina King**         | Most **spin finishes** (`player_scores` entries scoring exactly **1**).   |
| **Over Lord**            | Most **over finishes** (`player_scores` entries scoring exactly **2**).   |
| **Extreme Champion**     | Most **extreme finishes** (`player_scores` entries scoring exactly **3**).|
| **Burst God**            | Most **burst finishes** (`player_scores` entries scoring **2 with a burst finish**). |

Ties are broken deterministically by lowest player id. Note that **Over Lord** counts every
entry scoring 2 (including burst finishes), while **Burst God** is the burst-only subset.

---

## Pages

| Page                 | Access  | Highlights                                                                 |
| -------------------- | ------- | -------------------------------------------------------------------------- |
| **Login**            | Public  | PrimeVue-styled Fortify login.                                             |
| **Players**          | Guarded | DataTable + row action menu: update player (dialog), delete player (confirm), add scores (multi-entry dialog). |
| **Scores**           | Guarded | DataTable with per-row **update** dialog (no delete).                      |
| **Audit Logs**       | Guarded | DataTable of recent score changes with human-readable messages.            |
| **Current Standings**| Public  | Award cards with live tallies.                                             |

The score dialogs (on both Players and Scores pages) share the same validation: checking **"Is it
burst finish?"** forces the score to 2 and disables the score input.

---

## Testing & Quality

```bash
# Backend tests (Pest, SQLite in-memory)
docker compose exec app php artisan test

# Static analysis
docker compose exec app vendor/bin/phpstan analyse --memory-limit=512M

# Frontend checks
docker compose exec app npm run types:check
docker compose exec app npm run lint:check
```

Feature tests live in `tests/Feature`:
`PlayerManagementTest`, `ScoreManagementTest`, and `StandingsTest`.
