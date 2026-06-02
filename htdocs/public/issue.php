<?php
// Vieno pranesimo perziura
// GDI: Sis kodas buvo sugeneruotas naudojant Cursor (Composer / Agent, 2026-03-21).
// Užklausa: Reikia puslapio vieno pranesimo perziurai su nuotraukomis, jo aprasymu, mini zemelapiu ir komentarais.
// Rezultatas dalinai koreguotas.
require_once __DIR__ . "/../lib/bootstrap.php";
$user = current_user();

// Gauna issue id is URL, jei nera redirect
$id = (int) ($_GET["id"] ?? 0);
if ($id <= 0) {
    redirect("issues.php");
}

// Apdoroja POST - komentaro pridejimas arba salinimas
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    require_login();
    $action = $_POST["action"] ?? "add_comment";
    if ($action === "delete_comment") {
        // Patikrina ar komentaras priklauso vartotojui pries trinant
        $commentId = (int) ($_POST["comment_id"] ?? 0);
        if ($commentId > 0) {
            $query = db()->prepare("SELECT user_id FROM issue_comments WHERE id = :cid AND issue_id = :id");
            $query->execute(["cid" => $commentId, "id" => $id]);
            $ownerId = (int) ($query->fetchColumn() ?: 0);
            if ($ownerId && $ownerId === (int) current_user()["id"]) {
                $del = db()->prepare("DELETE FROM issue_comments WHERE id = :cid");
                $del->execute(["cid" => $commentId]);
                log_audit("issue", $id, "COMMENT_DELETE", (int) current_user()["id"]);
            }
        }
    } else {
        // Prideda nauja komentara jei tekstas nera tuscias
        $commentText = trim($_POST["comment"] ?? "");
        if ($commentText !== "") {
            $query = db()->prepare("INSERT INTO issue_comments (issue_id, user_id, text, created_at) VALUES (:issue_id, :user_id, :text, NOW())");
            $query->execute([
                "issue_id" => $id,
                "user_id" => current_user()["id"],
                "text" => $commentText,
            ]);
            log_audit("issue", $id, "COMMENT_ADD", (int) current_user()["id"]);
        }
    }
    redirect("issue.php?id=" . $id);
}

// Gauna pranesima su autoriaus email
$issueStmt = db()->prepare("SELECT i.*, u.email FROM issues i LEFT JOIN users u ON u.id = i.user_id WHERE i.id = :id LIMIT 1");
$issueStmt->execute(["id" => $id]);
$issue = $issueStmt->fetch();
if (!$issue) {
    redirect("issues.php");
}

// Gauna nuotraukas
$photosStmt = db()->prepare("SELECT file_path FROM issue_photos WHERE issue_id = :id ORDER BY id ASC");
$photosStmt->execute(["id" => $id]);
$photos = $photosStmt->fetchAll();

// Gauna komentarus su autoriaus info
$commentsStmt = db()->prepare(
    "SELECT c.*, u.email, u.full_name
     FROM issue_comments c
     LEFT JOIN users u ON u.id = c.user_id
     WHERE c.issue_id = :id
     ORDER BY c.created_at ASC"
);
$commentsStmt->execute(["id" => $id]);
$comments = $commentsStmt->fetchAll();

// Gauna admin ID sarasa komentaro badge rodymui
$adminIdsStmt = db()->prepare(
    "SELECT DISTINCT ur.user_id
     FROM user_roles ur
     INNER JOIN roles r ON r.id = ur.role_id
     WHERE r.name = 'ADMIN'"
);
$adminIdsStmt->execute();
$adminIds = array_map("intval", $adminIdsStmt->fetchAll(PDO::FETCH_COLUMN));

// Nustato statuso ir kategorijos info rodymui
$issueStatus = issue_status();
$categoryIcons = category_icons();
// GDI: HTML struktura sugeneruota is Figma eksporto, modifikuota naudojant Cursor.
// Uzklausa: Pritaikyti Figma HTML i PHP sablona.
// Rezultatas dalinai koreguotas, modifikuota ir perdaryta.
$statusInfo = $issueStatus[$issue["status"]] ?? $issueStatus["NEW"];
$category = $issue["category"] ?? "Pažeidimai";
$categoryIcon = $categoryIcons[$category] ?? $categoryIcons["Pažeidimai"];

$commentCount = count($comments);
$firstPhoto = $photos[0]["file_path"] ?? "";


?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Problemos peržiūra | ManoMiestas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/app.css">
    <link rel="icon" type="image/svg+xml" href="../icons/miestas.svg">
</head>
<body class="is-detail" data-auth="<?php echo $user ? "1" : "0"; ?>">
    
<?php include 'header.php';?>

