# Trade-offs and design decisions

What was chosen, what it cost, and what the next step would be. The three decisions that are hard to reverse have their own records in [adr/](adr/); the rest are here.

## Categorisation

**AI-derived rules at build time, not an LLM at runtime** ([ADR 0001](adr/0001-rules-at-runtime.md)). Free, deterministic, testable to the row; blind to descriptions nobody wrote a rule for. Those land in Miscellaneous with `categorisation_source = fallback`, so finding them is one `WHERE`. The `Categoriser` interface is where an LLM would slot in for the fallback case; it was left empty because, on this data, it would add cost and a secret for no gain.

**Keyword rules with word boundaries, first match wins.** Handles `ALBERT HEIJN 1234 AMSTERDAM` and `Uber Eats` versus `Uber`. The cost is that short tokens (`ns`, `cz`, `bp`, `ah`) are only as safe as their boundaries, and a bank that writes `NSInternational` without a space would miss. The fixture test fails the moment a new description appears in the CSV without an expectation, which is the guard against silent drift.

**Two judgement calls in the table.** Municipal tax is Housing rather than Utilities; a credit-card settlement is Miscellaneous rather than Shopping, to avoid counting the underlying purchases twice. Both are argued in [prompts/01-derive-rules.md](prompts/01-derive-rules.md) and both could be flipped by editing one line.

**No re-categorisation.** The category is written once, at submit. Change the rule table and old rows keep their old category. The next step is a separate `categorisations` table with history and a job that re-runs the table over stored rows; it was not built because the brief does not ask for it and it would double the schema for a feature nobody exercises.

## Data model

**The server mints ids; the bank's id is a unique reference** ([ADR 0002](adr/0002-server-minted-ids.md)). Idempotency is only as strong as the client's use of `reference`: omit it and the same transaction can be stored twice. That is the client's choice, and the alternative (fingerprinting amount, timestamp and description) would wrongly merge two identical coffees.

**Amount and type stored exactly as submitted** ([ADR 0003](adr/0003-store-amounts-as-given.md)). The supplied history disagrees with itself about direction in half its rows. Nothing is rejected, nothing is repaired, nothing downstream depends on direction. A derived `direction` field is the next step once the bank's convention is known.

**Timestamps without a zone are UTC.** The bank's CSV has no zone. Assuming UTC is the only assumption that round-trips without inventing an offset; the API says so and returns everything in UTC.

**One table.** The transaction and its categorisation are one row, written in one statement. Simplest thing that can be correct; see "no re-categorisation" for what it rules out.

## API

**Minimal list, capped at 500, one filter.** The brief says "retrieve all categorised transactions"; returning an unbounded table is a denial of service waiting to happen, so the list is capped and says so in `meta.cap`. Paging (`limit`/`offset`, or a cursor on `created_at, id`) is the obvious next step and is deliberately absent, as is any filter beyond category.

**Validation reports every failing field at once.** Costs a few lines in the validator; saves a client six round trips.

**Non-UUID ids are a 404, not a 400.** The shape of our ids is not a contract clients are told about.

**No authentication, no rate limiting, no CORS.** Not in the brief; the page shares the API's origin. Each would be a small handler-wrapping piece in the kernel.

**No bulk import endpoint.** The brief provides the history as a CSV and this repository uses it as source material for the rule table and the test fixture. The system's contract is one transaction per POST, and the journey documentation follows exactly that. A CSV import would be a thin loop over the same handler; it was left out to keep the API at the three endpoints the brief names.

## Stack and operations

**Plain PHP, no framework** ([language-choice.md](language-choice.md)). Every decision is visible in the source; every piece of infrastructure a framework would provide is hand-written or absent.

**Schema by init script, no migration tool.** MariaDB runs `docker/mariadb/*.sql` on first start only. A schema change after that means `docker compose down -v`. Right for an assessment, wrong for production, where a migrations directory applied on deploy would replace it.

**PHPUnit in the production image.** The image is larger than it should be, and the test command is one line with no host dependencies. Splitting a `test` build target off is a five-line change when it matters.

**Integration tests in-process, smoke through nginx.** The suite drives `Kernel::handle` against the real test database; nginx and PHP-FPM are covered by `scripts/smoke.sh`. Running PHPUnit over HTTP would have needed a second web and PHP pair pointed at the test database, for no extra confidence about our own code.

**The connection is opened lazily, once per request.** A request that never touches the database never connects, and a database outage is a `503` from `/health` and a JSON `500` from a write, never a blank page. There is no connection pool because PHP-FPM gives each request its own process; persistent PDO connections would be the next step under load.

**Port 8090 and project name `transaction-categoriser-php`** so this stack runs beside another local stack without collisions.

## What I would do next, in order

1. Paging on `GET /transactions`.
2. A `categorisations` history table and a re-run job, so rule changes reach stored rows.
3. The LLM-backed `Categoriser` for the fallback case, consulted only when the rules miss, with the answer cached as a new rule candidate for review.
4. Authentication, then rate limiting, as kernel middleware.
5. A migrations directory applied on deploy, replacing the init scripts.
