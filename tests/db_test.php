<?php

declare(strict_types=1);

function temp_db_file(): string
{
    return sys_get_temp_dir() . '/vku-test-' . bin2hex(random_bytes(4)) . '/sub/partner.sqlite';
}

test('db: legt fehlenden Ordner und Datei an', function () {
    $file = temp_db_file();
    db_connect($file);
    assert_true(is_file($file), 'Datei fehlt: ' . $file);
});

test('db: speichert Duplikate und liest in Reihenfolge', function () {
    $pdo = db_connect(temp_db_file());
    $values = ['schule' => 'Fahrschule Rüegger', 'name' => 'Zoë Müller', 'email' => 'z@x.ch', 'tel' => '079 638 61 38', 'consent' => true];
    $now = new DateTimeImmutable('2026-10-15 14:30:00');

    assert_same(1, db_insert_anmeldung($pdo, $values, '127.0.0.1', 'UA', $now));
    assert_same(2, db_insert_anmeldung($pdo, $values, '127.0.0.1', 'UA', $now));

    $rows = db_all_anmeldungen($pdo);
    assert_same(2, count($rows));
    assert_same([
        'id' => 1,
        'erstellt_am' => '2026-10-15 14:30:00',
        'fahrschule' => 'Fahrschule Rüegger',
        'name' => 'Zoë Müller',
        'email' => 'z@x.ch',
        'telefon' => '079 638 61 38',
    ], $rows[0]);
    assert_same(2, $rows[1]['id']);
});

test('db: zweites Verbinden auf bestehende Datei ist idempotent', function () {
    $file = temp_db_file();
    db_connect($file);
    $pdo = db_connect($file);
    assert_same([], db_all_anmeldungen($pdo));
});
