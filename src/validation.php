<?php
// Prüfung des Anmeldeformulars. Die Meldungen sind in public/assets/js/partner.js gespiegelt.

declare(strict_types=1);

const ANMELDUNG_FIELDS = ['schule', 'name', 'email', 'tel', 'consent'];
const FIELD_MAX_LENGTH = 200;

/**
 * @return array{values: array{schule:string, name:string, email:string, tel:string, consent:bool}, errors: array<string,string>}
 */
function validate_anmeldung(array $input): array
{
    $text = static fn (string $key): string => is_string($input[$key] ?? null) ? trim($input[$key]) : '';

    $values = [
        'schule' => $text('schule'),
        'name' => $text('name'),
        'email' => $text('email'),
        'tel' => $text('tel'),
        'consent' => isset($input['consent']),
    ];

    $required = [
        'schule' => 'Bitte gib den Namen deiner Fahrschule ein.',
        'name' => 'Bitte gib deinen Vornamen und Namen ein.',
        'email' => 'Bitte gib deine E-Mail ein.',
        'tel' => 'Bitte gib deine Telefonnummer ein.',
    ];

    $errors = [];
    foreach ($required as $key => $message) {
        if ($values[$key] === '') {
            $errors[$key] = $message;
        } elseif (mb_strlen($values[$key]) > FIELD_MAX_LENGTH) {
            $errors[$key] = 'Bitte kürze diese Angabe auf höchstens 200 Zeichen.';
        }
    }

    if (!isset($errors['email']) && !preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/u', $values['email'])) {
        $errors['email'] = 'Diese E-Mail scheint unvollständig zu sein, z. B. name@fahrschule.ch.';
    }

    if (!isset($errors['tel']) && strlen(preg_replace('/\D/', '', $values['tel'])) < 9) {
        $errors['tel'] = 'Bitte gib eine vollständige Telefonnummer ein, z. B. 061 123 45 67.';
    }

    if (!$values['consent']) {
        $errors['consent'] = 'Bitte bestätige, dass VKU-Basel dich kontaktieren darf.';
    }

    $ordered = [];
    foreach (ANMELDUNG_FIELDS as $key) {
        if (isset($errors[$key])) {
            $ordered[$key] = $errors[$key];
        }
    }

    return ['values' => $values, 'errors' => $ordered];
}
