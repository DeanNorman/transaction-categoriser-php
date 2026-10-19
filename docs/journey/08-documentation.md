# Step 8. The documentation

The last step adds the reference material the brief asks for and closes the journey.

## What exists after this step

- **[README.md](../../README.md)**: setup and running instructions, the one test command, and a map of the repository.
- **[docs/api.md](../api.md)**: every endpoint, the transaction shape, every status and error code.
- **[docs/language-choice.md](../language-choice.md)**: why plain PHP and MariaDB, what it buys, what it costs.
- **[docs/trade-offs.md](../trade-offs.md)**: every decision with its cost and its next step, grouped by categorisation, data model, API and operations.
- **[docs/adr/](../adr/)**: the three decisions that are hard to reverse: rules at runtime, server-minted ids, amounts stored as given.
- **[docs/prompts/](../prompts/)**: the design interview that produced the decisions, and the prompt and answer that produced the rule table.
- **[CONTEXT.md](../../CONTEXT.md)**: the glossary. Transaction, Reference, Description, Type, Category, Categorisation, Rule, Fallback, Journey.

## The journey, in one place

| Step | The transaction is… | Commit adds |
|---|---|---|
| [1](01-scaffold.md) | given somewhere to arrive | Compose stack, schema, `/health` |
| [2](02-request.md) | received and validated | Request, Response, Router, Kernel, validator, `422` |
| [3](03-categorisation.md) | categorised | Rule table from Fable, `RuleBasedCategoriser`, fallback |
| [4](04-persistence.md) | stored | Repository, UUID, single insert, `201`, `409` |
| [5](05-retrieval.md) | read back | `GET` by id, `GET` list with filter and cap |
| [6](06-front-end.md) | visible in a browser | Static page over the three endpoints |
| [7](07-integration-tests.md) | proven against the real database | Second database, integration suite, smoke script |
| [8](08-documentation.md) | documented | This step |

## How the AI coding tool was used

Claude Code with Claude Fable 5.1 was used in two distinct ways, and the repository keeps the record of both:

1. **As an interviewer before any code.** The brief and the CSV were handed over with the instruction to look up facts in the files, ask one decision at a time, and recommend an answer for each. The twelve questions and my answers are [prompts/00-design-interview.md](../prompts/00-design-interview.md). Every decision in this repository traces to a row of that table.
2. **As the deriver of the rule table.** A narrow prompt with the data profile and explicit constraints produced the 21 rules, the ambiguities and the expected distribution: [prompts/01-derive-rules.md](../prompts/01-derive-rules.md). The code transcribes that answer; the tests pin it.

The commits were written step by step, each verified against the running stack before the next began. No commit carries an AI attribution trailer; the attribution is this section and the prompts directory, where it can be read.

Previous: [Step 7](07-integration-tests.md).
