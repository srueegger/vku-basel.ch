<?php
// Gemeinsamer Einstieg für Seiten, Export und Tests.

declare(strict_types=1);

date_default_timezone_set('Europe/Zurich');

foreach (['validation', 'db', 'guard', 'xlsx'] as $module) {
    if (is_file(__DIR__ . "/$module.php")) {
        require_once __DIR__ . "/$module.php";
    }
}

/**
 * Pfade zu Quellcode und Daten. Auf dem Server liegt src/ in ~/vku-basel-app/src,
 * daher landet data/ dort automatisch in ~/vku-basel-app/data.
 *
 * @return array{src:string, data_dir:string, data_file:string, secret_file:string}
 */
function app_paths(): array
{
    $env = getenv('VKU_DATA_DIR');
    $dataDir = ($env !== false && $env !== '') ? rtrim($env, '/') : dirname(__DIR__) . '/data';

    return [
        'src' => __DIR__,
        'data_dir' => $dataDir,
        'data_file' => $dataDir . '/partner.sqlite',
        'secret_file' => $dataDir . '/secret.key',
    ];
}

function e(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}
