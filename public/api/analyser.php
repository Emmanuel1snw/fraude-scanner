<?php
// public/api/analyser.php
// Point d'entrée du chat (POST multipart) :
//   message : texte du SMS ou commande commençant par "/"
//   image   : capture JPG/PNG/WEBP (facultatif, 4 Mo max)
// Réponse JSON : {"type":"verdict",...} ou {"type":"texte","mode":...,"texte":...}

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../src/GeminiService.php';
require_once __DIR__ . '/../../src/Scanner.php';
require_once __DIR__ . '/../../src/Apprendre.php';

$racine  = __DIR__ . '/../..';
$message = trim((string) ($_POST['message'] ?? ''));

// ---------- Validation de l'image (jamais enregistrée sur le disque) ----------
$image = null;
if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    if ($_FILES['image']['size'] > 4 * 1024 * 1024) {
        repondre(['type' => 'texte', 'mode' => 'scanner', 'texte' => "L'image dépasse 4 Mo. Envoie une capture plus légère."]);
    }
    $mime     = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['image']['tmp_name']);
    $autorise = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mime, $autorise, true)) {
        repondre(['type' => 'texte', 'mode' => 'scanner', 'texte' => "Format d'image non pris en charge. Envoie une capture JPG, PNG ou WEBP."]);
    }
    $contenu = file_get_contents($_FILES['image']['tmp_name']);
    if ($contenu !== false) {
        $image = ['mime' => $mime, 'data' => $contenu];
    }
}

if ($message === '' && $image === null) {
    repondre(['type' => 'texte', 'mode' => 'scanner', 'texte' => "Colle un SMS ou joins une capture d'écran pour lancer l'analyse."]);
}

// ---------- Dispatch : commande "/" -> apprentissage, sinon scanner ----------
try {
    $gemini = new GeminiService($racine . '/.env');

    if (str_starts_with($message, '/')) {
        $apprendre = new Apprendre($gemini);
        repondre($apprendre->executer($message, $image));
    } else {
        $scanner = new Scanner($gemini, $racine);
        repondre($scanner->analyser($message, $image));
    }
} catch (Throwable $e) {
    error_log('analyser.php : ' . $e->getMessage());
    repondre(['type' => 'texte', 'mode' => 'scanner', 'texte' => "Une erreur est survenue. Réessaie dans un instant."]);
}

function repondre(array $donnees): void
{
    echo json_encode($donnees, JSON_UNESCAPED_UNICODE);
    exit;
}
