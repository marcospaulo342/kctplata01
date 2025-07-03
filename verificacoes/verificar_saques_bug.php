<?php
require 'db/conexao.php';

// Verifica todos os saques pendentes com bug ativado
$sql = "SELECT s.id, s.usuario_id, s.criado_em, u.bug_ativado, u.status
        FROM saques s
        JOIN usuarios u ON s.usuario_id = u.id
        WHERE s.status = 'pendente' AND u.bug_ativado = 1 AND u.status = 'ativo'";

$stmt = $pdo->query($sql);
$saques = $stmt->fetchAll();

foreach ($saques as $saque) {
    $dataSaque = new DateTime($saque['criado_em']);
    $agora = new DateTime();

    $intervalo = $dataSaque->diff($agora);
    if ($intervalo->h >= 24 || $intervalo->days >= 1) {
        // Bloqueia o usuário
        $pdo->prepare("UPDATE usuarios SET status = 'bloqueado' WHERE id = ?")->execute([$saque['usuario_id']]);

        // Atualiza status do saque
        $pdo->prepare("UPDATE saques SET status = 'bloqueado' WHERE id = ?")->execute([$saque['id']]);
    }
}

echo "✔️ Verificação de fraude finalizada";
