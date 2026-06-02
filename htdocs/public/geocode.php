<?php

// GDI: Šis kodas buvo sugeneruotas naudojant Cursor (Composer / Agent, 2026-03-14).
// Užklausa: Reikia, ka PHP pagal ivesta adresa kreiptusi i Nominatim ir grazintu JSON 
// Rezultatas dalinai koreguotas.

// Adreso paieška per Nominatim
header("Content-Type: application/json; charset=utf-8");

// Gauna paieskos teksta is URL jei tuscias grazina 400 (bad request)
$q = trim($_GET["q"] ?? "");
if ($q === "") {
    http_response_code(400);
    echo json_encode(["error" => "bad_request"]);
    exit;
}

// Uzkoduoja paieska ir sudaro Nominatim URL (tam url nustatoma limit 5 ir lietuviu kalba)
$query = rawurlencode($q);
$url = "https://nominatim.openstreetmap.org/search?format=jsonv2&q={$query}&limit=5&addressdetails=1&accept-language=lt";

// HTTP kontekstas su user agent (Nominatim reikalavimas)
$context = stream_context_create([
    "http" => [
        "method" => "GET",
        "header" => "User-Agent: ManoMiestas\r\nAccept: application/json\r\n"
    ]
]);


// Siuncia uzklausima i Nominatim jei nepavyksta grazina 502 (bad gateway)
$response = @file_get_contents($url, false, $context);

if ($response === false) {
    http_response_code(502);
    echo json_encode(["error" => "upstream_failed"]);
    exit;
}

// Grazinamas atsakymas
echo $response;
