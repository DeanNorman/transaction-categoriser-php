# SOUS practice deck

Slides for presenting this repository in the SOUS interview, with speaker notes. The source is [deck.md](deck.md), a [Marp](https://marp.app) markdown deck; `deck.pdf` and `deck.html` are rendered from it.

## Present

- Open `deck.html` in a browser (press `P` for presenter view with the notes, `F` for full screen).
- Or open `deck.pdf`.

## Re-render after editing deck.md

No host install needed; the Marp CLI image bundles Chromium:

```bash
docker run --rm -v "$PWD/docs/sous-practice:/home/marp/app" -e LANG=C.UTF-8 marpteam/marp-cli deck.md --pdf --allow-local-files
docker run --rm -v "$PWD/docs/sous-practice:/home/marp/app" -e LANG=C.UTF-8 marpteam/marp-cli deck.md --html
```

## Running order

| Slide | Minutes | Purpose |
|---|---|---|
| 1 to 4 | 3 | brief, data, the twelve decisions |
| 5 to 6 | 2 | the stack and the journey (the spine) |
| 7 to 10 | 5 | validation, categorisation, AI usage, storage |
| 11 to 13 | 3 | retrieval, the page, the proof |
| 14 to 16 | 2 | trade-offs, language choice, expected questions |

The notes under each slide carry the answers to the questions the slide is likely to prompt.
