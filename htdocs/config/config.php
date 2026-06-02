<?php

// config failas, kuriame yra db duomenys ir ikelimo nustatymai (folder path, url, kiekis nuotrauku, dydis ir tipai)
return [
    "db" => [
        "host" => "127.0.0.1",
        "name" => "manomiestas",
        "user" => "root",
        "pass" => "",
        "charset" => "utf8mb4",
    ],
    "uploads" => [
        "base_dir" => __DIR__ . "/../public/uploads",
        "base_url" => "uploads",
        "max_files" => 5,
        "max_size" => 2 * 1024 * 1024,
        "allowed_types" => ["image/jpeg", "image/png"],
    ],
];
