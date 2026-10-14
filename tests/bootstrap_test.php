<?php

declare(strict_types=1);

test('app_paths: Standard-Datenpfad liegt in data/', function () {
    putenv('VKU_DATA_DIR');
    assert_true(str_ends_with(app_paths()['data_file'], '/data/partner.sqlite'), app_paths()['data_file']);
});

test('app_paths: VKU_DATA_DIR überschreibt den Datenordner', function () {
    putenv('VKU_DATA_DIR=/tmp/x');
    $paths = app_paths();
    putenv('VKU_DATA_DIR');
    assert_same('/tmp/x/partner.sqlite', $paths['data_file']);
    assert_same('/tmp/x/secret.key', $paths['secret_file']);
});

test('e: escaped HTML-Sonderzeichen', function () {
    assert_same('&lt;a href=&quot;x&quot;&gt;&amp;&#039;', e('<a href="x">&\''));
    assert_same('', e(null));
});
