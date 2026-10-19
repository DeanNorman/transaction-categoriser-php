# Prompt 0: the design interview

Before any code, the brief and the CSV were put to Claude Code (Claude Fable 5.1) with the instruction to interview me one decision at a time, recommending an answer for each, and to look up facts in the files rather than ask. The model profiled the CSV first (1000 rows, 20 distinct descriptions, no duplicate ids, the amount sign disagreeing with the type column in about half the rows) and then asked the questions below. My answers are the decisions the rest of the repository implements.

| # | Question put to me | Recommendation | Decision |
|---|---|---|---|
| 1 | Does "AI-powered categorisation" mean a runtime LLM call, or AI-derived rules that run offline? | Rules derived at build time, LLM behind an interface and off | Rules, derived by Fable; see [ADR 0001](../adr/0001-rules-at-runtime.md) |
| 2 | Plain PHP in the Worxit shape, or a micro-framework? | Plain PHP with one front controller | Plain PHP, no framework |
| 3 | What role does the CSV play at runtime? | Replay it through the endpoint as a seed | Source material only: rules and fixtures. The focus is one POST end to end |
| 4 | Who owns the transaction id? | Server-minted UUID, bank id kept as a unique `reference`, 409 on repeat | As recommended; see [ADR 0002](../adr/0002-server-minted-ids.md) |
| 5 | The amount sign disagrees with `type` in half the rows. Store as given, reject, or normalise? | Store as given; the description alone drives the category | As recommended; see [ADR 0003](../adr/0003-store-amounts-as-given.md) |
| 6 | Exact-description lookup or keyword rules? | Ordered keyword rules, first match wins, Miscellaneous fallback | As recommended |
| 7 | Categorisation on the transaction row, or its own record? | One row, one insert, synchronous | As recommended |
| 8 | `GET /transactions`: plain list, or filtering and paging? | Newest first, `?category=` filter, hard cap, no paging | Minimal, as recommended |
| 9 | How is the front-end served? | One static page from nginx in the same stack, no build step | As recommended |
| 10 | Where do integration tests run? | Inside the PHP container against a second database on the same MariaDB | As recommended |
| 11 | Repository name, location, commit style? | Public repo, one commit per journey step, no AI trailers, prompts in the docs | As recommended |
| 12 | The eight journey steps | Scaffold, request, categorisation, persistence, retrieval, front-end, integration tests, documentation | Accepted |

The rule table itself came from a second, narrower prompt: [01-derive-rules.md](01-derive-rules.md).
