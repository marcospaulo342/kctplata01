<?php
$valor = $_GET['valor'] ?? '0.00';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Obrigado</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-black text-white flex flex-col items-center justify-center min-h-screen px-6">
  <div class="bg-zinc-900 p-6 rounded-2xl max-w-sm w-full text-center shadow-md">
    <h1 class="text-2xl font-bold text-green-500 mb-2">✅ Pagamento Confirmado</h1>
    <p class="text-lg mb-4">Valor: <strong>R$ <?= number_format($valor, 2, ',', '.') ?></strong></p>
    <a href="index.php" class="inline-block bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-6 rounded-lg text-sm">Fazer novo depósito</a>
  </div>
</body>
</html>
