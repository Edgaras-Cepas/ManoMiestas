<?php
// Pranesimo redagavimas
// GDI: Sis kodas buvo sugeneruotas naudojant Cursor (Composer / Agent, 2026-04-10).
// Uzklausa: Reikia puslapio kur vartotojas gali redaguoti savo pranesima iki kol busena nauja.
// Rezultatas dalinai koreguotas.
require_once __DIR__ . "/../lib/bootstrap.php";
require_login();
$user = current_user();

// Gauna issue id is URL, jei nera redirect
$id = (int) ($_GET["id"] ?? 0);
if ($id <= 0) {
    redirect("my-issues.php");
}

// Patikrina ar pranesimas priklauso vartotojui ir ar statusas NEW
$issueStmt = db()->prepare("SELECT * FROM issues WHERE id = :id AND user_id = :uid LIMIT 1");
$issueStmt->execute([
    "id" => $id,
    "uid" => current_user()["id"],
]);
$issue = $issueStmt->fetch();
if (!$issue || $issue["status"] !== "NEW") {
    redirect("my-issues.php");
}

// Gauna esamas nuotraukas
$photosStmt = db()->prepare("SELECT id, file_path FROM issue_photos WHERE issue_id = :id ORDER BY id ASC");
$photosStmt->execute(["id" => $id]);
$photos = $photosStmt->fetchAll();

$error = "";

