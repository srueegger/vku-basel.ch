<?php
// Formularschutz: CSRF-Token, signierter Ladezeitpunkt, Honeypot.

declare(strict_types=1);

const HONEYPOT_FIELD = 'website';

function guard_secret(string $file): string
{
    if (is_file($file)) {
        $secret = trim((string) file_get_contents($file));
        if (preg_match('/^[0-9a-f]{64}$/', $secret)) {
            return $secret;
        }
    }

    $dir = dirname($file);
    if (!is_dir($dir) && !mkdir($dir, 0770, true) && !is_dir($dir)) {
        throw new RuntimeException("Datenordner kann nicht angelegt werden: $dir");
    }
    $secret = bin2hex(random_bytes(32));
    file_put_contents($file, $secret, LOCK_EX);
    chmod($file, 0600);

    return $secret;
}

function guard_timestamp_issue(string $secret, int $now): string
{
    return $now . '.' . hash_hmac('sha256', (string) $now, $secret);
}

function guard_timestamp_ok(string $token, string $secret, int $now, int $minSeconds = 3, int $maxSeconds = 86400): bool
{
    if (!preg_match('/^(\d+)\.([0-9a-f]{64})$/', $token, $m)) {
        return false;
    }
    if (!hash_equals(hash_hmac('sha256', $m[1], $secret), $m[2])) {
        return false;
    }
    $age = $now - (int) $m[1];

    return $age >= $minSeconds && $age <= $maxSeconds;
}

function guard_csrf_token(): string
{
    if (!isset($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function guard_csrf_ok(?string $token): bool
{
    $expected = $_SESSION['csrf'] ?? null;

    return is_string($expected) && is_string($token) && hash_equals($expected, $token);
}
