<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$cheminEnv = __DIR__ . '/../../.env';
$env = [];
if (is_readable($cheminEnv)) {
    foreach (file($cheminEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $ligne) {
        if ($ligne[0] === '#' || strpos($ligne, '=') === false) {
            continue;
        }
        [$cle, $valeur] = explode('=', $ligne, 2);
        $env[trim($cle)] = trim($valeur, " \t\"'");
    }
}
foreach (['SUPABASE_URL', 'SUPABASE_PUBLISHABLE_KEY'] as $cle) {
    $valeur = getenv($cle);
    if ($valeur !== false) {
        $env[$cle] = $valeur;
    }
}

$url = rtrim($env['SUPABASE_URL'] ?? '', '/');
$cle = $env['SUPABASE_PUBLISHABLE_KEY'] ?? '';
if (!filter_var($url, FILTER_VALIDATE_URL) || !str_starts_with($url, 'https://') || $cle === '') {
    http_response_code(503);
    echo json_encode(['error' => 'Configuration Supabase manquante dans .env.']);
    exit;
}

echo json_encode([
    'url' => $url,
    'publishableKey' => $cle,
], JSON_UNESCAPED_SLASHES);
