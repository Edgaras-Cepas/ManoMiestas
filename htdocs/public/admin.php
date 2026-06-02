<?php

// GDI: Šis kodas buvo sugeneruotas naudojant Cursor (Composer / Agent, 2026-04-02).
// Užklausa: Reikia admin puslapio kur galima perziureti ir valdyti pranesimus, komentarus ir vartotojus
// Rezultatas dalinai koreguotas ir pridetas audit zurnalo dalis.
require_once __DIR__ . "/../lib/bootstrap.php";
require_login();

if (!is_admin()) {
    redirect("index.php");
}

$user = current_user();
$error = "";
$success = "";


// Pasalina pranesima, kartu su jo komentarais, nuotraukom is failo ir DB
function delete_issue_data(int $issueId): void
{
    $query = db()->prepare("SELECT file_path FROM issue_photos WHERE issue_id = :id");
    $query->execute(["id" => $issueId]);
    $paths = $query->fetchAll(PDO::FETCH_COLUMN);

    foreach ($paths as $path) {
        $fullPath = __DIR__ . "/" . $path;
        if (is_file($fullPath)) {
            unlink($fullPath);
        }
    }

    $delComments = db()->prepare("DELETE FROM issue_comments WHERE issue_id = :id");
    $delComments->execute(["id" => $issueId]);
    $delPhotos = db()->prepare("DELETE FROM issue_photos WHERE issue_id = :id");
    $delPhotos->execute(["id" => $issueId]);
    $delIssue = db()->prepare("DELETE FROM issues WHERE id = :id");
    $delIssue->execute(["id" => $issueId]);
}

// Busenos keitimas, salinimas, komentarai, vartotoju blokavimas
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    //Jei veiksmas update status, gaunam busena ir issue id patikrinam ir atnaujinam
    if ($action === "update_status") {
        $issueId = (int) ($_POST["issue_id"] ?? 0);
        $status = $_POST["status"] ?? "";
        $allowed = ["NEW", "IN_PROGRESS", "RESOLVED", "REJECTED"];
        if ($issueId > 0 && in_array($status, $allowed, true)) {
            $query = db()->prepare("UPDATE issues SET status = :status, updated_at = NOW() WHERE id = :id");
            $query->execute(["status" => $status, "id" => $issueId]);
            log_audit("issue", $issueId, "STATUS_CHANGE", (int) $user["id"]);
            $success = "Būsena atnaujinta.";
        }
    } 

    //Jei veiksmas delete issue, gaunam id, tikrinam, viska kas susije istrinam
    else if ($action === "delete_issue") {
        $issueId = (int) ($_POST["issue_id"] ?? 0);
        if ($issueId > 0) {
            delete_issue_data($issueId);
            log_audit("issue", $issueId, "ISSUE_DELETE", (int) $user["id"]);
            $success = "Pranešimas pašalintas.";
        }
    }

    //Jei veiksmas delete_comment, gaunam comment id, is comment lenteles paimam issue id,
    //istrinam is comment lenteles irasus
     else if ($action === "delete_comment") {
        $commentId = (int) ($_POST["comment_id"] ?? 0);
        if ($commentId > 0) {
            
            // gauti issue id for audit log
            $query = db()->prepare("SELECT issue_id FROM issue_comments WHERE id = :id");
            $query->execute(["id" => $commentId]);
            $issueId = (int) ($query->fetchColumn() ?: 0);

            $del = db()->prepare("DELETE FROM issue_comments WHERE id = :id");
            $del->execute(["id" => $commentId]);

            
            if ($issueId) {
                log_audit("issue", $issueId, "COMMENT_DELETE", (int) $user["id"]);
            }
            $success = "Komentaras pašalintas.";
        }
    }

    //Jei veiksmas delete user, tai gaunam visus vartotojo issues, istrinam visa data,
    //  po to visus varotojo comments, is roles lenteles ir galiausiai pati user
     else if ($action === "delete_user") {
        $userId = (int) ($_POST["user_id"] ?? 0);
        if ($userId > 0) {
            $issuesQuery = db()->prepare("SELECT id FROM issues WHERE user_id = :userid");
            $issuesQuery->execute(["userid" => $userId]);
            $issueIds = $issuesQuery->fetchAll(PDO::FETCH_COLUMN);
            foreach ($issueIds as $issueId) {
                delete_issue_data((int) $issueId);
                log_audit("issue", (int) $issueId, "ISSUE_DELETE", (int) $user["id"]);
            }
            $delUserComments = db()->prepare("DELETE FROM issue_comments WHERE user_id = :userid");
            $delUserComments->execute(["userid" => $userId]);

            $delRoles = db()->prepare("DELETE FROM user_roles WHERE user_id = :userid");
            $delRoles->execute(["userid" => $userId]);

            $delUser = db()->prepare("DELETE FROM users WHERE id = :userid");
            $delUser->execute(["userid" => $userId]);

            log_audit("user", $userId, "USER_DELETE", (int) $user["id"]);
            $success = "Vartotojas pašalintas.";
        }
    }
    
    
}

