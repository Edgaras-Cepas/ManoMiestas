<?php

// Bendras ikelimas puslapiams

declare(strict_types=1);

// GDI: Šis kodas buvo sugeneruotas naudojant Cursor (Composer / Agent, 2026-02-19).
// Užklausa: Reikia vieno failo kuris viska paleistu (sesija, config, bibliotekas).
// Rezultatas dalinai koreguotas.

// Pradeta sesija
session_start();

// Store config in global, kad viskas galetu pasiekti
$config = require __DIR__ . "/../config/config.php";
$GLOBALS["app_config"] = $config;

// Kad galima butu visur naudot ju functions
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/helpers.php";
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/audit.php";
require_once __DIR__ . "/uploads.php";
