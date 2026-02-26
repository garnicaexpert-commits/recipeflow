<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../app/Database.php';
$config = require __DIR__ . '/../config/config.php';

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';

try {
  $pdo = Database::connect($config['db']);
  Database::ensureUsersSchema($pdo);

  $stmt = $pdo->prepare('SELECT id, username, password_hash, full_name, display_name, specialty, access_level FROM users WHERE username = ?');
  $stmt->execute([$username]);
  $user = $stmt->fetch();

  if (!$user || !password_verify($password, $user['password_hash'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Credenciales inválidas']);
    exit;
  }

  $_SESSION['user'] = [
    'id' => $user['id'],
    'username' => $user['username'],
    'full_name' => $user['full_name'],
    'specialty' => $user['specialty'],
    'display_name' => $user['display_name'] ?: $user['full_name'],
    'access_level' => $user['access_level'] ?: 'usuario',
  ];

  echo json_encode(['ok' => true]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'message' => 'Error de conexión MySQL']);
}
