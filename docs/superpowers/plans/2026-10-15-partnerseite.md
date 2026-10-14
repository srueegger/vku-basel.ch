# VKU-Basel Partnerseite Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Responsive Interessenten-Seite `vku-basel.ch/partner` mit Formular, SQLite-Speicherung und passwortgeschütztem XLSX-Export unter `/partner/export`.

**Architecture:** Plain PHP 8.3 ohne Framework. Öffentliche Einstiegspunkte in `public/`, Logik als kleine Funktionsdateien in `src/` (Validierung, DB, XLSX, Formularschutz), ein Template. Lokal DDEV (Apache), Produktion cyon via rsync.

**Tech Stack:** PHP 8.3 (PDO SQLite, ZipArchive), Apache `.htaccess` + Basic Auth, Vanilla JS, DDEV v1.25.

**Spec:** `docs/superpowers/specs/2026-10-15-partnerseite-design.md`
Design-Quelle (Texte, Farben, Markup): Claude-Artefakt https://claude.ai/artifact/TbWsH1aSgoxzogmXna3ZiJ, Dateien `project/Main.dc.html` (Mobile), `project/Desktop.dc.html` (Desktop), `project/Mobile-Danke.dc.html`, `project/Mobile-Fehler.dc.html`. Lokale Kopien: Artifact-Tool `read` mit `paths`.

## Global Constraints

- PHP 8.3, keine Composer-Abhängigkeiten, kein Framework, kein MySQL.
- Texte wörtlich aus der Vorlage, keine Flyer-Texte, Sprache `de-CH`, Du-Form.
- Keine E-Mail-Benachrichtigung, kein Datenschutz-/Impressum-Link, Duplikate erlaubt.
- `/` = leeres HTML mit `<meta name="robots" content="noindex">`.
- Export: `/partner/export/`, Basic Auth, Benutzer/Passwort aus `.env.local` (`EXPORT_USER`, `EXPORT_PASSWORD`), nie im Repo.
- Server: `scifitre@rueegger.dev`, Webroot `~/public_html/vku-basel.ch`, App `~/vku-basel-app/{src,data}`, `.htpasswd` in `~/vku-basel-app/.htpasswd`.
- Keine externen Requests im Browser (Fonts lokal).
- Commit-Messages ohne KI-Hinweise oder Co-Authored-By-Trailer.
- Tests laufen mit `ddev exec php tests/run.php` (eigener Mini-Runner, kein PHPUnit).

## Review Focus

1. Nur Leerzeichen in Pflichtfeldern → muss als leer gelten (trim). Test in Task 2.
2. Umlaute, `&`, `<`, `"` und Emoji in Eingaben → korrekt im HTML (escaped) und im XLSX (gültiges XML). Tests in Task 2/4/6.
3. Session abgelaufen / CSRF ungültig beim Absenden → Eingaben bleiben erhalten, verständliche Meldung, kein 500. Test in Task 6.
4. Datenordner fehlt auf dem Server → wird beim ersten Speichern angelegt, Schema automatisch. Test in Task 3.
5. Telefon im Format `+41 79 638 61 38` oder `079/638.61.38` → gültig; Eingaben über 200 Zeichen → Fehlermeldung statt Abschneiden. Tests in Task 2.

---

## File Structure

| Datei | Verantwortung |
|---|---|
| `.ddev/config.yaml` | DDEV-Projekt |
| `public/index.html` | leere Root-Seite |
| `public/.htaccess` | Punkt-Dateien sperren, Sicherheits-Header, `DirectoryIndex` |
| `public/partner/index.php` | Controller: GET/POST, PRG, rendert Template |
| `public/partner/export/index.php` | XLSX-Download |
| `public/assets/css/partner.css` | Styles (aus Vorlage übertragen, Mobile-first, Desktop ab 768px) |
| `public/assets/js/partner.js` | clientseitige Validierung |
| `public/assets/fonts/noto-sans-{400,600,700}.woff2` | Schrift |
| `public/assets/img/{logo.svg|png, icon-*.*, leitlinie.*}` | Bilder aus dem Artefakt |
| `src/bootstrap.php` | Pfade, Zeitzone, Autoload der Funktionsdateien |
| `src/validation.php` | Feldprüfung + Meldungen |
| `src/db.php` | SQLite |
| `src/guard.php` | CSRF + signierter Zeitstempel + Honeypot |
| `src/xlsx.php` | XLSX-Writer |
| `src/templates/partner.php` | HTML der Seite |
| `tests/run.php`, `tests/*_test.php` | Mini-Testrunner + Tests |
| `deploy/export.htaccess.tpl` | Basic-Auth-Vorlage |
| `scripts/setup-local.sh` | lokale `.htpasswd` + Export-`.htaccess` |
| `deploy.sh` | Deploy |

