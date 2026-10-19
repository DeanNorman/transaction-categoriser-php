---
marp: true
theme: default
paginate: true
size: 16:9
style: |
  section { font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; font-size: 26px; padding: 56px 64px; }
  h1 { font-size: 44px; margin-bottom: 8px; }
  h2 { font-size: 36px; color: #0b5fff; margin-bottom: 18px; }
  h3 { font-size: 28px; margin: 12px 0 6px; }
  code { font-size: 0.85em; }
  pre { font-size: 20px; line-height: 1.35; }
  table { font-size: 22px; }
  section.title { display: flex; flex-direction: column; justify-content: center; }
  section.title h1 { font-size: 56px; }
  section.title p { color: #5c6670; font-size: 28px; }
  .muted { color: #5c6670; }
  .two { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; }
  .pill { display: inline-block; padding: 2px 12px; border-radius: 999px; border: 1px solid #d9dee4; font-size: 20px; margin-right: 6px; }
  footer { color: #9aa5b1; font-size: 16px; }
footer: "Transaction Categoriser (PHP) · DeanNorman/transaction-categoriser-php"
---

<!-- _class: title -->
<!-- _paginate: false -->
<!-- _footer: "" -->

# Banking Transaction Categorisation

One transaction's journey through a plain-PHP backend

Dean Norman · SOUS senior engineer assessment · October 2026

<!--
Opening line: "I built this the way I build the backend I work on every day, and I wrote the repository as the journey of one transaction. I'd like to walk that journey with you, then take questions."
Time budget: 12 to 15 minutes for the deck, the rest for questions. Keep slides 7 to 10 as the core; the rest can be skimmed.
-->

---

## The brief, and where each line is answered

| Requirement | Where |
|---|---|
| Accept and categorise transaction history (CSV) | `POST /transactions`; CSV as source material for rules and fixtures |
| Ten predefined categories | `Category` enum, in the brief's order |
| API to submit, list, fetch by id | the three endpoints, `docs/api.md` |
| AI-powered categorisation, prompts in the repo | rules derived by Claude Fable 5.1, `docs/prompts/` |
| Language and framework, with reasoning | plain PHP 8.3 + MariaDB, `docs/language-choice.md` |
| Docker Compose | three services, port 8090 |
| Documentation with trade-offs | `docs/trade-offs.md`, three ADRs |
| Unit and integration tests | 93 tests inside the stack, plus a smoke script |
| Small, logical commits | eight steps, eight commits, eight documents |

<!--
Say: every requirement maps to a place in the repo. The reviewer should never have to hunt.
If asked "why not a CSV import endpoint": the brief names three endpoints; the CSV is the data the rules were derived from. A bulk import is a loop over the same handler. I kept the API at what the brief asks for.
-->

---

## What the data said before I wrote any code

<div class="two">
<div>

**1000 rows, 20 distinct descriptions**

Albert Heijn, AH Online, NS, Car Lease, Eneco, Ziggo, T-Mobile, Netflix, Bol.com, Rent, Municipal Tax, plus salary, tax refund, transfers, ATM, PayPal, iDEAL, credit-card settlement.

**Three Dutch banks**, no duplicate ids, no malformed rows.

</div>
<div>

**One catch**

The sign of `amount` disagrees with `type` in about half the rows: negative credits, positive debits.

Decision: store both exactly as given, never let direction influence the category.

<span class="muted">ADR 0003</span>

</div>
</div>

<!--
This slide shows I looked before I built. The sign/type mismatch is the thing a reviewer may ask about; the answer is "I noticed, I did not repair money values, and I documented it".
Profiling was one shell line: cut, sort, uniq -c.
-->

---

## Twelve decisions before the first commit

I had the AI tool interview me, one decision at a time, with a recommendation for each. The record is `docs/prompts/00-design-interview.md`.

<span class="pill">rules, not a runtime LLM</span> <span class="pill">plain PHP, one front controller</span> <span class="pill">CSV is source material</span> <span class="pill">server-minted ids + unique reference</span> <span class="pill">amounts stored as given</span> <span class="pill">keyword rules, first match, Miscellaneous fallback</span> <span class="pill">one row, one insert</span> <span class="pill">minimal list, cap 500</span> <span class="pill">static page, same origin</span> <span class="pill">integration tests on a second database</span> <span class="pill">one commit per step</span> <span class="pill">eight steps</span>

Every later choice traces back to one of these.

<!--
Key message: the AI was used first as a thinking partner, not a code generator. The interview format forced each decision into the open before it was baked into code.
-->

---

## The stack

```
  browser / curl
        │
        ▼
  ┌───────────┐   static files from public/     ┌───────────────┐
  │   nginx   │ ───────────────────────────────▶ │ index.html    │
  │  :8090    │   everything else                │ app.js / css  │
  └─────┬─────┘                                  └───────────────┘
        ▼ fastcgi
  ┌───────────┐   public/index.php → Kernel → Router → Handlers
  │  php-fpm  │   src/Http · src/Transactions · src/Categorisation
  └─────┬─────┘
        ▼ PDO (lazy, one connection per request)
  ┌───────────┐   transactions         (application)
  │  MariaDB  │   transactions_test    (integration suite)
  └───────────┘
```

Three containers, one `docker compose up -d --build`, nothing on the host but Docker.

<!--
Point out: Composer and PHPUnit are baked into the image. The second database is created by MariaDB's init scripts on first start, with the table copied LIKE the real one, so there is one schema definition.
-->

---

## The journey of one transaction

```
POST /transactions
   │
   ├─ Request::fromGlobals        decode JSON, remember if it was malformed     400
   ├─ Router                      method + path, {id} segments                  404 / 405
   ├─ TransactionValidator        every field checked, all failures at once     422
   ├─ RuleBasedCategoriser        first matching rule wins, else fallback
   ├─ TransactionRepository       mint UUID, one INSERT, unique reference       409
   └─ Response::ok(201)           the stored row, same shape as every GET
```

Each line is a step, a commit and a document in `docs/journey/`.

<!--
This is the spine of the talk. Say it slowly. If there is one slide to remember, it is this one.
"The handler is four calls in this order. Everything else in the repo exists to make one of those four calls honest."
-->

---

## Step 2 · Validation says everything at once

```json
POST {"amount": "lots", "type": "wire"}

422 {"error": {"code": "validation_failed",
               "message": "One or more fields are invalid",
               "fields": {"amount": "must be a decimal number such as -87.18",
                          "timestamp": "is required",
                          "description": "is required",
                          "type": "must be debit or credit",
                          "account_number": "is required"}}}
```

- Field names are the CSV columns in snake_case; unknown fields are ignored
- Money is a decimal string from the moment it is normalised, never a float downstream
- The bank's `2024-05-16 07:22:18.808433` and ISO 8601 both accepted; no zone means UTC

<!--
Why "all at once": a client fixing a form should not need six round trips to learn six mistakes.
Why strings for amounts: DECIMAL(14,2) in the database, and a float would lose the cents somewhere.
-->

---

## Step 3 · How a category is decided

<div class="two">
<div>

**21 ordered rules, 9 categories, one fallback**

- Case-insensitive, word-bounded patterns
- First match wins; Dining Out sits before Transportation so `Uber Eats` beats `Uber`
- No rule maps to Miscellaneous: it is only ever the fallback, and the source says so
- The description is the only input

</div>
<div>

**On the supplied 1000 rows**

| Category | Rows |
|---|---|
| Miscellaneous | 444 |
| Utilities | 146 |
| Transportation | 114 |
| Housing | 101 |
| Groceries | 95 |
| Entertainment | 51 |
| Shopping | 49 |

Dining Out, Healthcare, Education: rules exist, no examples in the data.

</div>
</div>

<!--
Two judgement calls to be ready for: Municipal Tax → Housing (it follows the property, not consumption). Credit-card settlement → Miscellaneous (the spending it covers was categorised when it happened; counting it again double counts).
Both are one-line changes and both are argued in the prompt record.
-->

---

## How the AI coding tool was used

**Prompt 0: the design interview.** The brief and the CSV, with the instruction to look up facts in the files, ask one decision at a time, and recommend an answer. Twelve questions, twelve answers, every one implemented.

**Prompt 1: deriving the rule table.** The data profile (20 descriptions with counts) and the ten categories, with constraints: word boundaries, no Miscellaneous rule, cover the three empty categories, stable ids, call out ambiguities and ordering.

The answer is transcribed one to one into `rules.php`. The tests pin all 20 descriptions, the whole fixture and the distribution above, so the transcription cannot drift.

**What was not done:** no LLM at runtime. `Categoriser` is an interface; the LLM implementation is the empty slot behind it. ADR 0001 says why.

<!--
If asked "isn't rules-only a cop-out on 'AI-powered'": the brief says "with any AI coding tool of your choice, include the prompts". I used the tool to derive the categoriser and I included the prompts. A per-request LLM call on 20 known descriptions would add cost, a secret and non-determinism for nothing. The interface is where it goes when the data outgrows the rules.
-->

---

## Step 4 · Storage, and the two things the database enforces

```sql
INSERT INTO transactions (id, reference, amount, occurred_at, description, type,
  account_number, category, categorisation_source, categorised_at, created_at)
```

- **The server mints the id** (UUID v4). The bank's `TXN00001` is kept as `reference`, optional and unique. A repeat is `409`; a submission without a reference is always accepted. <span class="muted">ADR 0002</span>
- **One row, one statement.** Transaction and categorisation together; no pending state.
- **`DECIMAL(14,2)`** keeps money exact; **`DATETIME(6)`** round-trips the bank's microseconds; the **unique key** is what turns a duplicate into a `409`, not application code.
- **The connection is lazy.** Wiring never touches the database; an outage is a `503` from `/health` and a JSON `500` from a write, never a blank page.

<!--
"Why not let the client supply the id": then the API trusts a key space it does not control, and a bank-format id shapes the URL design.
"Why not fingerprint duplicates by amount+time+description": two identical coffees would merge.
-->

---

## Step 5 · Reading it back

| Request | Answer |
|---|---|
| `GET /transactions/{id}` | the same JSON the POST returned, byte for byte |
| `GET /transactions/TXN00001` | `404`, the id shape is not a contract clients are told |
| `GET /transactions` | newest first, at most 500, `meta: {count, cap, category}` |
| `GET /transactions?category=Housing` | one indexed `WHERE`; the index was created in step 1 for this |
| `GET /transactions?category=Pets` | `422`, with the ten valid names |

No paging, on purpose. The cap says so in `meta.cap`; paging is the first item on the "next" list.

<!--
"Why a cap instead of paging": the brief says "retrieve all". Unbounded is a denial of service waiting to happen; a cap is the smallest honest answer, and the meta block tells the client it hit it.
-->

---

## Step 6 · The journey in a browser

One static page from `public/`, same origin as the API, no build step.

- **Left:** the POST form, with sample rows from the history plus two the history does not contain
- **Middle:** the list, the category filter, the count
- **Right:** fetch by id; filled automatically after a submit or a row click

Every response shown is the API's own JSON. The page adds nothing the API does not say, including the `422` field map and the `409`.

<!--
If demoing live: Fill with a sample row → Submit → 201 with the Housing badge. Submit again → 409. Change the filter to Housing. Click a row.
If the demo cannot run: the screenshot in docs/journey/06-front-end.md describes the same sequence.
-->

---

## Step 7 · Proof

| Suite | What it drives | Count |
|---|---|---|
| Unit | router, request, response, validator, rule table, categoriser, UUID, serialisation | 72 tests |
| Integration | the real `Kernel` against `transactions_test` on the same MariaDB | 21 tests |
| Smoke | 14 requests through nginx and PHP-FPM, status codes | `scripts/smoke.sh` |

```bash
docker compose run --rm php vendor/bin/phpunit     # 93 tests, 334 assertions
scripts/smoke.sh                                    # smoke passed
```

The integration base class refuses to run if the test database is the application database. Every test starts from an empty table. The `Clock` is held still so timestamps are asserted exactly.

<!--
"Why in-process rather than over HTTP": the handlers, repository and schema are what can be wrong. nginx is covered by the smoke script. PHPUnit over HTTP would need a second web+php pair pointed at the test database for no extra confidence about our own code.
"Why MariaDB and not SQLite for tests": SQLite would not exercise ENUM, DECIMAL, DATETIME(6) or the duplicate-key error code the repository translates.
-->

---

## Trade-offs I would defend

| Chose | Gave up | Next step |
|---|---|---|
| Rules at runtime | unseen descriptions | LLM behind `Categoriser` for the fallback case |
| Category written once, on submit | rule changes reach old rows | `categorisations` history table + re-run job |
| Reference-based idempotency | protection when the client omits it | nothing; fingerprinting merges real duplicates |
| Cap of 500, no paging | large histories | cursor on `(created_at, id)` |
| Init SQL, no migration tool | schema changes after first start | migrations applied on deploy |
| PHPUnit in the image | image size | a `test` build target |
| No auth, no rate limit | production readiness | kernel middleware |

<!--
Lead with the first two; they are the ones that show judgement. The rest are "known, small, listed".
-->

---

## Why plain PHP and MariaDB

- It is the stack I ship daily; the brief asked for the one I am most comfortable with
- No framework between the reader and the decisions: the router is forty lines, the validator says what each field must be, the kernel wires four handlers by hand
- PHP 8.3 as it is now: enums, readonly, named arguments, first-class callables, strict types everywhere
- One deliberate upgrade on the shape I work in: a single front controller, so handlers take a `Request` and return a `Response` and can be driven from a test without a web server
- MariaDB because the invariants (unique reference, exact money, microsecond timestamps) belong to the database

<!--
If asked about Laravel: more familiar to many reviewers, less honest about how I build, and the interesting parts here are framework-independent.
If asked about Node or Python: I can write both; I do not ship either daily.
-->

---

## Questions I expect, answers I have

- **"Walk me through a POST."** Slide 6, four calls, in order.
- **"What happens if the same transaction is sent twice?"** With a reference: 409, nothing stored. Without: two rows, the client's choice.
- **"How do you add a category or a merchant?"** One line in `rules.php`; the fixture test fails until the expectation table agrees.
- **"Why is `Salary` Miscellaneous?"** The fixed list has no income category; the fallback says so in `categorisation_source`.
- **"Where would an LLM go?"** Behind `Categoriser`, consulted only on fallback, answers cached as rule candidates for review.
- **"What is not production-ready?"** Auth, rate limiting, paging, migrations, re-categorisation. All listed, all small, all deliberately absent.

<!--
End on: "The repository is the talk. Eight commits, eight documents, and the prompts that produced the design. Happy to open any file."
-->

---

<!-- _class: title -->
<!-- _paginate: false -->
<!-- _footer: "" -->

# Thank you

github.com/DeanNorman/transaction-categoriser-php

<span class="muted">Start at README.md, then docs/journey/01-scaffold.md</span>
