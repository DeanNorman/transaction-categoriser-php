# Transaction Categorisation

The language of this repository: a bank transaction arrives, is placed in one of ten categories, and can be read back. Nothing more.

## Language

**Transaction**:
One line of banking history: an amount, a timestamp, a description, a type, an account number. Submitted once, never edited.
_Avoid_: Payment, record, entry

**Reference**:
The bank's own identifier for a transaction (for example `TXN00001`). Optional, unique, never our primary key.
_Avoid_: External id, transaction id

**Description**:
The free text the bank attached to the transaction. The only input the categoriser reads.
_Avoid_: Memo, narrative, merchant

**Type**:
The bank's word for the direction of a transaction: `debit` or `credit`. Stored as given and never reconciled with the sign of the amount.
_Avoid_: Direction, side

**Category**:
One of the ten predefined labels: Groceries, Dining Out, Utilities, Transportation, Entertainment, Healthcare, Shopping, Housing, Education, Miscellaneous.
_Avoid_: Tag, class, label, bucket

**Categorisation**:
The act and the outcome of assigning a category to a transaction, together with its source: the rule that matched, or the fallback.
_Avoid_: Classification, tagging

**Rule**:
A keyword pattern paired with a category. Rules are ordered; the first rule whose pattern matches the description decides the category.
_Avoid_: Mapping, heuristic

**Fallback**:
The categorisation a transaction receives when no rule matches: Miscellaneous, with the source recorded as `fallback`.
_Avoid_: Default, unknown, other

**Journey**:
The path of a single transaction through the system: request, validation, categorisation, persistence, retrieval. The documentation in `docs/journey/` follows it step by step.
