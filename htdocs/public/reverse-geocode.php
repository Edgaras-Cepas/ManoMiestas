<?php
// Koordinates i adresa per Nominatim (public/reverse-geocode.php)
// GDI: Sis kodas buvo sugeneruotas naudojant Cursor (Composer / Agent, 2026-03-21).
// Uzklausa: Kaip is koordinaciu lat/lng gauti adresa ir grazinti JSON report formai?
// Rezultatas dalinai koreguotas.
header("Content-Type: application/json; charset=utf-8");

// Gauna koordinates is URL, jei netinkamos grazina 400
$lat = $_GET["lat"] ?? "";
$lng = $_GET["lng"] ?? "";

if (!is_numeric($lat) || !is_numeric($lng)) {
    http_response_code(400);
    echo json_encode(["error" => "bad_request"]);
    exit;
}

// Uzkoduoja koordinates ir sudaro Nominatim URL (lietuviu kalba)
$lat = rawurlencode($lat);
$lng = rawurlencode($lng);
$url = "https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat={$lat}&lon={$lng}&addressdetails=1&accept-language=lt";

// HTTP kontekstas su User-Agent (Nominatim reikalavimas)
$context = stream_context_create([
    "http" => [
        "method" => "GET",
        "header" => "User-Agent: ManoMiestas\r\nAccept: application/json\r\n"
    ]
]);

// Siuncia uzklausima i Nominatim, jei nepavyksta grazina 502
$response = @file_get_contents($url, false, $context);

if ($response === false) {
    http_response_code(502);
    echo json_encode(["error" => "upstream_failed"]);
    exit;
}

// Grazina Nominatim atsakyma tiesiai i narsykle
echo $response;
