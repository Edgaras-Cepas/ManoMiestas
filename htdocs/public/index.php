<?php
// Pagrindinis zemelapis su pranesimu zymems
require_once __DIR__ . "/../lib/bootstrap.php";
header("Content-Type: text/html; charset=UTF-8");

$user = current_user();

// Gauna visus pranesimus su koordinatemis is DB, perduoda i JS per window.MANO_ISSUES
$query = db()->query(
    "SELECT id, title, category, lat, lng, address, status
     FROM issues
     WHERE lat IS NOT NULL AND lng IS NOT NULL
     ORDER BY created_at DESC"
);
$issues = $query->fetchAll();

// Miestu koordinates for home city (tiesiog hardcoded)
$cityCenters = [
    "Vilnius" => ["lat" => 54.6872, "lng" => 25.2797],
    "Kaunas" => ["lat" => 54.8985, "lng" => 23.9036],
    "Klaipėda" => ["lat" => 55.7033, "lng" => 21.1443],
    "Šiauliai" => ["lat" => 55.9349, "lng" => 23.3137],
    "Trakai" => ["lat" => 54.6372, "lng" => 24.9344],
    "Elektrėnai" => ["lat" => 54.7859, "lng" => 24.6578],
    "Radviliškis" => ["lat" => 55.8106, "lng" => 23.5417],
];

// Pagal vartotojo nustatymuose nustatyta miesta centruoa zemelapi
$defaultCenter = null;
if ($user && !empty($user["city"]) && isset($cityCenters[$user["city"]])) {
    $defaultCenter = $cityCenters[$user["city"]];
}

// GDI: HTML struktura sugeneruota is Figma eksporto, modifikuota naudojant Cursor.
// Uzklausa: Pritaikyti Figma HTML i PHP sablona.
// Rezultatas dalinai koreguotas, modifikuota ir perdaryta.
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ManoMiestas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="anonymous">
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css">
    <link rel="stylesheet" href="assets/app.css">
    <link rel="icon" type="image/svg+xml" href="../icons/miestas.svg">
</head>
<body data-auth="<?php echo $user ? "1" : "0"; ?>">
    
    <?php include 'header.php';?>

    <!-- Zemelapis: paieska, zemelapis, lokacijos patvirtinimas -->
    <main class="map-page">
        <section class="map-shell" id="map-shell">
            <!-- Adreso paieska — geocode.php per JS -->
            <div class="search-wrapper shadow-sm">
                <input type="text" class="form-control search-input" placeholder="Enter address" aria-label="Adreso paieška">
                <button class="btn btn-link text-decoration-none clear-search" type="button" aria-label="Išvalyti paiešką">&times;</button>
                <div class="search-suggestions" id="search-suggestions" hidden></div>
            </div>
            <!-- Leaflet zemelapis -->
            <div id="map" class="map map-full"></div>
            <div class="center-pin" id="center-pin" aria-hidden="true"></div>
            <!-- Lokacijos patvirtinimo mygtukai -->
            <div class="confirm-actions" id="confirm-actions">
                <button type="button" class="btn btn-dark btn-lg action-cancel" id="cancel-select"><img src="../icons/close.svg" alt=""></button>
                <button type="button" class="btn btn-primary btn-lg action-confirm" id="confirm-select">Patvirtinti lokaciją</button>
            </div>
        </section>
    </main>

    <?php $navActive = 'map'; $navFab = true; include 'navbar.php'; ?>

    <!-- Pranesimu duomenys ir zemelapio centras perduodami i JS -->
    <script>
        window.MANO_ISSUES = <?php echo json_encode($issues, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        window.MANO_DEFAULT_CENTER = <?php echo $defaultCenter ? json_encode($defaultCenter, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) : "null"; ?>;
    </script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="anonymous"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    <script src="assets/app.js"></script>
</body>
</html>
