# VKU-Basel Partnerseite – Design

Stand: 2026-10-15

## Ziel

Fahrlehrerinnen und Fahrlehrer gelangen per QR-Code auf `vku-basel.ch/partner`,
lesen das Partnerangebot und melden unverbindlich ihr Interesse an. Die
Betreiber laden alle Anmeldungen über eine geheime, passwortgeschützte URL als
Excel-Datei (XLSX) herunter.

## Vorgaben

- Gestaltung und Texte exakt nach der Design-Vorlage
  (Claude-Artefakt «VKU Basel Partnerseite», Artboards Mobile · Formular,
  Mobile · Fehlermeldungen, Mobile · Danke, Desktop). Keine Texte aus den Flyern.
- `vku-basel.ch/` bleibt eine leere Seite. Nur `/partner` hat Inhalt.
- Reines PHP, kein Framework, keine Composer-Abhängigkeiten.
- Speicherung in einer SQLite-Datei, kein MySQL.
- Lokale Entwicklung mit DDEV.
- Produktion: `ssh scifitre@rueegger.dev`, Webroot `~/public_html/vku-basel.ch`
  (cyon Shared Hosting, PHP 8.3.30, `pdo_sqlite`, `zip`, `xmlwriter` vorhanden,
  kein Composer).
- Keine E-Mail-Benachrichtigung, kein Datenschutz-/Impressum-Link.
  Doppelte Anmeldungen sind erlaubt.

## Struktur

```
vku-basel.ch/
├── .ddev/config.yaml        PHP 8.3, webserver apache-fpm, ohne DB-Container
├── public/                  Webroot (lokal und Server)
│   ├── index.html           leere Seite, noindex
│   ├── .htaccess            Punkt-Dateien sperren, Sicherheits-Header
│   ├── assets/              CSS, JS, Noto Sans (woff2), Logo, Icons, Leitlinie
│   └── partner/
│       ├── index.php        Seite + Formular (GET anzeigen, POST speichern)
│       └── export-b5f7ae8af658b752/
│           ├── .htaccess    Basic Auth
│           └── index.php    liefert anmeldungen-JJJJ-MM-TT.xlsx
├── src/                     nicht öffentlich
│   ├── config.php           Pfade (Datendatei), umgebungsabhängig
│   ├── db.php               SQLite: Verbindung, Schema, insert, alle lesen
│   ├── validation.php       Pflichtfelder/Formate, deutsche Fehlermeldungen
│   ├── xlsx.php             minimaler XLSX-Writer (ZipArchive)
│   └── templates/partner.php
├── data/                    lokal: SQLite-Datei (gitignored)
├── tests/                   PHP-Testskripte ohne PHPUnit
├── deploy/export.htaccess.tpl  Vorlage Basic Auth
├── scripts/setup-local.sh   lokale .htpasswd + Export-.htaccess
├── .env.local               Export-Passwort (gitignored)
└── deploy.sh                rsync auf den Server
```

Auf dem Server:

- `public/*` → `~/public_html/vku-basel.ch/`
- `src/` → `~/vku-basel-app/src/`
- Datendatei → `~/vku-basel-app/data/partner.sqlite`
- `.htpasswd` → `~/vku-basel-app/.htpasswd`

`public/partner/index.php` findet `src/` über einen Pfad aus einer kleinen
Bootstrap-Logik: zuerst `../../src` (lokal), sonst `~/vku-basel-app/src`
(Server). Das bestimmt auch den Pfad der Datendatei.

## Seite `/partner`

Eine responsive Seite. Mobile-Layout bis ca. 768 px, darüber das Desktop-Layout
(max. 1120 px Inhaltsbreite, Vorteile im Raster, Formular als Karte rechts
neben dem Einleitungstext). Farben, Abstände, Schriftgrössen und Texte 1:1 aus
der Vorlage. Noto Sans 400/600/700 lokal als woff2, keine Anfragen an Google.

Bereiche: Header (Wortmarke, Knopf «Anmelden» → `#anmeldung`), Hero,
Leitlinie, «Was du davon hast» (3 Vorteile), Box Partnerstufen,
Anmeldebereich (Formular oder Danke), Footer (Adresse, E-Mail, Telefon,
Copyright).

## Formular

Felder (alle Pflicht):

| Feld | name | Typ | Prüfung |
|---|---|---|---|
| Name der Fahrschule | `schule` | text | nicht leer |
| Vorname und Name | `name` | text | nicht leer |
| E-Mail | `email` | email | nicht leer, Format `x@y.zz` |
| Telefon | `tel` | tel | nicht leer, mind. 9 Ziffern |
| Einwilligung | `consent` | checkbox | angehakt |

Fehlermeldungen wörtlich aus der Vorlage. Maximallänge je Textfeld 200 Zeichen.

Ablauf:

1. `GET /partner` zeigt das Formular mit CSRF-Token (Session), Zeitstempel
   (signiert) und einem verborgenen Honeypot-Feld.
2. `POST /partner`:
   - CSRF ungültig → Formular erneut mit allgemeiner Meldung.
   - Honeypot gefüllt oder weniger als 3 Sekunden seit dem Laden →
     Weiterleitung auf die Danke-Ansicht, nichts gespeichert.
   - Validierungsfehler → Formular mit Eingaben, Fehlerzusammenfassung
     (`role="alert"`, Links auf die Felder) und Feldfehlern, HTTP 422.
   - Gültig → Datensatz speichern, E-Mail in der Session merken,
     `303` auf `/partner?danke#anmeldung`.
