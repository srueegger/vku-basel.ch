<?php
// Gibt die Anzahl gespeicherter Anmeldungen aus (für tests/http_test.sh).

declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';

echo count(db_all_anmeldungen(db_connect(app_paths()['data_file']))), "\n";