---

### Task 1: Gerüst, DDEV, Testrunner, Root-Seite

**Files:**
- Create: `.ddev/config.yaml`, `public/index.html`, `public/.htaccess`, `src/bootstrap.php`, `tests/run.php`, `tests/bootstrap_test.php`

**Interfaces:**
- Produces:
  - `src/bootstrap.php` definiert `e(?string $s): string` (`htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`) und `app_paths(): array{src:string, data_dir:string, data_file:string, secret_file:string}`; setzt `date_default_timezone_set('Europe/Zurich')`; `require_once` von `validation.php`, `db.php`, `guard.php`, `xlsx.php` (sobald vorhanden; `is_file`-Check).
  - `data_dir`: Umgebungsvariable `VKU_DATA_DIR`, sonst `dirname(__DIR__) . '/data'`. Auf dem Server liegt `src/` in `~/vku-basel-app/src`, also ergibt `dirname(__DIR__).'/data'` dort automatisch `~/vku-basel-app/data`. `data_file` = `data_dir/partner.sqlite`, `secret_file` = `data_dir/secret.key`.
  - `tests/run.php`: lädt `src/bootstrap.php`, alle `tests/*_test.php`; stellt `test(string $name, callable $fn): void`, `assert_same(mixed $expected, mixed $actual, string $msg = ''): void`, `assert_true(bool $cond, string $msg = ''): void` bereit; Ausgabe `PASS name` / `FAIL name: msg`, Exit-Code 1 bei Fehlern, Abschlusszeile `N passed, M failed`.
  - Einstiegspunkte in `public/` finden `src/` so: `$src = is_dir(__DIR__ . '/../../src') ? __DIR__ . '/../../src' : getenv('HOME') . '/vku-basel-app/src';` (Pfadtiefe pro Datei anpassen).

- [ ] **Step 1:** `.ddev/config.yaml`: `name: vku-basel`, `type: php`, `docroot: public`, `php_version: "8.3"`, `webserver_type: apache-fpm`, `omit_containers: [db]`. `ddev start` → Projekt läuft unter `https://vku-basel.ddev.site`.
- [ ] **Step 2:** `public/index.html` (leer, `lang="de-CH"`, `noindex`, `<title>VKU-Basel</title>`), `public/.htaccess`: `Options -Indexes`, `DirectoryIndex index.php index.html`, `<FilesMatch "^\.">Require all denied</FilesMatch>`, Header `X-Content-Type-Options nosniff`, `Referrer-Policy strict-origin-when-cross-origin`, `X-Frame-Options DENY`.
- [ ] **Step 3:** Test `tests/bootstrap_test.php`: `app_paths()['data_file']` endet auf `/data/partner.sqlite`; mit `putenv('VKU_DATA_DIR=/tmp/x')` → `/tmp/x/partner.sqlite`.
- [ ] **Step 4:** `ddev exec php tests/run.php` → FAIL (Funktion fehlt), dann `src/bootstrap.php` + `tests/run.php` implementieren → PASS.
- [ ] **Step 5:** `curl -sk https://vku-basel.ddev.site/` → 200, enthält `noindex`. `curl -sk https://vku-basel.ddev.site/.env.local` → 403 oder 404 (Datei liegt ausserhalb Webroot ohnehin).
- [ ] **Step 6:** Commit `Projektgerüst mit DDEV, Testrunner und leerer Startseite`.

---

### Task 2: Validierung

**Files:**
- Create: `src/validation.php`, `tests/validation_test.php`

