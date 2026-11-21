<?php
// Baut aus prototyp/ je eine Version pro Eintrag in prototyp/versionen.json nach public/<pfad>/.
// Jede Version enthält nur die Daten und Fotos ihrer Partnerinnen.
//   php scripts/build-prototyp.php

declare(strict_types=1);

const MARKER = "# Vom Build erzeugt (scripts/build-prototyp.php), nicht bearbeiten.\n";
const NICHT_KOPIEREN = ['partner.json', 'versionen.json', 'LIESMICH.txt'];

$root = dirname(__DIR__);
$src = "$root/prototyp";
$public = "$root/public";

$partner = json_decode((string) file_get_contents("$src/partner.json"), true, flags: JSON_THROW_ON_ERROR);
$versionen = json_decode((string) file_get_contents("$src/versionen.json"), true, flags: JSON_THROW_ON_ERROR);
unset($versionen['_hinweis']);

$byId = [];
foreach ($partner as $p) {
    $byId[$p['id']] = $p;
}
$alleFotos = array_column($partner, 'photo');

function fail(string $msg): never
{
    fwrite(STDERR, "Fehler: $msg\n");
    exit(1);
}

function rmTree(string $dir): void
{
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $f) {
        $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
    }
    rmdir($dir);
}

// Frühere Versionen (erkennbar an der .gitignore mit Marker) entfernen.
foreach (glob("$public/*", GLOB_ONLYDIR) as $dir) {
    $gi = "$dir/.gitignore";
    if (is_file($gi) && str_starts_with((string) file_get_contents($gi), MARKER)) {
        rmTree($dir);
    }
}

foreach ($versionen as $pfad => $ids) {
    if (!preg_match('/^[a-z0-9]{4,}$/', (string) $pfad)) {
        fail("ungültiger Pfad «{$pfad}»");
    }
    $ziel = "$public/$pfad";
    if (file_exists($ziel)) {
        fail("public/$pfad existiert schon und stammt nicht vom Build");
    }
    $auswahl = [];
    foreach ($ids as $id) {
        $auswahl[] = $byId[$id] ?? fail("Partnerin «{$id}» fehlt in partner.json");
    }
    if (!$auswahl) {
        fail("Version «{$pfad}» hat keine Partnerin");
    }
    $fremdeFotos = array_diff($alleFotos, array_column($auswahl, 'photo'));

    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($it as $f) {
        $rel = substr($f->getPathname(), strlen($src) + 1);
        if (in_array($rel, NICHT_KOPIEREN, true) || str_starts_with(basename($rel), '.')) {
            continue;
        }
        if (str_starts_with($rel, 'assets/img/') && in_array(basename($rel), $fremdeFotos, true)) {
            continue;
        }
        $out = "$ziel/$rel";
        if ($f->isDir()) {
            mkdir($out, 0755, true);
        } else {
            @mkdir(dirname($out), 0755, true);
            copy($f->getPathname(), $out);
        }
    }

    $json = json_encode($auswahl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    $js = (string) file_get_contents("$ziel/assets/js/partner.js");
    if (substr_count($js, '/*PARTNER*/[]') !== 1) {
        fail('Platzhalter /*PARTNER*/[] fehlt in assets/js/partner.js');
    }
    file_put_contents("$ziel/assets/js/partner.js", str_replace('/*PARTNER*/[]', $json, $js));

    file_put_contents("$ziel/.htaccess", "# Klick-Prototyp: öffentlich erreichbar, aber nicht in Suchmaschinen aufnehmen.\n"
        . "<IfModule mod_headers.c>\n    Header always set X-Robots-Tag \"noindex, nofollow\"\n</IfModule>\n");
    file_put_contents("$ziel/.gitignore", MARKER . "*\n");

    echo "public/$pfad/ → " . implode(', ', array_column($auswahl, 'name')) . "\n";
}
