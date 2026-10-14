<?php

declare(strict_types=1);

function valid_input(array $override = []): array
{
    return array_merge([
        'schule' => 'Fahrschule Muster',
        'name' => 'Max Muster',
        'email' => 'max@fahrschule.ch',
        'tel' => '061 123 45 67',
        'consent' => '1',
    ], $override);
}

test('validation: leeres Formular meldet alle Felder in fester Reihenfolge', function () {
    $r = validate_anmeldung([]);
    assert_same(['schule', 'name', 'email', 'tel', 'consent'], array_keys($r['errors']));
    assert_same('Bitte gib den Namen deiner Fahrschule ein.', $r['errors']['schule']);
    assert_same('Bitte gib deinen Vornamen und Namen ein.', $r['errors']['name']);
    assert_same('Bitte gib deine E-Mail ein.', $r['errors']['email']);
    assert_same('Bitte gib deine Telefonnummer ein.', $r['errors']['tel']);
    assert_same('Bitte bestätige, dass VKU-Basel dich kontaktieren darf.', $r['errors']['consent']);
});

test('validation: nur Leerzeichen gilt als leer', function () {
    $r = validate_anmeldung(valid_input(['schule' => "   \t "]));
    assert_same(['schule' => 'Bitte gib den Namen deiner Fahrschule ein.'], $r['errors']);
});

test('validation: E-Mail ohne Endung ist ungültig', function () {
    $r = validate_anmeldung(valid_input(['email' => 'max@fahrschule']));
    assert_same('Diese E-Mail scheint unvollständig zu sein, z. B. name@fahrschule.ch.', $r['errors']['email'] ?? null);
});

test('validation: E-Mail mit Grossbuchstaben und Subdomain ist gültig', function () {
    $r = validate_anmeldung(valid_input(['email' => 'Max.Muster@Mail.Fahrschule.CH']));
    assert_same([], $r['errors']);
});

test('validation: Telefon mit zu wenig Ziffern ist ungültig', function () {
    $r = validate_anmeldung(valid_input(['tel' => '1234']));
    assert_same('Bitte gib eine vollständige Telefonnummer ein, z. B. 061 123 45 67.', $r['errors']['tel'] ?? null);
});

test('validation: Telefon im Format +41 und mit Trennzeichen ist gültig', function () {
    assert_same([], validate_anmeldung(valid_input(['tel' => '+41 79 638 61 38']))['errors']);
    assert_same([], validate_anmeldung(valid_input(['tel' => '079/638.61.38']))['errors']);
});

test('validation: fehlende Einwilligung', function () {
    $input = valid_input();
    unset($input['consent']);
    $r = validate_anmeldung($input);
    assert_same(['consent' => 'Bitte bestätige, dass VKU-Basel dich kontaktieren darf.'], $r['errors']);
    assert_same(false, $r['values']['consent']);
});

test('validation: mehr als 200 Zeichen wird abgelehnt, 200 sind ok', function () {
    $r = validate_anmeldung(valid_input(['schule' => str_repeat('ä', 201)]));
    assert_same('Bitte kürze diese Angabe auf höchstens 200 Zeichen.', $r['errors']['schule'] ?? null);
    assert_same(str_repeat('ä', 201), $r['values']['schule']);
    assert_same([], validate_anmeldung(valid_input(['schule' => str_repeat('ä', 200)]))['errors']);
});

test('validation: gültiger Datensatz wird getrimmt, Sonderzeichen bleiben', function () {
    $r = validate_anmeldung(valid_input(['schule' => '  Rüegger & Co <AG>  ']));
    assert_same([], $r['errors']);
    assert_same('Rüegger & Co <AG>', $r['values']['schule']);
    assert_same(true, $r['values']['consent']);
});

test('validation: Nicht-String-Eingaben (Arrays) gelten als leer', function () {
    $r = validate_anmeldung(valid_input(['name' => ['x']]));
    assert_same('Bitte gib deinen Vornamen und Namen ein.', $r['errors']['name'] ?? null);
});
