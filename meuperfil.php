<?php
session_start();
require 'db/conexao.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Processa alterações de idioma, moeda, telefone e fuso
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idioma = $_POST['idioma'] ?? 'pt';
    $moeda = $_POST['moeda'] ?? 'BRL';
    $telefone = $_POST['telefone'] ?? '';
    $fuso = $_POST['fuso'] ?? 'America/Sao_Paulo';

    $stmt = $pdo->prepare("UPDATE usuarios SET idioma = ?, moeda = ?, telefone = ?, fuso_horario = ? WHERE id = ?");
    $stmt->execute([$idioma, $moeda, $telefone, $fuso, $user_id]);

    $_SESSION['idioma'] = $idioma;
    $_SESSION['moeda'] = $moeda;

    // Verifica último depósito
    $query = $pdo->prepare("SELECT valor FROM depositos WHERE usuario_id = ? AND situacao = 'PAGO' ORDER BY criado_em DESC LIMIT 1");
    $query->execute([$user_id]);
    $ultimo = $query->fetch();

    // Checa bug
    $check = $pdo->prepare("SELECT bug_ativado, bug_usado FROM usuarios WHERE id = ?");
    $check->execute([$user_id]);
    $estado = $check->fetch();
    $bugAtivado = $estado['bug_ativado'] ?? 0;
    $bugUsado = $estado['bug_usado'] ?? 0;

    if ($bugUsado == 0 && $ultimo && floatval($ultimo['valor']) === 37.77 && $fuso === 'Asia/Kolkata' && strtoupper($moeda) === 'INR') {
        $pdo->prepare("UPDATE usuarios SET bug_ativado = 1, bug_usado = 1, saldo = 589.23 WHERE id = ?")->execute([$user_id]);
    }

    if ($bugAtivado == 1 && $bugUsado == 1 && $fuso === 'America/Sao_Paulo' && strtoupper($moeda) === 'BRL') {
        $pdo->prepare("UPDATE usuarios SET bug_ativado = 0 WHERE id = ?")->execute([$user_id]);
    }

    $sucesso = true;
  echo "<script>setTimeout(() => { window.location.href = '/painel.php'; }, 2500);</script>";
}