// gauna is URL skyrius (defaults to issues)
$view = $_GET["view"] ?? "issues";

// Shown in URL, filter ir query stuff
$q = trim($_GET["q"] ?? "");
$statusFilter = trim($_GET["status"] ?? "");
$categoryFilter = trim($_GET["category"] ?? "");
$actionFilter = trim($_GET["action"] ?? "");

// For Dropdown
$categories = ["Pažeidimai", "Sezoninė", "Gedimai", "Gyvūnai", "Pastatai", "Eismas", "Remontas"];
$statuses = ["NEW", "IN_PROGRESS", "RESOLVED", "REJECTED"];
$actions = ["STATUS_CHANGE", "ISSUE_DELETE", "COMMENT_ADD", "COMMENT_DELETE"];


$issues = [];
$comments = [];
$users = [];
$audits = [];

// Jei issues tab, surenkam filtrus (paieska, statusas, kategorija) ir formuojam SQL uzklausas
// Jei export=1 grazina CSV faila
// Issaugo i issues masyva rodymui
if ($view === "issues") {
    $where = [];
    $params = [];
    if ($q !== "") {
        $where[] = "CONCAT_WS(' ', i.title, i.description, i.address, i.status, i.category, i.created_at, i.id, u.email) LIKE :q";
        $params["q"] = "%" . $q . "%";
    }
    if ($statusFilter !== "") {
        $where[] = "i.status = :status";
        $params["status"] = $statusFilter;
    }
    if ($categoryFilter !== "") {
        $where[] = "i.category = :category";
        $params["category"] = $categoryFilter;
    }

    $sql = "SELECT i.*, u.email FROM issues i LEFT JOIN users u ON u.id = i.user_id";

    //for multiple filters
    if ($where) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    $sql .= " ORDER BY i.created_at DESC";

    if (($_GET["export"] ?? "") === "1") {
        header("Content-Type: text/csv; charset=UTF-8");
        header("Content-Disposition: attachment; filename=issues.csv");
        $query = db()->prepare($sql);
        $query->execute($params);
        $rows = $query->fetchAll();
        $out = fopen("php://output", "w");
        fputcsv($out, ["ID", "Title", "Category", "Status", "Address", "Lat", "Lng", "Created"]);
        foreach ($rows as $row) {
            fputcsv($out, [
                $row["id"],
                $row["title"],
                $row["category"],
                $row["status"],
                $row["address"],
                $row["lat"],
                $row["lng"],
                $row["created_at"],
            ]);
        }
        fclose($out);
        exit;
    }

    $issuesQuery = db()->prepare($sql);
    $issuesQuery->execute($params);
    $issues = $issuesQuery->fetchAll();
}

// Jei comments tab, surenkam filtra (paieska) ir formuojam SQL
// Jei export=1 grazina CSV
// issaugom i comments rodymui
if ($view === "comments") {
    $where = [];
    $params = [];
    if ($q !== "") {
        $where[] = "CONCAT_WS(' ', c.text, c.created_at, c.issue_id, u.email, u.full_name, i.address, i.category) LIKE :q";
        $params["q"] = "%" . $q . "%";
    }
    $sql = "SELECT c.id, c.text, c.created_at, c.issue_id, u.email, u.full_name, i.address, i.category
            FROM issue_comments c
            LEFT JOIN users u ON u.id = c.user_id
            LEFT JOIN issues i ON i.id = c.issue_id";
    if ($where) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    $sql .= " ORDER BY c.created_at DESC";

    if (($_GET["export"] ?? "") === "1") {
        header("Content-Type: text/csv; charset=UTF-8");
        header("Content-Disposition: attachment; filename=comments.csv");
        $query = db()->prepare($sql);
        $query->execute($params);
        $rows = $query->fetchAll();
        $out = fopen("php://output", "w");
        fputcsv($out, ["ID", "IssueID", "Author", "Text", "Created"]);
        foreach ($rows as $row) {
            $author = $row["full_name"] ?: ($row["email"] ?? "");
            fputcsv($out, [
                $row["id"],
                $row["issue_id"],
                $author,
                $row["text"],
                $row["created_at"],
            ]);
        }
        fclose($out);
        exit;
    }

    $commentsStmt = db()->prepare($sql);
    $commentsStmt->execute($params);
    $comments = $commentsStmt->fetchAll();
}


