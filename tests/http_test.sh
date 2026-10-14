#!/usr/bin/env bash
# HTTP-Tests für /partner gegen die lokale DDEV-Umgebung. Schreibt Testdaten in data/partner.sqlite.
# Nicht gegen Produktion laufen lassen.
set -uo pipefail

BASE="${BASE:-https://vku-basel.ddev.site}"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT
PASS=0
FAIL=0

ok()   { PASS=$((PASS + 1)); echo "PASS $1"; }
fail() { FAIL=$((FAIL + 1)); echo "FAIL $1"; }
check() { if eval "$2"; then ok "$1"; else fail "$1"; fi; }

count() { ddev exec php tests/support/count_anmeldungen.php 2>/dev/null | tr -d '\r[:space:]'; }

# Lädt das Formular in einer neuen Session und liest csrf + ts aus.
load_form() {
  rm -f "$TMP/jar"
  curl -sk -c "$TMP/jar" -b "$TMP/jar" "$BASE/partner/" -o "$TMP/form.html"
  CSRF=$(sed -n 's/.*name="csrf" value="\([^"]*\)".*/\1/p' "$TMP/form.html" | head -1)
  TS=$(sed -n 's/.*name="ts" value="\([^"]*\)".*/\1/p' "$TMP/form.html" | head -1)
}

# post <name> [curl --data-urlencode args...] → $TMP/<name>.html, Status in $TMP/<name>.code, Location in $TMP/<name>.loc
post() {
  local name="$1"; shift
  curl -sk -c "$TMP/jar" -b "$TMP/jar" -o "$TMP/$name.html" \
    -w '%{http_code} %{redirect_url}' -X POST "$BASE/partner/" "$@" > "$TMP/$name.meta"
  cut -d' ' -f1 "$TMP/$name.meta" > "$TMP/$name.code"
  cut -d' ' -f2- "$TMP/$name.meta" > "$TMP/$name.loc"
}

valid=(--data-urlencode "name=Max Muster" --data-urlencode "email=max@fahrschule.ch"
       --data-urlencode "tel=061 123 45 67" --data-urlencode "consent=1")

# 1. Seite lädt
code=$(curl -sk -o "$TMP/page.html" -w '%{http_code}' "$BASE/partner/")
check "GET /partner/ liefert 200" '[ "$code" = 200 ]'
check "Seite enthält Titel" 'grep -q "Gemeinsam stark: Werde jetzt Partner" "$TMP/page.html"'
check "Seite lädt nichts von Google Fonts" '! grep -q "fonts.googleapis" "$TMP/page.html"'
check "Formular enthält CSRF und Zeitstempel" 'grep -q "name=\"csrf\"" "$TMP/page.html" && grep -q "name=\"ts\"" "$TMP/page.html"'

# 2. Leeres Formular → 5 Fehler
load_form; sleep 3
post empty --data-urlencode "csrf=$CSRF" --data-urlencode "ts=$TS"
check "Leeres Formular liefert 422" '[ "$(cat $TMP/empty.code)" = 422 ]'
check "Leeres Formular meldet 5 Angaben" 'grep -q "5 Angaben fehlen noch:" "$TMP/empty.html"'
check "Leeres Formular zeigt Feldfehler" 'grep -q "Bitte gib den Namen deiner Fahrschule ein." "$TMP/empty.html"'

# 3. Gültige Anmeldung → Danke
before=$(count)
load_form; sleep 3
post ok --data-urlencode "csrf=$CSRF" --data-urlencode "ts=$TS" --data-urlencode "schule=Rüegger & Co" "${valid[@]}"
check "Gültige Anmeldung leitet um (303)" '[ "$(cat $TMP/ok.code)" = 303 ]'
check "Umleitung auf Danke-Ansicht" 'grep -q "/partner/?danke#anmeldung" "$TMP/ok.loc"'
curl -sk -c "$TMP/jar" -b "$TMP/jar" "$BASE/partner/?danke" -o "$TMP/danke.html"
check "Danke-Ansicht zeigt E-Mail" 'grep -q "max@fahrschule.ch" "$TMP/danke.html" && grep -q "Danke! Ich freue mich über dein Interesse!" "$TMP/danke.html"'
after=$(count)
check "Anmeldung gespeichert ($before → $after)" '[ "$after" = "$((before + 1))" ]'

# 4. HTML wird escaped
load_form; sleep 3
post xss --data-urlencode "csrf=$CSRF" --data-urlencode "ts=$TS" --data-urlencode "schule=<b>x</b>"
check "Eingaben werden escaped" 'grep -q "&lt;b&gt;x" "$TMP/xss.html" && ! grep -q "<b>x</b>" "$TMP/xss.html"'

# 5. Falsches CSRF → 400, Werte bleiben
load_form; sleep 3
post csrf --data-urlencode "csrf=falsch" --data-urlencode "ts=$TS" --data-urlencode "schule=Meine Fahrschule" "${valid[@]}"
check "Falsches CSRF liefert 400" '[ "$(cat $TMP/csrf.code)" = 400 ]'
check "Falsches CSRF zeigt Hinweis" 'grep -q "Die Seite war zu lange offen" "$TMP/csrf.html"'
check "Falsches CSRF behält Eingaben" 'grep -q "value=\"Meine Fahrschule\"" "$TMP/csrf.html"'

# 6. Honeypot → Danke, nichts gespeichert
before=$(count)
load_form; sleep 3
post hp --data-urlencode "csrf=$CSRF" --data-urlencode "ts=$TS" --data-urlencode "schule=Bot" --data-urlencode "website=http://spam" "${valid[@]}"
check "Honeypot leitet auf Danke um" '[ "$(cat $TMP/hp.code)" = 303 ] && grep -q "danke" "$TMP/hp.loc"'
check "Honeypot speichert nichts" '[ "$(count)" = "$before" ]'

# 7. Zu schnell abgeschickt → Danke, nichts gespeichert
before=$(count)
load_form
post fast --data-urlencode "csrf=$CSRF" --data-urlencode "ts=$TS" --data-urlencode "schule=Schnell" "${valid[@]}"
check "Zu schnelles Absenden leitet um" '[ "$(cat $TMP/fast.code)" = 303 ]'
check "Zu schnelles Absenden speichert nichts" '[ "$(count)" = "$before" ]'

# 8. Danke ohne Session
curl -sk "$BASE/partner/?danke" -o "$TMP/danke2.html"
check "Danke ohne Session zeigt neutralen Satz" 'grep -q "Du bekommst eine persönliche Rückmeldung per E-Mail oder telefonisch." "$TMP/danke2.html"'

echo "$PASS passed, $FAIL failed"
[ "$FAIL" -eq 0 ]
