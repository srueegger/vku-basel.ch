#!/usr/bin/env bash
# Prüft, ob die Einstiegspunkte src/ in der Server-Struktur finden:
#   ~/public_html/vku-basel.ch/  (Inhalt von public/)
#   ~/vku-basel-app/src/         (Inhalt von src/)
set -uo pipefail
cd "$(dirname "$0")/.."

HOME_SIM="$(mktemp -d)"
trap 'rm -rf "$HOME_SIM"' EXIT
mkdir -p "$HOME_SIM/public_html/vku-basel.ch" "$HOME_SIM/vku-basel-app"
cp -R public/. "$HOME_SIM/public_html/vku-basel.ch/"
cp -R src "$HOME_SIM/vku-basel-app/src"

PASS=0
FAIL=0
check() { if eval "$2"; then PASS=$((PASS + 1)); echo "PASS $1"; else FAIL=$((FAIL + 1)); echo "FAIL $1"; fi; }

out=$(cd "$HOME_SIM" && env -u HOME php -d display_errors=stderr public_html/vku-basel.ch/partner/index.php 2>&1)
check "Partnerseite findet src/ und rendert" 'grep -q "Gemeinsam stark: Werde jetzt Partner" <<<"$out"'
check "Datenordner liegt in ~/vku-basel-app/data" '[ -f "$HOME_SIM/vku-basel-app/data/secret.key" ]'

out=$(cd "$HOME_SIM" && env -u HOME php -d display_errors=stderr public_html/vku-basel.ch/partner/export/index.php 2>&1 | head -c 2)
check "Export findet src/ und liefert XLSX" '[ "$out" = "PK" ]'

echo "$PASS passed, $FAIL failed"
[ "$FAIL" -eq 0 ]
