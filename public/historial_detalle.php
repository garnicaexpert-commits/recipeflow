<?php
session_start();
if (!isset($_SESSION['user'])) {
  header('Location: index.php');
  exit;
}
$id = (int)($_GET['id'] ?? 0);
$autoPrint = (($_GET['autoprint'] ?? '0') === '1') ? '1' : '0';
header('Location: receta_pdf.php?id=' . $id . '&autoprint=' . $autoPrint);
exit;