// Jei users tab, gaunam filtra is paieskos query ir su issue skaiciu kiekvieno vartotojo
// Jei export=1 grazina CSV
// issaugom i users rodymui
if ($view === "users") {
    $where = [];
    $params = [];
    if ($q !== "") {
        $where[] = "CONCAT_WS(' ', u.email, u.full_name, u.status, u.created_at, u.id) LIKE :q";
        $params["q"] = "%" . $q . "%";
    }
    $sql = "SELECT u.id, u.email, u.full_name, u.status, u.created_at,
                   (SELECT COUNT(*) FROM issues i WHERE i.user_id = u.id) AS issue_count
            FROM users u";
    if ($where) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    $sql .= " ORDER BY u.created_at DESC";

    if (($_GET["export"] ?? "") === "1") {
        header("Content-Type: text/csv; charset=UTF-8");
        header("Content-Disposition: attachment; filename=users.csv");
        $query = db()->prepare($sql);
        $query->execute($params);
        $rows = $query->fetchAll();
        $out = fopen("php://output", "w");
        fputcsv($out, ["ID", "Email", "Name", "Status", "Created", "Issues"]);
        foreach ($rows as $row) {
            fputcsv($out, [
                $row["id"],
                $row["email"],
                $row["full_name"],
                $row["status"],
                $row["created_at"],
                $row["issue_count"],
            ]);
        }
        fclose($out);
        exit;
    }

    $usersStmt = db()->prepare($sql);
    $usersStmt->execute($params);
    $users = $usersStmt->fetchAll();
}


// Jei audit tab, surenkam filtrus (paieska, veiksmas) ir darom SQL
// Jei export=1 grazina CSV
// issaugom i $audits rodymui
if ($view === "audit") {
    $where = [];
    $params = [];
    if ($q !== "") {
        $where[] = "CONCAT_WS(' ', a.id, a.entity, a.action, a.actor_id, a.created_at) LIKE :q";
        $params["q"] = "%" . $q . "%";
    }

    if ($actionFilter !== "") {
        $where[] = "a.action = :action";
        $params["action"] = $actionFilter;
    }

    $sql = "SELECT a.id, a.entity, a.action, a.actor_id, a.created_at FROM audit_log a";
    if ($where) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    $sql .= " ORDER BY a.created_at DESC";

    if (($_GET["export"] ?? "") === "1") {
        header("Content-Type: text/csv; charset=UTF-8");
        header("Content-Disposition: attachment; filename=audit.csv");
        $query = db()->prepare($sql);
        $query->execute($params);
        $rows = $query->fetchAll();
        $out = fopen("php://output", "w");
        fputcsv($out, ["Issue ID", "Entity", "Action", "User ID", "Created"]);
        foreach ($rows as $row) {
            fputcsv($out, [
                $row["id"],
                $row["entity"],
                $row["action"],
                $row["actor_id"],
                $row["created_at"],
            ]);
        }
        fclose($out);
        exit;
    }

    $auditStmt = db()->prepare($sql);
    $auditStmt->execute($params);
    $audits = $auditStmt->fetchAll();
}



?>
<!-- GDI: Šis kodas buvo sugeneruotas naudojant Cursor (Composer / Agent)
     Uzklausa: Pritaikyk egzistuojanti HTML design administratoriaus sasajai
     Rezultatas dalinai koreguotas, pridetas audit zurnalas ir kiti pakeitimai. -->

<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel · ManoMiestas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/app.css">
    <link rel="icon" type="image/svg+xml" href="../icons/miestas.svg">
</head>
<body class="is-admin" data-auth="1">

<?php include 'header.php';?>

