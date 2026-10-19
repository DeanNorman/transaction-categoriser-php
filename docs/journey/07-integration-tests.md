# Step 7. The journey, proven against the real database

The unit tests from steps 2 to 4 cover the pure parts: routing, validation, the rule table, serialisation. This step adds the integration suite, which drives the real `Kernel` against a real MariaDB, and a smoke script that walks the journey through nginx. One command runs both PHPUnit suites inside the stack:

```bash
docker compose run --rm php vendor/bin/phpunit
```

## The second database

`docker/mariadb/20-test-database.sql` creates `transactions_test` on first start, copies the table with `CREATE TABLE ... LIKE`, so the schema has exactly one definition, and grants the application user access. Compose passes `TEST_DB_NAME=transactions_test` to the PHP container. `IntegrationTestCase` refuses to run if that variable is missing or equal to `DB_NAME`, so the suite can never empty the application's table by misconfiguration. Every test starts from an empty table.

## What the suite drives

Requests go through `Kernel::handle(Request)`, the exact call the front controller makes, with a `Connection` pointed at the test database. Only nginx and PHP-FPM are outside the loop.

- **`SubmitTransactionTest`**: a valid POST is `201`, is categorised, and the row in the table matches the response to the microsecond; an unknown description is the fallback; a repeated reference is `409` and stores nothing; reference-less submissions are always accepted; `422` and `400` store nothing; an ISO timestamp with an offset is stored in UTC.
- **`RetrieveTransactionTest`**: what POST returned is what GET by id returns, byte for byte; unknown and malformed ids are `404`; the list is newest first with `meta`; the category filter; an unknown category is `422`; an empty table lists nothing.
- **`TransactionRepositoryTest`**: the round trip of every field through the real schema, the unique key enforced by the database, ordering and the limit, filtering by category. The `Clock` is held still so `categorised_at` is asserted exactly.
- **`HealthTest`**: `200` when the database answers; `503` from `/health` and a JSON `500` from a write when it does not, so an outage is never a blank page.

## The smoke script

`scripts/smoke.sh` runs on the host against the running stack and checks the status of fourteen requests over HTTP, from `/health` through the whole POST, GET and error matrix to `405` and `404`. It is the one check that includes nginx's routing and PHP-FPM.

```bash
docker compose up -d --build && scripts/smoke.sh
```

## Decisions taken here

- **In-process kernel, real database.** The handlers, repository and schema are what can be wrong; the HTTP transport is nginx's job and a fourteen-line smoke script covers it. Running the suite over HTTP would have needed a second PHP-FPM and nginx pair pointed at the test database, for no extra confidence about our own code.
- **A second database on the same server, not SQLite.** SQLite would not exercise the `ENUM`, the `DECIMAL`, the microsecond `DATETIME(6)` or the duplicate-key error code that the repository translates.
- **PHPUnit ships in the image.** It makes the image larger than a production image should be, and it makes the test command one line with no host dependencies. For an assessment that is the right trade.

## Numbers

| Suite | Tests | Assertions |
|---|---|---|
| unit | 72 | 267 |
| integration | 21 | 67 |
| smoke | 14 requests | status codes |

Previous: [Step 6](06-front-end.md). Next: [Step 8. Documentation](08-documentation.md).
