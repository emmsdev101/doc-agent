# DocAgent

DocAgent is a B2B knowledge-base product. Teams upload documents (PDF, Markdown, TXT, DOCX), the app chunks and embeds them, and a small chat widget can be pasted onto any site to answer questions from that private corpus.

Answers are grounded in retrieved passages. The model is instructed not to invent facts when the knowledge base has nothing relevant.

## How it works

1. An organization signs up and creates one or more knowledge bases.
2. Documents are uploaded and queued (`ProcessDocumentJob` on Redis).
3. Text is extracted, split into ~750-token chunks with overlap, and embedded.
4. Chunks are stored in PostgreSQL. If the `vector` extension is available, search uses pgvector (HNSW + cosine). Otherwise embeddings are stored as JSON and scored in PHP.
5. The embeddable widget (`/widget.js`) calls `/api/v1/chat`. The question is embedded, similar chunks are retrieved, and DeepSeek streams the reply over SSE.

**Chat and embeddings are separate APIs.** DeepSeek does not ship an embeddings endpoint. Chat uses DeepSeek; embeddings default to an OpenAI-compatible host such as [Jina](https://jina.ai/embeddings/) (`jina-embeddings-v3`, 1024 dimensions). `EMBEDDING_DRIVER=local` is a hash-based fallback for development only and is a poor semantic match.

## Stack

- Laravel 11, PHP 8.2+
- Inertia + React + Tailwind admin UI
- PostgreSQL 16+ (pgvector optional)
- Redis + queue worker (Horizon in Docker)
- DeepSeek for completions
- Jina (or any `/v1/embeddings` provider) for vectors
- Standalone IIFE widget with shadow DOM

## Local setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Point `.env` at PostgreSQL and Redis. Install pgvector if you want native vector search (`CREATE EXTENSION vector`). Then:

```bash
php artisan migrate --seed
npm run build
php artisan serve
```

In a second terminal:

```bash
php artisan queue:work redis --queue=documents,default --tries=3 --timeout=300
```

Seeded login: `admin@docagent.test` / `password`.

Docker (app, Horizon, pgvector Postgres, Redis):

```bash
docker compose up --build
```

### Important environment

| Variable | Purpose |
|---|---|
| `DEEPSEEK_API_KEY` | Widget chat completions |
| `EMBEDDING_DRIVER` | `openai` (any compatible host) or `local` |
| `EMBEDDING_API_KEY` | Jina / OpenAI / other embedding key |
| `EMBEDDING_BASE_URL` | e.g. `https://api.jina.ai/v1` |
| `EMBEDDING_MODEL` | e.g. `jina-embeddings-v3` |
| `EMBEDDING_DIMENSIONS` | Must match the model **and** the `document_chunks.embedding` column |
| `DOCUMENTS_DISK` | `documents` (local) or `s3` (R2 / S3 / B2) |
| `QUEUE_CONNECTION` | Use `redis` so uploads do not block the HTTP request |

After changing the embedding model or dimension, re-embed existing files (Re-embed on the knowledge-base page). Changing dimensions also requires a matching Postgres `vector(n)` column.

## Widget

Each knowledge base has a snippet:

```html
<script src="https://your-app.example/widget.js" data-kb-id="WIDGET-UUID" async></script>
```

Restrict `allowed_origins` in knowledge-base settings. APIs live at `/api/v1/widget/{token}` and `/api/v1/chat` (CORS + origin check + rate limit).

## Deploy notes

Render (and similar hosts) wipe the local disk on deploy, and a web service cannot share files with a separate worker. Use object storage (`DOCUMENTS_DISK=s3`, Cloudflare R2 recommended) so uploads and the queue worker see the same files. Run the queue worker as its own process. Put Postgres in the same region as the app; a remote cloud database from a laptop will add seconds of latency per query.

## Tests

```bash
php artisan test
```
