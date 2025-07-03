<?php
require 'db/conexao.php';

// 1. Bloqueia usuários após 24 horas de solicitação de saque
$stmt = $pdo->prepare("
    UPDATE usuarios 
    SET status = 'bloqueado' 
    WHERE data_suspensao <= NOW() 
    AND status = 'ativo'
");
$stmt->execute();
$usuarios_bloqueados = $stmt->rowCount();

// 2. Lógica adicional de bloqueio para usuários com bug ativado conforme já estava na lógica anterior
$limite = date('Y-m-d H:i:s', strtotime('-24 hours'));
$stmt_bug = $pdo->prepare("
    SELECT s.usuario_id FROM saques s
    JOIN usuarios u ON s.usuario_id = u.id
    WHERE s.status = 'pendente' AND s.criado_em <= ? AND u.bug_ativado = 1 AND u.status = 'ativo'
");
$stmt_bug->execute([$limite]);
$usuarios_bug = $stmt_bug->fetchAll();

foreach ($usuarios_bug as $usuario) {
    $pdo->prepare("UPDATE usuarios SET status = 'bloqueado' WHERE id = ?")->execute([$usuario['usuario_id']]);
}
$usuarios_bug_bloqueados = count($usuarios_bug);

// Registra log de execução
$log = "Verificação de bloqueio executada em " . date('Y-m-d H:i:s') . "\n";
$log .= "- Usuários bloqueados por tempo de suspensão: $usuarios_bloqueados\n";
$log .= "- Usuários bloqueados por bug ativado: $usuarios_bug_bloqueados\n";
file_put_contents("log_bloqueios.txt", $log, FILE_APPEND);

// Resposta em formato visual caso seja executado no navegador
if (php_sapi_name() != 'cli') {
    echo "<pre>";
    echo "Verificação de bloqueio executada!\n";
    echo "- Bloqueados por tempo: $usuarios_bloqueados\n";
    echo "- Bloqueados por bug: $usuarios_bug_bloqueados\n";
    echo "</pre>";
}
?>