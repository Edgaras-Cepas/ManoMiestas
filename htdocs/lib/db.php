<?php
// Pdo rysis su SQL
declare(strict_types=1);


// GDI: Šis kodas buvo sugeneruotas naudojant Cursor (Composer / Agent).
// Užklausa: Reiktu singleton connection prie MySQL
// Rezultatas dalinai koreguotas.

// Vienas PDO object visai uzklausai (singleton)
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = $GLOBALS["app_config"]["db"];
    $data = "mysql:host={$config['host']};dbname={$config['name']};charset={$config['charset']}";

    $pdo = new PDO($data, $config["user"], $config["pass"], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;



}
