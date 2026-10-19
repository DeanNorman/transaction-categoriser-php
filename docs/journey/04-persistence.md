# Step 4. The transaction is stored

The categorised transaction is written to MariaDB in one insert, with a server-minted id, and the stored row is returned as `201 Created`. This is the step where `POST /transactions` becomes real; the `501` is gone.

## The journey, now complete on the write side

```
Request -> Kernel -> Router -> SubmitTransaction
   -> TransactionValidator::validate      (step 2)   422 on failure
   -> RuleBasedCategoriser::categorise    (step 3)
   -> TransactionRepository::insert       (step 4)   409 on a repeated reference
   -> Response::ok(transaction, 201)
```

## What exists after this step

- **`App\Transactions\Transaction`**: the stored transaction, built from a table row, serialised to the one JSON shape every endpoint returns. Timestamps come out as ISO 8601 in UTC with microseconds.
- **`App\Transactions\TransactionRepository::insert`**: mints the id, writes the eleven columns in one statement, and translates MariaDB's duplicate-key error on `reference` into a `DuplicateReferenceException`. The handler turns that into `409`.
- **`App\Support\Uuid`** (version 4 from `random_bytes`) and **`App\Support\Clock`** (the one source of "now", so tests can hold time still).
- **`App\Db\Connection`** is now lazy: the kernel builds every handler with a connection that has not been opened, and the first handler that needs the database opens it. A request that never touches the database never connects, and a database outage surfaces as `/health`'s `503`, not a crash during wiring.

## The response

```json
{"data":{
  "id":"1897e6b2-4bbd-4db0-97f1-a76cad10ff4e",
  "reference":"TXN00001",
  "amount":"-87.18",
  "timestamp":"2024-05-16T07:22:18.808433Z",
  "description":"Municipal Tax Payment",
  "type":"debit",
  "account_number":"NLINGB1944573686",
  "category":"Housing",
  "categorisation":{"source":"housing.taxes","categorised_at":"2026-10-19T14:26:11.510449Z"},
  "created_at":"2026-10-19T14:26:11.510449Z"}}
```

The same body again:

```json
{"error":{"code":"duplicate_reference","message":"A transaction with reference TXN00001 already exists","fields":{"reference":"already exists"}}}
```

## Decisions taken here

- **The server mints the id; the bank's id is a unique reference.** [ADR 0002](../adr/0002-server-minted-ids.md). A reference-less submission is always accepted, so the unique key allows many NULLs.
- **Amount and type are stored exactly as submitted.** The supplied history disagrees with itself about direction in half its rows; we neither reject nor repair that. [ADR 0003](../adr/0003-store-amounts-as-given.md).
- **One insert carries transaction and categorisation.** There is no "pending" state and no second table. Re-categorising stored rows when the rule table changes is the next feature this design would need; see the trade-offs document.
- **Timestamps without a zone are taken as UTC** and every timestamp is returned in UTC. The bank's `2024-05-16 07:22:18.808433` round-trips to the microsecond.

## Try it

```bash
B='{"reference":"TXN00001","amount":-87.18,"timestamp":"2024-05-16 07:22:18.808433","description":"Municipal Tax Payment","type":"debit","account_number":"NLINGB1944573686"}'
curl -s -X POST localhost:8090/transactions -H 'Content-Type: application/json' -d "$B"   # 201
curl -s -X POST localhost:8090/transactions -H 'Content-Type: application/json' -d "$B"   # 409
```

The repository's behaviour against the real database is covered in step 7; this step's unit tests pin the UUID shape and the row-to-JSON mapping.

Previous: [Step 3](03-categorisation.md). Next: [Step 5. Retrieval](05-retrieval.md).
