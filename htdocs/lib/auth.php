<?php

// ManoMiestas sesija, prisijungimas ir roles

declare(strict_types=1);

// GDI: Šis kodas buvo sugeneruotas naudojant Cursor (Composer / Agent, 2026-03-14).
// Užklausa: Reikia funkciju, kad gauti vartotojus, jie galetu prisijungti, atsijungti ir jei reikia, kad reikalautu prisijungimo
// Rezultatas dalinai koreguotas.

// Vartotojo paieska pagal email prisijungimui/registracijai.
function find_user_by_email(string $email): ?array
{
    $query = db()->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
    $query->execute(["email" => $email]);
    $user = $query->fetch();
    return $user ?: null;
}


// Iraso user_id i sesija po sekmingo prisijungimo
function login_user(array $user): void
{
    $_SESSION["user_id"] = (int) $user["id"];
}

// Gauti vartotoja pagal id
function get_user_by_id(int $id): ?array
{
    $query = db()->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
    $query->execute(["id" => $id]);
    $user = $query->fetch();
    return $user ?: null;
}

// Tikrina ar sesijoj yra vartotojas, jei tuscia grazina null jei ne duoda jo info
function current_user(): ?array
{
    if (empty($_SESSION["user_id"])) {
        return null;
    }
    return get_user_by_id((int) $_SESSION["user_id"]);
}


// Sunaikina sesija ir slapukus (cookies)
// tiesiog padaro, kad cookies expiry time butu in the past
function logout_user(): void
{
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), "", time() - 1, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
}

// Jei neprisijunges  nukreipia i login.php (po sekmingo log in, grizta kur bande nueiti)
function require_login(): void
{
    if (!current_user()) {
        $next = urlencode($_SERVER["REQUEST_URI"]);
        redirect("login.php?next={$next}");
    }
}

// Grazina role ID
function get_role_id(string $name): int
{
    $query = db()->prepare("SELECT id FROM roles WHERE name = :name LIMIT 1");
    $query->execute(["name" => $name]);
    $roleId = $query->fetchColumn();
    return (int) $roleId;
}

// GDI: Šis kodas buvo sugeneruotas naudojant Cursor (Composer / Agent, 2026-03-14).
// Užklausa: Kaip priskirti vartotojui role (USER/ADMIN), jei turiu lenteles roles ir user_roles?
// Rezultatas dalinai koreguotas.


// Priskiria role vartotojui
function assign_role(int $userId, string $roleName): void
{
    $roleId = get_role_id($roleName);
    $query = db()->prepare("SELECT 1 FROM user_roles WHERE user_id = :userid AND role_id = :roleid");
    $query->execute(["userid" => $userId, "roleid" => $roleId]);

    if (!$query->fetchColumn()) {
        $insert = db()->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (:userid, :roleid)");
        $insert->execute(["userid" => $userId, "roleid" => $roleId]);
    }
}

// Tikrinti ar vartotojas admin
function is_admin(): bool
{
    $userId = $_SESSION["user_id"] ?? null;
    if (!$userId) {
        return false;
    }

    $query = db()->prepare(
        "SELECT 1 FROM user_roles user
         INNER JOIN roles role ON role.id = user.role_id
         WHERE user.user_id = :userid AND role.name = 'ADMIN'
         LIMIT 1"
    );

    $query->execute(["userid" => $userId]);
    return (bool) $query->fetchColumn();
}