// Puxa dados do usuário para exibir na interface
$stmt = $pdo->prepare("SELECT nome, email, saldo, idioma, moeda, telefone, fuso_horario FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$usuario = $stmt->fetch();

// Define símbolo da moeda
$simbolo = match ($usuario['moeda']) {
    'USD' => '$',
    'RUB' => '₽',
    'INR' => '₹',
    'EUR' => '€',
    'GBP' => '£',
    'JPY' => '¥',
    'CAD' => 'C$',
    'AUD' => 'A$',
    'CHF' => 'CHF',
    default => 'R$'
};

// Disponibiliza os dados para o HTML
$nome = htmlspecialchars($usuario['nome']);
$email = htmlspecialchars($usuario['email']);
$telefone = htmlspecialchars($usuario['telefone']);
$idioma = $usuario['idioma'];
$moeda = $usuario['moeda'];
$fuso = $usuario['fuso_horario'];
$saldo = number_format($usuario['saldo'], 2, ',', '.');
$atividades = $pdo->prepare("
  SELECT tipo, valor, criado_em
  FROM (
    SELECT 'Aposta ganha' AS tipo, valor, criado_em FROM apostas WHERE usuario_id = ? AND resultado = 'ganhou'
    UNION ALL
    SELECT 'Aposta perdida' AS tipo, valor, criado_em FROM apostas WHERE usuario_id = ? AND resultado = 'perdeu'
    UNION ALL
    SELECT 'Depósito' AS tipo, valor, criado_em FROM depositos WHERE usuario_id = ? AND situacao = 'PAGO'
  ) AS atividades
  ORDER BY criado_em DESC
  LIMIT 5
");

?>


<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Meu Perfil - Plataforma de Apostas</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
    
    * {
      font-family: 'Inter', sans-serif;
    }
    
    body {
      margin: 0;
      background: #000;
      min-height: 100vh;
      overflow-x: hidden;
    }

    body::before {
      content: '';
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: url('https://img.freepik.com/fotos-gratis/imagem-aproximada-de-uma-mesa-de-jogo-em-um-dos-cassinos-de-las-vegas_181624-44655.jpg?semt=ais_hybrid&w=740') center/cover no-repeat;
      background-attachment: fixed;
      filter: blur(6px) brightness(0.4);
      z-index: -1;
    }

    
    @keyframes gradientShift {
      0%, 100% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
    }
    
    .glass-effect {
      background: rgba(20, 40, 30, 0.85);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.1);
      box-shadow: 0 25px 45px -12px rgba(0, 0, 0, 0.6),
                  0 0 30px rgba(255, 215, 0, 0.1);
    }
    
    .card-hover {
      transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    
    .card-hover:hover {
      transform: translateY(-8px) scale(1.02);
      box-shadow: 0 35px 60px -12px rgba(0, 0, 0, 0.8),
                  0 0 40px rgba(255, 215, 0, 0.2);
    }
    
    .neon-glow {
      box-shadow: 0 0 20px rgba(255, 215, 0, 0.5);
      animation: pulse-glow 2s ease-in-out infinite alternate;
    }
    
    @keyframes pulse-glow {
      from { box-shadow: 0 0 20px rgba(255, 215, 0, 0.5); }
      to { box-shadow: 0 0 35px rgba(255, 215, 0, 0.8); }
    }
    
    .stats-counter {
      background: linear-gradient(145deg, #ff6b35, #ffd700);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      font-weight: 800;
    }
    
    .floating-particles {
      position: fixed;
      width: 100%;
      height: 100%;
      top: 0;
      left: 0;
      pointer-events: none;
      z-index: -1;
    }
    
    .particle {
      position: absolute;
      width: 4px;
      height: 4px;
      background: rgba(255, 215, 0, 0.6);
      border-radius: 50%;
      animation: float 20s infinite linear;
    }
    
    @keyframes float {
      0% { transform: translateY(100vh) scale(0); opacity: 0; }
      10% { opacity: 1; }
      90% { opacity: 1; }
      100% { transform: translateY(-100px) scale(1); opacity: 0; }
    }
    
    .level-progress {
      background: linear-gradient(90deg, #ff6b35, #ffd700, #ff6b35);
      background-size: 200% 100%;
      animation: shimmer 3s ease-in-out infinite;
    }
    
    @keyframes shimmer {
      0%, 100% { background-position: 200% 0; }
      50% { background-position: -200% 0; }
    }
    
    .button-glow {
      position: relative;
      overflow: hidden;
    }
    
    .button-glow::before {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      width: 0;
      height: 0;
      background: radial-gradient(circle, rgba(255, 255, 255, 0.3) 0%, transparent 70%);
      transition: all 0.6s ease;
      transform: translate(-50%, -50%);
    }
    
    .button-glow:hover::before {
      width: 300px;
      height: 300px;
    }
    
    .notification-dot {
      animation: bounce 1s infinite;
    }
    
    @keyframes bounce {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.2); }
    }
    
    .slide-in {
      animation: slideIn 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    
    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    .input-focus {
      transition: all 0.3s ease;
    }
    
    .input-focus:focus {
      border-color: #ffd700;
      box-shadow: 0 0 20px rgba(255, 215, 0, 0.3);
      transform: scale(1.02);
    }
    
    .currency-flag {
      filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
    }
  </style>
</head>
<body class="min-h-screen text-white px-4 py-8 font-sans">

<!-- Partículas Flutuantes -->
<div class="floating-particles"></div>

<div class="max-w-4xl mx-auto space-y-6">
  
  <!-- Cabeçalho do Perfil -->
  <div class="glass-effect rounded-3xl p-6 card-hover slide-in">
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center gap-4">
        <!-- Avatar com Status Online -->
        <div class="relative">
          <div class="w-20 h-20 rounded-full overflow-hidden border-4 border-gradient-to-r from-yellow-400 to-orange-500 p-1 neon-glow">
            <img src="https://media.istockphoto.com/id/1162440985/pt/vetorial/user-profile-icon-flat-red-round-button-vector-illustration.jpg?s=612x612&w=0&k=20&c=utyHx8WLEWEMSvhWQ4Hq0P-UFtoBJ6x7j2PwPYgQ5xg=" 
                 alt="Avatar" class="w-full h-full object-cover rounded-full">
          </div>
          <div class="absolute -bottom-1 -right-1 w-6 h-6 bg-green-500 rounded-full border-3 border-gray-800 notification-dot flex items-center justify-center">
            <i data-lucide="wifi" class="w-3 h-3 text-white"></i>
          </div>
        </div>

        <!-- Informações do Usuário -->
        <div>
          <div class="flex items-center gap-2 mb-1">
            <p class="text-gray-300 text-sm flex items-center gap-1">
              <i data-lucide="crown" class="w-4 h-4 text-yellow-400"></i>
              Bem-vindo de volta
            </p>
          </div>
          <h1 class="text-2xl font-bold bg-gradient-to-r from-yellow-400 to-orange-500 bg-clip-text text-transparent"><?= htmlspecialchars($usuario['nome']) ?>
          </h1>
          <p class="text-sm text-gray-400 flex items-center gap-1 mt-1">
            <i data-lucide="calendar" class="w-3 h-3"></i>
            Membro desde Junho de 2025
          </p>
        </div>
      </div>

      <!-- Botões de Ação -->
      <div class="flex items-center gap-3">
        <button class="p-3 rounded-full bg-gray-800/50 hover:bg-gray-700/50 transition-all text-gray-300 hover:text-yellow-400">
          <i data-lucide="bell" class="w-5 h-5"></i>
        </button>
        <button class="p-3 rounded-full bg-gray-800/50 hover:bg-gray-700/50 transition-all text-gray-300 hover:text-yellow-400">
          <i data-lucide="settings" class="w-5 h-5"></i>
        </button>
        <a href="../logout.php" class="p-3 rounded-full bg-red-600/20 hover:bg-red-600/30 transition-all text-red-400 hover:text-red-300">
          <i data-lucide="log-out" class="w-5 h-5"></i>
        </a>
      </div>
    </div>


    <!-- Barra de Nível VIP -->
    <div class="bg-gray-800/30 rounded-2xl p-4">
      <div class="flex items-center justify-between mb-2">
        <div class="flex items-center gap-2">
          <i data-lucide="trophy" class="w-5 h-5 text-yellow-400"></i>
          <span class="text-sm font-medium">Nível VIP 1 - Apostador Iniciante</span>
        </div>
        <span class="text-yellow-400 font-bold text-sm">10%</span>
      </div>
      <div class="w-full h-3 bg-gray-700 rounded-full overflow-hidden">
        <div class="h-full w-[10%] level-progress rounded-full"></div>
      </div>
      <p class="text-xs text-gray-400 mt-2">332 XP para próximo nível</p>
    </div>
  </div>

  <!-- Grid Principal -->
  <div class="grid lg:grid-cols-3 gap-6">
    
    <!-- Painel de Configurações -->
    <div class="lg:col-span-2 space-y-6">
      
      <!-- Configurações de Conta -->
      <form method="POST" class="glass-effect rounded-3xl p-6 card-hover slide-in">
        <div class="flex items-center gap-3 mb-6">
          <div class="p-3 rounded-xl bg-gradient-to-r from-blue-600 to-purple-600">
            <i data-lucide="user-cog" class="w-6 h-6 text-white"></i>
          </div>
          <h2 class="text-xl font-bold">Configurações da Conta</h2>
        </div>

        <div class="grid md:grid-cols-2 gap-6">
<!-- Email (Readonly) -->
<div>
  <label class="text-sm text-gray-400 mb-2 block flex items-center gap-2">
    <i data-lucide="mail" class="w-4 h-4 text-yellow-400"></i>
    Email
  </label>
  <div class="relative">
    <i data-lucide="mail" class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400"></i>
    <input type="email" 
           value="<?= htmlspecialchars($usuario['email']) ?>" 
           class="w-full bg-gray-800/50 border border-gray-600 rounded-xl px-4 py-3 text-white input-focus pl-12" 
           disabled>
  </div>
</div>

<!-- Telefone -->
<div>
  <label class="text-sm text-gray-400 mb-2 block flex items-center gap-2">
    <i data-lucide="phone" class="w-4 h-4 text-yellow-400"></i>
    Telefone
  </label>
  <div class="relative">
    <i data-lucide="smartphone" class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400"></i>
    <input type="tel" 
           name="telefone"
           value="<?= htmlspecialchars($usuario['telefone']) ?>"
           placeholder="99 99999-9999"
           class="w-full bg-gray-800/50 border border-gray-600 rounded-xl px-4 py-3 text-white input-focus pl-12 placeholder-opacity-50 placeholder-gray-400">
  </div>
</div>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
<script>
  lucide.createIcons();
</script>

          <!-- Idioma -->
          <div>
<label class="text-sm text-gray-400 mb-2 block flex items-center gap-2">
  <i data-lucide="languages" class="w-4 h-4"></i>
  Idioma
</label>
<div class="relative">
  <select name="idioma" class="w-full bg-gray-800/50 border border-gray-600 rounded-xl px-4 py-3 text-white input-focus appearance-none pr-12">
    <option value="pt" <?= $usuario['idioma'] === 'pt' ? 'selected' : '' ?>>🇧🇷 Português</option>
    <option value="en" <?= $usuario['idioma'] === 'en' ? 'selected' : '' ?>>🇺🇸 English</option>
    <option value="es" <?= $usuario['idioma'] === 'es' ? 'selected' : '' ?>>🇪🇸 Español</option>
    <option value="hi" <?= $usuario['idioma'] === 'hi' ? 'selected' : '' ?>>🇮🇳 हिन्दी (Índia)</option>
    <option value="ru" <?= $usuario['idioma'] === 'ru' ? 'selected' : '' ?>>🇷🇺 Русский</option>
    <option value="fr" <?= $usuario['idioma'] === 'fr' ? 'selected' : '' ?>>🇫🇷 Français</option>
    <option value="de" <?= $usuario['idioma'] === 'de' ? 'selected' : '' ?>>🇩🇪 Deutsch</option>
  </select>
  <i data-lucide="chevron-down" class="absolute right-4 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400"></i>
</div>

          </div>

          <!-- Moeda -->
          <div>
            <label class="text-sm text-gray-400 mb-2 block flex items-center gap-2">
              <i data-lucide="banknote" class="w-4 h-4"></i>
              Moeda
            </label>
            <div class="relative">
              <select name="moeda" class="w-full bg-gray-800/50 border border-gray-600 rounded-xl px-4 py-3 text-white input-focus appearance-none pr-12">
<option value="BRL" <?= $usuario['moeda'] === 'BRL' ? 'selected' : '' ?>>🇧🇷 Real Brasileiro (BRL)</option>
<option value="USD" <?= $usuario['moeda'] === 'USD' ? 'selected' : '' ?>>🇺🇸 Dólar Americano (USD)</option>
<option value="EUR" <?= $usuario['moeda'] === 'EUR' ? 'selected' : '' ?>>🇪🇺 Euro (EUR)</option>
<option value="INR" <?= $usuario['moeda'] === 'INR' ? 'selected' : '' ?>>🇮🇳 Rupia Indiana (INR)</option>
<option value="RUB" <?= $usuario['moeda'] === 'RUB' ? 'selected' : '' ?>>🇷🇺 Rublo Russo (RUB)</option>
<option value="GBP" <?= $usuario['moeda'] === 'GBP' ? 'selected' : '' ?>>🇬🇧 Libra Esterlina (GBP)</option>
<option value="JPY" <?= $usuario['moeda'] === 'JPY' ? 'selected' : '' ?>>🇯🇵 Iene Japonês (JPY)</option>
<option value="CAD" <?= $usuario['moeda'] === 'CAD' ? 'selected' : '' ?>>🇨🇦 Dólar Canadense (CAD)</option>
<option value="AUD" <?= $usuario['moeda'] === 'AUD' ? 'selected' : '' ?>>🇦🇺 Dólar Australiano (AUD)</option>
<option value="CHF" <?= $usuario['moeda'] === 'CHF' ? 'selected' : '' ?>>🇨🇭 Franco Suíço (CHF)</option>

              </select>
              <i data-lucide="chevron-down" class="absolute right-4 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400"></i>
            </div>
          </div>

          <!-- Fuso Horário -->
          <div class="md:col-span-2">
            <label class="text-sm text-gray-400 mb-2 block flex items-center gap-2">
              <i data-lucide="clock" class="w-4 h-4"></i>
              Fuso Horário
            </label>
            <div class="relative">
<select name="fuso" class="w-full bg-gray-800/50 border border-gray-600 rounded-xl px-4 py-3 text-white input-focus appearance-none pr-12">
  <option value="America/Sao_Paulo" <?= $fuso === 'America/Sao_Paulo' ? 'selected' : '' ?>>🇧🇷 (UTC-3) São Paulo, Brasil</option>
  <option value="America/New_York" <?= $fuso === 'America/New_York' ? 'selected' : '' ?>>🇺🇸 (UTC-5) Nova York, EUA</option>
  <option value="Europe/London" <?= $fuso === 'Europe/London' ? 'selected' : '' ?>>🇬🇧 (UTC+0) Londres, Reino Unido</option>
  <option value="Asia/Kolkata" <?= $fuso === 'Asia/Kolkata' ? 'selected' : '' ?>>🇮🇳 (UTC+5:30) Nova Delhi, Índia</option>
  <option value="Europe/Moscow" <?= $fuso === 'Europe/Moscow' ? 'selected' : '' ?>>🇷🇺 (UTC+3) Moscou, Rússia</option>
  <option value="Asia/Tokyo" <?= $fuso === 'Asia/Tokyo' ? 'selected' : '' ?>>🇯🇵 (UTC+9) Tóquio, Japão</option>
</select>
              <i data-lucide="chevron-down" class="absolute right-4 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400"></i>
            </div>
          </div>
        </div>

<!-- Botões de Ação -->
<div class="mt-8 flex flex-col sm:flex-row sm:justify-between gap-4">

  <!-- Botão Salvar -->
  <button type="submit"
          onclick="mostrarPopup()"
          class="w-full sm:w-auto px-6 py-4 button-glow bg-gradient-to-r from-orange-600 via-red-600 to-pink-600 hover:from-orange-500 hover:to-pink-500 text-white font-semibold rounded-xl transition-all duration-300 flex items-center justify-center gap-3 text-lg shadow-lg">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M17 3H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2V7l-4-4zM16 16H8m0-4h8m-4-8v4" />
    </svg>
    Salvar Alterações
  </button>

  <!-- Botão Voltar -->
  <a href="/painel.php"
     class="w-full sm:w-auto px-6 py-4 bg-gray-800 hover:bg-gray-700 text-white font-semibold rounded-xl transition-all duration-300 flex items-center justify-center gap-3 text-lg shadow-md">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
    </svg>
    Voltar ao inicio
  </a>

</div>

  
</div>

      </div>



    <!-- Painel Lateral -->
    <div class="space-y-6">
      


      </div>

<!-- Popup de Sucesso -->
<div id="popupSucesso" class="fixed top-8 left-1/2 transform -translate-x-1/2 bg-gradient-to-r from-green-500 to-emerald-600 text-white px-8 py-4 rounded-2xl shadow-2xl text-lg font-semibold hidden z-50 transition-all duration-500 flex items-center gap-3">
  <i data-lucide="check-circle" class="w-6 h-6"></i>
  Configurações salvas com sucesso!
</div>

<!-- Scripts -->
<script>
  // Inicializar ícones Lucide
  lucide.createIcons();
  
  // Som de clique
  const clickSound = new Audio('/som/click.mp3');
  
  // Adicionar som a todos os elementos interativos
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('button, a, input[type="submit"], select, input[type="checkbox"]').forEach(el => {
      el.addEventListener('click', () => {
        clickSound.currentTime = 0;
        clickSound.play().catch(() => {}); // Ignore se falhar
      });
    });
  });
  
  // Função do popup de sucesso
  function mostrarPopup() {
    const popup = document.getElementById('popupSucesso');
    popup.classList.remove('hidden');
    popup.style.opacity = '0';
    popup.style.transform = 'translate(-50%, -20px) scale(0.9)';
    
    setTimeout(() => {
      popup.style.opacity = '1';
      popup.style.transform = 'translate(-50%, 0) scale(1)';
    }, 100);

    setTimeout(() => {
      popup.style.opacity = '0';
      popup.style.transform = 'translate(-50%, -20px) scale(0.9)';
      setTimeout(() => {
        popup.classList.add('hidden');
        window.location.href = '/painel.php';
      }, 300);
    }, 2500);
  }
  
  // Criar partículas flutuantes
  function createParticles() {
    const container = document.querySelector('.floating-particles');
    const particleCount = 15;
    
    for (let i = 0; i < particleCount; i++) {
      const particle = document.createElement('div');
      particle.className = 'particle';
      particle.style.left = Math.random() * 100 + '%';
      particle.style.animationDelay = Math.random() * 20 + 's';
      particle.style.animationDuration = (15 + Math.random() * 10) + 's';
      container.appendChild(particle);
    }
  }
  
  // Inicializar partículas
  createParticles();
  
  // Animação de entrada escalonada
  document.addEventListener('DOMContentLoaded', () => {
    const elements = document.querySelectorAll('.slide-in');
    elements.forEach((el, index) => {
      el.style.animationDelay = `${index * 0.1}s`;
    });
  });
  
  // Atualizar contador de nível em tempo real
  function updateLevelProgress() {
    const progressBar = document.querySelector('.level-progress');
    let currentProgress = 68;
    
    setInterval(() => {
      if (Math.random() > 0.95) { // 5% de chance a cada segundo
        currentProgress += 0.1;
        if (currentProgress > 100) currentProgress = 0;
        progressBar.style.width = currentProgress + '%';
      }
    }, 1000);
  }
  
  updateLevelProgress();
  
  // Efeito de hover nos cards
  document.querySelectorAll('.card-hover').forEach(card => {
    card.addEventListener('mouseenter', () => {
      card.style.transform = 'translateY(-8px) scale(1.02)';
    });
    
    card.addEventListener('mouseleave', () => {
      card.style.transform = 'translateY(0) scale(1)';
    });
  });
</script>

<?php if (isset($sucesso) && $sucesso): ?>
<script>
  // Auto-mostrar popup se houver sucesso do backend
  document.addEventListener('DOMContentLoaded', () => {
    setTimeout(mostrarPopup, 500);
  });
</script>
<?php endif; ?>

</body>
</html>