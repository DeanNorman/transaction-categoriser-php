#!/usr/bin/env bash
# The journey through the real stack: nginx -> PHP-FPM -> MariaDB, over HTTP.
# The integration suite covers the application in-process; this covers the
# transport around it. Needs curl and python3 on the host; exits non-zero on
# the first unexpected status.
#
#   docker compose up -d --build && scripts/smoke.sh
set -euo pipefail
BASE="${BASE:-http://localhost:8090}"
REF="SMOKE-$(date +%s)"
BODY="{\"reference\":\"$REF\",\"amount\":-87.18,\"timestamp\":\"2024-05-16 07:22:18.808433\",\"description\":\"Municipal Tax Payment\",\"type\":\"debit\",\"account_number\":\"NLINGB1944573686\"}"

expect() { # expect <label> <wanted status> <actual status>
  if [ "$2" = "$3" ]; then echo "ok   $1 -> $3"; else echo "FAIL $1 -> $3 (wanted $2)"; exit 1; fi
}
status() { curl -s -o "$TMP" -w '%{http_code}' "$@"; }
TMP="$(mktemp)"; trap 'rm -f "$TMP"' EXIT

expect "GET  /health"                 200 "$(status "$BASE/health")"
expect "GET  / (front-end)"           200 "$(status "$BASE/")"
expect "POST /transactions (valid)"   201 "$(status -X POST "$BASE/transactions" -H 'Content-Type: application/json' -d "$BODY")"
ID="$(python3 -c 'import json,sys; d=json.load(open(sys.argv[1])); print(d["data"]["id"]); assert d["data"]["category"]=="Housing", d' "$TMP")"
expect "POST /transactions (repeat)"  409 "$(status -X POST "$BASE/transactions" -H 'Content-Type: application/json' -d "$BODY")"
expect "POST /transactions (invalid)" 422 "$(status -X POST "$BASE/transactions" -H 'Content-Type: application/json' -d '{"amount":"x"}')"
expect "POST /transactions (broken)"  400 "$(status -X POST "$BASE/transactions" -d '{')"
expect "GET  /transactions/{id}"      200 "$(status "$BASE/transactions/$ID")"
expect "GET  /transactions/unknown"   404 "$(status "$BASE/transactions/00000000-0000-4000-8000-000000000000")"
expect "GET  /transactions"           200 "$(status "$BASE/transactions")"
expect "GET  /transactions?category"  200 "$(status "$BASE/transactions?category=Housing")"
expect "GET  /transactions?bad"       422 "$(status "$BASE/transactions?category=Pets")"
expect "DELETE /transactions"         405 "$(status -X DELETE "$BASE/transactions")"
expect "GET  /nothing"                404 "$(status "$BASE/nothing")"
echo "smoke passed; stored $ID with reference $REF"
