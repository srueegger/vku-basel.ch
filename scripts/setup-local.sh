#!/usr/bin/env bash
# Richtet den Passwortschutz für /partner/export in der lokalen DDEV-Umgebung ein.
set -euo pipefail
cd "$(dirname "$0")/.."

if [ ! -f .env.local ]; then
  echo "Fehlt: .env.local mit EXPORT_USER und EXPORT_PASSWORD" >&2
  exit 1
fi
# shellcheck disable=SC1091
source .env.local

ddev exec php scripts/htpasswd-line.php "$EXPORT_USER" "$EXPORT_PASSWORD" | tr -d '\r' > .htpasswd
chmod 644 .htpasswd
sed 's#{{HTPASSWD}}#/var/www/html/.htpasswd#' deploy/export.htaccess.tpl > public/partner/export/.htaccess
echo "Lokaler Export-Schutz eingerichtet."
