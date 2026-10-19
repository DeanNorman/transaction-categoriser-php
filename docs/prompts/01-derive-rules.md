# Prompt 1: deriving the rule table

**Tool:** Claude Code, model Claude Fable 5.1 (`claude-fable-5-1`), 19 October 2026.
**Result:** the ordered rule table transcribed into [`src/Categorisation/rules.php`](../../src/Categorisation/rules.php) and the expectation table in [`tests/Unit/Categorisation/RuleBasedCategoriserTest.php`](../../tests/Unit/Categorisation/RuleBasedCategoriserTest.php).

The data profile in the prompt was produced first with `cut | sort | uniq -c` over the supplied CSV, so the model worked from the real distinct descriptions rather than from the raw 1000 rows.

## The prompt

> You are deriving a categorisation rule table for a banking transaction API. The categoriser is deterministic: an ordered list of case-insensitive keyword patterns, first match wins, and anything unmatched falls back to **Miscellaneous**. The rules must be transcribable into PHP `preg_match` patterns with word boundaries.
>
> **Categories (fixed, use no others):** Groceries, Dining Out, Utilities, Transportation, Entertainment, Healthcare, Shopping, Housing, Education, Miscellaneous.
>
> **Every distinct description in the supplied history, with its count (1000 rows, Dutch bank accounts at ABN AMRO, ING and Rabobank):**
>
> Tax Refund 66 · Car Lease Payment 60 · Municipal Tax Payment 59 · Albert Heijn Purchase 55 · Transfer to ING Account 54 · Payment to Ziggo 54 · NS Train Ticket 54 · Payment to Rabobank 53 · Netflix Subscription 51 · Bunq Transfer 50 · PayPal Transfer 49 · Eneco Energy Bill 49 · Bol.com Purchase 49 · Payment to Credit Card 48 · Salary from Employer 47 · iDEAL Payment 46 · T-Mobile Bill Payment 43 · Rent Payment 42 · AH Online Groceries 40 · Geldmaat ATM Withdrawal 31
>
> Constraints:
> 1. Assign each of the 20 descriptions a category and say which keyword decides it. Transfers between accounts, salary, tax refunds, cash withdrawals and generic payment rails (iDEAL, PayPal, credit card settlement) are not spending categories: leave them to the fallback rather than inventing a rule.
> 2. Three categories (Dining Out, Healthcare, Education) have no examples in the data. Add rules for them anyway, from well-known Dutch merchants and generic words, so the engine is not blind to them. Do the same to broaden the seven that do have examples.
> 3. Never map a rule to Miscellaneous. Keep patterns on word boundaries and call out any ordering constraint between rules.
> 4. Give each rule a stable id of the form `category.topic` and a one-line note for the reader.
> 5. Where a description is genuinely ambiguous, say which category you chose and why, so the trade-off can be documented.

## The answer, as transcribed

