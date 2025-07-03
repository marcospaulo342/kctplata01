<?php
session_start();
header('Content-Type: application/json');
require_once 'db/conexao.php'; // ajuste o caminho conforme necessário

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['erro' => 'Usuário não autenticado']);
    exit;
}

$id_usuario = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT saldo FROM usuarios WHERE id = :id");
    $stmt->bindParam(':id', $id_usuario, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode(['saldo' => (float)$user['saldo']]);
    } else {
        echo json_encode(['saldo' => 0]);
    }
} catch (PDOException $e) {
    echo json_encode(['erro' => 'Erro ao consultar saldo']);
}
