<?php
// Erzeugt data/test.xlsx zur Sichtprüfung in Excel/Numbers: ddev exec php tests/manual/sample_xlsx.php

declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';

$dir = dirname(__DIR__, 2) . '/data';
if (!is_dir($dir)) {
    mkdir($dir, 0770, true);
}

xlsx_write($dir . '/test.xlsx', 'Anmeldungen',
    ['Nr.', 'Datum/Zeit', 'Fahrschule', 'Vorname und Name', 'E-Mail', 'Telefon'],
    [
        [1, new DateTimeImmutable('2026-10-15 14:30'), 'Fahrschule Rüegger & Co', 'Zoë Müller', 'z@x.ch', '079 638 61 38'],
        [2, new DateTimeImmutable('2026-10-16 09:05'), 'Test <AG>', 'Max', 'm@x.ch', '+41 61 123 45 67'],
    ],
    [6, 17, 32, 28, 34, 18]);

echo "data/test.xlsx geschrieben\n";
