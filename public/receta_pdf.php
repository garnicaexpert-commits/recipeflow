<?php
session_start();
if (!isset($_SESSION['user'])) {
  header('Location: index.php');
  exit;
}

require __DIR__ . '/../app/Database.php';
$config = require __DIR__ . '/../config/config.php';

$id = (int)($_GET['id'] ?? 0);
$recipe = null;
$items = [];
$error = '';
$autoPrint = (($_GET['autoprint'] ?? '0') === '1');
$doctorDisplayName = (string)($_SESSION['user']['display_name'] ?? $_SESSION['user']['full_name'] ?? $_SESSION['user']['username'] ?? 'Profesional de salud');
$specialty = (string)($_SESSION['user']['specialty'] ?? $_SESSION['user']['full_name'] ?? $_SESSION['user']['username'] ?? 'Profesional de salud');
try {
  $pdo = Database::connect($config['db']);
  Database::ensureUsersSchema($pdo);
  Database::ensurePrescriptionsOwnershipSchema($pdo);

  $userId = (int)($_SESSION['user']['id'] ?? 0);
  if ($userId <= 0) {
    throw new RuntimeException('Sesión inválida');
  }

  $stmt = $pdo->prepare('SELECT * FROM prescriptions WHERE id = ? AND user_id = ?');
  $stmt->execute([$id, $userId]);
  $recipe = $stmt->fetch();

  if ($recipe) {
    $stItems = $pdo->prepare('SELECT medicine_name, quantity, dose, instructions FROM prescription_items WHERE prescription_id = ?');
    $stItems->execute([$id]);
    $items = $stItems->fetchAll();

    $vademecumRows = $pdo->query('SELECT nombre_comercial, presentacion FROM vademecum')->fetchAll();
    foreach ($vademecumRows as $row) {
      $key = mb_strtolower(trim((string)($row['nombre_comercial'] ?? '')));
      if ($key === '') {
        continue;
      }
      $presentationsByName[$key] = trim((string)($row['presentacion'] ?? ''));
    }
  } else {
    $error = 'Receta no encontrada.';
  }
} catch (Throwable $e) {
  $error = 'No se pudo cargar la receta.';
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>PDF Receta #<?= (int)$id ?></title>
  <link rel="stylesheet" href="assets/styles.css">
  <style>
    .recipe-doc-header{display:flex;align-items:center;gap:10px;border-bottom:1px solid #cfd8dc;padding-bottom:8px;margin-bottom:8px}
    .recipe-doc-logo{width:52px;height:52px;object-fit:contain;flex:0 0 52px}
    .recipe-doc-title h4{margin:.1rem 0 .2rem;font-size:1rem;line-height:1.2}
    .recipe-doc-title p{margin:.1rem 0;color:#455a64;font-size:.9rem;line-height:1.25}
  </style>
</head>
<body>
  <header class="topbar no-print">
    <div style="display: flex;" >
    <img src="../imagen/logobig.png" style="width: 80px; height: 80px;">
    <h2>Vista Previa  -  Recetas</h2>
    </div>
    <div class="topbar-actions">
      <a class="btn-link" onclick="window.print()">Imprimir PDF</a>
      <a class="btn-link" href="historial.php">Regresar al historial</a>
    </div>
  </header>

  <main class="container">
    <?php if ($error): ?>
      <p class="msg"><?= htmlspecialchars($error) ?></p>
    <?php elseif ($recipe): ?>
      <section class="recipe-sheet-two-cols">
        <!-- parte izquierda del rececipe -->
        <article class="recipe-col">
          <div class="recipe-doc-header">
            <img class="recipe-doc-logo" src="../imagen/logobig.png" alt="Logo del sistema">
            <div class="recipe-doc-title">
              <h4>Sistema de Control de Recetas Médicas</h4>
              <p><b>Profesional:</b> <?= htmlspecialchars($doctorDisplayName) ?></p>
              <p><b>Especialidad:</b> <?=htmlspecialchars($specialty)?></p> 
            </div>
          </div>
          <h3>Receta Médica Nro:  <?=htmlspecialchars($id)?></h3>
          <p><b>Paciente:</b> <?= htmlspecialchars($recipe['patient_name']) ?> | <b>Cédula:</b> <?= htmlspecialchars($recipe['cedula']) ?>
          <b>Edad:</b> <?= htmlspecialchars($recipe['age']) ?>  
          <br><b>Tel:</b> <?= htmlspecialchars($recipe['phone']) ?> | <b>Dirección:</b> <?= htmlspecialchars($recipe['address']) ?><br>
          <b>Diagnóstico:</b> <?= htmlspecialchars($recipe['diagnosis']) ?> | <b>CIE-10:</b> <?= htmlspecialchars($recipe['cie10']) ?></p>
          <h4>Medicamentos</h4>
          <ul>
            <?php foreach ($items as $it): ?>
              <?php
                $presentation = $presentationsByName[mb_strtolower(trim((string)($it['medicine_name'] ?? '')))] ?? '';
              ?>
              <li>
                <b><?= htmlspecialchars($it['medicine_name']) ?></b>
                x <?= (int)$it['quantity'] ?> - 
                <?php if ($presentation !== ''): ?>
                  <small><b>Presentación:</b> <?= htmlspecialchars($presentation) ?></small>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </article>
        <!-- parte derecha del rececipe -->
        <article class="recipe-col">
          <div class="recipe-doc-header">
            <img class="recipe-doc-logo" src="../imagen/logobig.png" alt="Logo del sistema">
            <div class="recipe-doc-title">
              <h4>Sistema de Control de Recetas Médicas</h4>
              <p><b>Profesional:</b> <?= htmlspecialchars($doctorDisplayName) ?></p>
              <p><b>Especialidad:</b> <?=htmlspecialchars($specialty)?></p>
            </div>
          </div>
          <h3>Receta Médica Nro:  <?=htmlspecialchars($id)?></h3>
          <p><b>Paciente:</b> <?= htmlspecialchars($recipe['patient_name']) ?> | <b>Cédula:</b> <?= htmlspecialchars($recipe['cedula']) ?>
          <b>Edad:</b> <?= htmlspecialchars($recipe['age']) ?>  <br><b>Tel:</b> <?= htmlspecialchars($recipe['phone']) ?> | <b>Dirección:</b> <?= htmlspecialchars($recipe['address']) ?><br>
          <b>Diagnóstico:</b> <?= htmlspecialchars($recipe['diagnosis']) ?> | <b>CIE-10:</b> <?= htmlspecialchars($recipe['cie10']) ?></p>
          <h4>Indicaciones</h4>
          <ul>
            <?php foreach ($items as $it): ?>
              <li><b><?= htmlspecialchars($it['medicine_name']) ?></b>: <?= htmlspecialchars($it['dose']) ?> <b> Obs:</b> <?= htmlspecialchars($it['instructions']) ?></li>
            <?php endforeach; ?>
          </ul>
         <div class="bottom-section">
        <div class="signature-block">
            <div class="recipe-signature-line"></div>
            <p class="recipe-signature-name">
                Firma y Sello: <?= htmlspecialchars($doctorDisplayName) ?>
            </p>
        </div>

        <footer class="recipe-footer">
            <p>
                <b>Dirección:</b> <?= htmlspecialchars($recipe['address'] ?? 'Dirección del Consultorio') ?> | 
                <b>Teléfono:</b> <?= htmlspecialchars($recipe['phone'] ?? 'S/N') ?>
            </p>
            <p style="font-size: 0.7rem; margin-top: 4px;">
                Generado por Sistema de Control de Recetas Médicas - <?= date('d/m/Y H:i') ?>
            </p>
        </footer>
    </div>
</article>
      </section>
    <?php endif; ?>
  </main>

  <?php if ($autoPrint && !$error && $recipe): ?>
    <script>
      window.addEventListener('load', () => window.print());
    </script>
  <?php endif; ?>
</body>
</html>