#!/usr/bin/env bash
# Deploy auf cyon (scifitre@rueegger.dev).
#   ./deploy.sh                  Code hochladen
#   ./deploy.sh --init-password  zusätzlich .htpasswd aus .env.local neu schreiben
# Die Daten (~/vku-basel-app/data) werden nie überschrieben.
set -euo pipefail
cd "$(dirname "$0")"

HOST="scifitre@rueegger.dev"
WEBROOT="public_html/vku-basel.ch"
APP="vku-basel-app"
URL="https://vku-basel.ch"

ssh "$HOST" "mkdir -p ~/$APP/src ~/$APP/data && chmod 700 ~/$APP/data"

rsync -az --delete \
  --exclude 'cgi-bin' --exclude '.well-known' --exclude 'partner/export/.htaccess' \
  public/ "$HOST:$WEBROOT/"
rsync -az --delete src/ "$HOST:$APP/src/"

sed "s#{{HTPASSWD}}#/home/scifitre/$APP/.htpasswd#" deploy/export.htaccess.tpl \
  | ssh "$HOST" "cat > ~/$WEBROOT/partner/export/.htaccess"

if [ "${1:-}" = "--init-password" ]; then
  # shellcheck disable=SC1091
  source .env.local
  php scripts/htpasswd-line.php "$EXPORT_USER" "$EXPORT_PASSWORD" \
    | ssh "$HOST" "cat > ~/$APP/.htpasswd && chmod 644 ~/$APP/.htpasswd"
  echo "Passwort für den Export gesetzt."
fi

page=$(curl -s -o /dev/null -w '%{http_code}' "$URL/partner/")
export_code=$(curl -s -o /dev/null -w '%{http_code}' "$URL/partner/export/")
echo "Smoke-Test: /partner/ → $page, /partner/export/ ohne Passwort → $export_code"
[ "$page" = 200 ] && [ "$export_code" = 401 ]