**Interfaces:**
- Produces:
  - `const ANMELDUNG_FIELDS = ['schule', 'name', 'email', 'tel', 'consent'];` (Reihenfolge = Reihenfolge der Fehlerliste)
  - `validate_anmeldung(array $input): array{values: array{schule:string,name:string,email:string,tel:string,consent:bool}, errors: array<string,string>}` — Werte getrimmt; `consent` = `isset($input['consent'])`; `errors` nur für fehlerhafte Felder, Schlüssel in `ANMELDUNG_FIELDS`-Reihenfolge.
  - `const FIELD_MAX_LENGTH = 200;`

Meldungen (wörtlich):
- schule leer: `Bitte gib den Namen deiner Fahrschule ein.`
- name leer: `Bitte gib deinen Vornamen und Namen ein.`
- email leer: `Bitte gib deine E-Mail ein.`
- email Format (Regex `/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/u`): `Diese E-Mail scheint unvollständig zu sein, z. B. name@fahrschule.ch.`
- tel leer: `Bitte gib deine Telefonnummer ein.`
- tel < 9 Ziffern: `Bitte gib eine vollständige Telefonnummer ein, z. B. 061 123 45 67.`
- consent fehlt: `Bitte bestätige, dass VKU-Basel dich kontaktieren darf.`
- länger als 200 Zeichen (`mb_strlen`, jedes Textfeld): `Bitte kürze diese Angabe auf höchstens 200 Zeichen.`

- [ ] **Step 1: Tests schreiben** in `tests/validation_test.php`:
  - leeres Array → `array_keys(errors) === ['schule','name','email','tel','consent']`
  - `['schule'=>'   ', ...gültig]` → `errors['schule']` = Leer-Meldung
  - `email 'max@fahrschule'` → Format-Meldung; `'Max.Muster@Mail.Fahrschule.CH'` → gültig
  - `tel '1234'` → Ziffern-Meldung; `'+41 79 638 61 38'` und `'079/638.61.38'` → gültig
  - kein `consent` → consent-Meldung
  - `schule = str_repeat('ä', 201)` → Längen-Meldung; 200 → gültig
  - gültiger Datensatz mit `'  Rüegger & Co <AG>  '` → `errors === []`, `values['schule'] === 'Rüegger & Co <AG>'`, `values['consent'] === true`
- [ ] **Step 2:** `ddev exec php tests/run.php` → FAIL.
- [ ] **Step 3:** `src/validation.php` implementieren.
- [ ] **Step 4:** `ddev exec php tests/run.php` → alle PASS.
- [ ] **Step 5:** Commit `Validierung der Anmeldung`.

---

### Task 3: SQLite-Speicher

**Files:**
- Create: `src/db.php`, `tests/db_test.php`

**Interfaces:**
- Consumes: `app_paths()` (nur im Controller, nicht in `db.php`).
- Produces:
  - `db_connect(string $file): PDO` — legt Elternordner an (`mkdir 0770, recursive`), `ERRMODE_EXCEPTION`, `FETCH_ASSOC`, `PRAGMA journal_mode=WAL`, `PRAGMA busy_timeout=5000`, Schema `CREATE TABLE IF NOT EXISTS anmeldungen (...)` gemäss Spec.
  - `db_insert_anmeldung(PDO $pdo, array $values, string $ip, string $userAgent, DateTimeImmutable $now): int` — `$values` wie aus `validate_anmeldung`; speichert `erstellt_am` als `$now->format('Y-m-d H:i:s')` (Zürich-Zeit), `einwilligung` = 1; gibt neue `id` zurück.
  - `db_all_anmeldungen(PDO $pdo): array<int, array{id:int, erstellt_am:string, fahrschule:string, name:string, email:string, telefon:string}>` — sortiert `id ASC`.

- [ ] **Step 1: Tests** (temporärer Pfad `sys_get_temp_dir().'/vku-test-'.bin2hex(random_bytes(4)).'/sub/partner.sqlite'`, also nicht existierender Ordner):
  - `db_connect` legt Datei an (`is_file`)
  - zweimal einfügen (zweiter Datensatz identisch = Duplikat) → IDs 1 und 2, `db_all_anmeldungen` liefert 2 Zeilen in Reihenfolge, Felder korrekt inkl. Umlaute
  - zweiter `db_connect` auf dieselbe Datei wirft nicht (Schema idempotent)
