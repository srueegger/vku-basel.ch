<?php
// Minimaler Testrunner: php tests/run.php

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

$GLOBALS['__tests'] = [];

function test(string $name, callable $fn): void
{
    $GLOBALS['__tests'][] = [$name, $fn];
}

final class AssertionFailed extends Exception {}

function assert_same(mixed $expected, mixed $actual, string $msg = ''): void
{
    if ($expected !== $actual) {
        throw new AssertionFailed(trim($msg . ' expected ' . var_export($expected, true) . ', got ' . var_export($actual, true)));
    }
}

function assert_true(bool $cond, string $msg = ''): void
{
    if (!$cond) {
        throw new AssertionFailed($msg !== '' ? $msg : 'expected true');
    }
}

foreach (glob(__DIR__ . '/*_test.php') as $file) {
    require $file;
}

$passed = 0;
$failed = 0;
foreach ($GLOBALS['__tests'] as [$name, $fn]) {
    try {
        $fn();
        $passed++;
        echo "PASS $name\n";
    } catch (Throwable $e) {
        $failed++;
        echo "FAIL $name: " . get_class($e) . ': ' . $e->getMessage() . "\n";
    }
}
echo "$passed passed, $failed failed\n";
exit($failed > 0 ? 1 : 0);
