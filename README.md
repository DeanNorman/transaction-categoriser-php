# Transaction Categoriser (PHP)

A banking transaction categorisation API, built for the SOUS senior engineer assessment and written the way I build the Worxit backend: plain PHP, MariaDB, nginx, Docker Compose, no framework.

One transaction is POSTed, categorised into one of ten predefined categories by a rule table that an AI coding tool derived from the supplied history, stored in a single insert, and read back by id or as a filtered list. A static page in the same stack shows the journey in a browser.

The repository is written as that journey. Each step is one commit and one document under [docs/journey](docs/journey/).

## Quick start

```bash
docker compose up -d --build
curl -s localhost:8090/health
```

Then open <http://localhost:8090>.

Run the tests inside the stack:

```bash
docker compose run --rm php vendor/bin/phpunit
```

## Where to look

| Question | Where |
|---|---|
| How does a transaction move through the system? | [docs/journey](docs/journey/) |
| What are the endpoints? | [docs/api.md](docs/api.md) |
| How does categorisation work, and which prompts produced the rules? | [docs/journey/03-categorisation.md](docs/journey/03-categorisation.md), [docs/prompts](docs/prompts/) |
| Why plain PHP and MariaDB? | [docs/language-choice.md](docs/language-choice.md) |
| What was traded off? | [docs/trade-offs.md](docs/trade-offs.md), [docs/adr](docs/adr/) |
| What do the words mean? | [CONTEXT.md](CONTEXT.md) |

## Status

Step 1 of 8: the stack runs and answers `/health`. The remaining steps are added commit by commit.
