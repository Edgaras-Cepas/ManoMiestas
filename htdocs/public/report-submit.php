<?php
// Pranesimo formos POST apdorojimas
// GDI: Sis kodas buvo sugeneruotas naudojant Cursor (Composer / Agent, 2026-05-12).
// Uzklausa: Reikia POST apdorojimo failo pranesimo formai.
// Rezultatas dalinai koreguotas.
require_once __DIR__ . "/../lib/bootstrap.php";
require_login();

// Leidzia tik POST uzklausas
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirect("report.php");
}

// GDI: Sis kodas buvo sugeneruotas naudojant Cursor (Composer / Agent, 2026-05-12).
// Uzklausa: Kai POST per didelis ar nuotraukos netinkamos, grazinti klaidos zinute.
// Rezultatas dalinai koreguotas.
// Patikrina ar PHP nedropino viso POST del post_max_size limito
// Jei taip - $_POST ir $_FILES bus tusti bet CONTENT_LENGTH > 0
$contentLength = (int) ($_SERVER["CONTENT_LENGTH"] ?? 0);
if (empty($_POST) && empty($_FILES) && $contentLength > 0) {
    $_SESSION["report_error"] = "Siunčiama informacija per didelė (dažniausiai nuotraukos viršija serverio limitą). Sumažinkite failų dydį ir bandykite dar kartą.";
    redirect("report.php");
}

// Gauna formos duomenis
$category = trim($_POST["category"] ?? "");
$description = trim($_POST["description"] ?? "");
$lat = $_POST["lat"] ?? "";
$lng = $_POST["lng"] ?? "";
$address = trim($_POST["address"] ?? "");

// Validacija jei truksta duomenu redirect atgal
if ($category === "" || $description === "" || !is_numeric($lat) || !is_numeric($lng)) {
    redirect("report.php");
}

// Patikrina nuotraukas per uploads.php validate_report_photos()
$photoError = validate_report_photos($_FILES["photos"] ?? null);
if ($photoError !== null) {
    $_SESSION["report_error"] = $photoError;
    redirect("report.php");
}

// Issaugo pranesima DB
$user = current_user();
$query = db()->prepare(
    "INSERT INTO issues (user_id, title, description, category, lat, lng, address, status, created_at, updated_at)
     VALUES (:user_id, :title, :description, :category, :lat, :lng, :address, 'NEW', NOW(), NOW())"
);
$query->execute([
    "user_id" => $user["id"],
    "title" => $category,
    "description" => $description,
    "category" => $category,
    "lat" => $lat,
    "lng" => $lng,
    "address" => $address !== "" ? $address : null,
]);

$issueId = (int) db()->lastInsertId();

// Issaugo nuotraukas ir susieja su pranesimo id
$paths = handle_uploads($_FILES["photos"] ?? []);
if ($paths) {
    $photoStmt = db()->prepare("INSERT INTO issue_photos (issue_id, file_path, uploaded_at) VALUES (:issue_id, :file_path, NOW())");
    foreach ($paths as $path) {
        $photoStmt->execute([
            "issue_id" => $issueId,
            "file_path" => $path,
        ]);
    }
}

log_audit("issue", $issueId, "ISSUE_CREATE", (int) $user["id"]);

redirect("report-success.php?id=" . $issueId);