// Apdoroja POST pasalina pasirinktas nuotraukas, issaugo nauja info ir nuotraukas
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $category = trim($_POST["category"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $lat = $_POST["lat"] ?? "";
    $lng = $_POST["lng"] ?? "";
    $remove = $_POST["remove_photo"] ?? [];

    // Validacija
    if ($category === "" || $description === "" || !is_numeric($lat) || !is_numeric($lng)) {
        $error = "Prašome užpildyti visus privalomus laukus.";
    } else {
        // Pasalina pazymetas nuotraukas is disko ir DB
        $removeIds = array_map("intval", (array) $remove);
        if ($removeIds) {
            $in = implode(",", array_fill(0, count($removeIds), "?"));
            $query = db()->prepare("SELECT file_path FROM issue_photos WHERE issue_id = ? AND id IN ($in)");
            $query->execute(array_merge([$id], $removeIds));
            $toDelete = $query->fetchAll(PDO::FETCH_COLUMN);
            $delStmt = db()->prepare("DELETE FROM issue_photos WHERE issue_id = ? AND id IN ($in)");
            $delStmt->execute(array_merge([$id], $removeIds));
            foreach ($toDelete as $path) {
                $fullPath = __DIR__ . "/" . $path;
                if (is_file($fullPath)) {
                    unlink($fullPath);
                }
            }
        }

        // Patikrina nuotrauku kieki
        $remainingPhotos = count($photos) - count($removeIds);
        $incoming = isset($_FILES["photos"]["name"]) ? array_filter((array) $_FILES["photos"]["name"]) : [];
        $maxFiles = $GLOBALS["app_config"]["uploads"]["max_files"];
        if ($remainingPhotos + count($incoming) > $maxFiles) {
            $error = "Per daug nuotraukų. Maksimaliai leidžiama " . $maxFiles . ".";
        } else {
            // Atnaujina pranesima DB
            $update = db()->prepare(
                "UPDATE issues
                 SET title = :title, description = :description, category = :category,
                     lat = :lat, lng = :lng, address = :address, updated_at = NOW()
                 WHERE id = :id"
            );
            $update->execute([
                "title" => $category,
                "description" => $description,
                "category" => $category,
                "lat" => $lat,
                "lng" => $lng,
                "address" => $address !== "" ? $address : null,
                "id" => $id,
            ]);

            // Issaugo naujas nuotraukas
            $paths = handle_uploads($_FILES["photos"] ?? []);
            if ($paths) {
                $photoStmt = db()->prepare("INSERT INTO issue_photos (issue_id, file_path, uploaded_at) VALUES (:issue_id, :file_path, NOW())");
                foreach ($paths as $path) {
                    $photoStmt->execute([
                        "issue_id" => $id,
                        "file_path" => $path,
                    ]);
                }
            }

            log_audit("issue", $id, "ISSUE_UPDATE", (int) current_user()["id"]);
            redirect("my-issues.php");
        }
    }
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
    <title>Redaguoti pranešimą | ManoMiestas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/app.css">
    <link rel="icon" type="image/svg+xml" href="../icons/miestas.svg">
</head>
<body class="is-report" data-auth="<?php echo $user ? "1" : "0"; ?>">

<?php include 'header.php';?>

<!-- Redagavimo forma: kategorija, aprasymas, vieta, nuotraukos -->
<main class="report-page container-fluid">
    <form class="report-card" method="post" enctype="multipart/form-data">
        <h1 class="report-title text-center">Redaguoti pranešimą</h1>
        <?php if ($error): ?>
            <div class="alert alert-danger py-2"><?php echo e($error); ?></div>
        <?php endif; ?>

        <!-- Kategorijos dropdown su dabartiniu pasirinkimu -->
        <div class="report-field">
            <label>Problemos tipas</label>
            <select name="category" class="form-select">
                <?php
                $categories = ["Pažeidimai", "Sezoninė", "Gedimai", "Gyvūnai", "Pastatai", "Eismas", "Remontas"];
                foreach ($categories as $cat):
                ?>
                    <option value="<?php echo e($cat); ?>" <?php echo $issue["category"] === $cat ? "selected" : ""; ?>>
                        <?php echo e($cat); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Aprasymo laukas -->
        <div class="report-field">
            <label for="edit-description">Aprašymas</label>
            <textarea id="edit-description" name="description" class="form-control report-textarea" required><?php echo e($issue["description"]); ?></textarea>
        </div>

        <!-- Vietos laukas su zemelapiu, drag marker atnaujina koordinates ir adresa -->
        <div class="report-field">
            <label for="edit-address">Pažeidimo vieta</label>
            <textarea id="edit-address" name="address" class="form-control report-location-input" rows="3" readonly><?php echo e($issue["address"] ?? ""); ?></textarea>
            <input type="hidden" name="lat" id="edit-lat" value="<?php echo e($issue["lat"]); ?>">
            <input type="hidden" name="lng" id="edit-lng" value="<?php echo e($issue["lng"]); ?>">
            <div id="edit-map" class="report-map" data-lat="<?php echo e($issue["lat"]); ?>" data-lng="<?php echo e($issue["lng"]); ?>"></div>
        </div>

        <!-- Esamos nuotraukos su checkbox pasalinimui -->
        <?php if ($photos): ?>
            <div class="report-field">
                <label>Esamos nuotraukos</label>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($photos as $photo): ?>
                        <label class="border rounded p-2 d-flex align-items-center gap-2">
                            <img src="<?php echo e($photo['file_path']); ?>" alt="Nuotrauka" style="width:64px; height:64px; object-fit:cover;">
                            <span class="form-check">
                                <input class="form-check-input" type="checkbox" name="remove_photo[]" value="<?php echo (int) $photo['id']; ?>">
                                <span class="form-check-label">Pašalinti</span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Nauju nuotrauku ikelimas -->
        <div class="report-field">
            <label for="edit-photos">Pridėti nuotraukas (iki 5)</label>
            <input type="file" name="photos[]" id="edit-photos" class="form-control" accept="image/*" multiple>
        </div>

        <button type="submit" class="btn btn-primary report-cta">Išsaugoti</button>
    </form>
</main>

<!-- Leaflet zemelapis su drag marker ir reverse-geocode adreso atnaujinimu -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin="anonymous"></script>
<script>
(() => {
  const mapEl = document.getElementById("edit-map");
  const latInput = document.getElementById("edit-lat");
  const lngInput = document.getElementById("edit-lng");
  const addressInput = document.getElementById("edit-address");
  if (!mapEl || typeof L === "undefined") return;
  const lat = parseFloat(mapEl.dataset.lat);
  const lng = parseFloat(mapEl.dataset.lng);
  if (Number.isNaN(lat) || Number.isNaN(lng)) return;
  const map = L.map(mapEl, { zoomControl: false, attributionControl: false });
  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", { maxZoom: 19 }).addTo(map);
  map.setView([lat, lng], 16);
  const marker = L.marker([lat, lng], { draggable: true }).addTo(map);
  const updateAddress = (coords) => {
    if (!addressInput) return;
    addressInput.value = "Ieškoma adreso...";
    fetch(`reverse-geocode.php?lat=${encodeURIComponent(coords.lat)}&lng=${encodeURIComponent(coords.lng)}`, {
      headers: { "Accept": "application/json" }
    })
      .then(res => res.ok ? res.json() : null)
      .then(data => {
        if (!addressInput) return;
        addressInput.value = data && data.display_name ? data.display_name : "Adreso rasti nepavyko";
      })
      .catch(() => {});
  };
  const updateCoords = (coords) => {
    latInput.value = coords.lat.toFixed(6);
    lngInput.value = coords.lng.toFixed(6);
  };
  const updateAll = (coords) => {
    updateCoords(coords);
    updateAddress(coords);
  };
  marker.on("dragend", () => {
    updateAll(marker.getLatLng());
  });
  map.on("click", (e) => {
    marker.setLatLng(e.latlng);
    updateAll(e.latlng);
  });
  if (addressInput && !addressInput.value.trim()) {
    updateAddress({ lat, lng });
  }
})();
</script>
</body>
</html>
