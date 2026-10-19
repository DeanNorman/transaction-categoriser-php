---
status: accepted
---
# The server mints transaction ids; the bank's id is a unique reference

The supplied history carries bank ids such as `TXN00001`, and the brief's `GET /transactions/:id` needs an id. We mint a UUID on the server for every stored transaction and keep the bank's id as an optional, unique `reference`. A second submission with the same reference is refused with `409 Conflict`; submissions without a reference are always accepted, so the unique key allows many NULLs.

## Considered options

- **Client-supplied primary key.** Simpler, but the API would trust an external key space it does not control, a bank-format id would shape the URL design, and two banks could collide.
- **Server-minted id, reference kept (chosen).** The URL space is ours; idempotency on resubmission comes from the reference; a transaction with no reference can still be stored.

## Consequences

Idempotency is only as good as the reference: a client that omits it can store the same transaction twice. That is the client's choice, and the trade-offs document says so.
