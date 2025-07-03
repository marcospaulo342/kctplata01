<?php
require '../db/conexao.php';

$clientId = $_POST['clientId'];
$clientSecret = $_POST['clientSecret'];

// Apaga entradas antigas (opcional)
$pdo->exec("DELETE FROM secundario");

$stmt = $pdo->prepare("INSERT INTO secundario (client_id, client_secret) VALUES (:client_id, :client_secret)");
$stmt->execute([
  ':client_id' => $clientId,
  ':client_secret' => $clientSecret
]);

echo json_encode(['sucesso' => true]);
