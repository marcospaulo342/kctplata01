<?php
session_start();
$usuario = 'AD';
$senha = 'AD'; // Mude aqui

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: login.php");
    exit;
}
