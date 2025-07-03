<?php
require '../db/conexao.php';

$stmt = $pdo->prepare("SELECT client_id, client_secret FROM secundario ORDER BY id DESC LIMIT 1");
$stmt->execute();
$credenciais = $stmt->fetch(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode([
  'clientId' => $credenciais['client_id'],
  'clientSecret' => $credenciais['client_secret']
]);
