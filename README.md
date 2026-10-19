# Transaction Categoriser (PHP)

A banking transaction categorisation API, built for the SOUS senior engineer assessment and written the way I build the Worxit backend: plain PHP 8.3, MariaDB, nginx, Docker Compose, no framework.

One transaction is `POST`ed, placed in one of ten predefined categories by a rule table that an AI coding tool derived from the supplied history, stored in a single insert, and read back by id or as a filtered list. A static page in the same stack shows the journey in a browser.

The repository is written as that journey: **eight steps, eight commits, eight documents** under [docs/journey](docs/journey/). Each says what the transaction has become, what the code does, what was decided and why.

**The deck:** [slides as a PDF](docs/sous-practice/transaction-categoriser.pdf) · [present in the browser](https://deannorman.github.io/transaction-categoriser-php/sous-practice/transaction-categoriser.html) · [source and notes](docs/sous-practice/)

## Run it

Requirements: Docker with Compose v2. Nothing else on the host.

```bash
docker compose up -d --build
curl -s localhost:8090/health        # {"data":{"status":"ok","database":"ok"}}
open http://localhost:8090           # the front-end page
```

Submit a transaction:

```bash
curl -s -X POST localhost:8090/transactions -H 'Content-Type: application/json' -d '{
  "reference": "TXN00001", "amount": -87.18, "timestamp": "2024-05-16 07:22:18.808433",
  "description": "Municipal Tax Payment", "type": "debit", "account_number": "NLINGB1944573686"}'
```

Read it back:

```bash
curl -s localhost:8090/transactions                      # newest first, ?category=Housing to filter
curl -s localhost:8090/transactions/<id>                 # one by id
```

Stop, keeping the data: `docker compose down`. Throw everything away: `docker compose down -v`.

## Test it

Both suites run inside the stack against a second database, so they never touch stored data:

```bash
docker compose run --rm php vendor/bin/phpunit                        # unit + integration, 93 tests
docker compose run --rm php vendor/bin/phpunit --testsuite unit       # the pure parts, no database
scripts/smoke.sh                                                      # 14 requests through nginx
```

## Where to look

| Question | Where |
|---|---|
| How does one transaction move through the system? | [docs/journey](docs/journey/), starting at [step 1](docs/journey/01-scaffold.md) |
| What are the endpoints, shapes and error codes? | [docs/api.md](docs/api.md) |
| How is a category decided, and which prompts produced the rules? | [docs/journey/03-categorisation.md](docs/journey/03-categorisation.md), [docs/prompts](docs/prompts/) |
| Why plain PHP and MariaDB? | [docs/language-choice.md](docs/language-choice.md) |
| What was traded off, and what comes next? | [docs/trade-offs.md](docs/trade-offs.md), [docs/adr](docs/adr/) |
| How was the AI coding tool used? | [docs/journey/08-documentation.md](docs/journey/08-documentation.md), [docs/prompts/00-design-interview.md](docs/prompts/00-design-interview.md) |
| What do the words mean? | [CONTEXT.md](CONTEXT.md) |
| The slides? | [docs/sous-practice](docs/sous-practice/): [PDF](docs/sous-practice/transaction-categoriser.pdf), [presentable HTML](https://deannorman.github.io/transaction-categoriser-php/sous-practice/transaction-categoriser.html), source with speaker notes |

## The shape of the code

```
public/           index.php (front controller), index.html + app.js + app.css (the page)
src/Http/         Request, Response, Router, Kernel, Handlers/ (one class per endpoint)
src/Transactions/ Category, TransactionType, TransactionInput, TransactionValidator,
                  Transaction, TransactionRepository
src/Categorisation/ Categoriser (interface), Rule, RuleTable, rules.php, RuleBasedCategoriser
src/Db/           Connection (lazy PDO)      src/Support/  Uuid, Clock
tests/Unit/       router, request, response, validator, rule table, categoriser, uuid, transaction
tests/Integration/ POST, both GETs, repository, health, against the test database
tests/fixtures/   the supplied transaction_history.csv
docker/           php/Dockerfile, nginx/default.conf, mariadb/*.sql (schema + test database)
docs/             journey/, api.md, trade-offs.md, language-choice.md, adr/, prompts/
```

## The journey, in one table

| Step | The transaction is… | Adds |
|---|---|---|
| [1. Scaffold](docs/journey/01-scaffold.md) | given somewhere to arrive | Compose stack, schema, `/health` |
| [2. Request](docs/journey/02-request.md) | received and validated | Router, Kernel, validator, `422` |
| [3. Categorisation](docs/journey/03-categorisation.md) | categorised | Rule table derived by Claude Fable 5.1, fallback |
| [4. Persistence](docs/journey/04-persistence.md) | stored | Single insert, UUID, `201`, `409` |
| [5. Retrieval](docs/journey/05-retrieval.md) | read back | `GET` by id, `GET` list with filter and cap |
| [6. Front-end](docs/journey/06-front-end.md) | visible in a browser | Static page over the three endpoints |
| [7. Integration tests](docs/journey/07-integration-tests.md) | proven against the real database | Second database, suite, smoke script |
| [8. Documentation](docs/journey/08-documentation.md) | documented | API reference, trade-offs, language choice |

## Licence

MIT.
