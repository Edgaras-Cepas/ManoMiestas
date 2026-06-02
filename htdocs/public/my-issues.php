<?php
// Prisijungusio vartotojo pranesimu sarasas
// GDI: Sis kodas buvo sugeneruotas naudojant Cursor (Composer / Agent, 2026-04-9).
// Uzklausa: Noriu puslapio kur vartotojas matytu tik savo pranesimus su statusais.
// Rezultatas dalinai koreguotas.
require_once __DIR__ . "/../lib/bootstrap.php";
require_login();
$user = current_user();

// Gauna tik prisijungusio vartotojo pranesimus su pirma nuotrauka
$query = db()->prepare(
    "SELECT i.*, p.file_path
     FROM issues i
     LEFT JOIN issue_photos p
       ON p.id = (SELECT id FROM issue_photos WHERE issue_id = i.id ORDER BY id ASC LIMIT 1)
     WHERE i.user_id = :uid
     ORDER BY i.created_at DESC"
);
$query->execute(["uid" => current_user()["id"]]);
$issues = $query->fetchAll();

// Statuso info rodymui
$issueStatus = issue_status();
// GDI: HTML struktura sugeneruota is Figma eksporto, modifikuota naudojant Cursor.
// Uzklausa: Pritaikyti Figma HTML i PHP sablona.
// Rezultatas dalinai koreguotas, modifikuota ir perdaryta.
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tavo pranešimai · ManoMiestas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/app.css">
    <link rel="icon" type="image/svg+xml" href="../icons/miestas.svg">
</head>
<body class="is-list" data-auth="<?php echo $user ? "1" : "0"; ?>">

<?php include 'header.php';?>

<!-- Vartotojo pranesimu sarasas su nuotrauka, statusu ir redagavimo mygtuku -->
<main class="my-issues-page container-fluid" style="padding:24px 16px 90px; display:flex; flex-direction:column; align-items:center; gap:16px;">
    <h1 class="list-title text-center">Tavo pranešimai</h1>
    <section class="my-issues-list" style="width:min(520px, 100%); display:flex; flex-direction:column; gap:16px;">
        <?php foreach ($issues as $issue):
            $status = $issue["status"] ?? "NEW";
            $statusInfo = $issueStatus[$status] ?? $issueStatus["NEW"];
        ?>
            <article class="my-issue-card" style="display:grid; grid-template-columns:96px 1fr; gap:14px; border-top:1px solid #222; padding-top:12px;">
                <!-- Nuotrauka arba placeholder -->
                <div class="my-issue-thumb" style="width:96px; height:96px; background:#cfcfcf; border-radius:10px; position:relative; overflow:hidden;">
                    <?php if (!empty($issue["file_path"])): ?>
                        <img src="<?php echo e($issue['file_path']); ?>" alt="Pranešimo nuotrauka" style="width:100%; height:100%; object-fit:cover;">
                    <?php else: ?>
                        <div class="thumb-cross" style="position:absolute; inset:12px; border:2px solid rgba(0,0,0,0.2);"></div>
                        <div style="position:absolute; inset:12px; border-top:2px solid rgba(0,0,0,0.25); transform:rotate(45deg); transform-origin:center;"></div>
                        <div style="position:absolute; inset:12px; border-top:2px solid rgba(0,0,0,0.25); transform:rotate(-45deg); transform-origin:center;"></div>
                    <?php endif; ?>
                </div>
                <!-- Adresas, kategorija, data, statusas ir veiksmu mygtukai -->
                <div class="my-issue-body" style="display:flex; flex-direction:column; gap:4px;">
                    <div class="my-issue-place" style="font-weight:700;"><?php echo e($issue['address'] ?? 'Adresas nenurodytas'); ?></div>
                    <div class="my-issue-category" style="display:flex; align-items:center; gap:6px; font-weight:600;">
                        <?php echo e($issue['category'] ?? 'Kita'); ?>
                    </div>
                    <div class="my-issue-date" style="font-size:0.9rem; color:#333;">Data: <?php echo e($issue['created_at'] ?? ''); ?></div>
                    <div class="my-issue-actions" style="display:flex; align-items:center; gap:8px; margin-top:4px;">
                        <span class="status-dot" style="background: <?php echo e($statusInfo['color']); ?>;"></span>
                        <span class="status-text" style="font-weight:600;"><?php echo e($statusInfo['label']); ?></span>
                        <a class="btn btn-outline-secondary btn-sm ms-auto" href="issue.php?id=<?php echo (int) $issue['id']; ?>">Peržiūrėti</a>
                        <!-- Redaguoti tik jei statusas NEW -->
                        <?php if ($status === "NEW"): ?>
                            <a class="btn btn-outline-primary btn-sm" href="issue-edit.php?id=<?php echo (int) $issue['id']; ?>">Redaguoti</a>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
</main>

<?php $navActive = 'settings'; include 'navbar.php'; ?>

<script src="assets/app.js"></script>
</body>
</html>
