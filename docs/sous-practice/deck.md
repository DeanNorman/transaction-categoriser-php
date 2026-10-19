---
title: Banking Transaction Categorisation
subtitle: One transaction is posted, placed in one of ten categories by rules an AI tool derived from the supplied history, stored once, and read back; the repository is written as that journey.
task: Senior engineer assessment · take-home
short: Transaction Categoriser · PHP
date:
label: Take-home assessment
slug: transaction-categoriser
band: Run it: `docker compose up -d --build` · 93 tests pass · github.com/DeanNorman/transaction-categoriser-php
---

## What I built
> A categorisation API in plain PHP and MariaDB, with every decision traceable to a prompt or a test

### For
A reviewer checking the brief: three endpoints, ten categories, Compose, tests, and the AI tool's prompts in the repository.

### Done means
- `POST /transactions` validates, categorises and stores one row: `201`, `422` or `409`.
- Both `GET`s return the shape the POST returned, by id or filtered by category.
- 93 tests inside the stack; 14 smoke requests through nginx.
- Eight steps, eight commits, eight documents.

### Left out
- No CSV import, paging, authentication or runtime LLM call. Each is listed with its next step.

Note: Opening line: "I built this the way I build the backend I work on every day, and I wrote the repository as the journey of one transaction." Twelve minutes for the deck, the rest for questions.

## See it
> Submit a row from the history and the page shows the category, the rule that decided it, and the raw JSON

![Local run · demo data](http://localhost:8090/?shot=1)

Note: Live: click "Fill with a sample row", then Submit: 201 with the Housing badge. Submit again: 409. Set the filter to Housing. Click a row. The page shows only what the API returns.

## How it works
> Four calls in order: validate, categorise, insert, answer

1. [code] `TransactionValidator` checks every field and reports every failure at once as a `422` field map. Money becomes a decimal string, never a float.
2. [code] `RuleBasedCategoriser` walks 21 ordered, word-bounded keyword rules; first match wins; no match is Miscellaneous with the source `fallback`.
3. [code] `TransactionRepository` mints a UUID and writes transaction and category in one `INSERT`; the unique key on the bank's reference turns a repeat into `409`.
4. [person] The reviewer reads `categorisation.source` on every row: the rule id, or `fallback`, so a wrong category is one line in `rules.php`.

> **1 insert** per transaction, one rule per category decision, no network calls at runtime

Note: The handler is these four calls. Everything else in the repository exists to make one of them honest.

## Decisions {dark}
> The AI tool derived the rules once; the runtime is deterministic and free

| Decision | Why | What I gave up |
|---|---|---|
| Rules derived by Claude Fable 5.1 at build time, prompts committed | 20 distinct descriptions in 1000 rows; a per-request LLM call adds cost, a secret and non-determinism for nothing | Unseen descriptions land in Miscellaneous; the `Categoriser` interface is where an LLM would go |
| Server-minted UUID, bank id kept as a unique `reference` | Our URL space, idempotency from the reference, reference-less rows still accepted | A client that omits the reference can store a row twice |
| Amount and type stored exactly as submitted | The history disagrees with itself about direction in half its rows; repairing money values is worse than keeping them | No derived direction field yet |
| Plain PHP, one front controller, no framework | The stack I ship daily; the router is forty lines and every decision is visible | Middleware, auth and OpenAPI would be hand-written |

Note: Municipal Tax is Housing (it follows the property). Credit-card settlement is Miscellaneous (the spending it covers was categorised when it happened). Both are argued in docs/prompts/01-derive-rules.md.

## What I measured
> Every description in the supplied history lands where the prompt said it would

- [stat] **93** tests pass, unit and integration, inside the stack
- [stat] **20/20** descriptions pinned to a category and a rule
- [stat] **1000** fixture rows categorise to the expected distribution
- [stat] **14** requests through nginx in the smoke script, all expected statuses

```
docker compose run --rm php vendor/bin/phpunit     OK (93 tests, 334 assertions)
scripts/smoke.sh                                    smoke passed
```

Note: The distribution on the 1000 rows: Miscellaneous 444, Utilities 146, Transportation 114, Housing 101, Groceries 95, Entertainment 51, Shopping 49. Dining Out, Healthcare and Education have rules but no examples in the data.

## How production-ready it is
> Correct and safe for one client on one box; scale and operations are the listed next steps

### Done
- [done] Every input validated; all failures reported at once; unknown fields ignored
- [done] Duplicate submissions refused by the database, not by application code
- [done] A database outage is a `503` from `/health` and a JSON `500` from a write, never a blank page
- [done] Integration tests on a second database; the suite refuses to run against application data
- [done] List capped at 500 and says so in `meta.cap`

### Left
- [left] Paging on the list
- [left] Authentication and rate limiting as kernel middleware
- [left] A migrations directory instead of first-start init SQL
- [left] Re-categorising stored rows when the rule table changes

## What not to trust yet
> The rules know twenty descriptions; a bank that writes them differently will reach the fallback

- A description outside the twenty, or spelled without the spaces the patterns expect (`NSInternational`), is Miscellaneous until someone adds a rule.
- Idempotency depends on the client sending `reference`; without it, nothing stops a second row.
- Timestamps without a zone are taken as UTC; the bank's convention was not stated.
- The sign of `amount` and the `type` column disagree in half the supplied rows; both are stored as given and neither drives the category.
- PHPUnit ships in the production image; it should move to a test build target before this is deployed.

## Next steps
> Next: paging, then re-categorisation, then an LLM behind the fallback

1. Cursor paging on `GET /transactions` over `(created_at, id)`.
2. A `categorisations` history table and a job that re-runs the rule table over stored rows.
3. An LLM-backed `Categoriser` consulted only when the rules fall back, its answers cached as rule candidates for review.

Note: End on: "The repository is the talk. Eight commits, eight documents, and the prompts that produced the design. Happy to open any file."
