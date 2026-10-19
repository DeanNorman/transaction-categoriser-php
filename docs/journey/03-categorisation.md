# Step 3. The transaction is categorised

The validated `TransactionInput` from step 2 now passes through the categoriser. The output is a `Categorisation`: one of the ten categories, the id of the rule that decided it (or `fallback`), and the text that matched. Storage is still a step away, so `POST /transactions` keeps answering `501`, but the message now names the category the transaction would be stored with.

## How the category is decided

`App\Categorisation\RuleBasedCategoriser` walks an ordered table of rules. Each rule is a case-insensitive, word-bounded pattern paired with a category. The first rule whose pattern matches the description wins. If none matches, the result is Miscellaneous with the source `fallback`. The description is the only input; the amount, the type and the account play no part.

The table lives in [`src/Categorisation/rules.php`](../../src/Categorisation/rules.php): 21 rules covering nine categories. No rule maps to Miscellaneous. Rules carry a note saying why they exist and how many rows of the supplied history they account for.

## Where the rules came from

Claude Fable 5.1 derived them from the supplied history. The CSV was profiled first (`cut | sort | uniq -c`), which showed only 20 distinct descriptions across 1000 rows. That profile and the ten categories were put to the model with constraints: word-bounded patterns, no rule for Miscellaneous, rules for the three categories with no examples in the data, a stable id and a note per rule, and ambiguities called out. The prompt and the answer are in [docs/prompts/01-derive-rules.md](../prompts/01-derive-rules.md), and the code is a one-to-one transcription of that answer.

The result on the supplied history:

| Category | Rows | Decided by |
|---|---|---|
| Groceries | 95 | Albert Heijn, AH Online |
| Utilities | 146 | Eneco, Ziggo, T-Mobile |
| Transportation | 114 | NS, Car Lease |
| Entertainment | 51 | Netflix |
| Shopping | 49 | Bol.com |
| Housing | 101 | Rent, Municipal Tax |
| Miscellaneous | 444 | fallback: salary, tax refund, transfers, ATM, PayPal, iDEAL, credit-card settlement |
| Dining Out, Healthcare, Education | 0 | rules exist, no examples in the data |

Two of those placements were judgement calls, and the prompt record says why: municipal tax is Housing rather than Utilities, and a credit-card settlement is Miscellaneous rather than Shopping, because the spending it covers was already categorised when it happened.

## The LLM slot that is deliberately empty

`App\Categorisation\Categoriser` is an interface. The rule-based implementation is the only one shipped. An LLM-backed implementation would be consulted when the rules fall back, and would sit behind the same interface without touching the handler. It is not shipped because, on this data, it would add a per-request cost and a secret in every environment for no gain. [ADR 0001](../adr/0001-rules-at-runtime.md) records the trade-off.

## What the tests pin

- Every one of the 20 descriptions lands on its expected category and rule.
- The expectation table covers every distinct description in the fixture, so a new description in the CSV fails a test until someone decides where it belongs.
- The distribution over the 1000 rows (the table above).
- Variants the history does not contain: upper case with a store number, extra whitespace, `Uber Eats` as food but `Uber` as a ride, and the word boundaries that keep `rental` out of Housing and `transactions` out of Transportation.
- The rule table is well formed: unique ids, compiling patterns, no Miscellaneous rule, every other category covered.

## Try it

```bash
curl -s -X POST localhost:8090/transactions -H 'Content-Type: application/json' \
  -d '{"amount":-87.18,"timestamp":"2024-05-16 07:22:18","description":"Municipal Tax Payment","type":"debit","account_number":"NLINGB1944573686"}'
# 501: "... would be categorised as Housing (source: housing.taxes); storage arrives in step 4"
```

Previous: [Step 2](02-request.md). Next: [Step 4. Persistence](04-persistence.md).
