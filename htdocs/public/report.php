<?php
// 3 zingsniu pranesimo vedlys
// GDI: Sis kodas buvo sugeneruotas naudojant Cursor (Composer / Agent, 2026-05-12).
// Uzklausa: Reikia 3 zingsniu pranesimo formos
// Rezultatas dalinai koreguotas.
require_once __DIR__ . "/../lib/bootstrap.php";
require_login();
$user = current_user();

// GDI: Sis kodas buvo sugeneruotas naudojant Cursor (Composer / Agent, 2026-05-12).
// Uzklausa: Kaip po redirect parodyti klaidos pranesima ir perduoti failu limitus?
// Rezultatas dalinai koreguotas.
// Gauna klaidos zinute is sesijos (flash message is report-submit.php)
$reportError = "";
if (!empty($_SESSION["report_error"])) {
    $reportError = $_SESSION["report_error"];
    unset($_SESSION["report_error"]);
}

// Upload limitai perduodami i JS per data atributus
$uploadCfg = $GLOBALS["app_config"]["uploads"];
$maxPhotoBytes = (int) $uploadCfg["max_size"];
$maxPhotoCount = (int) $uploadCfg["max_files"];

// GDI: HTML struktura sugeneruota is Figma eksporto, modifikuota naudojant Cursor.
// Uzklausa: Pritaikyti Figma HTML i PHP sablona.
// Rezultatas dalinai koreguotas, modifikuota ir perdaryta.
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Problemos pranešimas | ManoMiestas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/app.css">
    <link rel="icon" type="image/svg+xml" href="../icons/miestas.svg">
</head>
<body class="is-report" data-auth="<?php echo $user ? "1" : "0"; ?>" data-report-error="<?php echo e($reportError); ?>">
    
