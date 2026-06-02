<?php
/**
 * ManoMiestas — nuotraukų įkėlimas ir validacija (lib/uploads.php).
 */
declare(strict_types=1);

// GDI: Šis kodas buvo sugeneruotas naudojant Cursor (Composer / Agent, 2026-05-12).
// Užklausa: Pries issaugant pranesima noriu, kad butu patikrinta ar nuotraukos ne per dideles ir ar ju ne per daug.
// Rezultatas dalinai koreguotas.


// Patikrina ikeltus failus pries issaugojima (kiekis, dydis, upload klaidos)
// Grazina klaidos zinute arba null jei viskas gerai
function validate_report_photos(?array $files): ?string
{
    if ($files === null || empty($files["name"])) {
        return null;
    }

    $config = $GLOBALS["app_config"]["uploads"];
    $maxSize = (int) $config["max_size"];
    $maxFiles = (int) $config["max_files"];
    $mbLabel = max(0.1, round($maxSize / (1024 * 1024), 1));

    $names = (array) $files["name"];
    $errors = (array) $files["error"];
    $sizes = (array) $files["size"];

    $nonEmpty = 0;
    $n = count($names);

    for ($i = 0; $i < $n; $i++) {
        $name = $names[$i] ?? "";
        if ($name === "" || $name === null) {
            continue;
        }

        $nonEmpty++;
        $err = (int) ($errors[$i] ?? UPLOAD_ERR_NO_FILE);

        if ($err === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
            return "Nuotrauka per didelė (serverio arba programos limitas). Kiekvienas failas turi būti ne didesnis nei {$mbLabel} MB.";
        }

        if ($err !== UPLOAD_ERR_OK) {
            return "Nuotraukų įkelti nepavyko. Bandykite dar kartą.";
        }
        
        if (($sizes[$i] ?? 0) > $maxSize) {
            return "Viena ar kelios nuotraukos viršija {$mbLabel} MB dydžio limitą.";
        }
    }

    if ($nonEmpty > $maxFiles) {
        return "Galima įkelti ne daugiau kaip {$maxFiles} nuotraukų.";
    }

    return null;
}

// Issaugo leistinas nuotraukas i uploads/YYYY/MM, grazina path
// Tikrina MIME, dydi ir kieki
function handle_uploads(array $files): array
{
    $config = $GLOBALS["app_config"]["uploads"];
    $maxFiles = (int) $config["max_files"];
    $maxSize = (int) $config["max_size"];
    $allowed = $config["allowed_types"];
    $paths = [];

    if (empty($files["name"])) {
        return $paths;
    }

    $names = (array) $files["name"];
    $tmpNames = (array) $files["tmp_name"];
    $sizes = (array) $files["size"];
    $errors = (array) $files["error"];

    $count = min(count($names), $maxFiles);


    // GDI: Šis kodas buvo sugeneruotas naudojant Cursor (Composer / Agent, 2026-03-21).
    // Užklausa: Kaip patikrinti ar ikeltas failas tikrai nuotrauka, ne tik pagal jpg/png pletini?
    // Rezultatas dalinai koreguotas.
    $finfo = new finfo(FILEINFO_MIME_TYPE);

    for ($i = 0; $i < $count; $i++) {
        if ($errors[$i] !== UPLOAD_ERR_OK) {
            continue;
        }
        if ($sizes[$i] > $maxSize) {
            continue;
        }
        $mime = $finfo->file($tmpNames[$i]);
        if (!in_array($mime, $allowed, true)) {
            continue;
        }

        $ext = $mime === "image/png" ? "png" : "jpg";
        $subDir = date("Y") . "/" . date("m");
        $targetDir = rtrim($config["base_dir"], "/") . "/" . $subDir;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        $filename = uniqid("issue_", true) . "." . $ext;
        $destPath = $targetDir . "/" . $filename;
        // GDI: Šis kodas buvo sugeneruotas naudojant Cursor (Composer / Agent, 2026-04-18).
        // Užklausa: PHPUnit metu move_uploaded_file neveikia, tai kaip issaugoti testini faila?
        // Rezultatas dalinai koreguotas.
        $saved = is_uploaded_file($tmpNames[$i])
            ? move_uploaded_file($tmpNames[$i], $destPath)
            : @copy($tmpNames[$i], $destPath);
        if ($saved) {
            $paths[] = rtrim($config["base_url"], "/") . "/" . $subDir . "/" . $filename;
        }
    }

    return $paths;
}
