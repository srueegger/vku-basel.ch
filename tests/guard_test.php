<?php

declare(strict_types=1);

const TEST_SECRET = 'testsecret';

test('guard: Zeitstempel zu frisch wird abgelehnt', function () {
    $t = guard_timestamp_issue(TEST_SECRET, 1000);
    assert_same(false, guard_timestamp_ok($t, TEST_SECRET, 1000));
    assert_same(false, guard_timestamp_ok($t, TEST_SECRET, 1002));
});

test('guard: Zeitstempel nach 3 Sekunden ist gültig', function () {
    $t = guard_timestamp_issue(TEST_SECRET, 1000);
    assert_same(true, guard_timestamp_ok($t, TEST_SECRET, 1003));
});

test('guard: Zeitstempel älter als ein Tag wird abgelehnt', function () {
    $t = guard_timestamp_issue(TEST_SECRET, 1000);
    assert_same(false, guard_timestamp_ok($t, TEST_SECRET, 1000 + 86401));
});

test('guard: manipulierter oder kaputter Zeitstempel wird abgelehnt', function () {
    $t = guard_timestamp_issue(TEST_SECRET, 1000);
    $sig = explode('.', $t)[1];
    assert_same(false, guard_timestamp_ok('900.' . $sig, TEST_SECRET, 1003));
    assert_same(false, guard_timestamp_ok($t, 'anderes-secret', 1003));
    assert_same(false, guard_timestamp_ok('müll', TEST_SECRET, 1003));
    assert_same(false, guard_timestamp_ok('', TEST_SECRET, 1003));
});

test('guard: Secret wird erzeugt und wiederverwendet', function () {
    $file = sys_get_temp_dir() . '/vku-test-' . bin2hex(random_bytes(4)) . '/secret.key';
    $first = guard_secret($file);
    assert_true((bool) preg_match('/^[0-9a-f]{64}$/', $first), $first);
    assert_same($first, guard_secret($file));
    assert_same('0600', substr(sprintf('%o', fileperms($file)), -4));
});

test('guard: CSRF-Token', function () {
    $_SESSION = [];
    $token = guard_csrf_token();
    assert_same(64, strlen($token));
    assert_same($token, guard_csrf_token());
    assert_same(true, guard_csrf_ok($token));
    assert_same(false, guard_csrf_ok('x'));
    assert_same(false, guard_csrf_ok(null));
    $_SESSION = [];
    assert_same(false, guard_csrf_ok($token));
});
