<?php
// Gibt eine .htpasswd-Zeile (bcrypt) aus: php scripts/htpasswd-line.php <user> <passwort>

declare(strict_types=1);

if ($argc !== 3) {
    fwrite(STDERR, "Usage: php scripts/htpasswd-line.php <user> <passwort>\n");
    exit(1);
}

echo $argv[1], ':', password_hash($argv[2], PASSWORD_BCRYPT), "\n";
