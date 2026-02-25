<?php
session_start();
if (isset($_SESSION['user'])) {
  header('Location: dashboard.php');
  exit;
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>RecipeFlow Login</title>
  <title>Sistema de Control de Recetas Médicas | Acceso</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
  <main class="center login-shell">
    <section class="card login-card">
      <div class="login-brand">
        <h1 style="text-align: center;">RecipeFlow</h1>
        <p style="text-align: center;"><span class="eyebrow">Gestión clínica de recetas con  clarida, rápides y segurida.</span></p>
      </div>
      <form id="login-form" class="stack">
        <input name="username" placeholder="Usuario" required>
        <input name="password" type="password" placeholder="Contraseña" required>
        <button type="submit">Ingresar</button>
      </form>
      <p id="msg" class="msg"></p>
    </section>
  </main>
  <script src="assets/app.js"></script>
</body>
</html>