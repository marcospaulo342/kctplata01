<?php
session_start();

// Verifica se é dispositivo mobile
$eh_mobile = preg_match('/iPhone|iPod|iPad|Android|BlackBerry|IEMobile/i', $_SERVER['HTTP_USER_AGENT']);

if (!$eh_mobile) {
    // Redireciona usuários de desktop
    header("Location: https://www.betmgm.bet.br/");
    exit;
}

// Se estiver logado, vai para painel.php
if (isset($_SESSION['user_id'])) {
    header("Location: painel.php");
    exit;
} else {
    header("Location: register.php");
    exit;
}
