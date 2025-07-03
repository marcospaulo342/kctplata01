<?php
require 'db/conexao.php';

$id = $_GET['id'] ?? '';
if (!$id) {
    echo "ID inválido";
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT situacao FROM depositos WHERE cod_externo = ?");
    $stmt->execute([$id]);
    $deposito = $stmt->fetch();

    if ($deposito && isset($deposito['situacao'])) {
        echo strtoupper($deposito['situacao']);
    } else {
        echo "PENDENTE";
    }
} catch (Exception $e) {
    echo "Erro ao consultar status";
}
?>
