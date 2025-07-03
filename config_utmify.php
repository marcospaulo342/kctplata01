<?php
session_start();
require 'db/conexao.php'; // Conexão PDO

// --- AUTENTICAÇÃO ---
$login = 'admin';
$senha = 'jumento123';

if (isset($_POST['login']) && isset($_POST['senha'])) {
    if ($_POST['login'] === $login && $_POST['senha'] === $senha) {
        $_SESSION['autenticado'] = true;
    } else {
        $erro = "Usuário ou senha inválidos.";
    }
}

if (isset($_GET['sair'])) {
    session_destroy();
    header("Location: config_utmify.php");
    exit;
}

// --- BLOQUEIA ACESSO SEM LOGIN ---
if (!isset($_SESSION['autenticado'])) {
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Login - UTMIFY</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-100 flex items-center justify-center h-screen">
        <form method="post" class="bg-white p-6 rounded shadow w-full max-w-sm">
            <h2 class="text-lg font-bold mb-4">🔐 Acesso Restrito</h2>
            <?php if (isset($erro)) : ?>
                <div class="text-red-600 mb-3"><?= $erro ?></div>
            <?php endif; ?>
            <label class="block mb-2 font-semibold">Usuário:</label>
            <input type="text" name="login" class="w-full mb-4 p-2 border rounded" required>
            <label class="block mb-2 font-semibold">Senha:</label>
            <input type="password" name="senha" class="w-full mb-4 p-2 border rounded" required>
            <button class="bg-blue-600 text-white w-full py-2 rounded hover:bg-blue-700">Entrar</button>
        </form>
    </body>
    </html>
    <?php
    exit;
}

// --- ATUALIZAÇÃO DO TOKEN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'])) {
    $novoToken = trim($_POST['token']);
    $stmt = $pdo->prepare("UPDATE integracoes SET token = ? WHERE nome = 'utmify'");
    $stmt->execute([$novoToken]);
    $mensagem = "Token atualizado com sucesso!";
}

// --- BUSCA TOKEN ATUAL ---
$stmt = $pdo->prepare("SELECT token FROM integracoes WHERE nome = 'utmify'");
$stmt->execute();
$tokenAtual = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Configurar Token UTMIFY</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-10">
  <div class="max-w-xl mx-auto bg-white p-6 rounded shadow">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-xl font-bold">🔧 UTMIFY Token</h2>
      <a href="?sair=1" class="text-sm text-red-600 hover:underline">Sair</a>
    </div>

    <?php if (!empty($mensagem)) : ?>
      <div class="bg-green-100 border border-green-400 text-green-700 p-3 mb-4 rounded">
        <?= $mensagem ?>
      </div>
    <?php endif; ?>

    <form method="post">
      <label class="block mb-2 font-semibold">Token Atual:</label>
      <textarea name="token" class="w-full p-2 border border-gray-300 rounded" rows="4"><?= htmlspecialchars($tokenAtual) ?></textarea>
      <button class="mt-4 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Salvar</button>
    </form>
  </div>
</body>
</html>
