---
status: accepted
---
# Categorisation runs on AI-derived rules, not on a runtime LLM call

The brief asks for AI-powered categorisation with the prompts in the repository. The supplied history has 20 distinct descriptions, and a per-request call to a language model would bill on every submission, including every test run, with non-deterministic answers. We therefore use the AI coding tool at build time: Claude Fable 5.1 derived an ordered keyword rule table from the data ([docs/prompts/01-derive-rules.md](../prompts/01-derive-rules.md)), the table is committed and transcribed one to one into `src/Categorisation/rules.php`, and the runtime categoriser is a pure, offline function of the description with Miscellaneous as the fallback.

## Considered options

- **Runtime LLM per transaction.** Handles unseen descriptions, costs money per request, non-deterministic, needs a key in every environment including CI.
- **Rules only (chosen).** Free, deterministic, testable to the row; blind to descriptions nobody has written a rule for, which land in Miscellaneous with `categorisation_source = fallback` so they are easy to find.
- **Rules first, LLM for the fallback.** The natural next step. The `Categoriser` interface exists so that implementation can be added without touching the handler; it is not shipped because it would add cost and a secret for no gain on this data.

## Consequences

Re-categorising stored rows when the table changes is a job this repository does not have; see docs/trade-offs.md.
