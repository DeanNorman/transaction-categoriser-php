---
status: accepted
---
# Amounts and types are stored as submitted; the description alone drives the category

In the supplied history the sign of `amount` disagrees with the `type` column in roughly half the rows: negative credits, positive debits. We store both fields exactly as submitted, validate only that the amount is a decimal and the type is `debit` or `credit`, and never let direction influence the category. "Salary from Employer" is the same category whichever way the sign points.

## Considered options

- **Reject inconsistent rows with 422.** Cleaner model; rejects half the provided history, which reads as misunderstanding the data.
- **Normalise: trust `type`, flip the sign.** Silently rewriting money values is the worst option for a banking system.
- **Store as given (chosen).** Nothing is lost, nothing is invented, and a later derived "direction" field can be added from either column once the bank's convention is known.
