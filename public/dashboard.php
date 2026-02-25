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

$hasUsersModule = false;

try {
  $pdo = Database::connect($config['db']);
  $modules = $pdo->query('SELECT name, description, ruta FROM modules ORDER BY id')->fetchAll();
  foreach ($modules as $module) {
    $path = trim((string)($module['ruta'] ?? ''));
    if ($path === '') {
      continue;
    }
    $rutaBase = strtolower(basename(parse_url($path, PHP_URL_PATH) ?: $path));
    if ($rutaBase === 'usuarios.php') {
      $hasUsersModule = true;
      break;
    }
  }
} catch (Throwable $e) {
  $error = 'No se pudo cargar módulos desde MySQL.';
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>RecipeFlow Inicio</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
  <header class="topbar">
    <div style="display: flex;">
      <img src="../imagen/logobig.png" style="width: 80px; height: 80px;">
      <h1>Sistema de Control de Recetas Médicas</h1>
      
    </div>
    <div class="topbar-actions">
      <span><?= htmlspecialchars($_SESSION['user']['display_name'] ?? $_SESSION['user']['full_name']) ?></span>
      <a class="btn-link" href="logout.php">Salir</a>
    </div>
  </header>
  <main class="container">
     <!-- <h2>Panel principal</h2> -->
    <section class="card " >
      <!--<p class="eyebrow">Sistema de Control de Recipes</p> -->
      <p style="text-align:center;">Gestion de Prescipciones Medicas, historial de recetas Madicas y módulos operativos con una experiencia simple y profesional.</p>
    </section>

    <h3>Módulos del sistema</h3>

    

    <?php if ($error): ?>
      <p class="msg"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <section class="grid">
      <?php foreach ($modules as $m): ?> 
        <?php 
        // Decidimos si mostrar la tarjeta:
        // 1. Si el nombre NO es 'Usuarios', se muestra a todos.
        // 2. Si el nombre ES 'Usuarios', solo se muestra si $isSuper es true.
        if ($m['name'] !== 'Usuarios' || ($m['name'] === 'Usuarios' && $isSuper)): 
        ?>
          <article class="card module-card" style="text-align:center;">
              <h4 ><?= htmlspecialchars($m['name']) ?></h4>
              <p><?= htmlspecialchars($m['description']) ?></p>
              <a class="btn-link" href="<?= htmlspecialchars($m['ruta']) ?>">Abrir módulo</a>
          </article>
        <?php endif; ?>
      <?php endforeach; ?>
      <?php if ($error): ?>
        <p class="msg"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>