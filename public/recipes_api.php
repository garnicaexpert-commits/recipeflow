<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['user'])) {
  http_response_code(401);
  echo json_encode(['ok' => false, 'message' => 'No autorizado']);
  exit;
}

require __DIR__ . '/../app/Database.php';
$config = require __DIR__ . '/../config/config.php';

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$medications = $input['medications'] ?? [];

try {
  $pdo = Database::connect($config['db']);
  Database::ensureUsersSchema($pdo);
  Database::ensurePrescriptionsOwnershipSchema($pdo);

  $userId = (int)($_SESSION['user']['id'] ?? 0);
  if ($userId <= 0) {
    throw new RuntimeException('Sesión inválida');
  }

  $pdo->beginTransaction();

  $stmt = $pdo->prepare('INSERT INTO prescriptions (user_id, patient_name, cedula, age, address, phone, diagnosis, cie10) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
  $stmt->execute([
    $userId,
    trim($input['patient_name'] ?? ''),
    trim($input['cedula'] ?? ''),
    (int)($input['age'] ?? 0),
    trim($input['address'] ?? ''),
    trim($input['phone'] ?? ''),
    trim($input['diagnosis'] ?? ''),
    trim($input['cie10'] ?? ''),
  ]);

  $prescriptionId = (int)$pdo->lastInsertId();
  $medStmt = $pdo->prepare('INSERT INTO prescription_items (prescription_id, medicine_name, quantity, dose, instructions) VALUES (?, ?, ?, ?, ?)');
  foreach ($medications as $m) {
    $medStmt->execute([
      $prescriptionId,
      trim($m['name'] ?? ''),
      max(1, (int)($m['quantity'] ?? 1)),
      trim($m['dose'] ?? ''),
      trim($m['instructions'] ?? ''),
    ]);
  }

  $pdo->commit();
  echo json_encode(['ok' => true, 'id' => $prescriptionId]);
} catch (Throwable $e) {
  if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['ok' => false, 'message' => 'No se pudo guardar receta']);
}