- [ ] **Step 2:** run → FAIL. **Step 3:** implementieren. **Step 4:** run → PASS.
- [ ] **Step 5:** Commit `SQLite-Speicher für Anmeldungen`.

---

### Task 4: XLSX-Writer

**Files:**
- Create: `src/xlsx.php`, `tests/xlsx_test.php`

**Interfaces:**
- Produces:
  - `xlsx_write(string $path, string $sheetName, array $headers, array $rows, array $columnWidths): void`
    - `$headers`: `list<string>`; `$rows`: `list<list<string|int|DateTimeInterface>>`; `$columnWidths`: `list<float>` (Excel-Zeichenbreiten).
    - Strings → `t="inlineStr"`; int → Zahl; `DateTimeInterface` → Excel-Seriennummer (Tage seit 1899-12-30, Bruchteil für Uhrzeit, aus lokaler Wandzeit) mit Zahlenformat `dd.mm.yyyy hh:mm` (custom numFmt id 164).
    - Kopfzeile Stil fett (styles.xml: 2 Fonts, cellXfs: 0 = normal, 1 = fett, 2 = Datum).
    - `<sheetView>` mit `<pane ySplit="1" topLeftCell="A2" state="frozen"/>`, `<autoFilter ref="A1:{lastCol}{lastRow}"/>`, `<cols>` aus `$columnWidths`.
    - Teile: `[Content_Types].xml`, `_rels/.rels`, `xl/workbook.xml`, `xl/_rels/workbook.xml.rels`, `xl/styles.xml`, `xl/worksheets/sheet1.xml`, `docProps/core.xml`, `docProps/app.xml`.
    - Alle Texte mit `htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8')`; Steuerzeichen (`[\x00-\x08\x0B\x0C\x0E-\x1F]`) entfernen.
    - Zellreferenz-Helfer `xlsx_col(int $index): string` (0 → `A`, 25 → `Z`, 26 → `AA`).

- [ ] **Step 1: Tests:**
  - `xlsx_col(0) === 'A'`, `xlsx_col(25) === 'Z'`, `xlsx_col(26) === 'AA'`
  - Datei schreiben mit Headern `['Nr.','Datum/Zeit','Fahrschule']` und Zeile `[1, new DateTimeImmutable('2026-10-15 14:30:00'), 'Rüegger & <Co> "AG" 🚗']`; `ZipArchive` öffnet sie; enthält alle 8 Teile; `sheet1.xml` ist per `simplexml_load_string` parsebar; enthält `Rüegger &amp; &lt;Co&gt;`; enthält `state="frozen"` und `<autoFilter ref="A1:C2"`; Datumszelle hat Wert, der mit `46310.604166` beginnt (15.10.2026 14:30) und `s="2"`.
  - Leere `$rows` → `autoFilter ref="A1:C1"`, Datei gültig.
- [ ] **Step 2:** FAIL. **Step 3:** implementieren. **Step 4:** PASS.
- [ ] **Step 5:** Manuell: Testdatei nach `data/test.xlsx` schreiben und in Numbers/Excel öffnen (`open data/test.xlsx`) — öffnet ohne Reparaturhinweis.
- [ ] **Step 6:** Commit `XLSX-Writer ohne externe Bibliothek`.

---

### Task 5: Formularschutz (CSRF, Zeitstempel, Honeypot)

**Files:**
- Create: `src/guard.php`, `tests/guard_test.php`

**Interfaces:**
- Produces:
  - `guard_secret(string $file): string` — liest 32-Byte-Hex-Secret aus Datei, erzeugt sie bei Bedarf (`0600`).
  - `guard_timestamp_issue(string $secret, int $now): string` — Format `"{$now}.{hmac_sha256($now, $secret)}"`.
  - `guard_timestamp_ok(string $token, string $secret, int $now, int $minSeconds = 3, int $maxSeconds = 86400): bool` — Signatur gültig (`hash_equals`) und `$minSeconds <= $now - ts <= $maxSeconds`.
  - `guard_csrf_token(): string` — aus `$_SESSION['csrf']`, erzeugt bei Bedarf (`bin2hex(random_bytes(32))`).
  - `guard_csrf_ok(?string $token): bool` — `hash_equals`.
  - `const HONEYPOT_FIELD = 'website';`

