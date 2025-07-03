<?php
require 'auth.php';

$host = 'localhost';
$db = 'derederderderd';
$user = 'derederderderd';
$pass = 'derederderderd';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT client_id, client_secret FROM credentials LIMIT 1");
    $credenciais = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT valor FROM configuracoes WHERE chave = 'link_suporte_telegram' LIMIT 1");
    $stmt->execute();
    $link = $stmt->fetchColumn();
} catch (PDOException $e) {
    die("Erro ao carregar credenciais: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Painel de Credenciais</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-10">
  <div class="max-w-xl mx-auto bg-white p-6 rounded shadow">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold">Editar credenciais</h1>
      <a href="logout.php" class="text-sm text-red-600 hover:underline">Sair</a>
    </div>

    <?php if (isset($_GET['salvo'])): ?>
      <div class="mb-4 text-green-700 bg-green-100 border border-green-400 rounded p-3">
        Dados salvos com sucesso!
      </div>
    <?php endif; ?>

    <form action="salvar.php" method="POST" class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">Client ID</label>
        <input name="client_id" value="<?= htmlspecialchars($credenciais['client_id']) ?>" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2" required>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Client Secret</label>
        <input name="client_secret" value="<?= htmlspecialchars($credenciais['client_secret']) ?>" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2" required>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Link de Suporte (Telegram)</label>
        <input name="link_suporte" value="<?= htmlspecialchars($link) ?>" class="mt-1 block w-full border border-gray-300 rounded px-3 py-2" required>
      </div>
      <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Salvar</button>
    </form>
  </div>
</body>
</html>