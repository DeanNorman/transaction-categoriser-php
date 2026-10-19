# API reference

Base URL when running locally: `http://localhost:8090`. Every body is JSON. Every success response has a top-level `data`; every failure a top-level `error` with a `code`, a `message` and, for input problems, a `fields` map.

## Endpoints

| Method | Path | Purpose | Success |
|---|---|---|---|
| `POST` | `/transactions` | Submit one transaction; it is categorised and stored | `201` |
| `GET` | `/transactions` | List stored transactions, newest first, optionally one category | `200` |
| `GET` | `/transactions/{id}` | One transaction by its id | `200` |
| `GET` | `/health` | Process and database liveness | `200` |

## The transaction shape

Returned by all three transaction endpoints, identically.

```json
{
  "id": "1897e6b2-4bbd-4db0-97f1-a76cad10ff4e",
  "reference": "TXN00001",
  "amount": "-87.18",
  "timestamp": "2024-05-16T07:22:18.808433Z",
  "description": "Municipal Tax Payment",
  "type": "debit",
  "account_number": "NLINGB1944573686",
  "category": "Housing",
  "categorisation": { "source": "housing.taxes", "categorised_at": "2026-10-19T14:26:11.510449Z" },
  "created_at": "2026-10-19T14:26:11.510449Z"
}
```

| Field | Meaning |
|---|---|
| `id` | Server-minted UUID v4. The only id the API recognises. |
| `reference` | The bank's own id as submitted, or `null`. Unique when present. |
| `amount` | Decimal string with two places, sign exactly as submitted. |
| `timestamp` | When the bank says it happened, ISO 8601, UTC, microseconds. |
| `type` | `debit` or `credit`, as submitted. Not reconciled with the sign. |
| `category` | One of the ten categories. |
| `categorisation.source` | The id of the rule that decided the category (`housing.taxes`), or `fallback`. |
| `categorisation.categorised_at` | When the category was assigned. Equal to `created_at` today, since categorisation happens on submit. |

## `POST /transactions`

Request body:

| Field | Required | Rule |
|---|---|---|
| `reference` | no | string, at most 64 characters; blank is treated as absent |
| `amount` | yes | number or numeric string, e.g. `-87.18` or `"473.45"`; at most 12 integer digits |
| `timestamp` | yes | `2024-05-16 07:22:18.808433` (as the bank writes it) or ISO 8601 `2024-05-16T07:22:18Z`; a value without a zone is taken as UTC |
| `description` | yes | string, 1 to 255 characters; the only input the categoriser reads |
| `type` | yes | `debit` or `credit`, case-insensitive |
| `account_number` | yes | string, 1 to 34 characters |

Unknown fields are ignored.

```bash
curl -s -X POST localhost:8090/transactions -H 'Content-Type: application/json' -d '{
  "reference": "TXN00001", "amount": -87.18, "timestamp": "2024-05-16 07:22:18.808433",
  "description": "Municipal Tax Payment", "type": "debit", "account_number": "NLINGB1944573686"}'
```

| Status | `error.code` | When |
|---|---|---|
| `201` | | Stored. Body is the transaction. |
| `400` | `invalid_json` | The body is not valid JSON. |
| `400` | `empty_body` | No body. |
| `422` | `validation_failed` | One or more fields invalid; `fields` lists every one. |
| `409` | `duplicate_reference` | A transaction with this `reference` already exists. Nothing stored. |

```json
{"error":{"code":"validation_failed","message":"One or more fields are invalid",
          "fields":{"amount":"must be a decimal number such as -87.18","type":"must be debit or credit"}}}
```

## `GET /transactions`

Query parameters:

| Parameter | Meaning |
|---|---|
| `category` | Optional. One of the ten category names, exactly as spelled (`Dining Out`). |

Newest first by `created_at`. At most 500 rows are returned; `meta.cap` says so and `meta.count` says how many came back. There is no paging.

```json
{"data":[ { "...": "transaction" } ],
 "meta":{"count":3,"cap":500,"category":null}}
```

| Status | `error.code` | When |
|---|---|---|
| `200` | | Always, including an empty list. |
| `422` | `validation_failed` | `category` is not one of the ten. The message lists them. |

## `GET /transactions/{id}`

| Status | `error.code` | When |
|---|---|---|
| `200` | | Body is the transaction. |
| `404` | `transaction_not_found` | No such id. An id that is not a UUID gets the same answer. |

## `GET /health`

```json
{"data":{"status":"ok","database":"ok"}}
```

`503` with `database_unavailable` when MariaDB does not answer.

## Everything else

| Status | `error.code` | When |
|---|---|---|
| `404` | `not_found` | No such route. |
| `405` | `method_not_allowed` | The route exists but not for this method. |
| `500` | `internal_error` | An unexpected failure; details go to the PHP log, not the client. |

## Categories

Groceries, Dining Out, Utilities, Transportation, Entertainment, Healthcare, Shopping, Housing, Education, Miscellaneous. How a description is placed in one is described in [journey/03-categorisation.md](journey/03-categorisation.md); the rule table is [`src/Categorisation/rules.php`](../src/Categorisation/rules.php).
