<?php
// SQLite-Speicher für Anmeldungen.

declare(strict_types=1);

function db_connect(string $file): PDO
{
    $dir = dirname($file);
    if (!is_dir($dir) && !mkdir($dir, 0770, true) && !is_dir($dir)) {
        throw new RuntimeException("Datenordner kann nicht angelegt werden: $dir");
    }

    $pdo = new PDO('sqlite:' . $file, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA busy_timeout = 5000');
    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec('CREATE TABLE IF NOT EXISTS anmeldungen (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        erstellt_am TEXT NOT NULL,
        fahrschule TEXT NOT NULL,
        name TEXT NOT NULL,
        email TEXT NOT NULL,
        telefon TEXT NOT NULL,
        einwilligung INTEGER NOT NULL,
        ip TEXT,
        user_agent TEXT
    )');

    return $pdo;
}

/**
 * @param array{schule:string, name:string, email:string, tel:string, consent:bool} $values
 */
function db_insert_anmeldung(PDO $pdo, array $values, string $ip, string $userAgent, DateTimeImmutable $now): int
{
    $stmt = $pdo->prepare('INSERT INTO anmeldungen
        (erstellt_am, fahrschule, name, email, telefon, einwilligung, ip, user_agent)
        VALUES (?, ?, ?, ?, ?, 1, ?, ?)');
    $stmt->execute([
        $now->format('Y-m-d H:i:s'),
        $values['schule'],
        $values['name'],
        $values['email'],
        $values['tel'],
        $ip,
        $userAgent,
    ]);

    return (int) $pdo->lastInsertId();
}

/**
 * @return list<array{id:int, erstellt_am:string, fahrschule:string, name:string, email:string, telefon:string}>
 */
function db_all_anmeldungen(PDO $pdo): array
{
    $rows = $pdo->query('SELECT id, erstellt_am, fahrschule, name, email, telefon
        FROM anmeldungen ORDER BY id ASC')->fetchAll();

    return array_map(static function (array $row): array {
        $row['id'] = (int) $row['id'];
        return $row;
    }, $rows);
}
