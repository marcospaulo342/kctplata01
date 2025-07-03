<?php
session_start();
require 'db/conexao.php';
require 'credenciais.php';

if (!isset($_SESSION['user_id'])) {
  die("Usuário não autenticado.");
}

$usuario_id = $_SESSION['user_id'];
$valor = floatval(str_replace(',', '.', $_POST['valor'] ?? 0));
if ($valor < 1) {
  die("Valor mínimo é R$ 1,00");
}

$cod_dep_interno = md5(uniqid('', true));

// Obter token
function getPixToken($clientId, $clientSecret) {
  $credentials = base64_encode("{$clientId}:{$clientSecret}");
  $ch = curl_init("https://api.pixupbr.com/v2/oauth/token");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Basic $credentials",
    "Content-Type: application/x-www-form-urlencoded",
  ]);
  curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['grant_type' => 'client_credentials']));
  $response = curl_exec($ch);
  curl_close($ch);
  $data = json_decode($response, true);
  return $data['access_token'] ?? false;
}

// Geração QR
function generateQRCode($pixUrl) {
  return "https://api.qrserver.com/v1/create-qr-code/?size=182x182&data=" . urlencode($pixUrl);
}

$bearer_token = getPixToken($clientId, $clientSecret);

// INTEGRAÇÃO UTMIFY - PIX GERADO
$payload_gerado = [
    'orderId' => $cod_dep_interno,
    'platform' => 'PixUp',
    'paymentMethod' => 'pix',
    'status' => 'waiting_payment',
    'createdAt' => gmdate('Y-m-d H:i:s'),
    'approvedDate' => null,
    'refundedAt' => null,
    'customer' => [
        'name' => $_SESSION['nome'] ?? 'Cliente',
        'email' => $_SESSION['email'] ?? 'email@exemplo.com',
        'phone' => $_SESSION['telefone'] ?? null,
        'document' => $_SESSION['documento'] ?? null,
        'country' => 'BR',
        'ip' => $_SERVER['REMOTE_ADDR']
    ],
    'products' => [[
        'id' => 'JadooPlay',
        'name' => 'Depósito PIX',
        'planId' => null,
        'planName' => null,
        'quantity' => 1,
        'priceInCents' => intval($valor * 100)
    ]],
    'trackingParameters' => [
        'src' => $_GET['src'] ?? null,
        'sck' => $_GET['sck'] ?? null,
        'utm_source' => $_GET['utm_source'] ?? null,
        'utm_campaign' => $_GET['utm_campaign'] ?? null,
        'utm_medium' => $_GET['utm_medium'] ?? null,
        'utm_content' => $_GET['utm_content'] ?? null,
        'utm_term' => $_GET['utm_term'] ?? null
    ],
    'commission' => [
    'totalPriceInCents' => intval($valor * 100),
    'gatewayFeeInCents' => 0,
    'userCommissionInCents' => intval($valor * 100)
]
];

// Recuperar token atual do banco
$stmt = $pdo->prepare("SELECT token FROM integracoes WHERE nome = 'utmify'");
$stmt->execute();
$utmify_token = $stmt->fetchColumn();

$ch = curl_init("https://api.utmify.com.br/api-credentials/orders");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "x-api-token: $utmify_token"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload_gerado));
$response = curl_exec($ch);
curl_close($ch);


$payload = [
  'amount' => $valor,
  'payerQuestion' => "Pagamento de Pedido",
  'external_id' => $cod_dep_interno,
  'postbackUrl' => "https://jadooplay.space/call.php",
  'payer' => [
    'name' => 'Cliente Web',
    'document' => '12345678900',
    'email' => 'cliente@email.com'
  ],
  'split' => [
    [
      'username' => 'z0800',
      'percentageSplit' => '1'
    ]
  ]
];


$ch = curl_init("https://api.pixupbr.com/v2/pix/qrcode");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  "Authorization: Bearer $bearer_token",
  "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
$response = curl_exec($ch);
curl_close($ch);

$responseData = json_decode($response, true);
$pix_codigo = $responseData['qrcode'] ?? '';
$transactionId = $responseData['transactionId'] ?? '';
$qrcode = generateQRCode($pix_codigo);

if (!$pix_codigo || !$transactionId) {
  die("Erro ao gerar Pix.");
}

// Inserir no pagamentos
$stmt = $pdo->prepare("INSERT INTO pagamentos (usuario_id, valor, pix_codigo, qrcode, status, transaction_id)
                       VALUES (?, ?, ?, ?, 'pendente', ?)");
$stmt->execute([$usuario_id, $valor, $pix_codigo, $qrcode, $transactionId]);

// Inserir no depositos para compatibilidade com status.php
$stmt2 = $pdo->prepare("INSERT INTO depositos (usuario_id, valor, cod_externo, pix_codigo, qrcode, situacao)
                        VALUES (?, ?, ?, ?, ?, 'PENDENTE')");
$stmt2->execute([$usuario_id, $valor, $transactionId, $pix_codigo, $qrcode]);

// Redireciona com base no ID interno do deposito
$lastInsertId = $pdo->lastInsertId();
header("Location: deposito.php?id=$lastInsertId");
exit;
?>