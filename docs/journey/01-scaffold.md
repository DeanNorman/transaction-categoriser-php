# Step 1. The stack the transaction will travel through

Before a transaction can arrive, there has to be somewhere for it to arrive. This step stands up the three services and the table that the rest of the journey writes to.

## What exists after this step

- **`docker-compose.yml`** with three services: `db` (MariaDB 11.8), `php` (PHP 8.3 FPM, built from `docker/php/Dockerfile`), `web` (nginx, port 8090 on the host). The database has a health check and PHP waits for it.
- **`docker/mariadb/10-schema.sql`**, run by MariaDB on first start. One table, `transactions`, with the transaction's own columns and the categorisation columns side by side. The shape is decided here because step 4 writes both in one insert.
- **`public/index.php`**, the front controller. For now it answers only `GET /health`, which opens a database connection and reports whether it worked.
- **`src/Db/Connection.php`**, the one place that knows how to open PDO to MariaDB, configured from the environment so the same image can point at a different database under test.
- **`docker/nginx/default.conf`**: static files from `public/` are served directly, everything else goes to the front controller. The front-end page in step 6 will live beside the API without a second server.

## Decisions taken here

- **No framework.** The Worxit backend is plain PHP endpoints behind nginx; this repository keeps that shape and tightens it with a single front controller so the brief's REST paths route cleanly. See [docs/language-choice.md](../language-choice.md).
- **Dependencies are baked into the image.** `composer install` runs in a build stage; the host needs only Docker. PHPUnit ships in the image so the tests run inside the stack with one command.
- **Port 8090**, not 8080, so this stack can run beside another local stack.

## Try it

```bash
docker compose up -d --build
curl -s localhost:8090/health
# {"data":{"status":"ok","database":"ok"}}
```

Next: [Step 2. The request arrives](02-request.md).
