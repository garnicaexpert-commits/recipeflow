<?php
session_start();
if (!isset($_SESSION['user'])) {
  header('Location: index.php');
  exit;
}
if (($_SESSION['user']['access_level'] ?? 'usuario') !== 'superusuario') {
  http_response_code(403);
  echo '<h2>Acceso denegado</h2><p>Solo superusuarios pueden acceder al módulo de usuarios.</p><a href="dashboard.php">Volver</a>';
  exit;
}

require __DIR__ . '/../app/Database.php';
$config = require __DIR__ . '/../config/config.php';

$msg = '';
$error = '';
$editId = (int)($_GET['edit'] ?? 0);
$editRow = null;
$list = [];

try {
  $pdo = Database::connect($config['db']);
  Database::ensureUsersSchema($pdo);

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
      $password = trim($_POST['password'] ?? '');
      if ($password === '') $password = '123456';
    $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, full_name, display_name, specialty, contact_phone, direction, correo, access_level) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
      $stmt->execute([
        trim($_POST['username'] ?? ''),
        password_hash($password, PASSWORD_BCRYPT),
        trim($_POST['full_name'] ?? ''),
        trim($_POST['display_name'] ?? ''),
        trim($_POST['specialty'] ?? ''),
        trim($_POST['contact_phone'] ?? ''),
        trim($_POST['direction'] ?? ''),
        trim($_POST['correo'] ?? ''),
        in_array($_POST['access_level'] ?? 'usuario', ['usuario', 'superusuario'], true) ? $_POST['access_level'] : 'usuario',
      ]);
      $msg = 'Usuario creado correctamente.';
    }

    if ($action === 'update') {
      $id = (int)($_POST['id'] ?? 0);
      $stmt = $pdo->prepare('UPDATE users SET username=?, full_name=?, display_name=?, specialty=?, contact_phone=?, direction=?, correo=?, access_level=? WHERE id=?');
      $stmt->execute([
        trim($_POST['username'] ?? ''),
        trim($_POST['full_name'] ?? ''),
        trim($_POST['display_name'] ?? ''),
        trim($_POST['specialty'] ?? ''),
        trim($_POST['contact_phone'] ?? ''),
        trim($_POST['direction'] ?? ''),
        trim($_POST['correo'] ?? ''),
        in_array($_POST['access_level'] ?? 'usuario', ['usuario', 'superusuario'], true) ? $_POST['access_level'] : 'usuario',
        $id,
      ]);

      $newPass = trim($_POST['password'] ?? '');
      if ($newPass !== '') {
        $stPass = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stPass->execute([password_hash($newPass, PASSWORD_BCRYPT), $id]);
      }

      $msg = 'Usuario actualizado correctamente.';
      $editId = 0;
    }

    if ($action === 'delete') {
      $id = (int)($_POST['id'] ?? 0);
      if ($id === (int)($_SESSION['user']['id'] ?? 0)) {
        $error = 'No puedes eliminar tu propio usuario en sesión.';
      } else {
        $st = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $st->execute([$id]);
        $msg = 'Usuario eliminado correctamente.';
      }
      if ($editId === $id) $editId = 0;
    }
  }

  if ($editId > 0) {
    $stEdit = $pdo->prepare('SELECT id, username, full_name, display_name, specialty, contact_phone, access_level FROM users WHERE id=?');
    $stEdit->execute([$editId]);
    $editRow = $stEdit->fetch();
  }

  $list = $pdo->query('SELECT id, username, full_name, display_name, specialty, contact_phone, access_level, created_at FROM users ORDER BY id DESC')->fetchAll();
} catch (Throwable $e) {
  $error = 'No se pudo operar el módulo de usuarios.';
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Módulo de Usuarios</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
  <header class="topbar">
    <h2>Módulo de Usuarios</h2>
    <a class="btn-link" href="dashboard.php">Regresar al dashboard</a>
  </header>

  <main class="container">
    <section class="card">
      <h3><?= $editRow ? 'Editar usuario' : 'Nuevo usuario' ?></h3>
      <?php if ($msg): ?><p class="msg ok"><?= htmlspecialchars($msg) ?></p><?php endif; ?>
      <?php if ($error): ?><p class="msg"><?= htmlspecialchars($error) ?></p><?php endif; ?>

      <form method="post" class="grid form-grid">
        <input name="username" placeholder="Nombre de usuario" required value="<?= htmlspecialchars($editRow['username'] ?? '') ?>">
        <input name="full_name" placeholder="Nombres y apellidos" required value="<?= htmlspecialchars($editRow['full_name'] ?? '') ?>">
        <input name="display_name" placeholder="Nombre a mostrar" required value="<?= htmlspecialchars($editRow['display_name'] ?? '') ?>">
        <input name="specialty" placeholder="Especialidad" required value="<?= htmlspecialchars($editRow['specialty'] ?? '') ?>">
        <input name="contact_phone" placeholder="Teléfono de contacto" required value="<?= htmlspecialchars($editRow['contact_phone'] ?? '') ?>">
        <input name="direction" placeholder="Direccion de Domicilio" required value="<?= htmlspecialchars($editRow['direction'] ?? '') ?>">
        <input name="correo" placeholder="Correo de Contacto" required value="<?= htmlspecialchars($editRow['correo'] ?? '') ?>">
        <select name="access_level" required>
          <?php $level = $editRow['access_level'] ?? 'usuario'; ?>
          <option value="usuario" <?= $level === 'usuario' ? 'selected' : '' ?>>Usuario</option>
          <option value="superusuario" <?= $level === 'superusuario' ? 'selected' : '' ?>>Superusuario</option>
        </select>

        <input name="password" type="password" placeholder="Contraseña (obligatoria al crear, opcional al editar)">

        <?php if ($editRow): ?>
          <input type="hidden" name="id" value="<?= (int)$editRow['id'] ?>">
          <input type="hidden" name="action" value="update">
          <div class="actions-row">
            <button type="submit">Actualizar</button>
            <a class="btn-link btn-secondary" href="usuarios.php">Cancelar</a>
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
      <h3>Listado de usuarios</h3>
      <?php if (!$list): ?><p>Sin usuarios registrados.</p><?php endif; ?>

      <div class="history-list">
        <?php foreach ($list as $u): ?>
          <article class="card history-item history-horizontal">
            <div class="history-content">
              <h4><?= htmlspecialchars($u['display_name']) ?> <small>(<?= htmlspecialchars($u['username']) ?>)</small></h4>
              <p><b>Nombres y apellidos:</b> <?= htmlspecialchars($u['full_name']) ?></p>
              <p><b>Especialidad:</b> <?= htmlspecialchars($u['specialty']) ?> | <b>Teléfono:</b> <?= htmlspecialchars($u['contact_phone']) ?></p>
              <p><b>Nivel:</b> <?= htmlspecialchars($u['access_level']) ?></p>
            </div>
            <div class="actions-row">
              <a class="btn-link" href="usuarios.php?edit=<?= (int)$u['id'] ?>">Editar</a>
              <form method="post" onsubmit="return confirm('¿Eliminar este usuario?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
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