<!-- Pranesimo kortele: kategorija, nuotraukos, meta info, zemelapis, veiksmai -->
<main class="detail-page container-fluid">
    <h1 class="detail-title text-center">Problemos peržiūra</h1>

    <article class="detail-card">
        <!-- Kategorijos ikona ir pavadinimas -->
        <header class="detail-head">
            <span class="category-icon <?php echo e($categoryIcon["class"]); ?>" aria-hidden="true">
                <?php echo $categoryIcon["svg"]; ?>
            </span>
            <span class="detail-head__title"><?php echo e($issue["title"] ?? $issue["category"]); ?></span>
        </header>
        <!-- Nuotrauku karusele arba placeholder -->
        <?php if ($photos): ?>
            <div class="detail-thumb photo-carousel">
                <button type="button" class="carousel-btn carousel-prev" aria-label="Ankstesnė nuotrauka">&lt;</button>
                <?php foreach ($photos as $idx => $photo): ?>
                    <img src="<?php echo e($photo["file_path"]); ?>" alt="Pranešimo nuotrauka" class="carousel-photo" <?php echo $idx === 0 ? "" : "hidden"; ?>>
                <?php endforeach; ?>
                <button type="button" class="carousel-btn carousel-next" aria-label="Kita nuotrauka">&gt;</button>
            </div>
        <?php else: ?>
            <div class="detail-thumb placeholder">
                <img src="../icons/placeholder.svg" alt="" aria-hidden="true">
            </div>
        <?php endif; ?>
        <!-- Laikas ir statusas -->
        <div class="detail-meta">
            <div class="meta-block">
                <div class="meta-label">Pažeidimo laikas</div>
                <div class="meta-value"><?php echo e($issue["created_at"]); ?></div>
            </div>
            <div class="meta-block status-block">
                <div class="meta-label">Būsena</div>
                <div class="meta-value status-chip">
                    <span class="status-dot" style="background: <?php echo e($statusInfo['color']); ?>"></span>
                    <?php echo e($statusInfo["label"]); ?>
                </div>
            </div>
        </div>
        <div class="detail-section">
            <div class="section-label">Aprašymas</div>
            <p class="section-body">
                <?php echo nl2br(e($issue["description"])); ?>
            </p>
        </div>
        <div class="detail-section">
            <div class="section-label">Pažeidimo vieta</div>
            <p class="section-body">
                <?php echo e($issue["address"] ?? "Adresas nenurodytas"); ?>
            </p>
        </div>
        <!-- Mini zemelapis su pranesimo vieta -->
        <div id="issue-map" class="detail-map" data-lat="<?php echo e($issue["lat"]); ?>" data-lng="<?php echo e($issue["lng"]); ?>"></div>
        <div class="detail-actions">
            <button type="button" class="icon-btn comment-toggle" aria-label="Komentarai">
                <img src="../icons/comment-alt-dots.svg" alt="">
            </button>
            <button type="button" class="icon-btn share-copy" aria-label="Dalintis" data-link="issue.php?id=<?php echo (int) $id; ?>">
                <img src="../icons/share.svg" alt="">
            </button>
        </div>
    </article>
</main>

<!-- Komentaru panelis su sarasu ir forma -->
<div class="comments-overlay" id="comments-overlay">
    <div class="comments-panel">
        <div class="comments-head d-flex align-items-center justify-content-between">
            <span>Komentarai: <?php echo (int) $commentCount; ?></span>
            <button class="btn btn-link text-white close-comments p-0" aria-label="Uzdaryti komentarus">
                <img src="../icons/close.svg" alt="">
            </button>
        </div>
        <div class="comments-body">
            <?php if (!$comments): ?>
                <div class="comment-item">
                    <div class="avatar"></div>
                    <div class="comment-main">
                        <div class="comment-meta">Sistemos pranesimas</div>
                        <div class="comment-text">Komentaru dar nera.</div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($comments as $comment):
                    $author = $comment["full_name"] ?: ($comment["email"] ?? "Vartotojas");
                    $isAdmin = in_array((int) $comment["user_id"], $adminIds, true);
                    $canDelete = $user && (int) $comment["user_id"] === (int) $user["id"];
                ?>
                    <div class="comment-item">
                        <div class="avatar<?php echo $isAdmin ? " success" : ""; ?>"></div>
                        <div class="comment-main">
                            <div class="comment-head">
                                <div class="comment-meta"><?php echo e($author); ?><?php if ($isAdmin): ?> <span class="badge-admin">Admin</span><?php endif; ?></div>
                                <?php if ($canDelete): ?>
                                    <form method="post" class="comment-delete-form">
                                        <input type="hidden" name="action" value="delete_comment">
                                        <input type="hidden" name="comment_id" value="<?php echo (int) $comment["id"]; ?>">
                                        <button type="submit" class="comment-delete" aria-label="Ištrinti komentarą">×</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            <div class="comment-text"><?php echo e($comment["text"]); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <form class="comment-input-row" method="post" action="issue.php?id=<?php echo (int) $id; ?>">
            <input type="text" name="comment" class="form-control" placeholder="Palikite komentara" <?php echo current_user() ? "" : "disabled"; ?>>
            <button class="btn btn-primary btn-sm" <?php echo current_user() ? "" : "disabled"; ?>>Siusti</button>
        </form>
    </div>
</div>

<?php $navActive = 'issues'; include 'navbar.php'; ?>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin="anonymous"></script>
<script src="assets/app.js"></script>
</body>
</html>
