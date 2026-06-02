<?php
// JSON API komentarams (public/issue-comments.php)
// GDI: Sis kodas buvo sugeneruotas naudojant Cursor (Composer / Agent, 2026-03-28).
// Uzklausa: Reikia JSON endpoint komentarams.
// Rezultatas dalinai koreguotas.
require_once __DIR__ . "/../lib/bootstrap.php";

header("Content-Type: application/json; charset=UTF-8");

// Gauna issue id is URL, jei nera grazina 400
$id = (int) ($_GET["id"] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(["error" => "bad_request"]);
    exit;
}

// Patikrina ar issue egzistuoja, jei ne grazina 404
$existsStmt = db()->prepare("SELECT 1 FROM issues WHERE id = :id");
$existsStmt->execute(["id" => $id]);
if (!$existsStmt->fetchColumn()) {
    http_response_code(404);
    echo json_encode(["error" => "not_found"]);
    exit;
}

// Gauna visus komentarus su autoriaus info
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

// Suformuoja JSON atsakyma su komentaru skaiciu ir sarasu
$payload = [
    "count" => count($comments),
    "comments" => array_map(function ($comment) use ($adminIds) {
        $author = $comment["full_name"] ?: ($comment["email"] ?? "Vartotojas");
        return [
            "author" => $author,
            "text" => $comment["text"],
            "created_at" => $comment["created_at"],
            "is_admin" => in_array((int) $comment["user_id"], $adminIds, true),
        ];
    }, $comments),
];

echo json_encode($payload, JSON_UNESCAPED_UNICODE);
