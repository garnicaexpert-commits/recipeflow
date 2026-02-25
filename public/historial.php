<?php
session_start();
if (!isset($_SESSION['user'])) {
  header('Location: index.php');
  exit;
}

require __DIR__ . '/../app/Database.php';
$config = require __DIR__ . '/../config/config.php';

$q = trim($_GET['q'] ?? '');
$list = [];
$error = '';

try {
  $pdo = Database::connect($config['db']);
  Database::ensureUsersSchema($pdo);
  Database::ensurePrescriptionsOwnershipSchema($pdo);

  $userId = (int)($_SESSION['user']['id'] ?? 0);
  if ($userId <= 0) {
    throw new RuntimeException('Sesión inválida');
  }
  if ($q === '') {
    $stmt = $pdo->prepare('SELECT id, patient_name, cedula, diagnosis, cie10, created_at FROM prescriptions WHERE user_id = ? ORDER BY id DESC');
    $stmt->execute([$userId]);
  } else {
    $stmt = $pdo->prepare('SELECT id, patient_name, cedula, diagnosis, cie10, created_at FROM prescriptions WHERE user_id = ? AND patient_name LIKE ? ORDER BY id DESC');
    $stmt->execute([$userId, "%{$q}%"]);
  }
  $list = $stmt->fetchAll();
} catch (Throwable $e) {
  $error = 'No se pudo cargar el historial desde MySQL.';
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Historial de Recetas</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
</head>
<body>
  <header class="topbar">
    <div style="display: flex;" >
      <img src="../imagen/logobig.png" style="width: 80px; height: 80px;">
      <h2>Módulo de Recetas</h2>
    </div>
    <a class="btn-link" href="dashboard.php">Regresar al Dashboard</a>
  </header>

  <main class="container">
    <section class="card">
      <form method="get" class="search-row">
        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por nombre de paciente">
        <button type="submit">Buscar</button>
      </form>

      <?php if ($error): ?>
        <p class="msg"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>

      <?php if (!$error && !$list): ?>
        <p>No hay recetas almacenadas para mostrar.</p>
      <?php endif; ?>

      <div class="history-list">
        <?php foreach ($list as $r): ?>
          <article class="card history-item history-horizontal">
            <div class="history-content">
              <p>
              <b><?= htmlspecialchars($r['patient_name']) ?></b> |  <b>Cédula:</b> <?= htmlspecialchars($r['cedula']) ?> 
              <b>Diagnóstico:</b> <?= htmlspecialchars($r['diagnosis']) ?>  | <b>CIE-10:</b> <?= htmlspecialchars($r['cie10']) ?>
              <small> |  De Fecha: <?= htmlspecialchars($r['created_at']) ?></small>
            </p> 
            </div>
            <div class="history-action">
              <a class="btn-link" href="historial_detalle.php?id=<?= (int)$r['id'] ?>" target="_blank" rel="noopener">Visualizar</a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  </main>
</body>
</html>
 