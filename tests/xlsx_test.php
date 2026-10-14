<?php

declare(strict_types=1);

function temp_xlsx_file(): string
{
    return sys_get_temp_dir() . '/vku-test-' . bin2hex(random_bytes(4)) . '.xlsx';
}

function xlsx_part(string $file, string $name): string
{
    $zip = new ZipArchive();
    assert_true($zip->open($file) === true, 'ZIP lässt sich nicht öffnen');
    $content = $zip->getFromName($name);
    $zip->close();
    assert_true($content !== false, "Teil fehlt: $name");
    return $content;
}

test('xlsx: Spaltenbuchstaben', function () {
    assert_same('A', xlsx_col(0));
    assert_same('Z', xlsx_col(25));
    assert_same('AA', xlsx_col(26));
    assert_same('AZ', xlsx_col(51));
});

test('xlsx: Datei enthält alle Teile, gültiges XML und escaped Sonderzeichen', function () {
    $file = temp_xlsx_file();
    xlsx_write($file, 'Anmeldungen', ['Nr.', 'Datum/Zeit', 'Fahrschule'], [
        [1, new DateTimeImmutable('2026-10-15 14:30:00'), "Rüegger & <Co> \"AG\" 🚗\x01"],
    ], [6, 17, 32]);

    foreach (['[Content_Types].xml', '_rels/.rels', 'xl/workbook.xml', 'xl/_rels/workbook.xml.rels',
        'xl/styles.xml', 'xl/worksheets/sheet1.xml', 'docProps/core.xml', 'docProps/app.xml'] as $part) {
        assert_true(simplexml_load_string(xlsx_part($file, $part)) !== false, "Ungültiges XML: $part");
    }

    $sheet = xlsx_part($file, 'xl/worksheets/sheet1.xml');
    assert_true(str_contains($sheet, 'Rüegger &amp; &lt;Co&gt;'), 'Escaping fehlt');
    assert_true(str_contains($sheet, '🚗'), 'Emoji fehlt');
    assert_true(str_contains($sheet, 'state="frozen"'), 'Fixierung fehlt');
    assert_true(str_contains($sheet, '<autoFilter ref="A1:C2"/>'), 'Autofilter fehlt');
    assert_true((bool) preg_match('/<c r="B2" s="2"><v>46310\.604166/', $sheet), 'Datumszelle falsch');
    assert_true((bool) preg_match('/<c r="A1" s="1" t="inlineStr">/', $sheet), 'Kopfzeile nicht fett');
    assert_true(str_contains(xlsx_part($file, 'xl/workbook.xml'), 'name="Anmeldungen"'), 'Blattname fehlt');
});

test('xlsx: ohne Datensätze bleibt die Datei gültig', function () {
    $file = temp_xlsx_file();
    xlsx_write($file, 'Anmeldungen', ['Nr.', 'Datum/Zeit', 'Fahrschule'], [], [6, 17, 32]);
    $sheet = xlsx_part($file, 'xl/worksheets/sheet1.xml');
    assert_true(simplexml_load_string($sheet) !== false);
    assert_true(str_contains($sheet, '<autoFilter ref="A1:C1"/>'), 'Autofilter fehlt');
});
