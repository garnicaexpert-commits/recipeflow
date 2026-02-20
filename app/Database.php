<?php
class Database {
  public static function connect(array $cfg): PDO {
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $cfg['host'], $cfg['port'], $cfg['name']);
    return new PDO($dsn, $cfg['user'], $cfg['pass'], [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
  }

  public static function ensureUsersSchema(PDO $pdo): void {
    $legacyBrokenAdminHash = '$2y$12$hD3yt44lrPn9/yS6rvByeurySRUNKM60xJr7BRN364/NJJRSQyIiO';
    $defaultAdminHash = password_hash('admin123', PASSWORD_BCRYPT);

    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS display_name VARCHAR(120) NULL");
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS specialty VARCHAR(120) NULL");
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS contact_phone VARCHAR(40) NULL");
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS access_level ENUM('usuario','superusuario') NOT NULL DEFAULT 'usuario'");
    $pdo->exec("UPDATE users SET display_name = COALESCE(NULLIF(display_name,''), full_name)");
    $pdo->exec("UPDATE users SET access_level = 'superusuario' WHERE username = 'admin'");

    $stFixHash = $pdo->prepare('UPDATE users SET password_hash = ? WHERE username = ? AND password_hash = ?');
    $stFixHash->execute([$defaultAdminHash, 'admin', $legacyBrokenAdminHash]);

    $stSeedAdmin = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, display_name, specialty, contact_phone, access_level)
      SELECT ?, ?, ?, ?, ?, ?, ?
      WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = ?)");
    $stSeedAdmin->execute([
      'admin',
      $defaultAdminHash,
      'Administrador General',
      'Administrador',
      'General',
      'N/D',
      'superusuario',
      'admin',
    ]);
  }


  public static function ensurePrescriptionsOwnershipSchema(PDO $pdo): void {
    $pdo->exec('ALTER TABLE prescriptions ADD COLUMN IF NOT EXISTS user_id INT NULL');
    $pdo->exec('ALTER TABLE prescription_items ADD COLUMN IF NOT EXISTS quantity INT NOT NULL DEFAULT 1');

    $pdo->exec("UPDATE prescriptions p JOIN users u ON u.username = 'admin' SET p.user_id = u.id WHERE p.user_id IS NULL");
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_prescriptions_user_created ON prescriptions (user_id, created_at)');
  }

}
