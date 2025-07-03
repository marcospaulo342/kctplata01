<?php
session_start();
require 'db/conexao.php';

if (!isset($_SESSION['user_id'])) {
  echo json_encode(['erro' => true]);
  exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT saldo FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$moeda = $_SESSION['moeda'] ?? 'BRL';
$simbolo = 'R$';
if ($moeda === 'USD') $simbolo = '$';
if ($moeda === 'RUB') $simbolo = '₽';

echo json_encode([
  'erro' => false,
  'saldo' => number_format($user['saldo'], 2, ',', '.'),
  'simbolo' => $simbolo
]);
?>
