<?php
// /partner – Partnerseite mit Anmeldeformular (GET anzeigen, POST speichern, danach Umleitung auf ?danke).

declare(strict_types=1);

$src = is_dir(__DIR__ . '/../../src') ? __DIR__ . '/../../src' : getenv('HOME') . '/vku-basel-app/src';
require $src . '/bootstrap.php';

$https = ($_SERVER['HTTPS'] ?? '') !== '' && $_SERVER['HTTPS'] !== 'off';
session_name('vku');
session_set_cookie_params(['lifetime' => 0, 'path' => '/partner/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Lax']);
session_start();

header('Cache-Control: no-store');

$paths = app_paths();
$secret = guard_secret($paths['secret_file']);

$view = 'form';
$values = ['schule' => '', 'name' => '', 'email' => '', 'tel' => '', 'consent' => false];
$errors = [];
$generalError = null;
$submittedEmail = null;

$redirectToThanks = static function (): never {
    header('Location: /partner/?danke#anmeldung', true, 303);
    exit;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = validate_anmeldung($_POST);
    $values = $result['values'];
    $honeypot = $_POST[HONEYPOT_FIELD] ?? '';
    $ts = is_string($_POST['ts'] ?? null) ? $_POST['ts'] : '';
    $csrf = is_string($_POST['csrf'] ?? null) ? $_POST['csrf'] : null;

    if (!guard_csrf_ok($csrf)) {
        http_response_code(400);
        $generalError = 'Die Seite war zu lange offen. Bitte sende das Formular noch einmal.';
    } elseif ($honeypot !== '' || !guard_timestamp_ok($ts, $secret, time())) {
        $redirectToThanks();
    } elseif ($result['errors'] !== []) {
        http_response_code(422);
        $errors = $result['errors'];
    } else {
        try {
            $pdo = db_connect($paths['data_file']);
            db_insert_anmeldung(
                $pdo,
                $values,
                (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
                mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500),
                new DateTimeImmutable('now')
            );
            session_regenerate_id(true);
            $_SESSION['submitted_email'] = $values['email'];
            $redirectToThanks();
        } catch (Throwable $e) {
            error_log('VKU Anmeldung konnte nicht gespeichert werden: ' . $e->getMessage());
            http_response_code(500);
            $generalError = 'Leider ist ein Fehler aufgetreten. Bitte versuche es später noch einmal oder schreib an info@reinis-fahrschule.ch.';
        }
    }
} elseif (isset($_GET['danke'])) {
    $view = 'success';
    $submittedEmail = is_string($_SESSION['submitted_email'] ?? null) ? $_SESSION['submitted_email'] : null;
    unset($_SESSION['submitted_email']);
}

$csrfToken = guard_csrf_token();
$tsToken = guard_timestamp_issue($secret, time());

require $src . '/templates/partner.php';
