# Why plain PHP and MariaDB

The brief asks for the language and framework I am most comfortable with, and a reason. The honest answer is the stack I work in every day on the Worxit backend: PHP behind nginx, talking to MariaDB over PDO, packaged with Docker Compose, with no framework in between. This repository is that stack, written the way I would want a colleague to find it.

## What that buys

- **Nothing between the reader and the decisions.** A framework would give routing, validation and dependency injection for free, and a reviewer would then be looking at framework scaffolding to find mine. Here the router is forty lines, the validator says in plain code what each field must be, and the kernel wires four handlers by hand. Every design decision in the brief's list is visible in the source rather than in configuration.
- **The modern language, not the folk memory of it.** PHP 8.3 has typed properties, readonly classes, backed enums (`Category`, `TransactionType`), named arguments, first-class callable syntax (`Transaction::fromRow(...)`), `match`, and strict types. The code uses them; there is no untyped array soup.
- **One deliberate upgrade over the Worxit shape.** Worxit serves one `.php` file per endpoint. This repository has a single front controller and a router, because the brief's REST paths (`/transactions/{id}`) want it and because handlers that receive a `Request` and return a `Response` can be driven from a test without a web server. That is the whole reason the integration suite can run in-process.
- **MariaDB because the data is tabular and the invariants are the database's job.** The unique key on `reference` is what makes a repeated submission a `409`; `DECIMAL(14,2)` is what keeps money exact; `DATETIME(6)` round-trips the bank's microseconds. A document store would make all three my code's problem.
- **Docker Compose with everything baked in.** The host needs Docker and nothing else: Composer runs in a build stage, PHPUnit ships in the image, the schema and the test database are created by MariaDB's init scripts on first start.

## What it costs

- No framework means no ecosystem of middleware. Authentication, rate limiting and OpenAPI generation would each be a small hand-written piece. None is in the brief, so none is here, and [trade-offs.md](trade-offs.md) says so.
- PHP's request-per-process model means the rule table is loaded on every request. At 21 rules that is microseconds; at a thousand it would want an opcode-cached array or a compiled trie.
- A reviewer who expected a type-checked language gets PHP's runtime types instead. `declare(strict_types=1)` is on in every file, every signature is typed, and the tests cover the shapes the types cannot.

## Why not the alternatives I considered

- **Laravel or Slim.** More familiar to many reviewers, less honest about how I build, and the interesting parts of this exercise (the rule table, the single insert, the lazy connection) are framework-independent anyway.
- **Node or Python.** I can write both; I do not ship either daily. The brief asked for the one I am most comfortable with.
