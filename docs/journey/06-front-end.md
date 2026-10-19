# Step 6. The journey, in a browser

A single static page, served by nginx from `public/` in the same stack, shows the three endpoints as three panels. It is plain HTML, CSS and JavaScript with no build step and no framework, which is how the Worxit dashboards started. Open <http://localhost:8090>.

## What the page does

- **Left: `POST /transactions`.** A form with the six fields. "Fill with a sample row" cycles through rows of the supplied history plus two descriptions the history does not contain (`Netflix Subscription`, `Uber Eats order 4471`), so a reviewer can see a rule match and the dining-before-transport ordering without typing. On submit the panel shows the HTTP status, a one-line summary (`"NS Train Ticket" is Transportation (transport.rail)`) and the raw JSON. A `422` shows its field map; a `409` shows the duplicate reference.
- **Middle: `GET /transactions`.** The list, newest first, with the category filter and the `meta.count`. Clicking a row fetches it on the right.
- **Right: `GET /transactions/{id}`.** The id box is filled automatically after a successful submit or a row click; "Fetch" shows the raw JSON, or the `404`.
- **Top:** `/health`, so a reviewer sees at once whether the database answered.

Every response shown is the API's own JSON, unformatted beyond indentation. The page adds nothing the API does not say.

## Decisions taken here

- **Static, in the same stack.** No second container, no bundler, no dependency. nginx already serves `public/`; the API and the page share an origin, so there is no CORS to configure.
- **The page shows raw JSON on purpose.** This is an illustration of an API, not a product. Pretty-printing the response is the most honest thing the page can do.
- **No client-side validation beyond `required`.** The server's `422` is the validation, and the page shows it. Duplicating the rules in JavaScript would hide the API's behaviour, which is the thing being demonstrated.

## Try it

1. `docker compose up -d`, open <http://localhost:8090>.
2. Click "Fill with a sample row", then "Submit transaction": `201`, category badge, JSON.
3. Submit the same row again: `409`.
4. Pick "Housing" in the filter; click a row; the right panel shows it.

Previous: [Step 5](05-retrieval.md). Next: [Step 7. Integration tests](07-integration-tests.md).
