<?php
session_start();

$host = 'localhost';
$db = 'derederderderd';
$user = 'derederderderd';
$pass = 'derederderderd';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $usuario = $_POST['usuario'];
        $senha = $_POST['senha'];

        $stmt = $pdo->prepare("SELECT password_hash FROM admin_users WHERE username = ?");
        $stmt->execute([$usuario]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($senha, $user['password_hash'])) {
            $_SESSION['logado'] = true;
            $_SESSION['usuario'] = $usuario;
            header("Location: painel.php");
            exit;
        } else {
            $erro = "Usuário ou senha inválidos!";
        }
    }

} catch (PDOException $e) {
    die("Erro no banco de dados: " . $e->getMessage());
}
?>


<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
  <form method="POST" class="bg-white p-8 rounded shadow-md w-80">
    <h2 class="text-xl font-bold mb-6 text-center">Painel Admin</h2>
    <?php if (isset($erro)): ?>
      <div class="text-red-500 text-sm mb-3"><?= $erro ?></div>
    <?php endif; ?>
    <input name="usuario" placeholder="Usuário" class="mb-4 w-full px-3 py-2 border rounded" required>
    <input name="senha" type="password" placeholder="Senha" class="mb-6 w-full px-3 py-2 border rounded" required>
    <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">Entrar</button>
  </form>
</body>
</html>