| Order | Rule id | Category | Pattern (word-bounded, case-insensitive) | Decides |
|---|---|---|---|---|
| 1 | groceries.albert-heijn | Groceries | `albert heijn` | Albert Heijn Purchase |
| 2 | groceries.ah | Groceries | `ah` | AH Online Groceries |
| 3 | groceries.supermarkets | Groceries | `jumbo\|lidl\|aldi\|plus supermarkt\|dirk\|spar\|coop\|picnic\|crisp\|groceries\|supermarkt` | — |
| 4 | dining.delivery | Dining Out | `thuisbezorgd\|uber eats\|deliveroo\|takeaway\|just eat` | — |
| 5 | dining.venues | Dining Out | `restaurant\|cafe\|café\|bistro\|pizzeria\|mcdonald's\|burger king\|kfc\|starbucks\|bakery\|bakkerij\|snackbar` | — |
| 6 | utilities.energy | Utilities | `eneco\|vattenfall\|essent\|greenchoice\|budget energie\|energy bill\|gas bill\|electricity` | Eneco Energy Bill |
| 7 | utilities.water | Utilities | `vitens\|waternet\|evides\|pwn\|dunea\|water bill` | — |
| 8 | utilities.telecom | Utilities | `ziggo\|t-mobile\|odido\|kpn\|vodafone\|tele2\|simyo\|lebara\|internet bill\|phone bill\|mobile bill` | Payment to Ziggo, T-Mobile Bill Payment |
| 9 | transport.rail | Transportation | `ns\|ov-chipkaart\|ov chipkaart\|prorail\|thalys\|eurostar` | NS Train Ticket |
| 10 | transport.car | Transportation | `car lease\|leaseplan\|athlon\|shell\|bp\|esso\|tinq\|tango\|totalenergies\|parking\|q-park\|anwb\|fuel\|tank` | Car Lease Payment |
| 11 | transport.ride | Transportation | `uber\|bolt\|taxi\|flixbus\|arriva\|connexxion\|gvb\|qbuzz\|ebs\|bus ticket\|train ticket` | — |
| 12 | entertainment.streaming | Entertainment | `netflix\|spotify\|disney+\|videoland\|hbo max\|prime video\|youtube premium\|apple music` | Netflix Subscription |
| 13 | entertainment.outings | Entertainment | `pathé\|pathe\|kinepolis\|vue cinema\|cinema\|bioscoop\|theatre\|theater\|concert\|ticketmaster\|museum\|steam\|playstation\|xbox\|nintendo` | — |
| 14 | healthcare.insurance | Healthcare | `zilveren kruis\|cz\|menzis\|vgz\|dsw\|ohra\|health insurance\|zorgverzekering` | — |
| 15 | healthcare.providers | Healthcare | `apotheek\|pharmacy\|huisarts\|doctor\|dentist\|tandarts\|ziekenhuis\|hospital\|fysio\|physio\|optician\|specsavers\|pearle` | — |
| 16 | shopping.online | Shopping | `bol.com\|bol com\|amazon\|zalando\|coolblue\|wehkamp\|aliexpress\|etsy\|marktplaats` | Bol.com Purchase |
| 17 | shopping.stores | Shopping | `mediamarkt\|ikea\|hema\|action\|primark\|h&m\|decathlon\|kruidvat\|etos\|blokker\|gamma\|praxis` | — |
| 18 | housing.rent | Housing | `rent\|huur\|mortgage\|hypotheek\|vve` | Rent Payment |
| 19 | housing.taxes | Housing | `municipal tax\|gemeente\|gemeentebelasting\|waterschap\|property tax\|ozb` | Municipal Tax Payment |
| 20 | education.institutions | Education | `university\|universiteit\|hogeschool\|school\|college\|duo\|tuition\|collegegeld` | — |
| 21 | education.courses | Education | `udemy\|coursera\|course\|textbook\|studystore\|bookshop` | — |
| — | fallback | Miscellaneous | (no match) | Salary from Employer, Tax Refund, Transfer to ING Account, Payment to Rabobank, Bunq Transfer, PayPal Transfer, iDEAL Payment, Payment to Credit Card, Geldmaat ATM Withdrawal |

**Ordering constraint called out:** Dining Out must precede Transportation, because `uber eats` must win over `uber`.

**Ambiguities called out:**

- *Municipal Tax Payment → Housing.* Dutch municipal charges (OZB, waste and sewerage levies) are costs of occupying a home. Utilities was the alternative; Housing was chosen because the charge follows the property, not consumption.
- *Car Lease Payment → Transportation*, not Housing or Miscellaneous: it is the cost of having a car.
- *Payment to Credit Card → Miscellaneous.* The settlement moves money to the card; the spending it covers was categorised when it happened, so categorising the settlement again would double count.
- *Tax Refund → Miscellaneous.* Income, not spending; the fixed list has no income category.
- *`ah` as a bare word* is accepted as the Albert Heijn abbreviation; it is listed after the full name so the source names the better rule when both apply.

**Expected distribution over the 1000 supplied rows:** Groceries 95, Utilities 146, Transportation 114, Entertainment 51, Shopping 49, Housing 101, Miscellaneous 444; Dining Out, Healthcare and Education 0. The unit test pins these numbers.
