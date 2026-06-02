<?php

// Pagalbines funkcijos

declare(strict_types=1);



// HTML escape, pavercia special char i HTML entities
// Taip pat pavercia invalid charcs ir kabutes
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}

// Nukreipia i kita page
function redirect(string $path): void
{
    header("Location: {$path}");
    exit;
}


// GDI: Šis kodas buvo sugeneruotas naudojant Cursor (Composer / Agent).
// Užklausa: Reikia, funckcijos kuri gautu visa pranesimo puslapio URL
// Rezultatas dalinai koreguotas.
// Pilnas dabartinio puslapio URL share mygtukui (realiai useless for localhost).
function current_url(): string
{
    $scheme = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
    $host = $_SERVER["HTTP_HOST"] ?? "localhost";
    $uri = $_SERVER["REQUEST_URI"] ?? "/";
    return "{$scheme}://{$host}{$uri}";
}


// Pranesimu kategoriju map
function issue_status(): array
{
    return [
        "NEW"         => ["label" => "New",         "color" => "#f76c6c"],
        "IN_PROGRESS" => ["label" => "In progress", "color" => "#f2b94f"],
        "RESOLVED"    => ["label" => "Resolved",    "color" => "#2f9b68"],
        "REJECTED"    => ["label" => "Rejected",    "color" => "#999999"],
    ];
}

// Kategoriju ikonos
function category_icons(): array
{
    return [
        "Pažeidimai" => ["class" => "cat-flag",      "svg" => "<img src='../icons/flag-alt.svg' alt=''>"],
        "Sezoninė"   => ["class" => "cat-snow",      "svg" => "<img src='../icons/season.svg' alt=''>"],
        "Gedimai"    => ["class" => "cat-cone",      "svg" => "<img src='../icons/repair.svg' alt=''>"],
        "Gyvūnai"    => ["class" => "cat-animals",   "svg" => "<img src='../icons/animal.svg' alt=''>"],
        "Pastatai"   => ["class" => "cat-buildings", "svg" => "<img src='../icons/building.svg' alt=''>"],
        "Eismas"     => ["class" => "cat-traffic",   "svg" => "<img src='../icons/road.svg' alt=''>"],
        "Remontas"   => ["class" => "cat-repair",    "svg" => "<img src='../icons/construction.svg' alt=''>"],
    ];
}