- [ ] **Step 1: Tests:** Zeitstempel: frisch (Δ0) → false; Δ3 → true; Δ86401 → false; manipulierte Zeit mit alter Signatur → false; Müll-String → false. `guard_secret` auf neuer Datei erzeugt 64 Hex-Zeichen, zweiter Aufruf liefert dasselbe. CSRF: mit `$_SESSION = []` → Token 64 Zeichen, `guard_csrf_ok(token)` true, `guard_csrf_ok('x')` false, `guard_csrf_ok(null)` false.
- [ ] **Step 2:** FAIL. **Step 3:** implementieren. **Step 4:** PASS.
- [ ] **Step 5:** Commit `Spamschutz und CSRF für das Formular`.

---

### Task 6: Partnerseite (Assets, Template, Controller, JS)

**Files:**
- Create: `public/partner/index.php`, `src/templates/partner.php`, `public/assets/css/partner.css`, `public/assets/js/partner.js`, `public/assets/fonts/*`, `public/assets/img/*`, `tests/http_test.sh`

**Interfaces:**
- Consumes: `app_paths()`, `validate_anmeldung()`, `ANMELDUNG_FIELDS`, `db_connect()`, `db_insert_anmeldung()`, `guard_*`, `HONEYPOT_FIELD`.
- Produces: Template-Variablen `$view` (`'form'|'success'`), `$values` (array), `$errors` (array<string,string>), `$generalError` (?string), `$submittedEmail` (?string), `$csrf` (string), `$ts` (string).

- [ ] **Step 1: Assets holen.**
  - Bilder: Artifact-Tool `read` auf https://claude.ai/artifact/TbWsH1aSgoxzogmXna3ZiJ mit `path` = Asset-ID: `5bf9f0d032c3e86ec8761b37c853c290` (Logo, 139×44 mobil / 164×52 desktop) → `img/logo.*`; `ad7792eaaadfaa049fed134ab866e974` → `img/icon-kosten.*`; `6f715b568f5220dbc197cdbf73d93f88` → `img/icon-kundschaft.*`; `c97643a9324e4b94c41705ddaabc8f1e` → `img/icon-fair.*`; `285315f72dac12d5e12276de984eccf7` → `img/leitlinie.*` (Endung nach geliefertem Typ).
  - Fonts: Noto Sans latin 400/600/700 woff2 von `https://cdn.jsdelivr.net/npm/@fontsource/noto-sans@5/files/noto-sans-latin-{400,600,700}-normal.woff2` → `fonts/noto-sans-{400,600,700}.woff2`.
