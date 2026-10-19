# Step 5. The transaction is read back

The stored transaction can now be fetched by its id, and all stored transactions can be listed, newest first, optionally by category. Both GETs return the same JSON shape the POST returned, built by the same `Transaction::toArray()`.

## What exists after this step

- **`TransactionRepository::find(id)`** returns a `Transaction` or null.
- **`TransactionRepository::list(category, limit)`** returns newest first (`created_at DESC, id DESC`, so two rows created in the same microsecond still order deterministically), filtered by category when one is given, never more than `limit` rows.
- **`GET /transactions/{id}`** (`App\Http\Handlers\GetTransaction`). Unknown id: `404 transaction_not_found`. An id that is not even a UUID gets the same 404; the shape of our ids is not a contract clients are told about.
- **`GET /transactions[?category=Groceries]`** (`App\Http\Handlers\ListTransactions`). An unknown category is `422` with the ten valid names in the message. The response carries a `meta` block beside `data`: how many rows came back, the cap, and the filter applied.

```json
{"data":[{"id":"...","category":"Housing","...":"..."}],
 "meta":{"count":1,"cap":500,"category":"Housing"}}
```

## Decisions taken here

- **Minimal list, hard cap of 500.** The brief asks for "all categorised transactions". Returning an unbounded table is a denial-of-service waiting to happen, so the list is capped and says so in `meta.cap`. Paging (`limit`/`offset` or a cursor) is the next step for this endpoint and is listed in the trade-offs; it was left out because nothing in the brief or the front-end needs it yet.
- **One filter, by category.** It gives the ten categories a visible purpose in the front-end and costs one indexed `WHERE`. The index `(category, created_at)` was created in step 1 for exactly this query.
- **No `GET` for the rule a transaction matched beyond its id.** `categorisation.source` names the rule; the rule table in the repository is the reference. Exposing the table over HTTP would be a fourth endpoint the brief does not ask for.

## Try it

```bash
curl -s localhost:8090/transactions | jq '.meta, (.data[] | .category)'
curl -s 'localhost:8090/transactions?category=Housing' | jq '.data[].reference'
curl -s localhost:8090/transactions/$(curl -s localhost:8090/transactions | jq -r '.data[0].id') | jq .
curl -s localhost:8090/transactions/TXN00001        # 404
curl -s 'localhost:8090/transactions?category=Pets' # 422
```

Previous: [Step 4](04-persistence.md). Next: [Step 6. The front-end](06-front-end.md).
