<?php

declare(strict_types=1);

$config = require __DIR__ . '/../config.php';

if (!is_dir(__DIR__ . '/../storage/sessions')) {
    mkdir(__DIR__ . '/../storage/sessions', 0775, true);
}

session_save_path(__DIR__ . '/../storage/sessions');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}

date_default_timezone_set('Europe/Warsaw');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

ensure_inventory_schema();
