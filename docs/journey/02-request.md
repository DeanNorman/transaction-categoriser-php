# Step 2. The request arrives

A client sends `POST /transactions` with a JSON body. This step takes the request from nginx to a typed, validated `TransactionInput`, or back out as a precise error. Nothing is categorised or stored yet; a valid submission is answered with `501` until steps 3 and 4 land, so the commit history shows exactly when each capability appeared.

## The path through the code

1. **`public/index.php`** is now three lines of intent: build a `Request` from PHP's globals, ask the `Kernel` for a `Response`, send it.
2. **`App\Http\Request`** reduces the request to method, path, query and a decoded JSON body. It remembers whether the body was malformed, so the handler can answer `400 invalid_json` rather than treating broken JSON as an empty submission.
3. **`App\Http\Kernel`** owns the route table and the last line of defence: any uncaught exception becomes a JSON `500` and a log line. It takes a connection factory rather than a connection, so a request that never touches the database never opens one.
4. **`App\Http\Router`** matches method and path. `{id}` segments become named parameters. It knows the difference between a path that does not exist (`404`) and a path that exists but not for this method (`405`).
5. **`App\Http\Handlers\SubmitTransaction`** rejects a missing or malformed body with `400`, then hands the body to the validator.
6. **`App\Transactions\TransactionValidator`** checks every field and reports every failure at once as a `422` with a field map. Its output is a `TransactionInput`: every field present, trimmed and typed. From here on, nothing downstream re-checks input.

## The request body

Field names follow the CSV columns in snake_case.

| Field | Rule |
|---|---|
| `reference` | optional, at most 64 characters, the bank's own id (`TXN00001`) |
| `amount` | required, a decimal number or numeric string, sign kept, normalised to two places |
| `timestamp` | required, `2024-05-16 07:22:18.808433` as the bank writes it, or ISO 8601 |
| `description` | required, at most 255 characters |
| `type` | required, `debit` or `credit`, case-insensitive |
| `account_number` | required, at most 34 characters (the longest IBAN) |

Unknown fields are ignored. Validation errors look like this:

```json
{"error":{"code":"validation_failed","message":"One or more fields are invalid",
          "fields":{"amount":"must be a decimal number such as -87.18","type":"must be debit or credit"}}}
```

## Decisions taken here

- **Amounts are decimal strings, not floats.** `-87.18` arrives as a JSON number, is normalised to the string `"-87.18"`, and travels as a string to a `DECIMAL(14,2)` column. Money never sits in a float for longer than the normalisation.
- **All failures at once.** A client fixing a form should not need six round trips to learn six mistakes.
- **The kernel does not read globals.** `Kernel::handle(Request): Response` is the same call the front controller makes and the integration tests will make in step 7.

## Try it

```bash
curl -s -X POST localhost:8090/transactions -H 'Content-Type: application/json' \
  -d '{"amount":"abc","type":"transfer"}'
# 422 with a field map

curl -s -X POST localhost:8090/transactions -H 'Content-Type: application/json' \
  -d '{"reference":"TXN00001","amount":-87.18,"timestamp":"2024-05-16 07:22:18.808433",
       "description":"Municipal Tax Payment","type":"debit","account_number":"NLINGB1944573686"}'
# 501: valid, but categorisation and storage are the next two steps
```

Tests: `docker compose run --rm php vendor/bin/phpunit --testsuite unit` (router, request decoding, every validator rule).

Previous: [Step 1](01-scaffold.md). Next: [Step 3. Categorisation](03-categorisation.md).
