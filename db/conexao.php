<?php
$host = 'http://127.0.0.1';
$db = 'ttttgggggggg';
$user = 'ttttgggggggg';
$pass = 'ttttgggggggg';
$port = 3306; // ✅ Porta adicionada aqui

try {
  // Incluindo a porta na string de conexão
  $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass);
  
  // Configurando para lançar exceções em caso de erro
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  
  // Configurando UTF-8 para evitar problemas com caracteres especiais
  $pdo->exec("SET NAMES utf8mb4");
  
} catch (PDOException $e) {
  // Mensagem de erro mais detalhada e segura
  die("Erro de conexão: " . $e->getMessage());
}
?>
