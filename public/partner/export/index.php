<?php
// /partner/export – liefert alle Anmeldungen als XLSX. Schutz per Basic Auth (.htaccess).

declare(strict_types=1);

$src = is_dir(__DIR__ . '/../../../src') ? __DIR__ . '/../../../src' : getenv('HOME') . '/vku-basel-app/src';
require $src . '/bootstrap.php';

$rows = array_map(static fn (array $a): array => [
    $a['id'],
    new DateTimeImmutable($a['erstellt_am']),
    $a['fahrschule'],
    $a['name'],
    $a['email'],
    $a['telefon'],
], db_all_anmeldungen(db_connect(app_paths()['data_file'])));

$tmp = tempnam(sys_get_temp_dir(), 'vku-export-');
try {
    xlsx_write($tmp, 'Anmeldungen',
        ['Nr.', 'Datum/Zeit', 'Fahrschule', 'Vorname und Name', 'E-Mail', 'Telefon'],
        $rows,
        [6, 17, 32, 28, 34, 18]);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="anmeldungen-' . date('Y-m-d') . '.xlsx"');
    header('Content-Length: ' . filesize($tmp));
    header('Cache-Control: no-store');
    readfile($tmp);
} finally {
    @unlink($tmp);
}
