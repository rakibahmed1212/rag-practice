# RAG Practice

A team-based Retrieval-Augmented Generation (RAG) app built on Laravel + Inertia + React. Upload PDFs, they get chunked and embedded in the background, then ask questions against your team's documents and get answers grounded in the retrieved chunks. Includes a dashboard with usage statistics.

## Features

- **Auth & teams** — every document and query is scoped to the current team (existing Laravel starter-kit auth/teams).
- **Document management** (`/{team}/documents`) — upload a PDF, see its processing status (pending → processing → completed/failed), inspect its chunks, delete it.
- **Background embedding** — uploads are parsed (`smalot/pdfparser`), chunked, and embedded via Gemini in a queued job (`App\Jobs\ProcessDocumentEmbeddings`), so uploads don't block the request.
- **RAG Query** (`/{team}/rag-query`) — ask a question, get an answer generated from the most similar embedded chunks (pgvector cosine similarity), with query history.
- **Dashboard** (`/{team}/dashboard`) — document/embedding counts, storage used, query volume, recent activity.

## Requirements

- PHP 8.2+
- Composer
- Node.js 18+ / npm
- **PostgreSQL 16+ with the [pgvector](https://github.com/pgvector/pgvector) extension** (required — the `embedding` columns use Laravel's native `vector` column type, which is only supported on Postgres, MySQL 9+, or MariaDB; SQLite is **not** supported here)
- A Gemini API key (used for both embeddings and the answer-generation agent)

## Setup

### 1. Install dependencies

```bash
composer install
npm install
```

### 2. Configure the environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` to point at whichever database you set up in step 3:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5433
DB_DATABASE=rag_practice
DB_USERNAME=rag_practice
DB_PASSWORD=rag_practice_local

GEMINI_API_KEY=your-gemini-api-key
```

### 3. Set up PostgreSQL + pgvector

Pick whichever is easier on your machine.

**Option A — Docker (fastest, no sudo needed):**

```bash
docker run -d --name rag_practice_pg \
  -e POSTGRES_USER=rag_practice \
  -e POSTGRES_PASSWORD=rag_practice_local \
  -e POSTGRES_DB=rag_practice \
  -p 5433:5432 \
  pgvector/pgvector:pg16

docker exec rag_practice_pg psql -U rag_practice -d rag_practice -c "CREATE EXTENSION IF NOT EXISTS vector;"

# also create the dedicated test database used by phpunit.xml
docker exec rag_practice_pg psql -U rag_practice -d rag_practice -c "CREATE DATABASE rag_practice_test OWNER rag_practice;"
docker exec rag_practice_pg psql -U rag_practice -d rag_practice_test -c "CREATE EXTENSION IF NOT EXISTS vector;"
```

This is what this repo's `.env` and `phpunit.xml` are currently wired for (host port `5433`, so it won't clash with a system Postgres on `5432`).

**Option B — a native PostgreSQL install:**

If the `vector` extension isn't installed yet (check with `SELECT * FROM pg_available_extensions WHERE name = 'vector';`):

```bash
# Debian/Ubuntu — adjust the version suffix (18) to match your installed PostgreSQL
sudo apt-get update
sudo apt-get install -y postgresql-18-pgvector
```

Create the database, user, and enable the extension:

```bash
sudo -u postgres psql <<'SQL'
CREATE USER rag_practice WITH PASSWORD 'your-password';
CREATE DATABASE rag_practice OWNER rag_practice;
\c rag_practice
CREATE EXTENSION IF NOT EXISTS vector;
SQL
```

With this option, use `DB_PORT=5432` in `.env` (and update `DB_PASSWORD` to match).

### 4. Migrate the database

```bash
php artisan migrate
```

### 5. Run the app

```bash
composer dev
```

This runs the PHP dev server, Vite, log tailing, **and a queue worker** together — the queue worker is what actually processes uploaded PDFs into embeddings, so don't skip it (running `php artisan serve` alone will leave uploads stuck in `pending`).

Visit `http://localhost:8000`, register an account, and a personal team is created automatically.

## Using it

1. Sign up / log in.
2. Go to **Documents** in the sidebar and upload a PDF (max 20MB). Its status starts as `pending`, moves to `processing`, then `completed` (or `failed` with an error message) once the queue worker picks it up.
3. Once at least one document is `completed`, go to **RAG Query** and ask a question — the answer is generated from the most relevant embedded chunks, and past questions/answers are kept as history.
4. Check the **Dashboard** for document/embedding counts, storage used, and recent activity.

Everything above is scoped per team — switch teams with the team switcher in the sidebar to see a different team's documents and queries.

## Useful commands

Tests run against a separate `rag_practice_test` database (configured in `phpunit.xml`), created alongside the main one in step 3 above — the vector column requirements rule out SQLite's usual in-memory test DB.

```bash
# Backend
vendor/bin/pint --parallel      # PHP code style
php artisan test                # Pest test suite

# Frontend
npm run lint                    # ESLint (--fix)
npm run types:check             # TypeScript
npm run format                  # Prettier
npm run build                   # Production build
```

## Troubleshooting

- **`type "vector" does not exist`** — the pgvector extension isn't installed/enabled in your database; redo step 3.
- **Uploads stuck on `pending`/`processing` forever** — no queue worker is running. Use `composer dev` (recommended) or run `php artisan queue:listen` in a separate terminal.
- **RAG Query / uploads fail with an AI provider error** — `GEMINI_API_KEY` is missing or invalid in `.env`.
- **Switching to SQLite** — not supported for this feature; the `vector` column type requires Postgres, MySQL 9+, or MariaDB.
