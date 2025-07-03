<?php
require 'auth.php';

$host = 'localhost';
$db = 'derederderderd';
$user = 'derederderderd';
$pass = 'derederderderd';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $clientId = $_POST['client_id'] ?? '';
        $clientSecret = $_POST['client_secret'] ?? '';
      $link_suporte = $_POST['link_suporte'] ?? '';

        $stmt = $pdo->prepare("UPDATE credentials SET client_id = ?, client_secret = ? WHERE id = 1");
        $stmt->execute([$clientId, $clientSecret]);
    }
  
  
      // Atualizar link de suporte Telegram (sem duplicar)
    $stmt = $pdo->prepare("UPDATE configuracoes SET valor = ? WHERE chave = 'link_suporte_telegram'");
    $stmt->execute([$link_suporte]);


    header("Location: painel.php?salvo=1");
    exit;

} catch (PDOException $e) {
    die("Erro ao salvar: " . $e->getMessage());
}
