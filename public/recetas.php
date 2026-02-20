<?php
session_start();
if (!isset($_SESSION['user'])) {
  header('Location: index.php');
  exit;
}

require __DIR__ . '/../app/Database.php';
$config = require __DIR__ . '/../config/config.php';
$vademecum = [];
$err = '';
try {
  $pdo = Database::connect($config['db']);
  $vademecum = $pdo->query('SELECT id, nombre_comercial, componente_quimico, dosis, presentacion FROM vademecum ORDER BY nombre_comercial')->fetchAll();
} catch (Throwable $e) {
  $err = 'No se pudo cargar el vademécum desde MySQL.';
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Módulo de Recetas</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
  <header class="topbar">
    <h2>Módulo de Recetas</h2>
    <a class="btn-link" href="dashboard.php">Regresar al dashboard</a>
  </header>

  <main class="container">
    <section class="card">
      <?php if ($err): ?><p class="msg"><?= htmlspecialchars($err) ?></p><?php endif; ?>

      <form id="recipe-form" class="stack">
        <div class="grid form-grid">
          <input name="patient_name" placeholder="Nombres y apellidos" required>
          <input name="cedula" placeholder="Cédula" required>
          <input name="age" type="number" min="0" placeholder="Edad" required>
          <input name="address" placeholder="Dirección" required>
          <input name="phone" placeholder="Teléfono" required>
          <input name="diagnosis" placeholder="Diagnóstico" required>
          <input name="cie10" placeholder="Código CIE-10" required>
        </div>

        <h4>Medicamentos</h4>
        <table class="recipe-table" id="med-table">
          <thead>
            <tr>
              <th>Medicamento (búsqueda progresiva)</th>
              <th>Cantidad</th>
              <th>Dosis</th>
              <th>Indicaciones</th>
              <th></th>
            </tr>
          </thead>
          <tbody id="med-body"></tbody>
        </table>
        <button type="button" id="add-med" class="btn-secondary">+ Agregar medicamento</button>

        <div class="actions-row">
          <button type="submit">Guardar receta</button>
          <button type="button" id="print-btn">Imprimir receta PDF</button>
        </div>
      </form>
      <p id="msg" class="msg"></p>
    </section>
  </main>

  <datalist id="medicamentos-list"></datalist>

  <script>
    window.VADEMECUM_DATA = <?= json_encode($vademecum, JSON_UNESCAPED_UNICODE) ?>;
  </script>
  <script src="assets/recetas.js"></script>
</body>
</html>
