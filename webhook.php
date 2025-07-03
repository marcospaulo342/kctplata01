<?php
require 'db/conexao.php';

// Coletar JSON enviado pela API
$input = file_get_contents("php://input");
file_put_contents("log_pix.txt", date("Y-m-d H:i:s") . " - " . $input . PHP_EOL, FILE_APPEND); // log para debug

$data = json_decode($input, true);

// Resposta visível no navegador
header('Content-Type: text/plain');

if (!isset($data['transactionId'])) {
    echo "❌ Faltando transactionId";
    exit;
}

$transactionId = $data['transactionId'];

// Buscar pagamento no banco
$stmt = $pdo->prepare("SELECT * FROM pagamentos WHERE transaction_id = ?");
$stmt->execute([$transactionId]);
$pagamento = $stmt->fetch();

if (!$pagamento) {
    echo "❌ Pagamento não encontrado para transactionId: $transactionId";
    exit;
}

if ($pagamento['status'] === 'confirmado') {
    echo "✅ Pagamento já estava confirmado.";
    exit;
}

// Atualizar pagamento e saldo
$pdo->beginTransaction();

$updateSaldo = $pdo->prepare("UPDATE usuarios SET saldo = saldo + ? WHERE id = ?");
$updateSaldo->execute([$pagamento['valor'], $pagamento['usuario_id']]);

$updateStatus = $pdo->prepare("UPDATE pagamentos SET status = 'confirmado' WHERE id = ?");
$updateStatus->execute([$pagamento['id']]);

$pdo->commit();

echo "✅ Pagamento confirmado com sucesso para ID: $transactionId";
