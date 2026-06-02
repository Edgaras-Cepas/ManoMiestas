<?php
// Sekmes puslapis po pranesimo pateikimo
// tik parodo zinute ir mygtuka atgal
require_once __DIR__ . "/../lib/bootstrap.php";
header("Content-Type: text/html; charset=UTF-8");
$user = current_user();
// GDI: HTML struktura sugeneruota is Figma eksporto, modifikuota naudojant Cursor.
// Uzklausa: Pritaikyti Figma HTML i PHP sablona.
// Rezultatas dalinai koreguotas, modifikuota ir perdaryta.
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pranešimas išsiųstas | ManoMiestas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/app.css">
    <link rel="icon" type="image/svg+xml" href="../icons/miestas.svg">
</head>
<body class="is-report" data-auth="<?php echo $user ? "1" : "0"; ?>">
    
    <?php include 'header.php';?>

    <main class="success-page container-fluid">
        <div class="success-card">
            <h1 class="success-title">Pranešimas išsiųstas!</h1>
            <p>Jūsų pranešimas sėkmingai užregistruotas. Ačiū už pagalbą gerinant miestą.</p>
            <a class="btn btn-primary w-100" href="index.php">Grįžti į pradžią</a>
        </div>
    </main>

    <?php $navActive = 'issues'; include 'navbar.php'; ?>

    <script src="assets/app.js"></script>
</body>
</html>
