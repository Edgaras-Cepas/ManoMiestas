<?php
// Prisijungimo puslapis
// GDI: Sis kodas buvo sugeneruotas naudojant Cursor (Composer / Agent, 2026-03-14).
// Uzklauса: Reikia prisijungimo formos su el. pastu ir slaptazodziu.
// Rezultatas dalinai koreguotas.
require_once __DIR__ . "/../lib/bootstrap.php";
$user = current_user();

$error = "";
// Gauna kur nukreipti po sekmingo prisijungimo
$next = $_GET["next"] ?? "index.php";

// Apdoroja POST tikrina el. pasta, slaptazodi ir paskyros statusa
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        $error = "Uzpildykite visus laukus.";
    } else {
        $user = find_user_by_email($email);
        // Tas pats klaidos pranesimas blogam el. pastui ir slaptazodziu (saugumo priezastys)
        if (!$user || !password_verify($password, $user["password_hash"])) {
            $error = "Neteisingas el. pastas arba slaptazodis.";
        } elseif (($user["status"] ?? "ACTIVE") !== "ACTIVE") {
            $error = "Jusu paskyra uzblokuota.";
        } else {
            login_user($user);
            redirect($next);
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
    <title>Prisijungimas · ManoMiestas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/app.css">
    <link rel="icon" type="image/svg+xml" href="../icons/miestas.svg">
</head>
<body class="is-auth" data-auth="<?php echo $user ? "1" : "0"; ?>">

<?php include 'header.php';?>

    <!-- Prisijungimo forma su el. pastu ir slaptazodziu -->
    <main class="auth-page">
        <h1 class="auth-title text-center">Prisijungimas</h1>
        <div class="auth-card">
            <h2 class="auth-heading">Prisijunkite prie paskyros</h2>
            <!-- ?next= issaugo kur nukreipti po prisijungimo -->
            <form method="post" action="login.php<?php echo $next ? "?next=" . urlencode($next) : ""; ?>">
                <?php if ($error): ?>
                    <div class="alert alert-danger py-2"><?php echo e($error); ?></div>
                <?php endif; ?>
                <div class="mb-3">
                    <input type="email" name="email" class="form-control" placeholder="El. paštas" value="<?php echo e($_POST['email'] ?? ''); ?>" required>
                </div>
                <div class="mb-3">
                    <input type="password" name="password" class="form-control" placeholder="Slaptažodis" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 auth-submit">Prisijungti</button>
            </form>
            <div class="auth-switch text-center">
                Neturite paskyros?
                <a href="register.php" class="auth-link">Registruokitės!</a>
            </div>
        </div>
    </main>


    <?php $navActive = 'account'; include 'navbar.php'; ?>

</nav>
</body>
</html>
