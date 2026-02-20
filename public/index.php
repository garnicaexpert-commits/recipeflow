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
  <title>MediSys Login</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
  <main class="center">
    <section class="card">
      <h1>MediSys</h1>
      <p>Ingreso al tablero</p>
      <form id="login-form" class="stack">
        <input name="username" placeholder="Usuario" required>
        <input name="password" type="password" placeholder="Contraseña" required>
        <button type="submit">Ingresar</button>
      </form>
      <small>Usuario demo: admin / admin123</small>
      <p id="msg" class="msg"></p>
    </section>
  </main>
  <script src="assets/app.js"></script>
</body>
</html>
