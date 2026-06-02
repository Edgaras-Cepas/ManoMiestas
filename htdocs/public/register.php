<?php
// Naujo vartotojo registracija
// GDI: Sis kodas buvo sugeneruotas naudojant Cursor (Composer / Agent, 2026-03-14).
// Uzklausa: Sukurk registracijos forma su slaptazodzio patikrinimu ir automatisku prisijungimu po registracijos.
// Rezultatas dalinai koreguotas.
require_once __DIR__ . "/../lib/bootstrap.php";
$user = current_user();

$error = "";
// Gauna kur nukreipti po sekmingos registracijos
$next = $_GET["next"] ?? "index.php";

// Apdoroja POST - validuoja lauku, el. pasta, slaptazodzius ir sukuria vartotoja
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm = $_POST["password_confirm"] ?? "";

    if ($email === "" || $password === "" || $confirm === "") {
        $error = "Uzpildykite visus laukus.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Neteisingas el. pasto formatas.";
    } elseif ($password !== $confirm) {
        $error = "Slaptazodziai nesutampa.";
    } elseif (find_user_by_email($email)) {
        $error = "Toks el. pastas jau uzregistruotas.";
    } else {
        // Sukuria vartotoja, priskiria USER role ir automatiskai prisijungia
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $query = db()->prepare("INSERT INTO users (email, password_hash, status, created_at) VALUES (:email, :hash, 'ACTIVE', NOW())");
        $query->execute(["email" => $email, "hash" => $hash]);
        $userId = (int) db()->lastInsertId();
        assign_role($userId, "USER");
        login_user(["id" => $userId]);
        redirect($next);
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
    <title>Registracija · ManoMiestas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/app.css">
    <link rel="icon" type="image/svg+xml" href="../icons/miestas.svg">
</head>
<body class="is-auth" data-auth="<?php echo $user ? "1" : "0"; ?>">

<?php include 'header.php';?>


<!-- Registracijos forma su el. pastu ir slaptazodziu patikrinimu -->
<main class="auth-page">
    <h1 class="auth-title text-center">Registracija</h1>
    <div class="auth-card">
        <h2 class="auth-heading">Sukurti paskyrą</h2>
        <!-- ?next= issaugo kur nukreipti po registracijos -->
        <form method="post" action="register.php<?php echo $next ? "?next=" . urlencode($next) : ""; ?>">
            <?php if ($error): ?>
                <div class="alert alert-danger py-2"><?php echo e($error); ?></div>
            <?php endif; ?>
            <div class="mb-3">
                <input type="email" name="email" class="form-control" placeholder="El. paštas" value="<?php echo e($_POST['email'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <input type="password" name="password" class="form-control" placeholder="Slaptažodis" required>
            </div>
            <div class="mb-3">
                <input type="password" name="password_confirm" class="form-control" placeholder="Pakartokite slaptažodį" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 auth-submit">Registruotis</button>
        </form>
        <div class="auth-switch text-center">
            Jau turite paskyrą?
            <a href="login.php" class="auth-link">Prisijunkite!</a>
        </div>
    </div>
</main>

<?php $navActive = 'account'; include 'navbar.php'; ?>


</body>
</html>
