<?php
session_start();
if (!isset($_SESSION['user'])) {
  header('Location: index.php');
  exit;
}

require __DIR__ . '/../app/Database.php';
$config = require __DIR__ . '/../config/config.php';

$editId = (int)($_GET['edit'] ?? 0);
$msg = '';
$error = '';
$editRow = null;
$list = [];

try {
  $pdo = Database::connect($config['db']);

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
      $stmt = $pdo->prepare('INSERT INTO vademecum (nombre_comercial, componente_quimico, dosis, presentacion) VALUES (?, ?, ?, ?)');
      $stmt->execute([
        trim($_POST['nombre_comercial'] ?? ''),
        trim($_POST['componente_quimico'] ?? ''),
        trim($_POST['dosis'] ?? ''),
        trim($_POST['presentacion'] ?? ''),
      ]);
      $msg = 'Medicamento agregado correctamente.';
    }

    if ($action === 'update') {
      $id = (int)($_POST['id'] ?? 0);
      $stmt = $pdo->prepare('UPDATE vademecum SET nombre_comercial=?, componente_quimico=?, dosis=?, presentacion=? WHERE id=?');
      $stmt->execute([
        trim($_POST['nombre_comercial'] ?? ''),
        trim($_POST['componente_quimico'] ?? ''),
        trim($_POST['dosis'] ?? ''),
        trim($_POST['presentacion'] ?? ''),
        $id,
      ]);
      $msg = 'Medicamento actualizado correctamente.';
      $editId = 0;
    }

    if ($action === 'delete') {
      $id = (int)($_POST['id'] ?? 0);
      $stmt = $pdo->prepare('DELETE FROM vademecum WHERE id = ?');
      $stmt->execute([$id]);
      $msg = 'Medicamento eliminado correctamente.';
      if ($editId === $id) {
        $editId = 0;
      }
    }
  }

  if ($editId > 0) {
    $stEdit = $pdo->prepare('SELECT * FROM vademecum WHERE id = ?');
    $stEdit->execute([$editId]);
    $editRow = $stEdit->fetch();
  }

  $list = $pdo->query('SELECT * FROM vademecum ORDER BY id DESC')->fetchAll();
} catch (Throwable $e) {
  $error = 'No se pudo operar el vademécum en MySQL.';
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Vademécum</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
  <header class="topbar">
    <h2>Módulo Vademécum</h2>
    <a class="btn-link" href="dashboard.php">Regresar al dashboard</a>
  </header>

  <main class="container">
    <section class="card">
      <h3><?= $editRow ? 'Editar medicamento' : 'Nuevo medicamento' ?></h3>
      <?php if ($msg): ?><p class="msg ok"><?= htmlspecialchars($msg) ?></p><?php endif; ?>
      <?php if ($error): ?><p class="msg"><?= htmlspecialchars($error) ?></p><?php endif; ?>

      <form method="post" class="grid form-grid">
        <input name="nombre_comercial" placeholder="Nombre comercial" required value="<?= htmlspecialchars($editRow['nombre_comercial'] ?? '') ?>">
        <input name="componente_quimico" placeholder="Componente químico" required value="<?= htmlspecialchars($editRow['componente_quimico'] ?? '') ?>">
        <input name="dosis" placeholder="Dosis" required value="<?= htmlspecialchars($editRow['dosis'] ?? '') ?>">
        <input name="presentacion" placeholder="Presentación" required value="<?= htmlspecialchars($editRow['presentacion'] ?? '') ?>">

        <?php if ($editRow): ?>
          <input type="hidden" name="id" value="<?= (int)$editRow['id'] ?>">
          <input type="hidden" name="action" value="update">
          <div class="actions-row">
            <button type="submit">Actualizar</button>
            <a class="btn-link btn-secondary" href="vademecum.php">Cancelar</a>
          </div>
        <?php else: ?>
          <input type="hidden" name="action" value="create">
          <div class="actions-row">
            <button type="submit">Guardar</button>
          </div>
        <?php endif; ?>
      </form>
    </section>

    <section class="card" style="margin-top:1rem;">
      <h3>Listado de medicamentos</h3>
      <?php if (!$list): ?>
        <p>Sin registros en vademécum.</p>
      <?php endif; ?>

      <div class="history-list">
        <?php foreach ($list as $row): ?>
          <article class="card history-item history-horizontal">
            <div class="history-content">
              <h4><?= htmlspecialchars($row['nombre_comercial']) ?></h4>
              <p><b>Componente:</b> <?= htmlspecialchars($row['componente_quimico']) ?></p>
              <p><b>Dosis:</b> <?= htmlspecialchars($row['dosis']) ?> | <b>Presentación:</b> <?= htmlspecialchars($row['presentacion']) ?></p>
            </div>
            <div class="actions-row">
              <a class="btn-link" href="vademecum.php?edit=<?= (int)$row['id'] ?>">Editar</a>
              <form method="post" onsubmit="return confirm('¿Eliminar este medicamento?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                <button type="submit" class="btn-danger">Eliminar</button>
              </form>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  </main>
</body>
</html>
