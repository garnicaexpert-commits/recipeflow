<?php
session_start();
if (!isset($_SESSION['user'])) {
  header('Location: index.php');
  exit;
}

require __DIR__ . '/../app/Database.php';
$config = require __DIR__ . '/../config/config.php';
$modules = [];
$error = '';
$isSuper = (($_SESSION['user']['access_level'] ?? 'usuario') === 'superusuario');

try {
  $pdo = Database::connect($config['db']);
  $modules = $pdo->query('SELECT name, description, ruta FROM modules ORDER BY id')->fetchAll();
} catch (Throwable $e) {
  $error = 'No se pudo cargar módulos desde MySQL.';
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Dashboard MediSys</title>
  <title>RecipeFlow Dashboard</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
  <header class="topbar">
    <h2>Dashboard</h2>
    <div>
      <h2>Dashboard</h2>
    </div>
    <div class="topbar-actions">
      <span><?= htmlspecialchars($_SESSION['user']['display_name'] ?? $_SESSION['user']['full_name']) ?></span>
      <a class="btn-link" href="logout.php">Salir</a>
    </div>
  </header>

  <main class="container">
    <h3>Módulos del sistema</h3>

    <?php if ($isSuper): ?>
    <section class="card module-highlight">
      <div>
        <h4>Usuarios</h4>
        <p>Administración de usuarios y niveles de acceso.</p>
      </div>
      <a class="btn-link" href="usuarios.php">Usuarios</a>
    </section>
    <?php endif; ?>

    <?php if ($error): ?>
      <p class="msg"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <section class="grid">
      <?php foreach ($modules as $m): ?>
        <article class="card">
        <article class="card module-card">
          <h4><?= htmlspecialchars($m['name']) ?></h4>
        <a class="btn-link" href=<?=htmlspecialchars($m['ruta'])?>><?= htmlspecialchars($m['name']) ?></a>

          <p><?= htmlspecialchars($m['description']) ?></p>
    <!--      <a class="btn-link" href="<?= htmlspecialchars($m['ruta']) ?>">Abrir módulo</a>-->
        </article></article>
      <?php endforeach; ?>
    </section>
  </main>
</body>
</html>