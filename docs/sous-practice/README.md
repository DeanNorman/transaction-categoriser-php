# The deck

The slides for presenting this repository, built from [deck.md](deck.md): one slide per section of the deliverable (what was built, see it, how it works, decisions, what was measured, how production-ready it is, what not to trust yet, next steps), with speaker notes.

| Want to… | Open |
|---|---|
| Read it now, anywhere | [transaction-categoriser.pdf](transaction-categoriser.pdf) (GitHub renders it in the browser) |
| Present it | [transaction-categoriser.html](https://deannorman.github.io/transaction-categoriser-php/sous-practice/transaction-categoriser.html), served from this repository by GitHub Pages. Arrow keys or space move between slides, N shows the speaker notes, `#4` at the end of the URL opens slide 4, printing gives one slide per page |
| Change it | edit [deck.md](deck.md) and rebuild (below) |

The "See it" slide is a screenshot of the running front-end at `http://localhost:8090`, saved under `shots/` so the deck does not need the stack to be up.

## How it is built

`deck.md` is a small Markdown dialect: a `##` heading is a slide, the `>` line under it is the headline, `###` makes columns, `[code]`/`[person]` tag the steps, `[done]`/`[left]` make status chips, `[stat]` lines become big numbers, `{dark}` marks the one dark slide, and a line starting `Note:` is a speaker note. A short Python script (standard library only, headless Chrome for the screenshot, the fit check and the PDF) turns it into the self-contained HTML file and the PDF beside it, and reports any slide whose content does not fit. The builder is a personal tool kept outside this repository; the built HTML and PDF are committed so nothing here depends on it.

## Running order

About twelve minutes, leaving the rest for questions.

| Slide | Minutes | Purpose |
|---|---|---|
| 1 to 2 | 2 | what was built, done means, left out |
| 3 | 2 | the page, live if the stack is running |
| 4 to 5 | 4 | the four calls, and the decisions they rest on |
| 6 to 7 | 2 | the numbers, and how production-ready it is |
| 8 to 9 | 2 | what not to trust yet, next steps |

The notes under each slide carry the answers to the questions that slide is likely to prompt.
