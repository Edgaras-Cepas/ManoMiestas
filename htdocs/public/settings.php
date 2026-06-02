<?php
// Vartotojo nustatymai (public/settings.php)
// GDI: Sis kodas buvo sugeneruotas naudojant Cursor (Composer / Agent, 2026-04-25).
// Uzklausa: Reikia nustatymu puslapio kur vartotojas galetu keisti email, telefono numeri ir miesta. Taip pat reikia tabs for messages, notifications and a button for user issues
// Rezultatas dalinai koreguotas.
require_once __DIR__ . "/../lib/bootstrap.php";
require_login();

$user = current_user();
$error = "";
$success = "";
// Galimi home miestai 
$cities = ["Kaunas", "Vilnius", "Klaipėda", "Trakai", "Elektrėnai", "Šiauliai", "Radviliškis"];

// Apdoroja POST - atnaujina email, telefona arba miesta
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";
    if ($action === "email") {
        $newEmail = trim($_POST["email"] ?? "");
        if ($newEmail === "") {
            $error = "El. paštas negali būti tuščias.";
        } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $error = "Neteisingas el. pašto formatas.";
        } elseif ($newEmail !== $user["email"] && find_user_by_email($newEmail)) {
            $error = "El. paštas jau naudojamas.";
        } else {
            $query = db()->prepare("UPDATE users SET email = :email WHERE id = :id");
            $query->execute([
                "email" => $newEmail,
                "id" => $user["id"],
            ]);
            $success = "El. paštas atnaujintas.";
        }
    } elseif ($action === "phone") {
        $phone = trim($_POST["phone"] ?? "");
        $query = db()->prepare("UPDATE users SET phone = :phone WHERE id = :id");
        $query->execute([
            "phone" => $phone !== "" ? $phone : null,
            "id" => $user["id"],
        ]);
        $success = "Telefono numeris atnaujintas.";
    } elseif ($action === "city") {
        $city = trim($_POST["city"] ?? "");
        // Patikrina ar miestas is leistino saraso
        if ($city !== "" && !in_array($city, $cities, true)) {
            $error = "Pasirinktas miestas neleistinas.";
        } else {
            $query = db()->prepare("UPDATE users SET city = :city WHERE id = :id");
            $query->execute([
                "city" => $city !== "" ? $city : null,
                "id" => $user["id"],
            ]);
            $success = "Miestas atnaujintas.";
        }
    }
    // Atnaujina vartotojo duomenis po issaugojimo
    $user = get_user_by_id((int) $user["id"]);
}

$userId = (int) $user["id"];
$city = $user["city"] ?? "Nenurodyta";

// Gauna komentarus kurie palikti ant vartotojo pranesimu (zinuciu tab)
$messagesStmt = db()->prepare(
    "SELECT c.text, c.created_at, c.user_id, u.email, u.full_name, i.address, i.category, i.id AS issue_id
     FROM issue_comments c
     INNER JOIN issues i ON i.id = c.issue_id
     LEFT JOIN users u ON u.id = c.user_id
     WHERE i.user_id = :uid
     ORDER BY c.created_at DESC
     LIMIT 50"
);
$messagesStmt->execute(["uid" => $userId]);
$messages = $messagesStmt->fetchAll();

// Gauna statuso pakeitimus ir trinimus is audit log (notifikaciju tab)
$notificationsStmt = db()->prepare(
    "SELECT a.action, a.created_at, i.id AS issue_id, i.category, i.address
     FROM audit_log a
     INNER JOIN issues i ON i.id = a.entity_id
     WHERE a.entity = 'issue'
       AND i.user_id = :uid
       AND a.action IN ('STATUS_CHANGE', 'ISSUE_DELETE')
     ORDER BY a.created_at DESC
     LIMIT 50"
);
$notificationsStmt->execute(["uid" => $userId]);
$notifications = $notificationsStmt->fetchAll();

// Gauna admin ID sarasa zinuciu badge rodymui
$adminIdsStmt = db()->prepare(
    "SELECT DISTINCT ur.user_id
     FROM user_roles ur
     INNER JOIN roles r ON r.id = ur.role_id
     WHERE r.name = 'ADMIN'"
);
$adminIdsStmt->execute();
$adminIds = array_map("intval", $adminIdsStmt->fetchAll(PDO::FETCH_COLUMN));

// GDI: HTML struktura sugeneruota is Figma eksporto, modifikuota naudojant Cursor.
// Uzklausa: Pritaikyti Figma HTML i PHP sablona.
// Rezultatas dalinai koreguotas, modifikuota ir perdaryta.
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nustatymai · ManoMiestas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/app.css">
    <link rel="icon" type="image/svg+xml" href="../icons/miestas.svg">
</head>
<body class="is-settings" data-auth="1">

<?php include 'header.php';?>


