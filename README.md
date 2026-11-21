# VKU-Basel Partnerseite

`vku-basel.ch/partner`: Partnerseite mit Interessenformular (SQLite) und
passwortgeschütztem Excel-Export unter `/partner/export/`. `vku-basel.ch/` ist
bewusst eine leere Seite. Dazu kommt der statische
Klick-Prototyp der neuen Website (siehe unten). Reines PHP 8.3, ohne Framework und ohne Composer.

## Lokal

```bash
ddev start
```

`.env.local` (nicht im Repo) anlegen:

```
EXPORT_USER=vku
EXPORT_PASSWORD=...
```

Dann den Passwortschutz für den Export einrichten:

```bash
bash scripts/setup-local.sh
```

- Seite: https://vku-basel.ddev.site/partner/
- Export: https://vku-basel.ddev.site/partner/export/
- Prototyp: https://vku-basel.ddev.site/<pfad>/ (nach `php scripts/build-prototyp.php`)
- Daten: `data/partner.sqlite`

## Tests

```bash
ddev exec php tests/run.php
bash tests/http_test.sh
bash tests/http_export_test.sh
bash tests/server_layout_test.sh
```

`tests/http_test.sh` schreibt Testdaten in die lokale Datenbank und darf nicht
gegen Produktion laufen.

## Deploy

```bash
./deploy.sh
```

Beim ersten Mal oder nach einem Passwortwechsel in `.env.local`:

```bash
./deploy.sh --init-password
```

Auf dem Server:

| Was | Wo |
|---|---|
| Webroot (Inhalt von `public/`) | `~/public_html/vku-basel.ch/` |
| Code (`src/`) | `~/vku-basel-app/src/` |
| Daten (SQLite, Secret) | `~/vku-basel-app/data/` |
| Passwortdatei | `~/vku-basel-app/.htpasswd` |

## Klick-Prototyp

Quelle in `prototyp/` (Export aus Claude Design, siehe `prototyp/LIESMICH.txt`),
wird nicht direkt ausgeliefert. Die Partnerinnen stehen nur in
`prototyp/partner.json`. `prototyp/versionen.json` legt fest, welche Version unter
welchem Pfad direkt unter `vku-basel.ch/` liegt und welche Partnerinnen sie zeigt.
So sieht jede Partnerin nur sich selbst, auch nicht im Quelltext oder bei den
Bildern.

```bash
php scripts/build-prototyp.php
```

Erzeugt `public/<pfad>/` (nicht im Repo, mit `X-Robots-Tag: noindex`). `deploy.sh`
baut automatisch. Pfad ändern oder Version entfernen: `versionen.json` anpassen,
der nächste Deploy entfernt alte Pfade vom Server.