- [ ] **Step 2: CSS** `partner.css`: `@font-face` ×3 (`font-display: swap`), alle Inline-Styles und `<helmet>`-Regeln der Vorlage als Klassen. Mobile-Werte aus `Main.dc.html`, ab `@media (min-width: 768px)` die Desktop-Werte aus `Desktop.dc.html` (max-width 1120px, `clamp()`-Werte übernehmen, Vorteile-Grid `repeat(auto-fit, minmax(240px, 1fr))`, Anmeldebereich zweispaltig flex-wrap mit weisser Formularkarte). Honeypot visuell versteckt (`position:absolute; left:-9999px`), nicht `display:none`.
- [ ] **Step 3: Template** `src/templates/partner.php`: Markup aus der Vorlage (Header, Hero, Leitlinie, Vorteile, Partnerstufen-Box, `#anmeldung`, Footer) mit Klassen statt Inline-Styles; alle dynamischen Werte über `e()` aus Task 1. Formular `method="post" action="/partner/#anmeldung" novalidate`, Felder mit `name`/`id`/`autocomplete`/`inputmode` wie Spec, `aria-invalid`, `aria-describedby`, Klasse `inp is-error` bei Fehler, Fehlerzusammenfassung `role="alert"` mit Titel `Eine Angabe fehlt noch:` bzw. `N Angaben fehlen noch:` und Links `#f-{key}`. Hidden-Felder `csrf`, `ts`, Honeypot `<label>`+`<input name="website" tabindex="-1" autocomplete="off">` in `aria-hidden`-Container. `$generalError` (CSRF) als Alert über dem Formular: `Die Seite war zu lange offen. Bitte sende das Formular noch einmal.` Danke-Ansicht wie `Mobile-Danke.dc.html`; ohne `$submittedEmail` lautet Punkt 1: `Du bekommst eine persönliche Rückmeldung per E-Mail oder telefonisch.`
- [ ] **Step 4: Controller** `public/partner/index.php`: Session (`session_name('vku')`, Cookie `httponly`, `samesite=Lax`, `secure` wenn HTTPS). GET `?danke` → `$view='success'`, E-Mail aus `$_SESSION['submitted_email']` (danach entfernen). POST: CSRF falsch → Formular mit Werten + `$generalError`, Status 400. Honeypot gefüllt oder Zeitstempel ungültig → `303 Location: /partner/?danke#anmeldung` ohne Speichern. Validierungsfehler → Status 422. Gültig → `db_insert_anmeldung` (IP `REMOTE_ADDR`, UA gekürzt auf 500 Zeichen, `new DateTimeImmutable('now')`), `$_SESSION['submitted_email']`, `303` auf `/partner/?danke#anmeldung`. DB-Fehler → Status 500 mit Meldung `Leider ist ein Fehler aufgetreten. Bitte versuche es später noch einmal oder schreib an info@reinis-fahrschule.ch.` und Werte bleiben, `error_log` des Fehlers.
- [ ] **Step 5: JS** `partner.js` (defer): auf `submit` dieselben Regeln/Meldungen wie `validation.php` (Meldungen als Objekt im JS duplizieren, Kommentar verweist auf `src/validation.php`); bei Fehlern `preventDefault`, Zusammenfassung + Feldfehler rendern (gleiche Markup-Struktur/Klassen wie Template), Fokus auf Zusammenfassung. Nach erstem Versuch bei `input` neu prüfen. Kein Fehler → normal absenden.
- [ ] **Step 6: HTTP-Test** `tests/http_test.sh` (bash + curl gegen `${BASE:-https://vku-basel.ddev.site}`, Cookie-Jar, `-k`), Testfälle mit `grep`-Assertions:
  1. GET `/partner/` → 200, enthält `Gemeinsam stark: Werde jetzt Partner`, keine Anfrage an `fonts.googleapis`.
  2. POST leer (gültiges CSRF, `ts` aus Seite, nach `sleep 3`) → 422, enthält `5 Angaben fehlen noch:`.
  3. POST gültig mit `schule=Rüegger & Co` → 303 auf `/partner/?danke#anmeldung`; Folge-GET enthält die eingegebene E-Mail; Zeilenzahl (via `ddev exec php -r` mit `db_all_anmeldungen`) um 1 gestiegen.
  4. POST mit `schule=<b>x</b>` und leerer E-Mail → 422, Antwort enthält `&lt;b&gt;x` und nicht `<b>x`.
  5. POST mit falschem CSRF → 400, enthält `Die Seite war zu lange offen`, Feldwert erhalten.
  6. POST mit Honeypot gefüllt → 303, Zeilenzahl unverändert.
  Das Skript nutzt `VKU_DATA_DIR` nicht; es schreibt in die lokale `data/partner.sqlite` (Testdaten lokal sind ok).
- [ ] **Step 7:** `bash tests/http_test.sh` → alle PASS; `ddev exec php tests/run.php` → PASS.
- [ ] **Step 8: Sichtprüfung** im Browser (Viewport 390×844 und 1280×900): Formular, Fehlerzustand, Danke — mit den Artboards vergleichen; Tastaturbedienung (Tab-Reihenfolge, Fokusrahmen); ohne JS (Server-Validierung) einmal prüfen.
- [ ] **Step 9:** Commit `Partnerseite mit Formular nach Designvorlage`.

---

### Task 7: Excel-Export mit Basic Auth

**Files:**
- Create: `public/partner/export/index.php`, `deploy/export.htaccess.tpl`, `scripts/setup-local.sh`, `tests/http_export_test.sh`

