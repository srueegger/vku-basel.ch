#!/usr/bin/env bash
# HTTP-Tests für /partner/export gegen die lokale DDEV-Umgebung.
# Voraussetzung: scripts/setup-local.sh wurde ausgeführt.
# Liest nur, darf daher auch gegen Produktion laufen: BASE=https://vku-basel.ch bash tests/http_export_test.sh
set -uo pipefail

BASE="${BASE:-https://vku-basel.ddev.site}"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT
# shellcheck disable=SC1091
source "$(dirname "$0")/../.env.local"
PASS=0
FAIL=0
check() { if eval "$2"; then PASS=$((PASS + 1)); echo "PASS $1"; else FAIL=$((FAIL + 1)); echo "FAIL $1"; fi; }

code=$(curl -sk -o /dev/null -w '%{http_code}' "$BASE/partner/export/")
check "Ohne Passwort 401" '[ "$code" = 401 ]'

code=$(curl -sk -o /dev/null -w '%{http_code}' -u "$EXPORT_USER:falsch" "$BASE/partner/export/")
check "Falsches Passwort 401" '[ "$code" = 401 ]'

code=$(curl -sk -o /dev/null -w '%{http_code}' "$BASE/partner/export")
check "Ohne Schrägstrich: Passwortabfrage oder Umleitung" '[ "$code" = 401 ] || [ "${code:0:1}" = 3 ]'

curl -sk -u "$EXPORT_USER:$EXPORT_PASSWORD" -D "$TMP/headers" -o "$TMP/export.xlsx" "$BASE/partner/export/"
check "Mit Passwort 200" 'head -1 "$TMP/headers" | grep -q " 200"'
check "Content-Type XLSX" 'grep -qi "^content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" "$TMP/headers"'
check "Dateiname mit heutigem Datum" 'grep -qi "filename=\"anmeldungen-$(date +%Y-%m-%d).xlsx\"" "$TMP/headers"'
check "Kein WWW-Authenticate bei Erfolg (sonst fragt Safari erneut)" '! grep -qi "^www-authenticate" "$TMP/headers"'
check "Kein Caching" 'grep -qi "^cache-control: no-store" "$TMP/headers"'
check "noindex-Header" 'grep -qi "^x-robots-tag: noindex" "$TMP/headers"'
check "Datei ist ZIP" '[ "$(head -c 2 "$TMP/export.xlsx")" = "PK" ]'
check "Blatt enthält Spaltenkopf und Daten" 'unzip -p "$TMP/export.xlsx" xl/worksheets/sheet1.xml | grep -q "Vorname und Name"'

echo "$PASS passed, $FAIL failed"
[ "$FAIL" -eq 0 ]
