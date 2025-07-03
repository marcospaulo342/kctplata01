<?php
// CONFIGURAÇÃO DO BANCO (ajuste com seus dados)
$host = '127.0.0.1';
$db   = 'ttttgggggggg';
$user = 'ttttgggggggg';
$pass = 'ttttgggggggg';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT client_id, client_secret FROM credentials LIMIT 1");
    $credenciais = $stmt->fetch(PDO::FETCH_ASSOC);

    $clientId = $credenciais['client_id'];
    $clientSecret = $credenciais['client_secret'];

} catch (PDOException $e) {
    die("Erro ao carregar credenciais: " . $e->getMessage());
}
