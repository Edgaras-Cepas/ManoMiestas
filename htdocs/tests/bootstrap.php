<?php
// PHPUnit testu aplinkos paruosimas (tests/bootstrap.php)
// GDI: Sis kodas buvo sugeneruotas naudojant Cursor (Composer / Agent, 2026-04-18).
// Uzklausa: Kaip paruosti tests/bootstrap.php kad veiktu unit ir integraciniai testai su DB?
// Rezultatas dalinai koreguotas.
declare(strict_types=1);

// Tucia sesija kad auth funkcijos nesutruktu CLI aplinkoje
$_SESSION = [];

// Ikelia config ir pakeicia uploads config i tmp aplanka testams
$GLOBALS['app_config'] = require __DIR__ . '/../config/config.php';
$GLOBALS['app_config']['uploads'] = [
    'base_dir'      => sys_get_temp_dir() . '/manomiestas_test',
    'base_url'      => 'uploads',
    'max_files'     => 5,
    'max_size'      => 2 * 1024 * 1024,
    'allowed_types' => ['image/jpeg', 'image/png'],
];

// Ikelia reikalingas bibliotekas
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/audit.php';
require_once __DIR__ . '/../lib/uploads.php';