**Interfaces:**
- Consumes: `app_paths()`, `db_connect()`, `db_all_anmeldungen()`, `xlsx_write()`.

- [ ] **Step 1:** `deploy/export.htaccess.tpl`:
  ```
  AuthType Basic
  AuthName "VKU-Basel Export"
  AuthUserFile {{HTPASSWD}}
  Require valid-user
  Header set X-Robots-Tag "noindex, nofollow"
  ```
- [ ] **Step 2:** `scripts/setup-local.sh`: liest `.env.local`, erzeugt `.htpasswd` im Projektordner mit `ddev exec htpasswd -nbB "$EXPORT_USER" "$EXPORT_PASSWORD"` (Fallback: `ddev exec php -r 'echo $argv[1].":".password_hash($argv[2], PASSWORD_BCRYPT);'`), schreibt `public/partner/export/.htaccess` aus Vorlage mit `{{HTPASSWD}}` = `/var/www/html/.htpasswd`.
- [ ] **Step 3:** `export/index.php`: alle Anmeldungen laden, Zeilen `[id, DateTimeImmutable(erstellt_am), fahrschule, name, email, telefon]`, Header `['Nr.', 'Datum/Zeit', 'Fahrschule', 'Vorname und Name', 'E-Mail', 'Telefon']`, Breiten `[6, 17, 32, 28, 34, 18]`, Blatt `Anmeldungen`; in Tempdatei schreiben, Header `Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`, `Content-Disposition: attachment; filename="anmeldungen-YYYY-MM-DD.xlsx"`, `Cache-Control: no-store`, `Content-Length`; `readfile`, Tempdatei löschen.
- [ ] **Step 4:** `tests/http_export_test.sh`: ohne Auth → 401; falsches Passwort → 401; richtiges (aus `.env.local`) → 200, `Content-Type` wie oben, Dateiname mit heutigem Datum, Body beginnt mit `PK`, `unzip -p - xl/worksheets/sheet1.xml` enthält Spaltenkopf `Fahrschule`.
- [ ] **Step 5:** `bash scripts/setup-local.sh && bash tests/http_export_test.sh` → PASS. Heruntergeladene Datei in Numbers/Excel öffnen: Kopf fett/fixiert, Filter, Datum korrekt formatiert.
- [ ] **Step 6:** Commit `Passwortgeschützter Excel-Export`.

---

### Task 8: Deploy

**Files:**
- Create: `deploy.sh`, `README.md`

- [ ] **Step 1:** `deploy.sh` (`set -euo pipefail`, Host `scifitre@rueegger.dev`):
  1. `ssh … 'mkdir -p ~/vku-basel-app/src ~/vku-basel-app/data && chmod 700 ~/vku-basel-app/data'`
  2. `rsync -az --delete --exclude 'cgi-bin' --exclude 'partner/export/.htaccess' public/ host:public_html/vku-basel.ch/`
  3. `rsync -az --delete src/ host:vku-basel-app/src/`
  4. Export-`.htaccess` aus Vorlage mit `{{HTPASSWD}}=/home/scifitre/vku-basel-app/.htpasswd` erzeugen und per `scp` hochladen.
  5. Falls `--init-password`: `.htpasswd` mit bcrypt-Hash (lokal erzeugt, wie in setup-local) per `ssh … 'cat > ~/vku-basel-app/.htpasswd && chmod 644 …'` schreiben.
  6. Smoke-Test: `curl -s -o /dev/null -w '%{http_code}' https://vku-basel.ch/partner/` = 200, `/partner/export/` = 401.
- [ ] **Step 2:** `README.md`: lokale Einrichtung (`ddev start`, `.env.local`, `scripts/setup-local.sh`), Tests, Deploy, Export-URL. Kein Passwort darin.
- [ ] **Step 3:** **Vor dem ersten Lauf Freigabe beim Nutzer einholen.** Dann `./deploy.sh --init-password`, Smoke-Test grün, Export mit Passwort auf Prod einmal herunterladen (leere Liste OK), dann `bash tests/http_test.sh` **nicht** gegen Prod laufen lassen (würde Testdaten speichern).
- [ ] **Step 4:** Commit `Deploy-Skript und README`.