<!-- Nustatymai su 3 tabais: nustatymai, zinutes, notifikacijos -->
<main class="settings-page container-fluid">
    <h1 class="settings-title text-center">Nustatymai</h1>
    <section class="settings-card">
        <!-- Mygtukas i my-issues.php ir tabu perjungimas per app.js -->
        <div class="settings-actions">
            <a class="btn btn-primary w-100" href="my-issues.php">Pranešimų istorija</a>
            <div class="d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-primary flex-fill settings-tab active" data-tab="settings">Nustatymai</button>
                <button type="button" class="btn btn-primary flex-fill settings-tab" data-tab="messages">Žinutės</button>
                <button type="button" class="btn btn-primary flex-fill settings-tab" data-tab="notifications">Notifikacijos</button>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2"><?php echo e($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success py-2"><?php echo e($success); ?></div>
        <?php endif; ?>

        <!-- Nustatymu tab: miestas, email, telefonas -->
        <div class="settings-content" data-tab-panel="settings">
            <form class="settings-field" data-field="city" method="post" id="city-form">
                <div class="field-label">Miestas</div>
                <input type="hidden" name="action" value="city">
                <input type="hidden" name="city" id="city-input" value="<?php echo e($user["city"] ?? ""); ?>">
                <div class="d-flex justify-content-between align-items-baseline">
                    <div class="field-value" id="setting-city"><?php echo e($city); ?></div>
                    <a href="#" class="field-link city-edit">Keisti</a>
                </div>
            </form>

            <form class="settings-field" data-field="email" method="post">
                <div class="field-label">Elektroninis paštas</div>
                <input type="hidden" name="action" value="email">
                <div class="field-inline">
                    <input type="email" name="email" class="form-control" value="<?php echo e($user["email"]); ?>" required>
                    <button type="submit" class="btn btn-primary btn-sm save-field">Išsaugoti</button>
                </div>
            </form>

            <form class="settings-field" data-field="phone" method="post">
                <div class="field-label">Telefono numeris</div>
                <input type="hidden" name="action" value="phone">
                <div class="field-inline">
                    <input type="text" name="phone" class="form-control" value="<?php echo e($user["phone"] ?? ""); ?>">
                    <button type="submit" class="btn btn-primary btn-sm save-field">Išsaugoti</button>
                </div>
            </form>
        </div>

        <!-- Zinuciu tab: komentarai ant vartotojo pranesimu -->
        <div class="settings-content" data-tab-panel="messages" hidden>
            <?php if (!$messages): ?>
                <div class="message-item">
                    <div class="avatar"></div>
                    <div class="message-body">
                        <div class="message-meta">Pranešimų nėra</div>
                        <div class="message-text">Kol kas komentarų negauta.</div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($messages as $message):
                    $author = $message["full_name"] ?: ($message["email"] ?? "Vartotojas");
                    $meta = $message["address"] ?: ($message["category"] ?? "Pranešimas");
                    $isAdmin = in_array((int) $message["user_id"], $adminIds, true);
                ?>
                    <div class="message-item">
                        <div class="avatar<?php echo $isAdmin ? " success" : ""; ?>"></div>
                        <div class="message-body">
                            <div class="message-meta"><?php echo e($author); ?> • <?php echo e($meta); ?></div>
                            <div class="message-text"><?php echo e($message["text"]); ?></div>
                            <a class="message-action" href="issue.php?id=<?php echo (int) $message["issue_id"]; ?>">Peržiūrėti</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Notifikaciju tab: statuso pakeitimai ir trinimai is audit log -->
        <div class="settings-content" data-tab-panel="notifications" hidden>
            <?php if (!$notifications): ?>
                <div class="message-item">
                    <div class="avatar success"></div>
                    <div class="message-body">
                        <div class="message-meta">Sistemos pranešimas</div>
                        <div class="message-text">Naujesnių pranešimų nėra.</div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $note):
                    $meta = $note["address"] ?: ($note["category"] ?? "Pranešimas");
                    if ($note["action"] === "STATUS_CHANGE") {
                        $text = "Jūsų pranešimo būsena atnaujinta.";
                    } elseif ($note["action"] === "ISSUE_DELETE") {
                        $text = "Jūsų pranešimas buvo pašalintas.";
                    } else {
                        $text = "Sistemos pranešimas.";
                    }
                ?>
                    <div class="message-item">
                        <div class="avatar success"></div>
                        <div class="message-body">
                            <div class="message-meta"><?php echo e($meta); ?></div>
                            <div class="message-text"><?php echo e($text); ?></div>
                            <a class="message-action" href="issue.php?id=<?php echo (int) $note["issue_id"]; ?>">Peržiūrėti</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <div class="logout-row">
        <a class="btn btn-outline-dark logout-btn" href="logout.php">Atsijungti</a>
    </div>
</main>

<!-- Miesto pasirinkimo modalas — atidaromas per app.js -->
<div class="modal-overlay" id="city-modal" hidden>
    <div class="modal-panel">
        <div class="modal-head d-flex justify-content-between align-items-center">
            <strong>Miestas</strong>
            <button class="btn btn-link close-modal" aria-label="Uždaryti">X</button>
        </div>
        <ul class="city-list" aria-label="Miestų sąrašas">
            <?php foreach ($cities as $cityName): ?>
                <li><button type="button" class="city-option" data-city="<?php echo e($cityName); ?>"><?php echo e($cityName); ?></button></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<?php $navActive = 'settings'; include 'navbar.php'; ?>

<script src="assets/app.js"></script>
</body>
</html>
