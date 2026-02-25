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
  <title>RecipeFlow - Recetas</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
  <header class="topbar">
    <div style="display: flex;" >
      <img src="../imagen/logobig.png" style="width: 80px; height: 80px;">
      <h2>Módulo de Recetas</h2>
    </div>
    <a class="btn-link" href="dashboard.php">Regresar al dashboard</a>
  </header>

  <main class="container">
    <section class="card">
      <?php if ($err): ?><p class="msg"><?= htmlspecialchars($err) ?></p><?php endif; ?>

      <form id="recipe-form" class="stack">
        <div class="grid inline-grid" style="display: flex;">
          <input name="patient_name" placeholder="Nombres y apellidos" required style="width: 800px;">
          <input name="cedula" placeholder="Cédula" required style="width: 300px;">
          <input name="age" type="number" min="0" placeholder="Edad" required style="width: 117px;">
         </div>
         <div class="grid inline-grid" style="display: flex;">  
          <input name="address" placeholder="Dirección" required style="width: 800px">
          <input name="phone" placeholder="Teléfono" required style="width: 435px">
         </div>
         <div  class="grid inline-grid" style="display: flex;">
          <input name="diagnosis" placeholder="Diagnóstico" required style="width: 934px">
          <input name="cie10" placeholder="Código CIE-10" required style="width: 300px">     
         </div>
        </div>

        <h4>Medicamentos</h4>
        <table class="recipe-table" id="med-table">
          <thead>
            <tr>
              <th>Medicamento (Búsqueda Progresiva)</th>
              <th>Cantidad</th>
              <th>Indicaciones</th>
              <th>Observaciones</th>
              <th></th>
            </tr>
          </thead>
          <tbody id="med-body"></tbody>
        </table>
        <button type="button" id="add-med" class="btn-secondary">+ Agregar medicamento</button>

        <div class="actions-row">
          <button type="submit" id="print-btn">Guardar receta</button>
          <!-- <button type="button" id="print-btn">Imprimir receta PDF</button> -->
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
