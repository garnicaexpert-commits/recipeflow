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
  <title>Visualizar Receta</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
  <header class="topbar no-print">
    <h2>Visualizar Receta</h2>
    <div class="topbar-actions">
      <button onclick="window.print()">Imprimir PDF</button>
      <a class="btn-link" href="historial.php">Regresar al historial</a>
    </div>
  </header>

  <main class="container">
    <?php if ($error): ?>
      <p class="msg"><?= htmlspecialchars($error) ?></p>
    <?php elseif ($recipe): ?>
      <section class="recipe-sheet-two-cols">
        <article class="recipe-col">
          <h3>Receta Médica</h3>
          <p><b>Paciente:</b> <?= htmlspecialchars($recipe['patient_name']) ?> | <b>Cédula:</b> <?= htmlspecialchars($recipe['cedula']) ?></p>
          <p><b>Edad:</b> <?= htmlspecialchars($recipe['age']) ?> | <b>Tel:</b> <?= htmlspecialchars($recipe['phone']) ?></p>
          <p><b>Dirección:</b> <?= htmlspecialchars($recipe['address']) ?></p>
          <p><b>Diagnóstico:</b> <?= htmlspecialchars($recipe['diagnosis']) ?> | <b>CIE-10:</b> <?= htmlspecialchars($recipe['cie10']) ?></p>
          <h4>Medicamentos</h4>
          <ul>
            <?php foreach ($items as $it): ?>
              <li><b><?= htmlspecialchars($it['medicine_name']) ?></b> x <?= (int)$it['quantity'] ?> - <?= htmlspecialchars($it['dose']) ?></li>
            <?php endforeach; ?>
          </ul>
        </article>

        <article class="recipe-col">
          <h3>Receta Médica</h3>
          <p><b>Paciente:</b> <?= htmlspecialchars($recipe['patient_name']) ?> | <b>Cédula:</b> <?= htmlspecialchars($recipe['cedula']) ?></p>
          <p><b>Edad:</b> <?= htmlspecialchars($recipe['age']) ?> | <b>Tel:</b> <?= htmlspecialchars($recipe['phone']) ?></p>
          <p><b>Dirección:</b> <?= htmlspecialchars($recipe['address']) ?></p>
          <p><b>Diagnóstico:</b> <?= htmlspecialchars($recipe['diagnosis']) ?> | <b>CIE-10:</b> <?= htmlspecialchars($recipe['cie10']) ?></p>
          <h4>Indicaciones</h4>
          <ul>
            <?php foreach ($items as $it): ?>
              <li><b><?= htmlspecialchars($it['medicine_name']) ?> x <?= (int)$it['quantity'] ?>:</b> <?= htmlspecialchars($it['instructions']) ?></li>
            <?php endforeach; ?>
          </ul>
        </article>
      </section>
    <?php endif; ?>
  </main>
</body>
</html>
