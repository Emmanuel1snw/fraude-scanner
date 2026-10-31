<?php
// En-tête commun : <head>, Bootstrap et barre de navigation.
$titre = $titre ?? "Fraude Scanner";
$actif = $actif ?? "";
$liens = [
    "index.php" => ["accueil", "Accueil"],
    "scanner.php" => ["scanner", "Scanner"],
    "comment-ca-marche.php" => ["usage", "Comment ça marche"],
    "documentation.php" => ["doc", "Documentation"],
    "a-propos.php" => ["apropos", "À propos"],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($titre) ?> – Fraude Scanner</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="icon" href="assets/img/logo-icone.png">
<link href="assets/site.css" rel="stylesheet">
<?= $extraHead ?? "" ?>
</head>
<body>
<nav class="navbar navbar-expand-lg site-nav">
  <div class="container">
    <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="index.php"><img class="marque-logo" src="assets/img/logo-icone.png" alt="Track Studi"><span>Fraude Scanner</span></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu" aria-controls="menu" aria-expanded="false" aria-label="Ouvrir le menu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="menu">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <?php foreach ($liens as $url => $infos): ?>
          <li class="nav-item">
            <a class="nav-link<?= $actif === $infos[0] ? ' active' : '' ?>" href="<?= $url ?>"><?= $infos[1] ?></a>
          </li>
        <?php endforeach; ?>
      </ul>
      <a class="btn btn-primary" href="scanner.php">Analyser un SMS</a>
    </div>
  </div>
</nav>