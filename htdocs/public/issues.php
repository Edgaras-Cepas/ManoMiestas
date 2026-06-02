<?php
// Visu pranesimu sarasas
// GDI: Sis kodas buvo sugeneruotas naudojant Cursor (Composer / Agent, 2026-03-05).
// Užklausa: Reikia pranesimu saraso su nuotraukomis ir galimybe filtruoti.
// Rezultatas dalinai koreguotas.
require_once __DIR__ . "/../lib/bootstrap.php";
$user = current_user();

// Gauna visus pranesimus su pirma nuotrauka ir komentaru skaiciu
$query = db()->query(
    "SELECT i.*, p.file_path,
            (SELECT COUNT(*) FROM issue_comments ic WHERE ic.issue_id = i.id) AS comments_count
     FROM issues i
     LEFT JOIN issue_photos p
       ON p.id = (SELECT id FROM issue_photos WHERE issue_id = i.id ORDER BY id ASC LIMIT 1)
     ORDER BY i.created_at DESC"
);
$issues = $query->fetchAll();

// Statuso ir kategoriju info rodymui
$issueStatus = issue_status();
$categoryIcons = category_icons();
// GDI: HTML struktura sugeneruota is Figma eksporto, modifikuota naudojant Cursor.
// Uzklausa: Pritaikyti Figma HTML i PHP sablona.
// Rezultatas dalinai koreguotas, modifikuota ir perdaryta.
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pranešimai · ManoMiestas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/app.css">
    <link rel="icon" type="image/svg+xml" href="../icons/miestas.svg">
</head>
<body class="is-list" data-auth="<?php echo $user ? "1" : "0"; ?>">

<?php include 'header.php';?>