3. `GET /partner?danke` zeigt die Danke-Ansicht mit der E-Mail aus der Session
   (ohne Session: Danke-Ansicht ohne Adresse-Hervorhebung, generischer Satz).

Progressive Enhancement: Ein kleines JS validiert vor dem Absenden mit
denselben Meldungen und rendert Zusammenfassung und Feldfehler. Ohne JS
funktioniert alles serverseitig.

## Daten

SQLite-Tabelle `anmeldungen`:

| Spalte | Typ |
|---|---|
| id | INTEGER PRIMARY KEY AUTOINCREMENT |
| erstellt_am | TEXT, ISO 8601, Zeitzone Europe/Zurich |
| fahrschule | TEXT |
| name | TEXT |
| email | TEXT |
| telefon | TEXT |
| einwilligung | INTEGER (1) |
| ip | TEXT |
| user_agent | TEXT |

Schema wird beim ersten Zugriff angelegt (`CREATE TABLE IF NOT EXISTS`).
Prepared Statements, WAL-Modus, `busy_timeout`.

## Excel-Export

- URL: `/partner/export-b5f7ae8af658b752/`
- Schutz: Basic Auth über `.htaccess`, Benutzer `vku`. Das Passwort wurde vom
  Auftraggeber festgelegt und steht nur in der lokalen, nicht versionierten
  `.env.local` sowie gehasht in der `.htpasswd` (ausserhalb des Webroots).
  `X-Robots-Tag: noindex`.
- Antwort: `anmeldungen-JJJJ-MM-TT.xlsx`, Content-Type
  `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`.
- Inhalt: ein Blatt «Anmeldungen», Spalten Nr., Datum/Zeit, Fahrschule,
  Vorname und Name, E-Mail, Telefon. Kopfzeile fett, fixiert, Autofilter,
  sinnvolle Spaltenbreiten. Texte als Inline-Strings (Telefonnummern bleiben
  Text). Datum/Zeit als Excel-Datum mit Format `dd.mm.yyyy hh:mm`.
- Erzeugt mit `ZipArchive` und handgeschriebenem SpreadsheetML, ohne Bibliothek.

## Root `/`

`public/index.html`: leeres HTML-Dokument mit `<meta name="robots" content="noindex">`.

## Sicherheit

- `.htaccess` im Webroot: Zugriff auf Punkt-Dateien sperren, `X-Content-Type-Options`,
  `Referrer-Policy`, `X-Frame-Options: DENY`.
- Session-Cookie `HttpOnly`, `SameSite=Lax`, `Secure` bei HTTPS.
- Alle Ausgaben mit `htmlspecialchars`.
- Datendatei, Quellcode und `.htpasswd` liegen nicht im Webroot.

## Lokale Entwicklung

DDEV-Projekt `vku-basel`, Typ `php`, `docroot: public`, PHP 8.3,
`webserver_type: apache-fpm` (damit `.htaccess` inkl. Basic Auth greift),
`omit_containers: [db]`.

Basic Auth braucht in `AuthUserFile` einen absoluten Pfad, der lokal und auf
dem Server verschieden ist. Deshalb ist die Export-`.htaccess` nicht
versioniert, sondern wird aus `deploy/export.htaccess.tpl` erzeugt:

- `scripts/setup-local.sh` liest das Passwort aus `.env.local`, schreibt
  `.htpasswd` (gitignored) in den Projektordner und die `.htaccess` mit
  `AuthUserFile /var/www/html/.htpasswd`.
- `deploy.sh` erzeugt die Server-Variante mit
  `AuthUserFile /home/scifitre/vku-basel-app/.htpasswd`.

## Deploy

`deploy.sh`:

1. `rsync -az --delete public/ scifitre@rueegger.dev:public_html/vku-basel.ch/`
   (ohne `cgi-bin`, ohne lokale Export-`.htaccess`)
2. `rsync -az --delete src/ scifitre@rueegger.dev:vku-basel-app/src/`
3. Export-`.htaccess` mit Serverpfad hochladen.
4. `data/` wird nie synchronisiert.

Einmalig: `~/vku-basel-app/data/` anlegen, `.htpasswd` erzeugen
(Hash lokal mit `htpasswd -nbB` bzw. PHP `password_hash` im bcrypt-Format).

## Tests

- `tests/validation_test.php`: leere Felder, ungültige E-Mail, zu kurze
  Telefonnummer, fehlende Einwilligung, gültiger Datensatz.
- `tests/xlsx_test.php`: erzeugte Datei ist ein gültiges ZIP mit den
  erwarteten Teilen, enthält Kopfzeile und Datensätze, Sonderzeichen
  (Umlaute, `&`, `<`) korrekt escaped.
- `tests/db_test.php`: Schema anlegen, einfügen, auslesen (temporäre Datei).
- Manuell unter DDEV: Formular mobil/desktop, Fehlerfall, Danke, Export mit
  und ohne Passwort, XLSX in Excel/Numbers öffnen.