<?php include 'header.php';?>


    <!-- 3 zingsniu pranesimo forma — valdoma per app.js -->
    <main class="report-page container-fluid">
        <!-- Hidden laukai uzpildomi per JS, upload limitai perduodami i JS per data- atributus -->
        <form class="report-card" id="report-form" method="post" action="report-submit.php" enctype="multipart/form-data" data-max-photo-bytes="<?php echo $maxPhotoBytes; ?>" data-max-photo-count="<?php echo $maxPhotoCount; ?>">
            <input type="hidden" name="category" id="report-category" value="">
            <input type="hidden" name="lat" id="report-lat" value="">
            <input type="hidden" name="lng" id="report-lng" value="">
            <input type="hidden" name="address" id="report-address" value="">
            <div class="report-flash-error" id="report-flash-error" role="alert" hidden></div>
            <div class="report-header">
                <span class="step-pill" id="report-step-indicator">1/3</span>
                <h1 class="report-title">Problemos pranešimas</h1>
                <div class="report-nav">
                    <button type="button" class="report-nav-btn report-close" id="report-close" aria-label="Uzdaryti"><img src="../icons/close.svg" alt=""></button>
                    <button type="button" class="report-nav-btn report-back" id="report-back" aria-label="Atgal" hidden>
                        <img src="../icons/back-arrow.svg" alt="">
                    </button>
                </div>
            </div>

            <!-- 1 zingsnis: kategorijos pasirinkimas -->
            <div class="report-step" data-step="1">
                <div class="report-search">
                    <input type="text" class="form-control" placeholder="Search" aria-label="Paieška">
                </div>
                <h2 class="report-section-title">Problemos tipas:</h2>
                <div class="category-list">
                    <button type="button" class="category-item cat-flag" data-category="Pažeidimai">
                        <span class="category-icon">
                            <img src="../icons/flag-alt.svg" alt="">
                        </span>
                        <span>Pažeidimai</span>
                        <span class="category-arrow">&gt;</span>
                    </button>
                    <button type="button" class="category-item cat-snow" data-category="Sezoninė">
                        <span class="category-icon">
                            <img src="../icons/season.svg" alt="">
                        </span>
                        <span>Sezoninė</span>
                        <span class="category-arrow">&gt;</span>
                    </button>
                    <button type="button" class="category-item cat-cone" data-category="Gedimai">
                        <span class="category-icon">
                            <img src="../icons/repair.svg" alt="">
                        </span>
                        <span>Gedimai</span>
                        <span class="category-arrow">&gt;</span>
                    </button>
                    <button type="button" class="category-item cat-animals" data-category="Gyvūnai">
                        <span class="category-icon">
                            <img src="../icons/animal.svg" alt="">
                        </span>
                        <span>Gyvūnai</span>
                        <span class="category-arrow">&gt;</span>
                    </button>
                    <button type="button" class="category-item cat-buildings" data-category="Pastatai">
                        <span class="category-icon">
                            <img src="../icons/building.svg" alt="">
                        </span>
                        <span>Pastatai</span>
                        <span class="category-arrow">&gt;</span>
                    </button>
                    <button type="button" class="category-item cat-traffic" data-category="Eismas">
                        <span class="category-icon">
                            <img src="../icons/road.svg" alt="">
                        </span>
                        <span>Eismas</span>
                        <span class="category-arrow">&gt;</span>
                    </button>
                    <button type="button" class="category-item cat-repair" data-category="Remontas">
                        <span class="category-icon">
                            <img src="../icons/construction.svg" alt="">
                        </span>
                        <span>Remontas</span>
                        <span class="category-arrow">&gt;</span>
                    </button>
                </div>
                <button type="button" class="btn btn-primary report-cta" id="report-next-1" disabled>Toliau</button>
            </div>

            <!-- 2 zingsnis: nuotraukos, aprasymas, laikas, vieta su zemelapiu -->
            <div class="report-step" data-step="2" hidden>
                <div class="report-selected-category" id="report-selected-category">
                    <span class="category-icon cat-flag" id="report-selected-icon">
                        <img src="../icons/flag-alt.svg" alt="">
                    </span>
                    <span id="report-selected-label">Pažeidimai</span>
                </div>
                <div class="upload-panel">
                    <div class="upload-placeholder" aria-hidden="true">
                        <img id="report-photo-preview" class="upload-preview" alt="" hidden>
                        <img src="../icons/camera.svg" alt="">
                    </div>
                    <label class="btn btn-primary upload-btn" for="report-photo">Įkelti nuotraukas</label>
                    <input type="file" id="report-photo" class="d-none" name="photos[]" accept="image/*" multiple>
                    <div class="upload-hint" id="report-photo-name">Nuotrauka nepasirinkta</div>
                    <p class="upload-limit-hint" id="report-photo-limit-hint"></p>
                    <p class="report-photo-error" id="report-photo-error" role="alert" hidden></p>
                </div>

                <div class="report-field is-empty" id="report-description-field">
                    <label for="report-description">Aprašymas</label>
                    <textarea id="report-description" name="description" class="form-control report-textarea" placeholder="Aprašymas" required></textarea>
                    <span class="report-hint">Laukas privalomas</span>
                </div>

                <div class="report-field">
                    <label class="label-with-icon"><img src="../icons/calendar.svg" alt="">Pažeidimo laikas</label>
                    <div class="report-grid">
                        <input type="date" id="report-date" class="form-control">
                        <input type="time" id="report-time" class="form-control">
                    </div>
                </div>

                <div class="report-field">
                    <label for="report-location">Pažeidimo vieta</label>
                    <div class="report-input-row">
                        <textarea id="report-location" class="form-control report-location-input" rows="3" readonly>Universiteto g. 10, Akademija, 53361 Kauno r. sav.</textarea>
                        <button type="button" class="btn btn-primary btn-sm location-edit-btn" id="report-location-edit">Keisti</button>
                    </div>
                    <div class="report-map-shell" id="report-map-shell">
                        <div id="report-map" class="report-map" aria-label="Pažeidimo vietos žemėlapis"></div>
                        <div class="map-actions" id="report-map-actions" hidden>
                            <button type="button" class="btn btn-dark map-cancel" id="report-map-cancel"><img src="../icons/close.svg" alt=""></button>
                            <button type="button" class="btn btn-primary map-confirm" id="report-map-confirm">Patvirtinti</button>
                        </div>
                    </div>
                </div>

                <button type="button" class="btn btn-primary report-cta" id="report-next-2">Toliau</button>
            </div>

            <!-- 3 zingsnis: suvestine perziurai pries pateikima -->
            <div class="report-step" data-step="3" hidden>
                <div class="review-card">
                    <div class="review-head">
                        <span class="category-icon cat-flag" id="summary-category-icon">
                            <img src="../icons/flag-alt.svg" alt="">
                        </span>
                        <span id="summary-category">Pažeidimai</span>
                    </div>
                    <div class="review-media" id="summary-media" aria-hidden="true">
                        <img id="summary-photo" class="review-photo" alt="" hidden>
                        <div class="review-placeholder" id="summary-photo-placeholder"></div>
                    </div>
                    <div class="review-meta">
                        <div>Pažeidimo laikas</div>
                        <div class="text-end"><span id="summary-date">----</span> <span id="summary-time">--:--</span></div>
                    </div>
                    <p id="summary-description" class="section-body">Aprašymas neįvestas.</p>
                    <div class="review-section">
                        <span class="section-label">Pažeidimo vieta</span>
                        <span id="summary-location">Universiteto g. 10, Akademija, 53361 Kauno r. sav.</span>
                        <div id="report-review-map" class="review-map" aria-label="Pažeidimo vietos žemėlapis"></div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary report-cta" id="report-submit">Pateikti</button>
            </div>
        </form>
    </main>

    <?php $navActive = 'issues'; include 'navbar.php'; ?>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin="anonymous"></script>
    <script src="assets/app.js"></script>
</body>
</html>