<!-- Pranesimu sarasas su paieska ir filtrais (filtravimas per app.js) -->
<main class="list-page container-fluid">
    <div class="list-header">
        <h1 class="list-title">Pranešimai</h1>
        <!-- Paieska ir filtru mygtukas -->
        <div class="list-tools">
            <div class="search-wrapper list-search shadow-sm">
                <input type="text" class="form-control search-input" id="issue-search" placeholder="Search" aria-label="Paieška pranešimuose">
                <button class="btn btn-link text-decoration-none clear-search" type="button" aria-label="Išvalyti paiešką">&times;</button>
            </div>
            <button class="btn btn-light filter-btn" type="button" id="filter-toggle" aria-label="Filtruoti">
                <img src="../icons/filter.svg" alt="">
            </button>
        </div>
        <!-- Filtru panelis: tipas, busena, laikotarpis, rikiavimas -->
        <div class="filter-panel collapsed" id="filter-panel">
            <div class="filter-grid">
                <label class="filter-field">
                    <span>Tipas</span>
                    <select id="filter-type" class="form-select">
                        <option value="">Visi</option>
                        <option value="pažeidimai">Pažeidimai</option>
                        <option value="sezoninė">Sezoninė</option>
                        <option value="gedimai">Gedimai</option>
                        <option value="gyvūnai">Gyvūnai</option>
                        <option value="pastatai">Pastatai</option>
                        <option value="eismas">Eismas</option>
                        <option value="remontas">Remontas</option>
                    </select>
                </label>
                <label class="filter-field">
                    <span>Būsena</span>
                    <select id="filter-status" class="form-select">
                        <option value="">Visos</option>
                        <option value="new">New</option>
                        <option value="in_progress">In progress</option>
                        <option value="resolved">Resolved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </label>
                <label class="filter-field">
                    <span>Laikotarpis</span>
                    <select id="filter-period" class="form-select">
                        <option value="all">Visas</option>
                        <option value="24h">Paskutinės 24h</option>
                        <option value="7d">Paskutinės 7d</option>
                        <option value="30d">Paskutinės 30d</option>
                    </select>
                </label>
                <label class="filter-field">
                    <span>Rikiuoti</span>
                    <select id="filter-sort" class="form-select">
                        <option value="newest">Naujausi viršuje</option>
                        <option value="oldest">Seniausi viršuje</option>
                    </select>
                </label>
            </div>
        </div>
    </div>

    <!-- Pranesimu korteles — data-* atributai naudojami JS filtrui -->
    <section class="list-scroll" aria-label="Pranešimų sąrašas">
        <?php foreach ($issues as $issue):
            $status = $issue["status"] ?? "NEW";
            $statusInfo = $issueStatus[$status] ?? $issueStatus["NEW"];
            $category = $issue["category"] ?? "Kita";
            $categoryKey = mb_strtolower($category);
            $categoryIcon = $categoryIcons[$category] ?? $categoryIcons["Pažeidimai"];
            $dateValue = $issue["created_at"] ?? "";
        ?>
            <a class="issue-card list-card"
               href="issue.php?id=<?php echo (int) $issue['id']; ?>"
               data-type="<?php echo e($categoryKey); ?>"
               data-status="<?php echo e(strtolower($status)); ?>"
               data-date="<?php echo e($dateValue); ?>">
                <header class="issue-card__head">
                    <span class="category-icon <?php echo e($categoryIcon["class"]); ?>" aria-hidden="true">
                        <?php echo $categoryIcon["svg"]; ?>
                    </span>
                    <span class="issue-card__title"><?php echo e($issue["title"] ?? $category); ?></span>
                </header>
                <?php if (!empty($issue["file_path"])): ?>
                    <div class="issue-thumb">
                        <img src="<?php echo e($issue["file_path"]); ?>" alt="Pranešimo nuotrauka">
                    </div>
                <?php else: ?>
                    <div class="issue-thumb placeholder">
                        <img src="../icons/camera.svg" alt="">
                    </div>
                <?php endif; ?>
                <div class="issue-info">
                    <div class="info-row">
                        <span class="info-icon" aria-hidden="true">
                            <img src="../icons/marker.svg" alt="">
                        </span>
                        <span class="info-text"><?php echo e($issue["address"] ?? "Adresas nenurodytas"); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-icon" aria-hidden="true">
                            <img src="../icons/clock.svg" alt="">
                        </span>
                        <span class="info-text"><?php echo e($dateValue); ?></span>
                    </div>
                    <div class="info-row info-actions">
                        <span class="status-dot" style="background: <?php echo e($statusInfo['color']); ?>"></span>
                        <span class="info-text"><?php echo e($statusInfo['label']); ?></span>
                        <span class="spacer"></span>
                        <button type="button" class="icon-btn comment-toggle" aria-label="Komentarai" data-issue-id="<?php echo (int) $issue['id']; ?>">
                            <img src="../icons/comment-alt-dots.svg" alt="">
                        </button>
                        <button class="icon-btn share-copy" aria-label="Dalintis" data-link="issue.php?id=<?php echo (int) $issue['id']; ?>">
                            <img src="../icons/share.svg" alt="">
                        </button>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </section>
</main>

<!-- Komentaru slankus panelis — uzpildomas per issue-comments.php fetch() -->
<div class="comments-overlay" id="comments-overlay">
    <div class="comments-panel">
        <div class="comments-head d-flex align-items-center justify-content-between">
            <span class="comments-count">Komentarai: 0</span>
            <a class="btn btn-link text-white comments-open-link p-0" href="issues.php">Peržiūrėti</a>
            <button class="btn btn-link text-white close-comments p-0" aria-label="Uždaryti komentarus">
                <img src="../icons/close.svg" alt="">
            </button>
        </div>
        <div class="comments-body">
            <div class="comment-item">
                <div class="comment-main">
                    <div class="comment-text">Pasirinkite pranešimą, kad matytumėte komentarus.</div>
                </div>
            </div>
        </div>
    </div>
</div>


<?php $navActive = 'issues'; $navFab = true; include 'navbar.php'; ?>


<script src="assets/app.js"></script>
</body>
</html>