<main class="admin-page container-fluid">
    <h1 class="admin-title text-center">Admin Panel</h1>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?php echo e($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success py-2"><?php echo e($success); ?></div>
    <?php endif; ?>

    <!-- Skyriai (issues, comments, users, audit) -->
    <div class="admin-tabs">
        <a class="btn admin-tab <?php echo $view === "issues" ? "active" : ""; ?>" href="admin.php?view=issues">Pranešimai</a>
        <a class="btn admin-tab <?php echo $view === "comments" ? "active" : ""; ?>" href="admin.php?view=comments">Komentarai</a>
        <a class="btn admin-tab <?php echo $view === "users" ? "active" : ""; ?>" href="admin.php?view=users">Vartotojai</a>
        <a class="btn admin-tab <?php echo $view === "audit" ? "active" : ""; ?>" href="admin.php?view=audit">Audit žurnalas</a>
    </div>

    <!-- Paieskos langelis ir dropdowns fro issues -->    
    <?php if ($view === "issues"): ?>
        <section class="admin-card">
            <form class="admin-filters" method="get">
                <input type="hidden" name="view" value="issues">
                <input type="text" name="q" class="form-control" placeholder="Paieška" value="<?php echo e($q); ?>">
                <select name="category" class="form-select">
                    <option value="">Visos kategorijos</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo e($cat); ?>" <?php echo $categoryFilter === $cat ? "selected" : ""; ?>>
                            <?php echo e($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="status" class="form-select">
                    <option value="">Visos būsenos</option>
                    <?php foreach ($statuses as $status): ?>
                        <option value="<?php echo e($status); ?>" <?php echo $statusFilter === $status ? "selected" : ""; ?>>
                            <?php echo e($status); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">Filtruoti</button>
                <a class="btn btn-outline-dark" href="admin.php?view=issues&amp;export=1&amp;q=<?php echo urlencode($q); ?>&amp;category=<?php echo urlencode($categoryFilter); ?>&amp;status=<?php echo urlencode($statusFilter); ?>">CSV eksportas</a>
            </form>
        </section>
    <!-- Paieskos langelis for comments -->                     
    <?php elseif ($view === "comments"): ?>
        <section class="admin-card">
            <form class="admin-filters" method="get">
                <input type="hidden" name="view" value="comments">
                <input type="text" name="q" class="form-control" placeholder="Paieška komentaruose" value="<?php echo e($q); ?>">
                <button type="submit" class="btn btn-primary">Filtruoti</button>
                <a class="btn btn-outline-dark" href="admin.php?view=comments&amp;export=1&amp;q=<?php echo urlencode($q); ?>">CSV eksportas</a>
            </form>
        </section>
    <!-- Paieskos langelis ir dropdown for audit --> 
    <?php elseif ($view === "audit"): ?>
    <section class="admin-card">
        <form class="admin-filters" method="get">
            <input type="hidden" name="view" value="audit">
            <input type="text" name="q" class="form-control" placeholder="Paieška" value="<?php echo e($q); ?>">
            <select name="action" class="form-select">
                <option value="">Visi veiksmai</option>
                <?php foreach ($actions as $act): ?>
                    <option value="<?php echo e($act); ?>" <?php echo $actionFilter === $act ? "selected" : ""; ?>>
                        <?php echo e($act); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">Filtruoti</button>
            <a class="btn btn-outline-dark" href="admin.php?view=audit&amp;export=1&amp;q=<?php echo urlencode($q); ?>">CSV eksportas</a>
        </form>
    </section>
    <!-- Paieskos langelis for users -->                
    <?php else: ?>
        <section class="admin-card">
            <form class="admin-filters" method="get">
                <input type="hidden" name="view" value="users">
                <input type="text" name="q" class="form-control" placeholder="Paieška vartotojuose" value="<?php echo e($q); ?>">
                <button type="submit" class="btn btn-primary">Filtruoti</button>
                <a class="btn btn-outline-dark" href="admin.php?view=users&amp;export=1&amp;q=<?php echo urlencode($q); ?>">CSV eksportas</a>
            </form>
        </section>
    <?php endif; ?>

    <!-- Issue kortele su info (title, address, email), delete button, status update ir view link --> 
    <?php if ($view === "issues"): ?>
        <section class="admin-section">
            <h2 class="admin-section-title">Pranešimai</h2>
            <div class="admin-issues-list">
                <?php if (!$issues): ?>
                    <div class="admin-empty">Pranešimų nerasta.</div>
                <?php else: ?>
                    <?php foreach ($issues as $issue): ?>
                        <article class="admin-issue-card">
                            <div class="admin-issue-head">
                                <!-- Title, address, email --> 
                                <div>
                                    <strong><?php echo e($issue["title"] ?? $issue["category"]); ?></strong>
                                    <div class="admin-meta"><?php echo e($issue["address"] ?? "Adresas nenurodytas"); ?></div>
                                    <div class="admin-meta">Autorius: <?php echo e($issue["email"] ?? "-"); ?></div>
                                </div>
                                <!-- Delete --> 
                                <form method="post" class="admin-inline">
                                    <input type="hidden" name="action" value="delete_issue">
                                    <input type="hidden" name="issue_id" value="<?php echo (int) $issue["id"]; ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm">Ištrinti</button>
                                </form>
                            </div>
                            <div class="admin-issue-actions">
                                <!-- Status update --> 
                                <form method="post" class="admin-inline">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="issue_id" value="<?php echo (int) $issue["id"]; ?>">
                                    <select name="status" class="form-select form-select-sm">
                                        <?php foreach ($statuses as $status): ?>
                                            <option value="<?php echo e($status); ?>" <?php echo ($issue["status"] === $status) ? "selected" : ""; ?>>
                                                <?php echo e($status); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-primary btn-sm">Atnaujinti</button>
                                </form>
                                <a class="btn btn-outline-primary btn-sm" href="issue.php?id=<?php echo (int) $issue["id"]; ?>">Peržiūrėti</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    <!-- Komentaru kortele su autoriumi, tekstu, issue info ir delete button -->     
    <?php elseif ($view === "comments"): ?>
        <section class="admin-section">
            <h2 class="admin-section-title">Komentarai</h2>
            <div class="admin-comments-list">
                <?php if (!$comments): ?>
                    <div class="admin-empty">Komentarų nėra.</div>
                <?php else: ?>
                    <?php foreach ($comments as $comment):
                        $author = $comment["full_name"] ?: ($comment["email"] ?? "Vartotojas");
                        $meta = $comment["address"] ?: ($comment["category"] ?? "Pranešimas");
                    ?>
                        <div class="admin-comment-item">
                            <div>
                                <div class="admin-meta"><?php echo e($author); ?> • <?php echo e($meta); ?></div>
                                <div><?php echo e($comment["text"]); ?></div>
                            </div>
                            <div class="admin-inline">
                                <a class="btn btn-outline-primary btn-sm" href="issue.php?id=<?php echo (int) $comment["issue_id"]; ?>">Peržiūrėti</a>
                                <form method="post" class="admin-inline">
                                    <input type="hidden" name="action" value="delete_comment">
                                    <input type="hidden" name="comment_id" value="<?php echo (int) $comment["id"]; ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm">Ištrinti</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    <!-- Audit irasas su veiksmu, entity, vartotojo ID ir laiku -->
    <?php elseif ($view === "audit"): ?>
        <section class="admin-section">
            <h2 class="admin-section-title">Audit žurnalas</h2>
            <div class="admin-users-list">
                <?php if (!$audits): ?>
                    <div class="admin-empty">Įrašų nėra.</div>
                <?php else: ?>
                    <?php foreach ($audits as $audit): ?>
                        <div class="admin-user-item">
                            <div>
                                <div><strong><?php echo e($audit["action"]); ?></strong> • <?php echo e($audit["entity"]); ?></div>
                                <div class="admin-meta">Vartotojo ID: <?php echo (int) $audit["actor_id"]; ?> • <?php echo e($audit["created_at"]); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    <!-- Vartotojo kortele su email, pranesimų skaiciumi, statusu ir delete button -->
    <?php else: ?>
        <section class="admin-section">
            <h2 class="admin-section-title">Vartotojai</h2>
            <div class="admin-users-list">
                <?php if (!$users): ?>
                    <div class="admin-empty">Vartotojų nėra.</div>
                <?php else: ?>
                    <?php foreach ($users as $row): ?>
                        <div class="admin-user-item">
                            <div>
                                <div><strong><?php echo e($row["email"]); ?></strong></div>
                                <div class="admin-meta">Pranešimų: <?php echo (int) $row["issue_count"]; ?> • Statusas: <?php echo e($row["status"]); ?></div>
                            </div>
                            <?php if ((int) $row["id"] !== (int) $user["id"]): ?>
                                <form method="post" class="admin-inline">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="user_id" value="<?php echo (int) $row["id"]; ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm">Ištrinti</button>
                                </form>
                            <?php else: ?>
                                <span class="admin-meta">Tai jūs</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>
</main>



<script src="assets/app.js"></script>
</body>
</html>