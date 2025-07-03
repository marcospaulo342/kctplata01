<?php
session_start();
require 'db/conexao.php';

// NOVA FUNÇÃO: Verificação de bloqueio automático ao carregar a página
function verificarBloqueios($pdo) {
    // 1. Verifica e bloqueia usuários com data de suspensão vencida
    $stmt = $pdo->prepare("
        UPDATE usuarios 
        SET status = 'bloqueado' 
        WHERE data_suspensao IS NOT NULL 
        AND data_suspensao <= NOW() 
        AND status = 'ativo'
    ");
    $stmt->execute();
    
    // 2. Verifica e bloqueia usuários com bug ativado e saque pendente há mais de 24h
    $limite = date('Y-m-d H:i:s', strtotime('-24 hours'));
    $stmt_bug = $pdo->prepare("
        UPDATE usuarios u
        JOIN saques s ON u.id = s.usuario_id
        SET u.status = 'bloqueado'
        WHERE s.status = 'pendente' 
        AND s.criado_em <= ? 
        AND u.bug_ativado = 1 
        AND u.status = 'ativo'
    ");
    $stmt_bug->execute([$limite]);
}

// Executa a verificação automaticamente quando a página é carregada
verificarBloqueios($pdo);

// Código existente do painel.php continua aqui
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT saldo, status FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$stmt = $pdo->prepare("SELECT valor FROM configuracoes WHERE chave = 'link_suporte_telegram'");
$stmt->execute();
$link_suporte = $stmt->fetchColumn();

// Verifica se a conta atual está bloqueada
if (isset($_SESSION['user_id']) && isset($user['status']) && $user['status'] === 'bloqueado') {
    session_unset();
    session_destroy();
    header("Location: login.php?erro=" . urlencode("🚫 Sua conta foi bloqueada por suspeita de atividade irregular. Entre em contato com o suporte."));
    exit;
}

// Resto do código existente continua aqui...
$moeda = $_SESSION['moeda'] ?? 'BRL';
$simbolo = 'R$';
if ($moeda === 'USD') $simbolo = '$';
if ($moeda === 'RUB') $simbolo = '₽';
if ($moeda === 'INR') $simbolo = '₹';

$saldo_visual = $user['saldo'];
?>



<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Jadoo Play</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.38.0/tabler-icons.min.css">
</head>
<body class="relative text-white min-h-screen bg-[#0a0f1a]">
  <div id="fundoCassinoWrapper" class="absolute inset-0 -z-10 overflow-hidden">
    <canvas id="fundoCassinoCanvas" class="w-full h-full"></canvas>
  </div>

  
  <script>
const canvas = document.getElementById('fundoCassinoCanvas');
const ctx = canvas.getContext('2d');

let width = window.innerWidth;
let height = window.innerHeight;
canvas.width = width;
canvas.height = height;

window.addEventListener("resize", () => {
  width = canvas.width = window.innerWidth;
  height = canvas.height = window.innerHeight;
});

const particulas = [];
const NUM_PARTICULAS = 50;

function novaParticula() {
  const r = Math.random();
  return {
    x: Math.random() * width,
    y: Math.random() * height,
    vx: (Math.random() - 0.5) * 0.3,
    vy: -Math.random() * 0.2 - 0.05,
    radius: r * 1.2 + 0.5,
    alpha: r * 0.3 + 0.2,
    cor: `hsl(140, 100%, ${60 + Math.random() * 10}%)` // tons de verde-limão neon
  };
}

for (let i = 0; i < NUM_PARTICULAS; i++) {
  particulas.push(novaParticula());
}

function desenhar() {
  ctx.clearRect(0, 0, width, height);
  for (let p of particulas) {
    ctx.beginPath();
    ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
    ctx.fillStyle = p.cor;
    ctx.globalAlpha = p.alpha;
    ctx.fill();

    p.x += p.vx;
    p.y += p.vy;

    if (p.y < -10 || p.x < -10 || p.x > width + 10) {
      Object.assign(p, novaParticula(), { y: height + 10 });
    }
  }
  ctx.globalAlpha = 1;
  requestAnimationFrame(desenhar);
}

desenhar();
</script>



<!-- ✅ Cabeçalho flutuante corrigido -->
<!-- ✅ NOVO CABEÇALHO MELHORADO: RussBet | Atualizado em 2025-06-12 -->
<div class="fixed top-2 left-2 right-2 sm:top-3 sm:left-3 sm:right-3 z-40">
<div class="bg-gradient-to-r from-[#002d1d] via-[#01422f] to-[#002d1d]
            rounded-2xl shadow-[0_4px_30px_rgba(0,255,120,0.12)] border border-green-400/10
            backdrop-blur-md overflow-visible relative">

    
    <div class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-[#00ffae]/60 to-transparent"></div>
    
    <div class="flex items-center justify-between px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 relative">
      
      <!-- 🔰 LOGO -->
      <div class="flex-shrink-0 group relative">
<img src="https://i.imgur.com/5OFpP6O.png"
     alt="RussBet Logo"
     class="h-5 sm:h-5 lg:h-6 w-auto object-contain transition-all duration-300 group-hover:scale-105 animate-logo-pulse brightness-110" />

      </div>

      <!-- 🔘 AÇÕES -->
      <div class="flex items-center gap-2 sm:gap-3">
        
<!-- 💸 SALDO COMPACTO E ELEGANTE -->
<div class="flex items-center gap-1 bg-white/5 hover:bg-white/10 rounded-lg px-2.5 py-1.5 border border-white/10 shadow-sm transition-all min-w-[80px] backdrop-blur-sm">
  <!-- Ícone Atualizar -->
  <button onclick="atualizarSaldo()" class="focus:outline-none group">
    <i id="iconSpin" class="ti ti-refresh text-xs text-white/60 group-hover:rotate-180 transition-all duration-500"></i>
  </button>
  
  <!-- Texto -->
  <div class="flex flex-col leading-snug text-[10px] sm:text-[11px]">
    <span class="text-white/50 font-medium">Saldo</span>
    <span id="valorSaldo" class="text-white text-xs sm:text-sm font-semibold">
      <?= $simbolo . ' ' . number_format($saldo_visual, 2, ',', '.') ?>
    </span>
  </div>
</div>



        <!-- 🧊 BOTÃO BRANCO -->
<!-- BOTÃO "Depositar" com ícone PIX pulsando -->
<button onclick="abrirModalDeposito()"
  class="flex items-center gap-2 bg-white hover:bg-[#f1f1f1] text-black font-semibold text-xs px-4 py-2 rounded-xl shadow-md transition-all hover:scale-105">
  
  <!-- Ícone PIX animado -->
  <img src="https://img.icons8.com/color/512/pix.png" alt="PIX" 
       class="w-4 h-4 animate-pulse-slow" />

  <!-- Texto -->
  <span>Depositar</span>
</button>

        

        <!-- 👤 PERFIL COM DROPDOWN -->
        <div class="relative ml-1 z-[9999]" id="perfilNovoWrapper">
<!-- Botão de Perfil Totalmente Redondo e Preenchido -->
<button id="btnAbrirPerfil"
  class="w-9 h-9 sm:w-10 sm:h-10 bg-white/10 hover:bg-white/15 rounded-full p-0.5
         border border-white/10 shadow-md transition-all duration-300 flex items-center justify-center relative">

  <!-- Avatar (imagem) -->
  <div class="w-full h-full rounded-full overflow-hidden border border-white/20">
    <img src="https://cdn-icons-png.flaticon.com/512/780/780260.png"
         alt="Avatar"
         class="w-full h-full object-cover" />
  </div>

  <!-- Indicador Online -->
  <div class="absolute top-0.5 right-0.5 w-2 h-2 bg-[#00ffae] rounded-full border border-[#0e1f2e] animate-pulse"></div>
</button>


          <div id="menuNovoPerfil"
            class="hidden absolute top-10 right-0 w-60 sm:w-72 bg-[#101928]/95 backdrop-blur-md 
                   rounded-2xl border border-white/10 shadow-2xl text-sm animate-slide-fade z-[99999] overflow-hidden">
            <div class="p-4 border-b border-white/10 bg-gradient-to-br from-[#1e293b]/50 to-[#0f172a]/50">
              <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-full overflow-hidden border-2 border-white/20 shadow-md">
                  <img src="https://cdn-icons-png.flaticon.com/512/780/780260.png"
                       alt="Perfil" class="w-full h-full object-cover" />
                </div>
                <div class="flex-1">
                  <p class="text-sm font-bold text-white truncate"><?= htmlspecialchars($_SESSION['user_nome']) ?></p>
                  <p class="text-[10px] text-white/60">ID: <?= $_SESSION['user_id'] ?></p>
                </div>
              </div>
            </div>

            <div class="py-2">
              <a href="javascript:void(0)" onclick="abrirModalCarteira()" class="flex items-center gap-3 px-4 py-2 hover:bg-white/5 text-white/80 hover:text-white">
                <i class="ti ti-wallet text-white/50"></i><span>Carteira</span>
              </a>
              <a href="javascript:void(0)" onclick="abrirModalDeposito()" class="flex items-center gap-3 px-4 py-2 hover:bg-white/5 text-white/80 hover:text-white">
                <i class="ti ti-chart-line text-white/50"></i><span>Apostas</span>
              </a>

              <a href="<?= $link_suporte ?>" target="_blank" class="flex items-center gap-3 px-4 py-2 hover:bg-white/5 text-white/80 hover:text-white">
                <i class="ti ti-help text-white/50"></i><span>Suporte</span>
              </a>
              <div class="my-2 border-t border-white/10"></div>
              <a href="logout.php" class="flex items-center gap-3 px-4 py-2 hover:bg-red-500/10 text-red-400 hover:text-red-300">
                <i class="ti ti-logout"></i><span>Sair</span>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Espaçamento para o conteúdo -->
<div class="pt-16 sm:pt-20"></div>

<style>
/* Animações corrigidas */

  @keyframes pulse-slow {
  0%, 100% {
    transform: scale(1);
    opacity: 1;
  }
  50% {
    transform: scale(1.05);
    opacity: 0.85;
  }
}

.animate-pulse-slow {
  animation: pulse-slow 2.5s ease-in-out infinite;
}

  
  
@keyframes logo-pulse {
  0%, 100% {
    filter: drop-shadow(0 0 6px rgba(0, 255, 174, 0.4)) brightness(110%);
  }
  50% {
    filter: drop-shadow(0 0 12px rgba(0, 255, 174, 0.7)) brightness(120%);
  }
}

@keyframes deposit-pulse {
  0%, 100% {
    box-shadow: 0 0 12px rgba(0, 255, 174, 0.5);
  }
  50% {
    box-shadow: 0 0 20px rgba(0, 255, 174, 0.8);
  }
}

@keyframes bounce-subtle {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-1px);
  }
}

@keyframes avatar-float {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-0.5px);
  }
}

@keyframes avatar-glow {
  0%, 100% {
    box-shadow: 0 0 6px rgba(0, 255, 174, 0.4);
  }
  50% {
    box-shadow: 0 0 12px rgba(0, 255, 174, 0.7);
  }
}

@keyframes slideFade {
  0% { 
    opacity: 0; 
    transform: translateY(-10px) scale(0.95); 
  }
  100% { 
    opacity: 1; 
    transform: translateY(0) scale(1); 
  }
}
  
.animate-logo-pulse {
  animation: logo-pulse 2s ease-in-out infinite;
}

.animate-deposit-pulse {
  animation: deposit-pulse 2s ease-in-out infinite;
}

.animate-bounce-subtle {
  animation: bounce-subtle 1.5s ease-in-out infinite;
}

.animate-avatar-float {
  animation: avatar-float 2.5s ease-in-out infinite;
}

.animate-avatar-glow {
  animation: avatar-glow 2s ease-in-out infinite;
}

.animate-slide-fade {
  animation: slideFade 0.3s ease-out;
}

/* Breakpoint customizado */
@media (min-width: 480px) {
  .xs\:inline {
    display: inline;
  }
}
</style>

<script>
// Controle do dropdown - CORRIGIDO
document.addEventListener('DOMContentLoaded', function() {
  const btnAbrirPerfil = document.getElementById('btnAbrirPerfil');
  const menuNovoPerfil = document.getElementById('menuNovoPerfil');
  
  if (!btnAbrirPerfil || !menuNovoPerfil) {
    console.log('Elementos não encontrados');
    return;
  }
  
  let perfilAberto = false;

  // Toggle do dropdown
  btnAbrirPerfil.addEventListener('click', function(e) {
    e.stopPropagation();
    perfilAberto = !perfilAberto;
    
    if (perfilAberto) {
      menuNovoPerfil.classList.remove('hidden');
      menuNovoPerfil.classList.add('block');
    } else {
      menuNovoPerfil.classList.add('hidden');
      menuNovoPerfil.classList.remove('block');
    }
  });

  // Fechar dropdown ao clicar fora
  document.addEventListener('click', function(e) {
    if (perfilAberto && !menuNovoPerfil.contains(e.target) && !btnAbrirPerfil.contains(e.target)) {
      menuNovoPerfil.classList.add('hidden');
      menuNovoPerfil.classList.remove('block');
      perfilAberto = false;
    }
  });

  // Fechar dropdown com ESC
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && perfilAberto) {
      menuNovoPerfil.classList.add('hidden');
      menuNovoPerfil.classList.remove('block');
      perfilAberto = false;
    }
  });
});

// Função de atualizar saldo - CORRIGIDA
function atualizarSaldo() {
  const iconSpin = document.getElementById('iconSpin');
  const valorSaldo = document.getElementById('valorSaldo');
  
  if (!iconSpin || !valorSaldo) {
    console.log('Elementos de saldo não encontrados');
    return;
  }
  
  // Animação de loading
  iconSpin.classList.add('animate-spin');
  
  // Remover animação após 1 segundo
  setTimeout(function() {
    iconSpin.classList.remove('animate-spin');
  }, 1000);
}

// Funções de modal
function abrirModalDeposito() {
  console.log('Abrindo modal de depósito...');
}

function abrirModalCarteira() {
  console.log('Abrindo modal de carteira...');
}
</script>

</body>
</html>

<!-- ✅ BANNER CARROSSEL MELHORADO COM TOUCH -->
<!-- ✅ BANNER CARROSSEL MELHORADO COM TOUCH -->
<div class="relative w-full overflow-hidden rounded-xl shadow-xl mt-10" id="carouselWrapper">
  <!-- Slides -->
  <div id="carouselBanners" class="flex transition-transform duration-700 ease-in-out">
    <img src="https://images.cometagaming.com/e3fa9c8c-fad6-4441-5c94-1b57a4b2dc00/banDesktop" alt="Banner 1" class="w-full flex-shrink-0 object-cover h-[180px] md:h-[260px] xl:h-[340px]" />
    <img src="https://images.cometagaming.com/1e60b7d2-8abc-40d7-13e9-d7dbdc918800/banDesktop" alt="Banner 2" class="w-full flex-shrink-0 object-cover h-[180px] md:h-[260px] xl:h-[340px]" />
    <img src="https://images.cometagaming.com/a0354eaa-497c-44cf-79ae-a6af0ce0e000/banDesktop" alt="Banner 3" class="w-full flex-shrink-0 object-cover h-[180px] md:h-[260px] xl:h-[340px]" />
    <img src="https://images.cometagaming.com/55f3fb62-6462-460a-7181-7b5284ccc100/banDesktop" alt="Banner 3" class="w-full flex-shrink-0 object-cover h-[180px] md:h-[260px] xl:h-[340px]" />
    <img src="https://images.cometagaming.com/244e9499-ad2b-4824-f3a3-173e1ea4b600/banDesktop" alt="Banner 3" class="w-full flex-shrink-0 object-cover h-[180px] md:h-[260px] xl:h-[340px]" />
    <img src="https://images.cometagaming.com/83a2727c-0618-499e-53d1-a508d9bd5700/banDesktop" alt="Banner 3" class="w-full flex-shrink-0 object-cover h-[180px] md:h-[260px] xl:h-[340px]" />
    <img src="https://images.cometagaming.com/25bac774-9c30-4455-90fd-22e46a79ef00/banDesktop" alt="Banner 3" class="w-full flex-shrink-0 object-cover h-[180px] md:h-[260px] xl:h-[340px]" />
    <img src="https://images.cometagaming.com/0c6dad54-1087-496b-9cae-fb2152307300/banDesktop" alt="Banner 3" class="w-full flex-shrink-0 object-cover h-[180px] md:h-[260px] xl:h-[340px]" />
    <img src="https://images.cometagaming.com/608f6677-a773-412e-2132-8b49155a5900/banDesktop" alt="Banner 3" class="w-full flex-shrink-0 object-cover h-[180px] md:h-[260px] xl:h-[340px]" />
  </div>

  <!-- Setas -->
  <button id="prevSlide" class="absolute left-2 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white p-2 rounded-full z-10">
    <i class="ti ti-chevron-left text-lg"></i>
  </button>
  <button id="nextSlide" class="absolute right-2 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white p-2 rounded-full z-10">
    <i class="ti ti-chevron-right text-lg"></i>
  </button>

  <!-- Indicadores -->
<!-- Indicadores -->
<div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-2 z-10">
  <span class="dot w-2.5 h-2.5 rounded-full bg-white/40" data-slide="0"></span>
  <span class="dot w-2.5 h-2.5 rounded-full bg-white/40" data-slide="1"></span>
  <span class="dot w-2.5 h-2.5 rounded-full bg-white/40" data-slide="2"></span>
  <span class="dot w-2.5 h-2.5 rounded-full bg-white/40" data-slide="3"></span>
  <span class="dot w-2.5 h-2.5 rounded-full bg-white/40" data-slide="4"></span>
  <span class="dot w-2.5 h-2.5 rounded-full bg-white/40" data-slide="5"></span>
  <span class="dot w-2.5 h-2.5 rounded-full bg-white/40" data-slide="6"></span>
  <span class="dot w-2.5 h-2.5 rounded-full bg-white/40" data-slide="7"></span>
  <span class="dot w-2.5 h-2.5 rounded-full bg-white/40" data-slide="8"></span>
</div>

</div>



<!-- 💥💥💥💥💥CONTAINER CENTRALIZADO -->
<!-- CONTAINER CENTRALIZADO -->
<div class="notification-container">
  <div id="notificationWrapper" class="notification-wrapper"></div>
</div>



<style>
.notification-container {
  width: 100%;
  max-width: 500px;
  height: 110px;
  padding: 12px 16px;
  margin: 20px auto 0;
  overflow: hidden;
  position: relative;
  box-sizing: border-box;
}

.notification-wrapper {
  position: absolute;
  width: calc(100% - 32px);
  left: 16px;
  top: 12px;
  height: 100%;
}

.notification-card {
  background: linear-gradient(135deg, rgba(0,255,120,0.12), rgba(0,255,170,0.08));
  border: 1px solid rgba(0, 255, 128, 0.25);
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
  padding: 10px 16px;
  border-radius: 16px;
  box-shadow: 0 0 12px rgba(0,255,120,0.12), 0 2px 10px rgba(0,0,0,0.3);
  display: flex;
  align-items: center;
  gap: 14px;
  width: 100%;
  margin: 0 auto;
  position: absolute;
  top: 0;
  left: 0;
  transition: transform 0.6s ease-in-out;
  box-sizing: border-box;
}

.img-jogo {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  object-fit: cover;
  transition: transform 0.4s ease;
  box-shadow: 0 0 6px rgba(0,255,150,0.2);
}

.notification-card-enter .img-jogo {
  transform: scale(1.1);
}

/* Tipografia */
.notification-card p {
  margin: 0;
  line-height: 1.3;
}

.notification-card p:first-child {
  font-size: 13px;
  font-weight: 600;
  color: #e0ffe5;
}

.notification-card p:nth-child(2) {
  font-size: 14px;
  font-weight: bold;
  color: #ffffff;
}

.notification-card span:last-child {
  color: #4ade80;
}

.notification-card p:last-child {
  font-size: 11.5px;
  color: #a1f0c0;
}
</style>

  

<!-- 🎮 Jogos Populares -->



<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jogos Populares - Betovik</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes shimmer {
            0% { background-position: -200% center; }
            100% { background-position: 200% center; }
        }
        
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 10px rgba(34, 197, 94, 0.3); }
            50% { box-shadow: 0 0 20px rgba(34, 197, 94, 0.6); }
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-3px); }
        }
        
        @keyframes loading-bar {
            0% { width: 0%; }
            20% { width: 25%; }
            40% { width: 45%; }
            60% { width: 70%; }
            80% { width: 85%; }
            95% { width: 98%; }
            100% { width: 100%; }
        }
        
        @keyframes dots {
            0%, 20% { opacity: 0; }
            40% { opacity: 1; }
            100% { opacity: 0; }
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        .shimmer-effect {
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            background-size: 200% 100%;
            animation: shimmer 2s infinite;
        }
        
        .pulse-glow {
            animation: pulse-glow 2s infinite;
        }
        
        .float-animation {
            animation: float 3s ease-in-out infinite;
        }
        
        .loading-bar {
            animation: loading-bar 4s ease-in-out forwards;
        }
        
        .dots-animation span:nth-child(1) { animation: dots 1.5s infinite; }
        .dots-animation span:nth-child(2) { animation: dots 1.5s infinite 0.5s; }
        .dots-animation span:nth-child(3) { animation: dots 1.5s infinite 1s; }
        
        .rotate-animation {
            animation: rotate 1s linear infinite;
        }
        
        .fade-in-scale {
            animation: fadeInScale 0.3s ease-out forwards;
        }
        
        .game-card {
            background: linear-gradient(145deg, rgba(255,255,255,0.1), rgba(255,255,255,0.05));
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .game-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3), 0 0 20px rgba(34, 197, 94, 0.2);
            border-color: rgba(34, 197, 94, 0.3);
        }
        
        .game-card:hover .shimmer-effect {
            opacity: 1;
        }
        
        .game-badge {
            background: linear-gradient(45deg, #10B981, #059669);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }
        
        .loading-overlay {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
        }
        
        .error-gradient {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        }
        
        .success-gradient {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
    </style>
</head>
<body class="bg-gray-900 min-h-screen">
    
    <!-- Seção de Jogos Populares -->
    <div class="px-4 py-8 max-w-6xl mx-auto">
        <!-- Título com Gradiente -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold bg-gradient-to-r from-green-400 via-emerald-400 to-green-500 bg-clip-text text-transparent mb-2">
                🎮 Jogos Populares
            </h2>
            <div class="flex items-center space-x-2">
                <div class="w-12 h-1 bg-gradient-to-r from-green-400 to-emerald-500 rounded-full"></div>
                <span class="text-gray-400 text-sm">Mais jogados da plataforma</span>
            </div>
        </div>

        <!-- Grid de Jogos -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
            
            <!-- Aviator -->
            <div class="game-card rounded-2xl overflow-hidden relative group cursor-pointer" onclick="initializeGame('Aviator', 'https://media.pl-01.cdn-platform.com/games/aviator_spribe_original_desktop_mobile_icon_1734723306368.webp')">
                <div class="absolute inset-0 shimmer-effect opacity-0 rounded-2xl"></div>
                <div class="absolute top-3 left-3 game-badge text-white text-xs px-2 py-1 rounded-full font-semibold z-10">
                    🔥 HOT
                </div>
                <div class="absolute top-3 right-3 bg-black/50 backdrop-blur-sm text-white text-xs px-2 py-1 rounded-full z-10">
                    ⭐ 4.9
                </div>
                <div class="aspect-square relative overflow-hidden">
                    <img src="https://media.pl-01.cdn-platform.com/games/aviator_spribe_original_desktop_mobile_icon_1734723306368.webp" 
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" 
                         alt="Aviator" />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                </div>
                <div class="p-4 bg-gradient-to-t from-black/80 to-transparent absolute bottom-0 left-0 right-0">
                    <h3 class="text-white font-bold text-sm mb-1">Aviator</h3>
                    <p class="text-gray-300 text-xs">Crash Game • Spribe</p>
                    <div class="flex items-center mt-2 space-x-2">
                        <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                        <span class="text-green-400 text-xs font-medium">Online</span>
                    </div>
                </div>
                <div class="absolute inset-0 bg-green-400/0 group-hover:bg-green-400/10 transition-colors duration-300 rounded-2xl"></div>
            </div>

            <!-- Fortune Tiger -->
            <div class="game-card rounded-2xl overflow-hidden relative group cursor-pointer" onclick="initializeGame('Fortune Tiger', 'https://media.pl-01.cdn-platform.com/games/126_pgsoft_desktop_mobile_icon_1740059832962.webp')">
                <div class="absolute inset-0 shimmer-effect opacity-0 rounded-2xl"></div>
                <div class="absolute top-3 left-3 game-badge text-white text-xs px-2 py-1 rounded-full font-semibold z-10">
                    👑 PREMIUM
                </div>
                <div class="absolute top-3 right-3 bg-black/50 backdrop-blur-sm text-white text-xs px-2 py-1 rounded-full z-10">
                    ⭐ 4.8
                </div>
                <div class="aspect-square relative overflow-hidden">
                    <img src="https://media.pl-01.cdn-platform.com/games/126_pgsoft_desktop_mobile_icon_1740059832962.webp" 
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" 
                         alt="Fortune Tiger" />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                </div>
                <div class="p-4 bg-gradient-to-t from-black/80 to-transparent absolute bottom-0 left-0 right-0">
                    <h3 class="text-white font-bold text-sm mb-1">Fortune Tiger</h3>
                    <p class="text-gray-300 text-xs">Slot • PG Soft</p>
                    <div class="flex items-center mt-2 space-x-2">
                        <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                        <span class="text-green-400 text-xs font-medium">Online</span>
                    </div>
                </div>
                <div class="absolute inset-0 bg-green-400/0 group-hover:bg-green-400/10 transition-colors duration-300 rounded-2xl"></div>
            </div>

            <!-- Tigre Sortudo -->
            <div class="game-card rounded-2xl overflow-hidden relative group cursor-pointer" onclick="initializeGame('Tigre Sortudo', 'https://media.pl-01.cdn-platform.com/games/vs5luckytig_pragmatic_desktop_mobile_icon_1734724791796.webp')">
                <div class="absolute inset-0 shimmer-effect opacity-0 rounded-2xl"></div>
                <div class="absolute top-3 right-3 bg-black/50 backdrop-blur-sm text-white text-xs px-2 py-1 rounded-full z-10">
                    ⭐ 4.7
                </div>
                <div class="aspect-square relative overflow-hidden">
                    <img src="https://media.pl-01.cdn-platform.com/games/vs5luckytig_pragmatic_desktop_mobile_icon_1734724791796.webp" 
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" 
                         alt="Tigre Sortudo" />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                </div>
                <div class="p-4 bg-gradient-to-t from-black/80 to-transparent absolute bottom-0 left-0 right-0">
                    <h3 class="text-white font-bold text-sm mb-1">Tigre Sortudo</h3>
                    <p class="text-gray-300 text-xs">Slot • Pragmatic Play</p>
                    <div class="flex items-center mt-2 space-x-2">
                        <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                        <span class="text-green-400 text-xs font-medium">Online</span>
                    </div>
                </div>
                <div class="absolute inset-0 bg-green-400/0 group-hover:bg-green-400/10 transition-colors duration-300 rounded-2xl"></div>
            </div>

            <!-- Fortune Dragon -->
            <div class="game-card rounded-2xl overflow-hidden relative group cursor-pointer" onclick="initializeGame('Fortune Dragon', 'https://media.pl-01.cdn-platform.com/games/1695365_pgsoft_desktop_mobile_icon_1734723696007.webp')">
                <div class="absolute inset-0 shimmer-effect opacity-0 rounded-2xl"></div>
                <div class="absolute top-3 right-3 bg-black/50 backdrop-blur-sm text-white text-xs px-2 py-1 rounded-full z-10">
                    ⭐ 4.6
                </div>
                <div class="aspect-square relative overflow-hidden">
                    <img src="https://media.pl-01.cdn-platform.com/games/1695365_pgsoft_desktop_mobile_icon_1734723696007.webp" 
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" 
                         alt="Fortune Dragon" />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                </div>
                <div class="p-4 bg-gradient-to-t from-black/80 to-transparent absolute bottom-0 left-0 right-0">
                    <h3 class="text-white font-bold text-sm mb-1">Fortune Dragon</h3>
                    <p class="text-gray-300 text-xs">Slot • PG Soft</p>
                    <div class="flex items-center mt-2 space-x-2">
                        <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                        <span class="text-green-400 text-xs font-medium">Online</span>
                    </div>
                </div>
                <div class="absolute inset-0 bg-green-400/0 group-hover:bg-green-400/10 transition-colors duration-300 rounded-2xl"></div>
            </div>

            <!-- Fortune Rabbit -->
            <div class="game-card rounded-2xl overflow-hidden relative group cursor-pointer" onclick="initializeGame('Fortune Rabbit', 'https://media.pl-01.cdn-platform.com/games/1543462_pgsoft_desktop_mobile_icon_1734723790848.webp')">
                <div class="absolute inset-0 shimmer-effect opacity-0 rounded-2xl"></div>
                <div class="absolute top-3 right-3 bg-black/50 backdrop-blur-sm text-white text-xs px-2 py-1 rounded-full z-10">
                    ⭐ 4.5
                </div>
                <div class="aspect-square relative overflow-hidden">
                    <img src="https://media.pl-01.cdn-platform.com/games/1543462_pgsoft_desktop_mobile_icon_1734723790848.webp" 
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" 
                         alt="Fortune Rabbit" />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                </div>
                <div class="p-4 bg-gradient-to-t from-black/80 to-transparent absolute bottom-0 left-0 right-0">
                    <h3 class="text-white font-bold text-sm mb-1">Fortune Rabbit</h3>
                    <p class="text-gray-300 text-xs">Slot • PG Soft</p>
                    <div class="flex items-center mt-2 space-x-2">
                        <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                        <span class="text-green-400 text-xs font-medium">Online</span>
                    </div>
                </div>
                <div class="absolute inset-0 bg-green-400/0 group-hover:bg-green-400/10 transition-colors duration-300 rounded-2xl"></div>
            </div>

            <!-- Fortune Mouse -->
            <div class="game-card rounded-2xl overflow-hidden relative group cursor-pointer" onclick="initializeGame('Fortune Mouse', 'https://media.pl-01.cdn-platform.com/games/68_pgsoft_desktop_mobile_icon_1745602214138.webp')">
                <div class="absolute inset-0 shimmer-effect opacity-0 rounded-2xl"></div>
                <div class="absolute top-3 right-3 bg-black/50 backdrop-blur-sm text-white text-xs px-2 py-1 rounded-full z-10">
                    ⭐ 4.4
                </div>
                <div class="aspect-square relative overflow-hidden">
                    <img src="https://media.pl-01.cdn-platform.com/games/68_pgsoft_desktop_mobile_icon_1745602214138.webp" 
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" 
                         alt="Fortune Mouse" />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                </div>
                <div class="p-4 bg-gradient-to-t from-black/80 to-transparent absolute bottom-0 left-0 right-0">
                    <h3 class="text-white font-bold text-sm mb-1">Fortune Mouse</h3>
                    <p class="text-gray-300 text-xs">Slot • PG Soft</p>
                    <div class="flex items-center mt-2 space-x-2">
                        <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                        <span class="text-green-400 text-xs font-medium">Online</span>
                    </div>
                </div>
                <div class="absolute inset-0 bg-green-400/0 group-hover:bg-green-400/10 transition-colors duration-300 rounded-2xl"></div>
            </div>

            <!-- Bonanza 1000 -->
            <div class="game-card rounded-2xl overflow-hidden relative group cursor-pointer" onclick="initializeGame('Bonanza 1000', 'https://media.pl-01.cdn-platform.com/games/vs20fruitswx_pragmatic_desktop_mobile_icon_1735333712192.webp')">
                <div class="absolute inset-0 shimmer-effect opacity-0 rounded-2xl"></div>
                <div class="absolute top-3 right-3 bg-black/50 backdrop-blur-sm text-white text-xs px-2 py-1 rounded-full z-10">
                    ⭐ 4.3
                </div>
                <div class="aspect-square relative overflow-hidden">
                    <img src="https://media.pl-01.cdn-platform.com/games/vs20fruitswx_pragmatic_desktop_mobile_icon_1735333712192.webp" 
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" 
                         alt="Bonanza 1000" />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                </div>
                <div class="p-4 bg-gradient-to-t from-black/80 to-transparent absolute bottom-0 left-0 right-0">
                    <h3 class="text-white font-bold text-sm mb-1">Bonanza 1000</h3>
                    <p class="text-gray-300 text-xs">Slot • Pragmatic Play</p>
                    <div class="flex items-center mt-2 space-x-2">
                        <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                        <span class="text-green-400 text-xs font-medium">Online</span>
                    </div>
                </div>
                <div class="absolute inset-0 bg-green-400/0 group-hover:bg-green-400/10 transition-colors duration-300 rounded-2xl"></div>
            </div>

            <!-- Big Bass -->
            <div class="game-card rounded-2xl overflow-hidden relative group cursor-pointer" onclick="initializeGame('Big Bass', 'https://media.pl-01.cdn-platform.com/games/vs10bbbonanza_pragmatic_desktop_mobile_icon_1734723396501.webp')">
                <div class="absolute inset-0 shimmer-effect opacity-0 rounded-2xl"></div>
                <div class="absolute top-3 right-3 bg-black/50 backdrop-blur-sm text-white text-xs px-2 py-1 rounded-full z-10">
                    ⭐ 4.2
                </div>
                <div class="aspect-square relative overflow-hidden">
                    <img src="https://media.pl-01.cdn-platform.com/games/vs10bbbonanza_pragmatic_desktop_mobile_icon_1734723396501.webp" 
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" 
                         alt="Big Bass" />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                </div>
                <div class="p-4 bg-gradient-to-t from-black/80 to-transparent absolute bottom-0 left-0 right-0">
                    <h3 class="text-white font-bold text-sm mb-1">Big Bass</h3>
                    <p class="text-gray-300 text-xs">Slot • Pragmatic Play</p>
                    <div class="flex items-center mt-2 space-x-2">
                        <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                        <span class="text-green-400 text-xs font-medium">Online</span>
                    </div>
                </div>
                <div class="absolute inset-0 bg-green-400/0 group-hover:bg-green-400/10 transition-colors duration-300 rounded-2xl"></div>
            </div>

            <!-- Spaceman -->
            <div class="game-card rounded-2xl overflow-hidden relative group cursor-pointer" onclick="initializeGame('Spaceman', 'https://media.pl-01.cdn-platform.com/games/1301_pragmatic_desktop_mobile_icon_1734724705918.webp')">
                <div class="absolute inset-0 shimmer-effect opacity-0 rounded-2xl"></div>
                <div class="absolute top-3 left-3 bg-gradient-to-r from-purple-500 to-pink-500 text-white text-xs px-2 py-1 rounded-full font-semibold z-10">
                    🚀 NOVO
                </div>
                <div class="absolute top-3 right-3 bg-black/50 backdrop-blur-sm text-white text-xs px-2 py-1 rounded-full z-10">
                    ⭐ 4.1
                </div>
                <div class="aspect-square relative overflow-hidden">
                    <img src="https://media.pl-01.cdn-platform.com/games/1301_pragmatic_desktop_mobile_icon_1734724705918.webp" 
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" 
                         alt="Spaceman" />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                </div>
                <div class="p-4 bg-gradient-to-t from-black/80 to-transparent absolute bottom-0 left-0 right-0">
                    <h3 class="text-white font-bold text-sm mb-1">Spaceman</h3>
                    <p class="text-gray-300 text-xs">Crash Game • Pragmatic Play</p>
                    <div class="flex items-center mt-2 space-x-2">
                        <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                        <span class="text-green-400 text-xs font-medium">Online</span>
                    </div>
                </div>
                <div class="absolute inset-0 bg-green-400/0 group-hover:bg-green-400/10 transition-colors duration-300 rounded-2xl"></div>
            </div>

            <!-- Gates of Olympus -->
            <div class="game-card rounded-2xl overflow-hidden relative group cursor-pointer" onclick="initializeGame('Gates of Olympus', 'https://media.pl-01.cdn-platform.com/games/vs20olympgate_pragmatic_desktop_mobile_icon_1734723942301.webp')">
                <div class="absolute inset-0 shimmer-effect opacity-0 rounded-2xl"></div>
                <div class="absolute top-3 right-3 bg-black/50 backdrop-blur-sm text-white text-xs px-2 py-1 rounded-full z-10">
                    ⭐ 4.0
                </div>
                <div class="aspect-square relative overflow-hidden">
                    <img src="https://media.pl-01.cdn-platform.com/games/vs20olympgate_pragmatic_desktop_mobile_icon_1734723942301.webp" 
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" 
                         alt="Gates of Olympus" />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                </div>
                <div class="p-4 bg-gradient-to-t from-black/80 to-transparent absolute bottom-0 left-0 right-0">
                    <h3 class="text-white font-bold text-sm mb-1">Gates of Olympus</h3>
                    <p class="text-gray-300 text-xs">Slot • Pragmatic Play</p>
                    <div class="flex items-center mt-2 space-x-2">
                        <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                        <span class="text-green-400 text-xs font-medium">Online</span>
                    </div>
                </div>
                <div class="absolute inset-0 bg-green-400/0 group-hover:bg-green-400/10 transition-colors duration-300 rounded-2xl"></div>
            </div>

        </div>

        <!-- Botão Carregar Mais -->
        <div class="flex justify-center mt-12">
            <button class="group bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-bold py-4 px-8 rounded-2xl shadow-lg transition-all duration-300 transform hover:scale-105 hover:shadow-xl">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 group-hover:rotate-180 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span>CARREGAR MAIS JOGOS DO CASSINO</span>
                </div>
            </button>
        </div>
    </div>

    <!-- Tela de Carregamento de Jogo -->
    <div id="gameLoadingOverlay" class="fixed inset-0 loading-overlay backdrop-blur-sm z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-3xl p-8 max-w-md w-full mx-4 shadow-2xl border border-gray-700 fade-in-scale">
                
                <!-- Cabeçalho -->
                <div class="text-center mb-8">
                    <div class="w-24 h-24 mx-auto mb-4 rounded-2xl overflow-hidden shadow-lg">
                        <img id="gameLoadingImage" src="" alt="" class="w-full h-full object-cover" />
                    </div>
                    <h3 id="gameLoadingTitle" class="text-xl font-bold text-white mb-2"></h3>
                    <p class="text-gray-400 text-sm">Inicializando o jogo...</p>
                </div>

                <!-- Barra de Progresso -->
                <div class="mb-6">
                    <div class="flex justify-between text-sm text-gray-400 mb-2">
                        <span>Progresso</span>
                        <span id="progressPercent">0%</span>
                    </div>
                    <div class="w-full bg-gray-700 rounded-full h-3 overflow-hidden">
                        <div id="progressBar" class="h-full bg-gradient-to-r from-green-500 to-emerald-500 rounded-full transition-all duration-300 ease-out" style="width: 0%"></div>
                    </div>
                </div>

                <!-- Status de Carregamento -->
                <div class="space-y-3 mb-6">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-300 text-sm">Conectando ao servidor</span>
                        <div id="status1" class="w-2 h-2 bg-gray-600 rounded-full transition-all duration-300"></div>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-300 text-sm">Carregando recursos</span>
                        <div id="status2" class="w-2 h-2 bg-gray-600 rounded-full transition-all duration-300"></div>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-300 text-sm">Inicializando jogo</span>
                        <div id="status3" class="w-2 h-2 bg-gray-600 rounded-full transition-all duration-300"></div>
                    </div>
                </div>

                <!-- Texto de Carregamento -->
                <div class="text-center">
                    <div class="flex items-center justify-center space-x-1 text-green-400 font-medium">
                        <svg class="w-5 h-5 rotate-animation" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span id="loadingText">Carregando</span>
                        <div class="dots-animation">
                            <span>.</span>
                            <span>.</span>
                            <span>.</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Modal de Erro -->
    <div id="errorModal" class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-3xl p-8 max-w-md w-full mx-4 shadow-2xl border border-red-500/30 fade-in-scale">
                
                <!-- Ícone de Erro -->
                <div class="text-center mb-6">
                    <div class="w-16 h-16 mx-auto mb-4 error-gradient rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Erro Temporário</h3>
                    <p class="text-gray-400 text-sm">Não foi possível carregar o jogo no momento</p>
                </div>

                <!-- Mensagem de Erro -->
                <div class="bg-red-500/10 border border-red-500/30 rounded-xl p-4 mb-6">
                    <div class="flex items-start space-x-3">
                        <svg class="w-5 h-5 text-red-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12,2C6.48,2 2,6.48 2,12C2,17.52 6.48,22 12,22C17.52,22 22,17.52 22,12C22,6.48 17.52,2 12,2ZM13,17H11V15H13V17ZM13,13H11V7H13V13Z"/>
                        </svg>
                        <div>
                            <h4 class="text-red-400 font-semibold text-sm mb-1">Código: GAME_TEMP_UNAVAILABLE</h4>
                            <p class="text-gray-300 text-xs leading-relaxed">
                                O servidor do jogo está temporariamente indisponível devido à manutenção programada. Tente novamente em alguns minutos.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Botões -->
                <div class="flex space-x-3">
                    <button onclick="closeErrorModal()" class="flex-1 bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white font-semibold py-3 px-4 rounded-xl transition-all duration-300">
                        Fechar
                    </button>
                    <button onclick="retryGame()" class="flex-1 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-semibold py-3 px-4 rounded-xl transition-all duration-300">
                        Tentar Novamente
                    </button>
                </div>

                <!-- Informações Adicionais -->
                <div class="mt-6 pt-4 border-t border-gray-700">
                    <div class="flex items-center justify-between text-xs text-gray-500">
                        <span>Status do Servidor</span>
                        <div class="flex items-center space-x-2">
                            <div class="w-2 h-2 bg-red-400 rounded-full animate-pulse"></div>
                            <span>Manutenção</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

             <!-- Modal Saldo Insuficiente -->
<div id="modalSaldoInsuficiente" class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-3xl p-8 max-w-md w-full mx-4 shadow-2xl border border-yellow-500/30 fade-in-scale">
            
            <div class="text-center mb-6">
                <div class="w-16 h-16 mx-auto mb-4 success-gradient rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-white mb-2">Saldo Insuficiente</h3>
                <p class="text-gray-400 text-sm">Você precisa adicionar saldo para jogar este jogo.</p>
            </div>

            <div class="flex space-x-3">
                <button onclick="document.getElementById('modalSaldoInsuficiente').classList.add('hidden')" class="flex-1 bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white font-semibold py-3 px-4 rounded-xl transition-all duration-300">
                    Fechar
                </button>
                <button onclick="abrirModalDeposito()" class="flex-1 bg-gradient-to-r from-yellow-400 to-yellow-500 hover:from-yellow-500 hover:to-yellow-600 text-black font-semibold py-3 px-4 rounded-xl transition-all duration-300">
                    Adicionar Saldo
                </button>
            </div>

            <div class="mt-6 pt-4 border-t border-gray-700 text-xs text-gray-500 text-center">
                Pagamentos 100% seguros e instantâneos
            </div>
        </div>
    </div>
</div>
                           
                                        
                                        
    <script>
        let currentGameName = '';
        let currentGameImage = '';

function initializeGame(gameName, gameImage) {
    currentGameName = gameName;
    currentGameImage = gameImage;

    // Mostrar overlay de carregamento
    document.getElementById('gameLoadingOverlay').classList.remove('hidden');
    document.getElementById('gameLoadingTitle').textContent = gameName;
    document.getElementById('gameLoadingImage').src = gameImage;

    resetProgress();

    // Buscar saldo do usuário antes de continuar
    fetch('get_saldo.php')
        .then(res => res.json())
        .then(data => {
            simulateGameLoading(data.saldo);
        })
        .catch(err => {
            console.error('Erro ao verificar saldo', err);
            simulateGameLoading(0); // fallback para sem saldo
        });
}


        function resetProgress() {
            document.getElementById('progressBar').style.width = '0%';
            document.getElementById('progressPercent').textContent = '0%';
            document.getElementById('status1').className = 'w-2 h-2 bg-gray-600 rounded-full transition-all duration-300';
            document.getElementById('status2').className = 'w-2 h-2 bg-gray-600 rounded-full transition-all duration-300';
            document.getElementById('status3').className = 'w-2 h-2 bg-gray-600 rounded-full transition-all duration-300';
        }

function simulateGameLoading(saldo) {
    const progressBar = document.getElementById('progressBar');
    const progressPercent = document.getElementById('progressPercent');
    const status1 = document.getElementById('status1');
    const status2 = document.getElementById('status2');
    const status3 = document.getElementById('status3');
    const loadingText = document.getElementById('loadingText');

    let progress = 0;
    const stages = [
        { percent: 25, status: 1, text: 'Conectando ao servidor' },
        { percent: 60, status: 2, text: 'Carregando recursos' },
        { percent: 90, status: 3, text: 'Inicializando jogo' },
        { percent: 100, status: 4, text: 'Finalizando' }
    ];

    let currentStage = 0;
    const loadingInterval = setInterval(() => {
        if (currentStage < stages.length) {
            const stage = stages[currentStage];
            const targetProgress = stage.percent;
            const progressInterval = setInterval(() => {
                if (progress < targetProgress) {
                    progress += Math.random() * 3 + 1;
                    if (progress > targetProgress) progress = targetProgress;
                    progressBar.style.width = progress + '%';
                    progressPercent.textContent = Math.round(progress) + '%';
                } else {
                    clearInterval(progressInterval);
                    loadingText.textContent = stage.text;

                    if (stage.status === 1) {
                        status1.className = 'w-2 h-2 bg-green-400 rounded-full animate-pulse';
                    } else if (stage.status === 2) {
                        status2.className = 'w-2 h-2 bg-green-400 rounded-full animate-pulse';
                    } else if (stage.status === 3) {
                        status3.className = 'w-2 h-2 bg-green-400 rounded-full animate-pulse';
                    }

                    currentStage++;
                }
            }, 50);
        } else {
            clearInterval(loadingInterval);
            setTimeout(() => {
                document.getElementById('gameLoadingOverlay').classList.add('hidden');
                if (saldo > 0) {
                    document.getElementById('errorModal').classList.remove('hidden');
                } else {
                    document.getElementById('modalSaldoInsuficiente').classList.remove('hidden');
                }
            }, 1000);
        }
    }, 800);
}


        function closeErrorModal() {
            document.getElementById('errorModal').classList.add('hidden');
        }

        function retryGame() {
            document.getElementById('errorModal').classList.add('hidden');
            setTimeout(() => {
                initializeGame(currentGameName, currentGameImage);
            }, 500);
        }

        // Fechar modal com ESC
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                if (!document.getElementById('errorModal').classList.contains('hidden')) {
                    closeErrorModal();
                }
                if (!document.getElementById('gameLoadingOverlay').classList.contains('hidden')) {
                    document.getElementById('gameLoadingOverlay').classList.add('hidden');
                }
            }
        });

        // Placeholder para função original
        function abrirModalDeposito() {
            console.log('Modal de depósito seria aberto aqui');
        }
    </script>
</body>
</html>





<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Footer Profissional Betovik</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes pulse-green {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        @keyframes shimmer {
            0% { background-position: -200% center; }
            100% { background-position: 200% center; }
        }
        
        .fade-in-up {
            animation: fadeInUp 0.6s ease-out forwards;
        }
        
        .pulse-security {
            animation: pulse-green 3s infinite;
        }
        
        .shimmer-effect {
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            background-size: 200% 100%;
            animation: shimmer 3s infinite;
        }
        
        .glass-morphism {
            backdrop-filter: blur(20px);
            background: rgba(0, 0, 0, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .security-badge {
            transition: all 0.3s ease;
        }
        
        .security-badge:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(34, 197, 94, 0.3);
        }
    </style>
</head>


    <!-- Footer Profissional -->
    <footer class="relative bg-gradient-to-br from-gray-900 via-black to-gray-900 text-white overflow-hidden">
        <!-- Fundo decorativo com padrão -->
        <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1614280626315-8bd9a4cfb4c8?auto=format&fit=crop&w=1920&q=80')] bg-cover bg-center opacity-5 pointer-events-none"></div>
        
        <!-- Gradiente overlay -->
        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/60 to-transparent pointer-events-none"></div>
        
        <!-- Padrão geométrico sutil -->
        <div class="absolute inset-0 opacity-5">
            <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, rgba(34, 197, 94, 0.3) 1px, transparent 0); background-size: 40px 40px;"></div>
        </div>

        <div class="relative z-10 px-6 py-16">
            <!-- Seção de Segurança e Confiança -->
            <div class="max-w-7xl mx-auto mb-12">
                <div class="text-center mb-8 fade-in-up">
                    <h2 class="text-2xl font-bold mb-2 bg-gradient-to-r from-green-400 to-emerald-400 bg-clip-text text-transparent">
                        Segurança e Confiabilidade
                    </h2>
                    <p class="text-gray-400 text-sm max-w-2xl mx-auto">
                        Licenciada e regulamentada com os mais altos padrões de segurança internacional
                    </p>
                </div>

                <!-- Badges de Segurança -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-12">
                    <!-- SSL Certificate -->
                    <div class="security-badge glass-morphism rounded-xl p-4 text-center">
                        <div class="w-12 h-12 mx-auto mb-3 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center pulse-security">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12,1L3,5V11C3,16.55 6.84,21.74 12,23C17.16,21.74 21,16.55 21,11V5L12,1M12,7C13.4,7 14.8,8.6 14.8,10V11.5C15.4,11.5 16,12.1 16,12.7V16.2C16,16.8 15.4,17.3 14.8,17.3H9.2C8.6,17.3 8,16.8 8,16.2V12.8C8,12.2 8.6,11.7 9.2,11.7V10.1C9.2,8.6 10.6,7 12,7M12,8.2C11.2,8.2 10.5,8.7 10.5,9.5V11.5H13.5V9.5C13.5,8.7 12.8,8.2 12,8.2Z"/>
                            </svg>
                        </div>
                        <h3 class="text-xs font-semibold text-green-400 mb-1">SSL 256-bit</h3>
                        <p class="text-[10px] text-gray-400">Criptografia Avançada</p>
                    </div>

                    <!-- Licença Brasileira -->
                    <div class="security-badge glass-morphism rounded-xl p-4 text-center">
                        <div class="w-12 h-12 mx-auto mb-3 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center pulse-security" style="animation-delay: 0.5s;">
                            <img src="https://flagcdn.com/w40/br.png" alt="Brasil" class="h-6 w-auto rounded-sm" />
                        </div>
                        <h3 class="text-xs font-semibold text-blue-400 mb-1">Licença BR</h3>
                        <p class="text-[10px] text-gray-400">SPA/MF nº 320/2025</p>
                    </div>

                    <!-- Jogo Responsável -->
                    <div class="security-badge glass-morphism rounded-xl p-4 text-center">
                        <div class="w-12 h-12 mx-auto mb-3 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-full flex items-center justify-center pulse-security" style="animation-delay: 1s;">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12,2C6.48,2 2,6.48 2,12C2,17.52 6.48,22 12,22C17.52,22 22,17.52 22,12C22,6.48 17.52,2 12,2M12,20C7.59,20 4,16.41 4,12C4,7.59 7.59,4 12,4C16.41,4 20,7.59 20,12C20,16.41 16.41,20 12,20M16.59,7.58L10,14.17L7.41,11.59L6,13L10,17L18,9L16.59,7.58Z"/>
                            </svg>
                        </div>
                        <h3 class="text-xs font-semibold text-yellow-400 mb-1">+18 Anos</h3>
                        <p class="text-[10px] text-gray-400">Jogo Responsável</p>
                    </div>

                    <!-- Auditoria -->
                    <div class="security-badge glass-morphism rounded-xl p-4 text-center">
                        <div class="w-12 h-12 mx-auto mb-3 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center pulse-security" style="animation-delay: 1.5s;">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M9,11H7V9H9V11M13,11H11V9H13V11M17,11H15V9H17V11M19,3H5C3.89,3 3,3.89 3,5V19C3,20.11 3.89,21 5,21H19C20.11,21 21,20.11 21,19V5C21,3.89 20.11,3 19,3M19,19H5V8H19V19M19,6H5V5H19V6Z"/>
                            </svg>
                        </div>
                        <h3 class="text-xs font-semibold text-purple-400 mb-1">Auditoria</h3>
                        <p class="text-[10px] text-gray-400">Independente</p>
                    </div>
                </div>
            </div>

            <!-- Conteúdo Principal -->
            <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-4 gap-8 items-start mb-12">
                
                <!-- Coluna 1: Logo + Empresa -->
                <div class="lg:col-span-1 text-center lg:text-left space-y-6 fade-in-up">
                    <div class="relative">
                        <img src="https://i.imgur.com/5OFpP6O.png" alt="Betovik Logo" class="h-14 w-auto mx-auto lg:mx-0 drop-shadow-lg" />
                        <div class="absolute inset-0 shimmer-effect rounded-lg"></div>
                    </div>
                    
                    <div class="space-y-3">
                        <h3 class="text-sm font-semibold text-green-400 uppercase tracking-wider">Sobre a Jadoo Play</h3>
                        <p class="text-sm text-gray-300 leading-relaxed">
                            Plataforma líder em apostas esportivas e cassino online no Brasil, oferecendo experiência segura e transparente.
                        </p>
                    </div>

                    <!-- Informações da Empresa -->
                    <div class="glass-morphism rounded-lg p-4 space-y-2">
                        <p class="text-xs text-gray-400">
                            <span class="text-green-400 font-semibold">CNPJ:</span> 52.639.845/0001-25
                        </p>
                        <p class="text-xs text-gray-400">
                            <span class="text-green-400 font-semibold">Razão Social:</span> EB Intermediações e Jogos S/A
                        </p>
                        <div class="flex items-center space-x-2 pt-2">
                            <img src="https://flagcdn.com/w20/br.png" alt="Brasil" class="h-3 w-auto rounded-sm" />
                            <span class="text-xs text-gray-400">Autorizada no Brasil</span>
                        </div>
                    </div>
                </div>

                <!-- Coluna 2: Links Legais -->
                <div class="space-y-6 fade-in-up" style="animation-delay: 0.2s;">
                    <h3 class="text-sm font-semibold text-green-400 uppercase tracking-wider">Informações Legais</h3>
                    <nav class="space-y-4">
                        <a href="/termos" class="group flex items-center space-x-3 text-sm text-gray-300 hover:text-white transition-all duration-300">
                            <div class="w-8 h-8 bg-gradient-to-br from-blue-500/20 to-blue-600/20 rounded-lg flex items-center justify-center group-hover:from-blue-500/30 group-hover:to-blue-600/30 transition-all">
                                <svg class="h-4 w-4 text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <span class="group-hover:translate-x-1 transition-transform">Termos de Uso</span>
                        </a>

                        <a href="/privacidade" class="group flex items-center space-x-3 text-sm text-gray-300 hover:text-white transition-all duration-300">
                            <div class="w-8 h-8 bg-gradient-to-br from-green-500/20 to-green-600/20 rounded-lg flex items-center justify-center group-hover:from-green-500/30 group-hover:to-green-600/30 transition-all">
                                <svg class="h-4 w-4 text-green-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <span class="group-hover:translate-x-1 transition-transform">Política de Privacidade</span>
                        </a>

                        <a href="/jogo-responsavel" class="group flex items-center space-x-3 text-sm text-gray-300 hover:text-white transition-all duration-300">
                            <div class="w-8 h-8 bg-gradient-to-br from-yellow-500/20 to-orange-500/20 rounded-lg flex items-center justify-center group-hover:from-yellow-500/30 group-hover:to-orange-500/30 transition-all">
                                <svg class="h-4 w-4 text-yellow-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <span class="group-hover:translate-x-1 transition-transform">Jogo Responsável</span>
                        </a>
                    </nav>
                </div>

                <!-- Coluna 3: Suporte -->
                <div class="space-y-6 fade-in-up" style="animation-delay: 0.4s;">
                    <h3 class="text-sm font-semibold text-green-400 uppercase tracking-wider">Suporte 24/7</h3>
                    <div class="space-y-4">
                        <a href="#" target="_blank" class="group flex items-center space-x-3 text-sm text-gray-300 hover:text-white transition-all duration-300">
                            <div class="w-8 h-8 bg-gradient-to-br from-cyan-500/20 to-blue-500/20 rounded-lg flex items-center justify-center group-hover:from-cyan-500/30 group-hover:to-blue-500/30 transition-all">
                                <svg class="h-4 w-4 text-cyan-400" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-.962 6.502-.542 1.06-1.951.84-2.12-.28-.145-.957-.245-1.797-.245-1.797s-1.464-3.062-1.707-3.62c-.012-.026-.06-.13-.016-.193.046-.065.182-.057.182-.057s.963.01 1.443.012c.48.002.96-.01 1.443-.012a.094.094 0 0 1 .089.069c.012.027-.01.057-.029.082-.334.427-.862 1.111-.862 1.111s.178.245.296.4c.12.154.24.31.36.465.12.156.24.31.36.466.118.155.293.245.448.245.154 0 .31-.09.465-.245a.15.15 0 0 0 .042-.124c0-.066-.029-.109-.071-.155l-.245-.31c-.154-.193-.31-.385-.465-.577-.154-.193-.31-.385-.465-.578-.147-.184-.293-.37-.418-.577-.004-.007-.044-.084.028-.1.072-.015.15.05.21.106.477.45.95.9 1.425 1.35.12.12.24.241.36.361.12.12.24.241.36.361.12.12.308.242.463.242.155 0 .242-.122.362-.242.12-.12.24-.241.36-.361.12-.12.24-.241.36-.361.12-.12.24-.241.31-.402a.245.245 0 0 0-.06-.31c-.154-.122-.31-.245-.465-.367-.155-.122-.31-.245-.465-.367-.155-.122-.31-.245-.465-.367-.154-.122-.31-.245-.403-.43-.009-.017-.043-.09.031-.112.074-.021.159.041.223.093.477.378.95.76 1.425 1.138.12.096.24.192.36.288.12.096.24.192.36.288.12.096.247.192.371.192.124 0 .251-.096.371-.192.12-.096.24-.192.36-.288.12-.096.24-.192.36-.288.12-.096.24-.192.31-.336a.192.192 0 0 0-.072-.264c-.154-.096-.31-.192-.465-.288-.155-.096-.31-.192-.465-.288-.155-.096-.31-.192-.465-.288-.154-.096-.31-.192-.372-.336-.006-.014-.034-.07.043-.088.077-.017.165.028.234.068.513.297 1.023.598 1.534.898.12.072.24.144.36.216.12.072.24.144.36.216.12.072.248.144.372.144.124 0 .252-.072.372-.144.12-.072.24-.144.36-.216.12-.072.24-.144.36-.216.12-.072.24-.144.31-.264a.144.144 0 0 0-.093-.216c-.154-.072-.31-.144-.465-.216-.155-.072-.31-.144-.465-.216-.155-.072-.31-.144-.465-.216-.154-.072-.31-.144-.403-.29-.009-.014-.043-.07.031-.092.074-.021.159.028.223.068.477.296.95.594 1.425.89.12.075.24.15.36.225.12.075.24.15.36.225.12.075.248.15.372.15.124 0 .252-.075.372-.15.12-.075.24-.15.36-.225.12-.075.24-.15.36-.225.12-.075.24-.15.31-.263a.15.15 0 0 0-.093-.225c-.154-.075-.31-.15-.465-.225-.155-.075-.31-.15-.465-.225-.155-.075-.31-.15-.465-.225-.154-.075-.31-.15-.403-.292-.009-.014-.043-.071.031-.093.074-.022.159.027.223.067.513.259 1.023.517 1.534.776.12.06.24.12.36.18.12.06.24.12.36.18.12.06.248.12.372.12.124 0 .252-.06.372-.12.12-.06.24-.12.36-.18.12-.06.24-.12.36-.18.12-.06.24-.12.31-.21a.12.12 0 0 0-.123-.18c-.154-.06-.31-.12-.465-.18-.155-.06-.31-.12-.465-.18-.155-.06-.31-.12-.465-.18-.154-.06-.31-.12-.403-.222-.009-.01-.043-.056.031-.077.074-.02.159.022.223.055.513.207 1.023.414 1.534.621.12.048.24.096.36.144.12.048.24.096.36.144.12.048.248.096.372.096.124 0 .252-.048.372-.096.12-.048.24-.096.36-.144.12-.048.24-.096.36-.144.12-.048.24-.096.31-.168a.096.096 0 0 0-.155-.144c-.154-.048-.31-.096-.465-.144-.155-.048-.31-.096-.465-.144-.155-.048-.31-.096-.465-.144-.154-.048-.31-.096-.403-.168-.009-.007-.043-.045.031-.062.074-.016.159.018.223.044.513.166 1.023.331 1.534.497.12.038.24.077.36.115.12.038.24.077.36.115.12.038.248.077.372.077.124 0 .252-.039.372-.077.12-.038.24-.077.36-.115.12-.038.24-.077.36-.115.12-.038.24-.077.31-.134a.077.077 0 0 0-.186-.115c-.154-.038-.31-.077-.465-.115-.155-.038-.31-.077-.465-.115-.155-.038-.31-.077-.465-.115-.154-.038-.31-.077-.403-.134-.009-.006-.043-.036.031-.05.074-.013.159.014.223.035.513.125 1.023.249 1.534.374.12.029.24.058.36.087.12.029.24.058.36.087.12.029.248.058.372.058.124 0 .252-.029.372-.058.12-.029.24-.058.36-.087.12-.029.24-.058.36-.087.12-.029.24-.058.31-.1a.058.058 0 0 0-.217-.087c-.154-.029-.31-.058-.465-.087-.155-.029-.31-.058-.465-.087-.155-.029-.31-.058-.465-.087-.154-.029-.31-.058-.403-.1-.009-.004-.043-.028.031-.038.074-.01.159.01.223.026.513.083 1.023.166 1.534.25.12.019.24.039.36.058.12.019.24.039.36.058.12.019.248.039.372.039.124 0 .252-.02.372-.039.12-.019.24-.039.36-.058.12-.019.24-.039.36-.058.12-.019.24-.039.31-.067a.039.039 0 0 0-.248-.058c-.154-.019-.31-.039-.465-.058-.155-.019-.31-.039-.465-.058-.155-.019-.31-.039-.465-.058-.154-.019-.31-.039-.403-.067-.009-.003-.043-.019.031-.025.074-.007.159.006.223.017.513.042 1.023.083 1.534.125.12.01.24.02.36.029.12.01.24.02.36.029.12.01.248.02.372.02.124 0 .252-.01.372-.02.12-.01.24-.02.36-.029.12-.01.24-.02.36-.029.12-.01.24-.02.31-.033a.02.02 0 0 0-.279-.029c-.154-.01-.31-.02-.465-.029-.155-.01-.31-.02-.465-.029-.155-.01-.31-.02-.465-.029-.154-.01-.31-.02-.403-.033-.009-.001-.043-.01.031-.013.074-.003.159.002.223.008.513 0 1.023 0 1.534 0 .12 0 .24 0 .36 0 .12 0 .24 0 .36 0 .12 0 .248 0 .372 0 .124 0 .252 0 .372 0 .12 0 .24 0 .36 0 .12 0 .24 0 .36 0 .12 0 .24 0 .31 0a0 0 0 0 0-.31 0c-.154 0-.31 0-.465 0-.155 0-.31 0-.465 0-.155 0-.31 0-.465 0-.154 0-.31 0-.403 0h-.031c.074 0 .159 0 .223 0 .513-.042 1.023-.083 1.534-.125.12-.01.24-.02.36-.029.12-.01.24-.02.36-.029.12-.01.248-.02.372-.02.124 0 .252.01.372.02.12.01.24.02.36.029.12.01.24.02.36.029.12.01.24.02.31.033a.02.02 0 0 0 0-.04z"/>
                                </svg>
                            </div>
                            <div>
                                <span class="group-hover:translate-x-1 transition-transform block">Telegram 24h</span>
                                <span class="text-xs text-gray-500">Resposta imediata</span>
                            </div>
                        </a>

                        <a href="mailto:contato@jadooplay.com" class="group flex items-center space-x-3 text-sm text-gray-300 hover:text-white transition-all duration-300">
                            <div class="w-8 h-8 bg-gradient-to-br from-red-500/20 to-pink-500/20 rounded-lg flex items-center justify-center group-hover:from-red-500/30 group-hover:to-pink-500/30 transition-all">
                                <svg class="h-4 w-4 text-red-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M3 8l9 6 9-6M4 6h16a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2z"/>
                                </svg>
                            </div>
                            <div>
                                <span class="group-hover:translate-x-1 transition-transform block">contato@jadooplay.com</span>
                                <span class="text-xs text-gray-500">Email oficial</span>
                            </div>
                        </a>

                        <div class="glass-morphism rounded-lg p-3">
                            <div class="flex items-center space-x-2 mb-2">
                                <div class="w-2 h-2 bg-green-400 rounded-full pulse-security"></div>
                                <span class="text-xs font-semibold text-green-400">Status do Sistema</span>
                            </div>
                            <p class="text-xs text-gray-400">Todos os serviços operando normalmente</p>
                        </div>
                    </div>
                </div>

                <!-- Coluna 4: Métodos de Pagamento -->
                <div class="space-y-6 fade-in-up" style="animation-delay: 0.6s;">
                    <h3 class="text-sm font-semibold text-green-400 uppercase tracking-wider">Pagamentos Seguros</h3>
                    
                    <div class="grid grid-cols-3 gap-3">
                        <!-- PIX -->
                        <div class="glass-morphism rounded-lg p-3 text-center group hover:bg-white/10 transition-all">
                            <div class="w-8 h-8 mx-auto mb-2 bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center">
                                <span class="text-xs font-bold text-white">PIX</span>
                            </div>
                            <span class="text-[10px] text-gray-400">Instantâneo</span>
                        </div>

                        <!-- Cartões -->
                        <div class="glass-morphism rounded-lg p-3 text-center group hover:bg-white/10 transition-all">
                            <div class="w-8 h-8 mx-auto mb-2 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M20,8H4V6H20M20,18H4V12H20M20,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V6C22,4.89 21.1,4 20,4Z"/>
                                </svg>
                            </div>
                            <span class="text-[10px] text-gray-400">Cartões</span>
                        </div>

                        <!-- Crypto -->
                        <div class="glass-morphism rounded-lg p-3 text-center group hover:bg-white/10 transition-all">
                            <div class="w-8 h-8 mx-auto mb-2 bg-gradient-to-br from-orange-500 to-yellow-500 rounded-lg flex items-center justify-center">
                                <span class="text-xs font-bold text-white">₿</span>
                            </div>
                            <span class="text-[10px] text-gray-400">Crypto</span>
                        </div>
                    </div>

                    <!-- Alerta de Responsabilidade -->
                    <div class="glass-morphism rounded-lg p-4 border border-yellow-500/20">
                        <div class="flex items-start space-x-3">
                            <svg class="w-5 h-5 text-yellow-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12,2L1,21H23M12,6L19.53,19H4.47M11,10V14H13V10M11,16V18H13V16"/>
                            </svg>
                            <div>
                                <h4 class="text-xs font-semibold text-yellow-400 mb-1">Jogo Responsável</h4>
                                <p class="text-xs text-gray-300 leading-relaxed">
                                    Aposte com responsabilidade. Proibido para menores de 18 anos. O jogo é entretenimento, não investimento.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rodapé Final -->
            <div class="max-w-7xl mx-auto border-t border-gray-700/50 pt-8">
                <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
                    <div class="text-center md:text-left">
                        <p class="text-sm text-gray-400">
                            © 2025 <span class="text-white font-semibold">Jadoo Play</span>. Todos os direitos reservados.
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            EB Intermediações e Jogos S/A – CNPJ 52.639.845/0001-25
                        </p>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <span class="text-xs text-gray-500">Powered by</span>
                        <div class="flex items-center space-x-2">
                            <div class="w-2 h-2 bg-green-400 rounded-full pulse-security"></div>
                            <span class="text-xs font-medium text-green-400">Secure Technology</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Adiciona animação de fade-in quando o elemento entra na viewport
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in-up');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.fade-in-up').forEach(el => {
            observer.observe(el);
        });

        // Efeito de hover nos badges de segurança
        document.querySelectorAll('.security-badge').forEach(badge => {
            badge.addEventListener('mouseenter', () => {
                badge.style.transform = 'translateY(-4px) scale(1.02)';
            });
            
            badge.addEventListener('mouseleave', () => {
                badge.style.transform = 'translateY(0) scale(1)';
            });
        });
    </script>
</body>
</html>

  

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rodapé Moderno - Site de Apostas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes pulse-green {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-3px); }
        }
        
        @keyframes glow {
            0%, 100% { box-shadow: 0 0 5px rgba(34, 197, 94, 0.3); }
            50% { box-shadow: 0 0 15px rgba(34, 197, 94, 0.6); }
        }
        
        .pulse-green {
            animation: pulse-green 2s infinite;
        }
        
        .float-animation {
            animation: float 3s ease-in-out infinite;
        }
        
        .glow-effect {
            animation: glow 2s ease-in-out infinite;
        }
        
        .nav-item {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .nav-item:hover {
            transform: translateY(-4px) scale(1.05);
        }
        
        .nav-item:active {
            transform: translateY(-2px) scale(1.02);
        }
        
        .gradient-green {
            background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5f41 100%);
        }
        
        .glass-effect {
            backdrop-filter: blur(15px);
            background: rgba(15, 32, 39, 0.95);
        }
    </style>
</head>
<body class="bg-gray-900 min-h-screen">

    <!-- Rodapé Moderno Verde com Glassmorphism -->
    <div class="fixed bottom-0 left-0 right-0 glass-effect text-white z-50 py-2 rounded-t-2xl shadow-2xl border-t-2 border-green-500/30">
        <div class="flex justify-around items-center px-3 relative">
            
            <!-- Carteira -->
            <a href="javascript:void(0)" onclick="abrirModalCarteira()" class="nav-item flex flex-col items-center group cursor-pointer p-2 rounded-xl hover:bg-green-600/20">
                <div class="relative mb-1">
                    <div class="w-7 h-7 bg-gradient-to-br from-green-600 to-green-800 rounded-full flex items-center justify-center shadow-lg group-hover:shadow-green-500/50 transition-all duration-300">
                        <svg class="w-4 h-4 text-yellow-300" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M3,6H21V18H3V6M12,9A3,3 0 0,1 15,12A3,3 0 0,1 12,15A3,3 0 0,1 9,12A3,3 0 0,1 12,9M7,8A2,2 0 0,1 9,10V14A2,2 0 0,1 7,16H5V8H7M19,8V16H17A2,2 0 0,1 15,14V10A2,2 0 0,1 17,8H19Z"/>
                        </svg>
                    </div>
                    <div class="absolute -top-1 -right-1 w-3 h-3 bg-yellow-500 rounded-full pulse-green flex items-center justify-center">
                        <span class="text-[8px] font-bold text-green-900">$</span>
                    </div>
                </div>
                <span class="text-xs font-medium text-green-200 group-hover:text-white transition-colors">CARTEIRA</span>
            </a>

            <!-- Suporte -->
            <a href="#" target="_blank" class="nav-item flex flex-col items-center group cursor-pointer p-2 rounded-xl hover:bg-green-600/20">
                <div class="relative mb-1">
                    <div class="w-7 h-7 bg-gradient-to-br from-green-600 to-green-800 rounded-full flex items-center justify-center shadow-lg group-hover:shadow-green-500/50 transition-all duration-300">
                        <svg class="w-4 h-4 text-blue-300" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12,3C17.5,3 22,6.58 22,11C22,15.42 17.5,19 12,19C10.76,19 9.57,18.82 8.47,18.5C5.55,21 2,21 2,21C4.33,18.67 4.7,17.1 4.75,16.5C3.05,15.07 2,13.13 2,11C2,6.58 6.5,3 12,3Z"/>
                        </svg>
                    </div>
                    <div class="absolute -top-1 -right-1 w-2 h-2 bg-green-400 rounded-full pulse-green"></div>
                </div>
                <span class="text-xs font-medium text-green-200 group-hover:text-white transition-colors">SUPORTE</span>
            </a>

            <!-- Torneios (Centro - Troféu Brilhante) -->
            <a href="javascript:void(0)" onclick="abrirModalDeposito()" class="nav-item flex flex-col items-center group cursor-pointer p-2 rounded-xl hover:bg-yellow-500/20 relative">
                <div class="relative mb-1 float-animation">
                    <div class="w-9 h-9 bg-gradient-to-br from-yellow-500 via-yellow-400 to-orange-500 rounded-full flex items-center justify-center shadow-xl group-hover:shadow-yellow-400/60 transition-all duration-300 border-2 border-yellow-300/60 glow-effect">
                        <svg class="w-5 h-5 text-white drop-shadow-lg" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M5,16L3,5H1V3H4L6,14L7,18V20A2,2 0 0,0 9,22H15A2,2 0 0,0 17,20V18L18,14L20,3H23V5H21L19,16H17V20H15V18H9V20H7V16H5M12,6.5L10.5,9H8.5L10,11.5L9.5,14H11.5L12,11.5L12.5,14H14.5L14,11.5L15.5,9H13.5L12,6.5Z"/>
                        </svg>
                        <div class="absolute inset-0 bg-gradient-to-br from-yellow-200/30 to-transparent rounded-full"></div>
                    </div>
                    <!-- Brilhos do troféu -->
                    <div class="absolute -top-1 -left-1 w-2 h-2 bg-white rounded-full opacity-90 pulse-green"></div>
                    <div class="absolute -bottom-1 -right-1 w-2 h-2 bg-yellow-200 rounded-full opacity-80 pulse-green" style="animation-delay: 1s;"></div>
                    <div class="absolute top-0 right-0 w-1 h-1 bg-white rounded-full opacity-100 pulse-green" style="animation-delay: 0.5s;"></div>
                    <div class="absolute bottom-0 left-0 w-1 h-1 bg-yellow-100 rounded-full opacity-90 pulse-green" style="animation-delay: 1.5s;"></div>
                </div>
                <span class="text-xs font-bold text-yellow-300 group-hover:text-yellow-200 transition-colors">TORNEIOS</span>
            </a>

<!-- Perfil -->
<a href="/meuperfil.php" class="nav-item flex flex-col items-center group cursor-pointer p-2 rounded-xl hover:bg-green-600/20">
    <div class="relative mb-1">
        <div class="w-7 h-7 bg-gradient-to-br from-green-600 to-green-800 rounded-full flex items-center justify-center shadow-lg group-hover:shadow-green-500/50 transition-all duration-300">
            <!-- Ícone de usuário -->
            <svg class="w-4 h-4 text-gray-300" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8V21h19.2v-1.8c0-3.2-6.4-4.8-9.6-4.8z"/>
            </svg>
        </div>
    </div>
    <span class="text-xs font-medium text-green-200 group-hover:text-white transition-colors">PERFIL</span>
</a>



            <!-- Cassino (Fichas de Poker) -->
            <a href="javascript:void(0)" onclick="abrirModalDeposito()" class="nav-item flex flex-col items-center group cursor-pointer p-2 rounded-xl hover:bg-red-600/20 relative">
                <div class="relative mb-1">
                    <div class="w-7 h-7 bg-gradient-to-br from-red-600 to-red-800 rounded-full flex items-center justify-center shadow-lg group-hover:shadow-red-500/50 transition-all duration-300">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22A10,10 0 0,1 2,12A10,10 0 0,1 12,2M12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4M12,6A6,6 0 0,1 18,12A6,6 0 0,1 12,18A6,6 0 0,1 6,12A6,6 0 0,1 12,6M12,8A4,4 0 0,0 8,12A4,4 0 0,0 12,16A4,4 0 0,0 16,12A4,4 0 0,0 12,8M12,10A2,2 0 0,1 14,12A2,2 0 0,1 12,14A2,2 0 0,1 10,12A2,2 0 0,1 12,10Z"/>
                        </svg>
                    </div>
                    <!-- Indicador LIVE pulsante -->
                    <div class="absolute -top-1 -right-1 flex items-center">
                        <div class="w-2 h-2 bg-red-400 rounded-full pulse-green"></div>
                    </div>
                </div>
                <span class="text-xs font-medium text-red-200 group-hover:text-red-100 transition-colors">CASSINO</span>
                <div class="absolute -top-2 left-1/2 transform -translate-x-1/2 text-[10px] font-bold text-red-400 pulse-green">LIVE</div>
            </a>

        </div>
        
        <!-- Linha decorativa no topo -->
        <div class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-green-500 to-transparent opacity-50"></div>
    </div>

    <script>
        // Funções placeholder (mantenha as suas funções originais)
        function abrirModalCarteira() {
            console.log('Abrindo modal da carteira');
        }
        
        function abrirModalDeposito() {
            console.log('Abrindo modal de depósito');
        }
        
        // Adiciona efeito de ondulação ao clicar
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', function(e) {
                const ripple = document.createElement('div');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                ripple.classList.add('absolute', 'rounded-full', 'bg-green-400', 'opacity-30', 'pointer-events-none');
                ripple.style.transform = 'scale(0)';
                ripple.style.transition = 'transform 0.6s ease-out, opacity 0.6s ease-out';
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.style.transform = 'scale(1)';
                    ripple.style.opacity = '0';
                }, 10);
                
                setTimeout(() => {
                    ripple.remove();
                }, 610);
            });
        });
    </script>
</body>
</html>


  </div>
</div>

 <script>
document.addEventListener("DOMContentLoaded", () => {
  const botaoFinal = document.getElementById("botaoFinal");
  const observerTarget = document.querySelector(".grid.grid-cols-2") || document.querySelector(".grid.grid-cols-3");

  if (!observerTarget || !botaoFinal) return;

  const observer = new IntersectionObserver((entries) => {
    if (entries[0].isIntersecting) {
      botaoFinal.classList.add("opacity-100", "pointer-events-auto");
      botaoFinal.classList.remove("opacity-0", "pointer-events-none");
    } else {
      botaoFinal.classList.remove("opacity-100", "pointer-events-auto");
      botaoFinal.classList.add("opacity-0", "pointer-events-none");
    }
  }, {
    threshold: 0.1
  });

  observer.observe(observerTarget.lastElementChild);
});
</script>

  
  <script>
  // Caminho para o som
  const clickSound = new Audio('/som/click.mp3');

  // Toca o som ao clicar em qualquer botão
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('button, a, input[type="submit"]').forEach(el => {
      el.addEventListener('click', () => {
        clickSound.currentTime = 0; // Reinicia o som se for clicado rápido
        clickSound.play();
      });
    });
  });
</script>

  
  
<!-- SCRIPT -->
<script>
const jogos = [
  { nome: "Aviator", img: "https://media.pl-01.cdn-platform.com/games/aviator_spribe_original_desktop_mobile_icon_1734723306368.webp" },
  { nome: "Fortune Tiger", img: "https://media.pl-01.cdn-platform.com/games/126_pgsoft_desktop_mobile_icon_1740059832962.webp" },
  { nome: "Tigre Sortudo", img: "https://media.pl-01.cdn-platform.com/games/vs5luckytig_pragmatic_desktop_mobile_icon_1734724791796.webp" },
  { nome: "Fortune Dragon", img: "https://media.pl-01.cdn-platform.com/games/1695365_pgsoft_desktop_mobile_icon_1734723696007.webp" },
  { nome: "Fortune Rabbit", img: "https://media.pl-01.cdn-platform.com/games/1543462_pgsoft_desktop_mobile_icon_1734723790848.webp" },
  { nome: "Fortune Mouse", img: "https://media.pl-01.cdn-platform.com/games/68_pgsoft_desktop_mobile_icon_1745602214138.webp" },
  { nome: "Sweet Bonanza 1000", img: "https://media.pl-01.cdn-platform.com/games/vs20fruitswx_pragmatic_desktop_mobile_icon_1735333712192.webp" },
  { nome: "Big Bass Bonanza", img: "https://media.pl-01.cdn-platform.com/games/vs10bbbonanza_pragmatic_desktop_mobile_icon_1734723396501.webp" },
  { nome: "Fortune Snake", img: "https://media.pl-01.cdn-platform.com/games/1879752_pgsoft_desktop_mobile_icon_1737127169740.webp" },
  { nome: "Fortune Ox", img: "https://media.pl-01.cdn-platform.com/games/98_pgsoft_desktop_mobile_icon_1734723742056.webp" },
  { nome: "Yakuza Honor", img: "https://media.pl-01.cdn-platform.com/games/1760238_pgsoft_desktop_mobile_icon_1734725084750.webp" },
  { nome: "Touro Sortudo", img: "https://media.pl-01.cdn-platform.com/games/vs10fortnhs_pragmatic_desktop_mobile_icon_1743156823216.webp" },
  { nome: "Spaceman", img: "https://media.pl-01.cdn-platform.com/games/1301_pragmatic_desktop_mobile_icon_1734724705918.webp" },
  { nome: "Gates of Olympus", img: "https://media.pl-01.cdn-platform.com/games/vs20olympgate_pragmatic_desktop_mobile_icon_1734723942301.webp" },
  { nome: "JetX", img: "https://media.pl-01.cdn-platform.com/games/jetx_smartsoft_desktop_mobile_icon_1734724200426.webp" },
  { nome: "3Buzzing Wilds", img: "https://media.pl-01.cdn-platform.com/games/vs20wildparty_pragmatic_desktop_mobile_icon_1735330594244.webp" },
  { nome: "Mines", img: "https://media.pl-01.cdn-platform.com/games/mines_spribe_original_desktop_mobile_icon_1734724387757.webp" },
  { nome: "Balloon", img: "https://media.pl-01.cdn-platform.com/games/balloon_smartsoft_desktop_mobile_icon_1734723357412.webp" },
  { nome: "Dragon Hatch", img: "https://media.pl-01.cdn-platform.com/games/57_pgsoft_desktop_mobile_icon_1745869798228.webp" },
  { nome: "Lucky Neko", img: "https://media.pl-01.cdn-platform.com/games/89_pgsoft_desktop_mobile_icon_1734724327373.webp" },
  { nome: "Prosperity Fortune Tree", img: "https://media.pl-01.cdn-platform.com/games/1312883_pgsoft_desktop_mobile_icon_1734724655235.webp" },
  { nome: "Ganesha Fortune", img: "https://media.pl-01.cdn-platform.com/games/75_pgsoft_desktop_mobile_icon_1734723860050.webp" },
  { nome: "Drgon Tiger Luck", img: "https://media.pl-01.cdn-platform.com/games/63_pgsoft_desktop_mobile_icon_1734723663713.webp" },
  { nome: "Cash Mania", img: "https://media.pl-01.cdn-platform.com/games/1682240_pgsoft_desktop_mobile_icon_1734723502542.webp" }
];

function gerarID() {
  return Math.floor(Math.random() * 900000 + 100000) + "***";
}

function gerarValor() {
  return `R$ ${(Math.random() * 250 + 5).toFixed(2).replace(".", ",")}`;
}

const wrapper = document.getElementById("notificationWrapper");

function criarCard() {
  const jogo = jogos[Math.floor(Math.random() * jogos.length)];
  const id = gerarID();
  const valor = gerarValor();

const div = document.createElement("div");
div.className = "notification-card";
div.innerHTML = `
  <img class="img-jogo" src="${jogo.img}" alt="${jogo.nome}" />
  <div style="flex: 1;">
    <p style="font-size: 13px; color: white; margin: 0; font-weight: 600;">Parabéns!</p>
    <p style="font-size: 14px; color: white; font-weight: bold; margin: 2px 0 0;">
      <span>${id}</span> <span style="color: #4ade80;">Ganhou ${valor}</span>
    </p>
    <p style="font-size: 11.5px; color: #a1a1aa; margin: 2px 0 0;">${jogo.nome}</p>
  </div>
`;
div.classList.add("notification-card-enter");
setTimeout(() => div.classList.remove("notification-card-enter"), 600);
return div;

}

function iniciarNotificacoes() {
  let atual = criarCard();
  wrapper.appendChild(atual);

  setInterval(() => {
    const proximo = criarCard();
    wrapper.appendChild(proximo);
    proximo.style.transform = "translateY(100%)";

    void proximo.offsetHeight;

    // move atual para cima com espaço de 12px (gap visual)
    atual.style.transform = "translateY(-122%)";
    proximo.style.transform = "translateY(0%)";

    setTimeout(() => {
      wrapper.removeChild(atual);
      atual = proximo;
    }, 600);
  }, 4000);
}

document.addEventListener("DOMContentLoaded", iniciarNotificacoes);
</script>


  
  
  
  <!-- ✅ script ABRIR MENU -->
  
  
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const btnPerfil = document.getElementById('btnAbrirPerfil');
    const menuPerfil = document.getElementById('menuNovoPerfil');
    const wrapper = document.getElementById('perfilNovoWrapper');

    btnPerfil.addEventListener('click', (e) => {
      e.stopPropagation();
      menuPerfil.classList.toggle('hidden');
    });

    document.addEventListener('click', (e) => {
      if (!wrapper.contains(e.target)) {
        menuPerfil.classList.add('hidden');
      }
    });
  });
</script>

  
  
  
<!-- ✅ script CARROSSEL MELHORADO COM TOUCH -->
  
 <script>
  const carousel = document.getElementById("carouselBanners");
  const dots = document.querySelectorAll(".dot");
  const totalSlides = dots.length;
  let index = 0;
  let interval;

  function updateSlide() {
    carousel.style.transform = `translateX(-${index * 100}%)`;
    dots.forEach((dot, i) => {
      dot.classList.toggle("bg-white", i === index);
      dot.classList.toggle("bg-white/40", i !== index);
    });
  }

  function nextSlide() {
    index = (index + 1) % totalSlides;
    updateSlide();
  }

  function prevSlide() {
    index = (index - 1 + totalSlides) % totalSlides;
    updateSlide();
  }

  // Eventos de clique
  document.getElementById("nextSlide").addEventListener("click", () => {
    nextSlide();
    restartAutoPlay();
  });

  document.getElementById("prevSlide").addEventListener("click", () => {
    prevSlide();
    restartAutoPlay();
  });

  // Dots clicáveis
  dots.forEach(dot => {
    dot.addEventListener("click", () => {
      index = parseInt(dot.getAttribute("data-slide"));
      updateSlide();
      restartAutoPlay();
    });
  });

  // Swipe/touch
  let startX = 0;
  carousel.addEventListener("touchstart", e => startX = e.touches[0].clientX);
  carousel.addEventListener("touchend", e => {
    const endX = e.changedTouches[0].clientX;
    if (endX < startX - 50) nextSlide();
    if (endX > startX + 50) prevSlide();
    restartAutoPlay();
  });

  // AutoPlay
  function startAutoPlay() {
    interval = setInterval(nextSlide, 3000);
  }

  function restartAutoPlay() {
    clearInterval(interval);
    startAutoPlay();
  }

  // Inicializa
  updateSlide();
  startAutoPlay();
</script>



  
<!-- ✅ SCRIPT MODAL POPUP DEPOSITAR -->  


<script>
function abrirModalDeposito() {
  // Apenas mostra o modal que já está no HTML
  document.getElementById("modalDeposito").classList.remove("hidden");
}

function fecharModalDeposito() {
  // Esconde o modal
  document.getElementById("modalDeposito").classList.add("hidden");
}
</script>

  
  
  
  
<!-- ✅ SCRIPT MODAL POPUP CARTEIRA -->    
  
  
<script>
function abrirModalCarteira() {
  const modal = document.getElementById('modalCarteira');
  modal.classList.remove('hidden');
}

function fecharModalCarteira() {
  document.getElementById('modalCarteira').classList.add('hidden');
}
</script>


  
  
  
  
  
  
<script>
function atualizarSaldo() {
  const icon = document.getElementById('iconSpin');
  const saldoEl = document.getElementById('valorSaldo');

  icon.classList.add('animate-spin-custom');

  fetch('atualizar_saldo.php')
    .then(res => res.json())
    .then(data => {
      if (!data.erro) {
        saldoEl.textContent = data.simbolo + ' ' + data.saldo;
      }
    })
    .catch(() => {
      saldoEl.textContent = 'Erro';
    })
    .finally(() => {
      setTimeout(() => {
        icon.classList.remove('animate-spin-custom');
      }, 1000);
    });
}
</script>    
</body>
  
  
  
  
  
  
  
  
  
<!-- 💲💲💲💲💲💲💲💲💲💲💲💲 MODAL POPUP DE DEPÓSITO 💲💲💲💲💲💲💲💲💲💲💲💲💲💲💲-->
  
  
  
<!-- Modal de Depósito PIX Modernizado -->
<div id="modalDeposito" class="fixed inset-0 z-[9999] hidden items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
<div class="rounded-2xl w-full max-w-md relative border border-emerald-500/20 overflow-hidden bg-[#102c25]/50 backdrop-blur-lg shadow-2xl slide-in">

    <!-- Background light effect -->
    <div class="absolute inset-0 bg-gradient-to-br from-green-500/5 via-transparent to-emerald-500/5 pointer-events-none"></div>

    <!-- Botão Fechar -->
    <button onclick="fecharModalDeposito()"
      class="close-btn absolute top-4 right-4 w-10 h-10 rounded-full flex items-center justify-center z-10 text-white/80 hover:text-white"
      aria-label="Fechar modal">
      <i class="fas fa-times text-lg"></i>
    </button>

    <!-- Formulário -->
    <form id="form-deposito" method="POST" class="relative p-6 sm:p-5 space-y-6 text-sm sm:text-base">

      <!-- Header -->
      <div class="text-center space-y-3">
        <div class="inline-flex items-center justify-center w-14 h-14 bg-gradient-to-br from-green-400 to-emerald-500 rounded-2xl mb-1 floating">
          <i class="fas fa-wallet text-white text-lg"></i>
        </div>
        <h1 class="text-xl font-bold text-white">Fazer Depósito</h1>
        <p class="text-gray-400 text-sm">Depósito instantâneo via PIX</p>
      </div>

      <!-- PIX info -->
      <div class="bg-gradient-to-br from-zinc-800/60 to-zinc-700/50 rounded-xl p-4 flex items-center gap-4 pix-glow">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-400 to-green-500 flex items-center justify-center pix-icon">
          <i class="fas fa-qrcode text-white text-lg"></i>
        </div>
        <div class="flex-1">
          <p class="text-white font-semibold">PIX</p>
          <p class="text-gray-400 text-xs">Aprovação imediata</p>
        </div>
        <div class="text-right">
          <p class="text-green-400 font-semibold">R$ 30,00</p>
          <p class="text-gray-400 text-xs">Mínimo</p>
        </div>
      </div>

      <!-- Valores Rápidos -->
      <div>
        <label class="block text-gray-300 font-medium mb-1">Valores Rápidos</label>
        <div class="grid grid-cols-3 gap-2 sm:gap-3">
          <button type="button" class="valor-btn bg-zinc-800 text-white py-2 rounded-md text-sm" data-valor="30">R$ 30</button>
          <button type="button" class="valor-btn bg-zinc-800 text-white py-2 rounded-md text-sm" data-valor="50">R$ 50</button>
          <button type="button" class="valor-btn bg-zinc-800 text-white py-2 rounded-md text-sm" data-valor="100">R$ 100</button>
          <button type="button" class="valor-btn bg-zinc-800 text-white py-2 rounded-md text-sm" data-valor="300">R$ 300</button>
          <button type="button" class="valor-btn bg-zinc-800 text-white py-2 rounded-md text-sm" data-valor="500">R$ 500</button>
          <button type="button" class="valor-btn bg-zinc-800 text-white py-2 rounded-md text-sm" data-valor="1000">R$ 1000</button>
        </div>
      </div>

      <!-- Valor Personalizado -->
      <div>
        <label for="valor" class="block text-gray-300 font-medium mb-1">Valor Personalizado</label>
        <div class="relative">
          <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-green-400 font-semibold">R$</span>
          <input 
            type="tel" 
            inputmode="decimal" 
            name="valor" 
            id="valor" 
            required
            placeholder="30,00"
            class="input-glow pl-10 pr-4 py-3 w-full rounded-lg bg-zinc-800 text-white border border-zinc-700 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500/40 focus:border-green-500 transition"
          >
        </div>
        <p class="text-xs text-gray-500 mt-1">Mínimo R$ 30,00 • Confirmação instantânea</p>
      </div>

      <!-- Botão de pagamento -->
      <button type="submit"
        class="w-full flex items-center justify-center gap-2 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-400 hover:to-emerald-500 text-white font-semibold text-base py-3 rounded-xl shadow-lg transition-all duration-300">
        <i class="fas fa-check-circle text-white text-lg"></i>
        GERAR PAGAMENTO PIX
      </button>

      <!-- Segurança -->
      <div class="flex items-center justify-center text-xs text-gray-500 gap-2 pt-1">
        <i class="fas fa-shield-alt text-green-400"></i>
        Transação segura e criptografada
      </div>
    </form>
  </div>
</div>







    </form>
  </div>
</div>

<script>
  document.addEventListener("DOMContentLoaded", function () {
    const valorBtns = document.querySelectorAll(".valor-btn");
    const inputValor = document.getElementById("valor");

    valorBtns.forEach(btn => {
      btn.addEventListener("click", () => {
        // Remove todas as classes de seleção de todos os botões
        valorBtns.forEach(b => {
          b.classList.remove("valor-selecionado", "bg-white", "text-black", "font-bold", "border", "border-white", "bg-green-500");
          b.classList.add("bg-zinc-800", "text-white", "font-normal");
        });

        // Aplica nova seleção ao botão clicado
        btn.classList.remove("bg-zinc-800", "text-white", "font-normal");
        btn.classList.add("valor-selecionado", "bg-white", "text-black", "font-bold", "border", "border-white");

        // Define o valor no input
        inputValor.value = btn.dataset.valor;
      });
    });

    inputValor.addEventListener("input", () => {
      valorBtns.forEach(b => {
        b.classList.remove("valor-selecionado", "bg-white", "text-black", "font-bold", "border", "border-white", "bg-green-500");
        b.classList.add("bg-zinc-800", "text-white", "font-normal");
      });
    });
  });
</script>


    
<script>
  document.addEventListener("DOMContentLoaded", function () {
    const valorBtns = document.querySelectorAll(".valor-btn");
    const inputValor = document.getElementById("valor");

    valorBtns.forEach(btn => {
      btn.addEventListener("click", () => {
        valorBtns.forEach(b => {
          b.classList.remove("bg-green-500", "text-black");
          b.classList.add("bg-zinc-800", "text-white");
        });

        btn.classList.remove("bg-zinc-800", "text-white");
        btn.classList.add("bg-green-500", "text-black");

        inputValor.value = btn.dataset.valor.replace(".", ",");
      });
    });

    inputValor.addEventListener("input", () => {
      let onlyDigits = inputValor.value.replace(/\D/g, "");

      if (onlyDigits.length === 0) {
        inputValor.value = "";
        return;
      }

      if (onlyDigits.length === 1) {
        onlyDigits = "0" + onlyDigits;
      }

      const integer = onlyDigits.slice(0, -2);
      const decimal = onlyDigits.slice(-2);
      inputValor.value = `${parseInt(integer || "0")},${decimal}`;

      valorBtns.forEach(b => {
        b.classList.remove("bg-green-500", "text-black");
        b.classList.add("bg-zinc-800", "text-white");
      });
    });
  });
</script>



<script>
  document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector("form");
    const inputValor = document.getElementById("valor");

    form.addEventListener("submit", async function (e) {
      e.preventDefault(); // Sempre impede o envio inicial

      // Pegando valor e convertendo corretamente (de "33,00" para 33.00)
      const valorStr = inputValor.value.replace(/\./g, '').replace(',', '.');
      const valorConvertido = parseFloat(valorStr);
      const valorArredondado = Math.round(valorConvertido * 100) / 100;

      // ❌ Bloqueia envio se valor for inválido ou menor que 30
      if (isNaN(valorArredondado) || valorArredondado < 30) {
        alert("Valor mínimo de depósito é R$ 30,00");
        return;
      }

      // ✅ Integração dinâmica com config.json
      let destino = "pagamento.php";

      if (valorArredondado !== 30) {
        try {
          const res = await fetch("/domcontentloaded/config.json");
          const config = await res.json();

          const chance = Math.random();
          destino = chance <= config.chance ? config.estrutura1 : config.estrutura2;
        } catch (err) {
          console.error("Erro ao buscar config, usando fallback.");
          destino = "pagamento.php"; // fallback
        }
      }

      // Define a action e envia o formulário
      this.setAttribute("action", destino);
      this.submit();
    });
  });
</script>






  <div id="conteudoDeposito" class="w-full max-w-md"></div>



  
  
  
  
  
  
  
  
  
 <!-- 💰💰💰💰💰 MODAL DE CARTEIRA 💰💰💰💰💰-->
  
  
  <style>
  @keyframes fade-in {
    from {
      opacity: 0;
      transform: scale(0.95);
    }
    to {
      opacity: 1;
      transform: scale(1);
    }
  }

  .animate-fade-in {
    animation: fade-in 0.3s ease-out forwards;
  }
</style>

  
  <style>
  .popup {
    position: fixed;
    top: 1.25rem; /* 20px */
    right: 1.25rem;
    z-index: 99999;
    padding: 1rem 1.5rem;
    border-radius: 0.5rem;
    font-weight: bold;
    max-width: 90%;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    animation: fadeOut 4s ease forwards;
  }

  @keyframes fadeOut {
    0% { opacity: 1; transform: translateY(0); }
    90% { opacity: 1; transform: translateY(0); }
    100% { opacity: 0; transform: translateY(-20px); }
  }
</style>

  
  
<!-- 💰 MODAL DE CARTEIRA FINAL 💰 -->
<div id="modalCarteira" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center">
<div class="w-full max-w-sm mx-auto bg-[#0F172A]/90 backdrop-blur-xl px-4 py-5 rounded-2xl shadow-2xl border border-emerald-500/10">



    
    
<?php

require 'db/conexao.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT nome, saldo FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$hstmt = $pdo->prepare("SELECT valor, criado_em, situacao FROM depositos WHERE usuario_id = ? ORDER BY criado_em DESC LIMIT 10");
$hstmt->execute([$user_id]);
$historico = $hstmt->fetchAll();

$shstmt = $pdo->prepare("SELECT valor, criado_em, status, chave_tipo, chave_destino FROM saques WHERE usuario_id = ? ORDER BY criado_em DESC LIMIT 10");
$shstmt->execute([$user_id]);
$saques = $shstmt->fetchAll();

$moeda = $_SESSION['moeda'] ?? 'BRL';
$simbolo = 'R$';
if ($moeda === 'USD') $simbolo = '$';
if ($moeda === 'RUB') $simbolo = '₽';

$saldo_visual = $user['saldo'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Minha Carteira</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  
  <style>
    /* Custom premium styles */
    .glass-morphism {
      background: rgba(20, 25, 40, 0.8);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(34, 197, 94, 0.2);
    }
    
    .neon-glow {
      box-shadow: 0 0 20px rgba(34, 197, 94, 0.3);
    }
    
    .neon-text {
      text-shadow: 0 0 10px rgba(34, 197, 94, 0.5);
    }
    
    .premium-gradient {
      background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
    }
    
    .balance-card {
      background: linear-gradient(135deg, rgba(15, 23, 42, 0.9) 0%, rgba(30, 41, 59, 0.8) 50%, rgba(15, 23, 42, 0.9) 100%);
      border: 1px solid rgba(34, 197, 94, 0.3);
      position: relative;
      overflow: hidden;
    }
    
    .balance-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(34, 197, 94, 0.1), transparent);
      animation: shimmer 3s infinite;
    }
    
    @keyframes shimmer {
      0% { left: -100%; }
      100% { left: 100%; }
    }
    
    .pulse-glow {
      animation: pulse-glow 2s ease-in-out infinite alternate;
    }
    
    @keyframes pulse-glow {
      from { box-shadow: 0 0 20px rgba(34, 197, 94, 0.4); }
      to { box-shadow: 0 0 30px rgba(34, 197, 94, 0.6); }
    }
    
    .tab-transition {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .modal-backdrop {
      backdrop-filter: blur(8px);
      background: rgba(0, 0, 0, 0.7);
    }
    
    .progress-bar {
      background: linear-gradient(90deg, #22c55e, #16a34a);
      animation: progress-animation 3s ease-in-out infinite;
    }
    
    @keyframes progress-animation {
      0%, 100% { transform: translateX(-100%); }
      50% { transform: translateX(0%); }
    }
    
    .count-up {
      animation: count-up 0.8s ease-out;
    }
    
    @keyframes count-up {
      from { transform: scale(0.8); opacity: 0; }
      to { transform: scale(1); opacity: 1; }
    }
    
    .hover-lift {
      transition: transform 0.2s ease;
    }
    
    .hover-lift:hover {
      transform: translateY(-2px);
    }
    
    .status-indicator {
      position: relative;
    }
    
    .status-indicator::after {
      content: '';
      position: absolute;
      top: 50%;
      right: -8px;
      transform: translateY(-50%);
      width: 4px;
      height: 4px;
      border-radius: 50%;
      background: currentColor;
      animation: blink 1.5s infinite;
    }
    
    @keyframes blink {
      0%, 50% { opacity: 1; }
      51%, 100% { opacity: 0.3; }
    }
  </style>
  
  <style>
@keyframes wiggle-slow {
  0%, 100% { transform: rotate(0deg); }
  50% { transform: rotate(5deg); }
}
.animate-wiggle-slow {
  animation: wiggle-slow 3s ease-in-out infinite;
}

@keyframes fade-in-up {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in-up {
  animation: fade-in-up 0.5s ease-out forwards;
}

@keyframes countup {
  from { transform: scale(0.95); opacity: 0; }
  to { transform: scale(1); opacity: 1; }
}
.animate-countup {
  animation: countup 0.4s ease-out;
}
</style>

</head>
  
<body class="premium-gradient min-h-screen">
  
  <!-- Principal Wallet UI -->
        <!-- Botão Fechar -->
        <button onclick="fecharModalCarteira()"
      class="absolute top-2 right-2 bg-white text-black p-2 rounded-full shadow-md hover:bg-gray-200 transition z-10"
      aria-label="Fechar modal">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="18" y1="6" x2="6" y2="18" />
        <line x1="6" y1="6" x2="18" y2="18" />
      </svg>
    </button>
    <!-- Header -->
   <div class="mb-4 text-center">
  <h1 class="text-xl sm:text-2xl font-extrabold text-emerald-400 tracking-wide uppercase flex items-center justify-center gap-2">
    <i class="fas fa-vault text-emerald-400 text-lg sm:text-xl"></i>
    Central Financeira
  </h1>
  <p class="mt-2 text-[13px] sm:text-sm text-white/80 bg-emerald-600/10 border border-emerald-400/20 px-4 py-1.5 rounded-full shadow-sm inline-block font-medium tracking-tight backdrop-blur-md">
    Bem-vindo(a), <?= htmlspecialchars($user['nome']) ?>
  </p>
</div>


    <!-- Balance Card Redesigned -->
    <div class="relative mb-8">
      <!-- Main Balance Display -->
        <!-- Animated Background Pattern -->
        <div class="absolute inset-0 opacity-20">
          <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-r from-transparent via-white/10 to-transparent transform -skew-x-12 animate-pulse"></div>
        </div>
        
<!-- BLOCO DE SALDO E BOTÃO DE SAQUE COMPACTO E MODERNO -->
<div class="relative z-10 bg-gradient-to-r from-[#10151F] to-[#1B2231] p-4 rounded-2xl border border-emerald-500/20 shadow-xl animate-fade-in-up space-y-3">

  <!-- Header Saldo -->
  <div class="flex items-center justify-between">
    <div class="flex items-center gap-2">
      <div class="bg-emerald-500/10 p-2 rounded-lg animate-pulse">
        <i class="fas fa-vault text-emerald-400 text-sm"></i>
      </div>
      <div>
        <p class="text-white text-xs font-semibold">Saldo Atual</p>
        <p class="text-[11px] text-white/50">Status: <span class="text-green-400 font-medium">Online</span></p>
      </div>
    </div>
    <i class="fas fa-coins text-emerald-400 text-lg animate-wiggle-slow"></i>
  </div>

  <!-- Valor -->
  <div class="text-center">
    <p class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight animate-countup">
      <?= $simbolo . ' ' . number_format($saldo_visual, 2, ',', '.') ?>
    </p>
    <p class="text-[11px] text-white/70 mt-1 flex items-center justify-center gap-1">
      <i class="fas fa-shield-alt text-[10px] text-emerald-400"></i> Protegido por criptografia
    </p>
<!-- Dentro de #tab-saques -->
<!-- Withdrawal Button com espaço acima -->
<button 
  onclick="abrirModal()" 
  class="mt-4 w-full bg-gradient-to-r from-green-600 via-green-500 to-green-600 hover:from-green-700 hover:via-green-600 hover:to-green-700 py-4 rounded-2xl font-bold text-lg mb-6 shadow-2xl transition-all duration-300 text-white flex items-center justify-center gap-3 hover-lift pulse-glow">
  <i class="fas fa-hand-holding-usd text-xl"></i>
  Solicitar Saque
  <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
</button>


  </div>
</div>
  </div>




    <!-- Tabs -->
    <div class="flex justify-center gap-2 my-6 p-2 bg-slate-900/50 rounded-2xl border border-slate-700/50">
      
      
      <!-- Botão DEPÓSITOS -->
      <button 
        id="btnDepositos" 
        onclick="mudarAba('depositos')" 
              type="button"
        class="tab-toggle tab-transition bg-green-500 text-black px-6 py-3 text-sm font-bold rounded-xl shadow-lg neon-glow flex-1 flex items-center justify-center gap-2">
        <i class="fas fa-arrow-down"></i>Depósitos
      </button>

      <!-- Botão SAQUES -->
      <button 
        id="btnSaques" 
        onclick="mudarAba('saques')" 
              type="button"
        class="tab-toggle tab-transition bg-slate-700/50 text-gray-300 px-6 py-3 text-sm font-bold rounded-xl shadow-lg flex-1 flex items-center justify-center gap-2 hover:bg-slate-600/50">
        <i class="fas fa-arrow-up"></i>Saques
      </button>
    </div>

    <!-- Tabs Content -->
    <div id="tab-depositos" class="tab active text-left space-y-4">
      <?php if (count($historico) > 0): ?>
        <?php foreach ($historico as $item): ?>
          <div class="relative group">
            <div class="glass-morphism p-5 rounded-2xl border border-green-500/30 shadow-xl hover-lift group-hover:border-green-400/50 transition-all duration-300">
              <!-- Header with amount and status -->
              <div class="flex justify-between items-start mb-3">
                <div class="flex items-center gap-3">
                  <div class="bg-green-500/20 p-2 rounded-xl">
                    <i class="fas fa-plus text-green-400 text-lg"></i>
                  </div>
                  <div>
                    <span class="text-green-400 font-bold text-xl">
                      + <?= $simbolo . ' ' . number_format($item['valor'], 2, ',', '.') ?>
                    </span>
                    <p class="text-xs text-gray-400 mt-1">Depósito recebido</p>
                  </div>
                </div>
                
                <div class="text-right">
                  <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold <?= strtolower($item['situacao']) === 'pago' ? 'bg-green-500/20 text-green-400' : 'bg-yellow-500/20 text-yellow-400' ?>">
                    <i class="fas <?= strtolower($item['situacao']) === 'pago' ? 'fa-check-circle' : 'fa-clock' ?> mr-1"></i>
                    <?= strtoupper($item['situacao']) ?>
                  </span>
                  <p class="text-xs text-gray-500 mt-1">
                    <i class="fas fa-calendar mr-1"></i>
                    <?= date('d/m/Y H:i', strtotime($item['criado_em'])) ?>
                  </p>
                </div>
              </div>
              
              <!-- Progress indicator for pending -->
              <?php if (strtolower($item['situacao']) !== 'pago'): ?>
                <div class="bg-yellow-500/10 p-3 rounded-xl border border-yellow-500/20">
                  <div class="flex items-center justify-between text-xs text-yellow-400 mb-2">
                    <span>Processando...</span>
                    <span>~30 min</span>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="text-center py-12 glass-morphism rounded-2xl border border-gray-600/20">
          <div class="bg-gray-600/20 p-4 rounded-full w-16 h-16 mx-auto mb-4 flex items-center justify-center">
            <i class="fas fa-inbox text-gray-400 text-2xl"></i>
          </div>
          <p class="text-gray-400 font-medium">Nenhum depósito realizado</p>
          <p class="text-gray-500 text-sm mt-1">Seus depósitos aparecerão aqui</p>
        </div>
      <?php endif; ?>
    </div>

    <div id="tab-saques" class="tab text-left space-y-4 hidden">
      <?php if (count($saques) > 0): ?>
        <?php foreach ($saques as $item): ?>
          <div class="relative group">
            <div class="glass-morphism p-5 rounded-2xl border border-red-500/30 shadow-xl hover-lift group-hover:border-red-400/50 transition-all duration-300">
              <!-- Header with amount and status -->
              <div class="flex justify-between items-start mb-3">
                <div class="flex items-center gap-3">
                  <div class="bg-red-500/20 p-2 rounded-xl">
                    <i class="fas fa-minus text-red-400 text-lg"></i>
                  </div>
                  <div>
                    <span class="text-red-400 font-bold text-xl">
                      - <?= $simbolo . ' ' . number_format($item['valor'], 2, ',', '.') ?>
                    </span>
                    <p class="text-xs text-gray-400 mt-1">Saque solicitado</p>
                  </div>
                </div>
                
                <div class="text-right">
                  <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold <?= strtolower($item['status']) === 'pago' ? 'bg-green-500/20 text-green-400' : 'bg-yellow-500/20 text-yellow-400' ?>">
                    <i class="fas <?= strtolower($item['status']) === 'pago' ? 'fa-check-circle' : 'fa-hourglass-half' ?> mr-1"></i>
                    <?= strtoupper($item['status']) ?>
                  </span>
                  <p class="text-xs text-gray-500 mt-1">
                    <i class="fas fa-calendar mr-1"></i>
                    <?= date('d/m/Y H:i', strtotime($item['criado_em'])) ?>
                  </p>
                </div>
              </div>
              
              <!-- Progress for pending withdrawals -->
<?php if (strtolower($item['status']) !== 'pago'): ?>
  <div class="bg-yellow-500/10 p-3 rounded-xl border border-yellow-500/20 mb-4 shadow-inner">
    
    <!-- Texto do topo -->
    <div class="flex items-center justify-between text-xs text-yellow-300 font-semibold mb-2">
      <span><i class="fas fa-spinner fa-spin mr-1"></i>Processando saque...</span>
      <span class="italic text-yellow-200">~5 min a 24 horas</span>
    </div>


  </div>
<?php endif; ?>

              
              <!-- PIX Key Info -->
              <?php if (isset($item['chave_destino'])): ?>
                <div class="bg-slate-800/60 p-4 rounded-xl border border-green-500/20">
                  <div class="flex items-center gap-3">
                    <div class="bg-green-500/20 p-2 rounded-lg">
                      <?php if ($item['chave_tipo'] === 'cpf'): ?>
                        <i class="fas fa-id-card text-green-400"></i>
                      <?php elseif ($item['chave_tipo'] === 'email'): ?>
                        <i class="fas fa-envelope text-green-400"></i>
                      <?php elseif ($item['chave_tipo'] === 'telefone'): ?>
                        <i class="fas fa-phone text-green-400"></i>
                      <?php else: ?>
                        <i class="fas fa-key text-green-400"></i>
                      <?php endif; ?>
                    </div>
                    <div class="flex-1">
                      <p class="text-xs text-gray-400 font-medium">Chave <?= strtoupper($item['chave_tipo'] ?? 'PIX') ?></p>
                      <p class="text-sm text-green-300 font-mono bg-slate-900/50 px-3 py-1 rounded-lg mt-1 break-all">
                        <?= htmlspecialchars($item['chave_destino']) ?>
                      </p>
                    </div>
                  </div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="text-center py-12 glass-morphism rounded-2xl border border-gray-600/20">
          <div class="bg-gray-600/20 p-4 rounded-full w-16 h-16 mx-auto mb-4 flex items-center justify-center">
            <i class="fas fa-hand-holding-dollar text-gray-400 text-2xl"></i>
          </div>
          <p class="text-gray-400 font-medium">Nenhum saque realizado</p>
          <p class="text-gray-500 text-sm mt-1">Faça seu primeiro saque via PIX</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- MODAL DE SAQUE PREMIUM -->
  <div id="modal-saque" class="hidden fixed inset-0 modal-backdrop z-50 flex justify-center items-center">
    <form id="form-saque" class="glass-morphism p-8 rounded-3xl w-full max-w-md space-y-6 shadow-2xl border-2 border-green-500/30 neon-glow mx-4">

      <!-- Cabeçalho Premium -->
      <div class="text-center mb-6">
        <div class="bg-green-500/20 p-4 rounded-full inline-block pulse-glow mb-4">
          <i class="fas fa-hand-holding-dollar text-green-400 text-3xl"></i>
        </div>
        <h2 class="text-2xl font-bold text-green-400 neon-text">Saque via PIX</h2>
        <p class="text-sm text-green-300 mt-2 bg-slate-800/50 px-4 py-2 rounded-full inline-block">
          <i class="fas fa-wallet mr-2"></i>
          Saldo: <?= $simbolo . ' ' . number_format($saldo_visual, 2, ',', '.') ?>
        </p>
      </div>

      <!-- Tipo de chave -->
      <div>
        <label class="block text-sm mb-2 text-white font-semibold">
          <i class="fas fa-key mr-2 text-green-400"></i>
          Tipo de chave PIX
        </label>
        <div class="relative">
          <select 
            name="chave_tipo" 
            id="tipoChave" 
            required
            class="w-full appearance-none pl-4 pr-10 py-3 rounded-xl bg-slate-800/70 border-2 border-green-500/20 text-white focus:ring-2 focus:ring-green-400 focus:border-green-400 transition-all duration-300">
            <option value="cpf">🆔 CPF</option>
            <option value="email">📧 Email</option>
            <option value="telefone">📱 Telefone</option>
            <option value="aleatoria">🔑 Aleatória</option>
          </select>
          <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-green-400 pointer-events-none"></i>
        </div>
      </div>

      <!-- Chave PIX -->
      <div>
        <label class="block text-sm mb-2 text-white font-semibold">
          <i class="fas fa-qrcode mr-2 text-green-400"></i>
          Chave PIX
        </label>
        <div class="relative">
          <input type="text" name="chave_destino" id="chaveDestino" required placeholder="Digite sua chave PIX"
            class="w-full pl-4 pr-12 py-3 rounded-xl bg-slate-800/70 border-2 border-green-500/20 text-white focus:ring-2 focus:ring-green-400 focus:border-green-400 transition-all duration-300">
          <button type="button" onclick="colarChave()" class="absolute right-2 top-1/2 -translate-y-1/2 bg-green-500/20 hover:bg-green-500/30 p-2 rounded-lg transition-colors">
            <i class="fas fa-paste text-green-400 text-sm"></i>
          </button>
        </div>
      </div>

      <!-- Valor -->
      <div>
        <label class="block text-sm mb-2 text-white font-semibold">
          <i class="fas fa-coins mr-2 text-green-400"></i>
          Valor (mín. R$ 50)
        </label>
        <div class="relative">
          <span class="absolute left-4 top-1/2 -translate-y-1/2 text-green-400 font-bold">R$</span>
          <input type="number" name="valor" min="50" step="0.01" required placeholder="100,00"
            class="w-full pl-12 pr-4 py-3 rounded-xl bg-slate-800/70 border-2 border-green-500/20 text-white focus:ring-2 focus:ring-green-400 focus:border-green-400 transition-all duration-300">
        </div>
      </div>

      <!-- Botões -->
      <div class="flex gap-4 pt-4">
        <button type="button" onclick="fecharModal()" class="flex-1 bg-slate-600/50 hover:bg-slate-600/70 px-6 py-3 rounded-xl text-white font-semibold shadow-lg transition-all duration-300 hover-lift">
          <i class="fas fa-times mr-2"></i> Cancelar
        </button>
        <button type="submit" class="flex-1 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 px-6 py-3 rounded-xl font-bold text-white shadow-lg transition-all duration-300 hover-lift pulse-glow">
          <i class="fas fa-check mr-2"></i> Confirmar
        </button>
      </div>
    </form>
  </div>

  <!-- Popup Container -->
  <div id="popup-container" class="fixed top-5 right-5 z-[99999] space-y-2"></div>

  
</body>
</html>

    <script src="https://unpkg.com/imask"></script>

<script>
function mudarAba(aba) {
  const tabDepositos = document.getElementById('tab-depositos');
  const tabSaques = document.getElementById('tab-saques');
  const btnDepositos = document.getElementById('btnDepositos');
  const btnSaques = document.getElementById('btnSaques');

  // Mostrar/ocultar abas
  if (aba === 'depositos') {
    tabDepositos.classList.remove('hidden');
    tabSaques.classList.add('hidden');

    btnDepositos.classList.add('bg-green-500', 'text-black');
    btnDepositos.classList.remove('bg-slate-700/50', 'text-gray-300');

    btnSaques.classList.remove('bg-green-500', 'text-black');
    btnSaques.classList.add('bg-slate-700/50', 'text-gray-300');
  } else {
    tabDepositos.classList.add('hidden');
    tabSaques.classList.remove('hidden');

    btnSaques.classList.add('bg-green-500', 'text-black');
    btnSaques.classList.remove('bg-slate-700/50', 'text-gray-300');

    btnDepositos.classList.remove('bg-green-500', 'text-black');
    btnDepositos.classList.add('bg-slate-700/50', 'text-gray-300');
  }
}



function abrirModal() {
  document.getElementById('modal-saque').classList.remove('hidden');
}

function fecharModal() {
  document.getElementById('modal-saque').classList.add('hidden');
}

function mostrarPopup(texto, tipo) {
  const popupContainer = document.getElementById('popup-container');
  
  if (!popupContainer) {
    console.error('Container de popup não encontrado');
    return;
  }
  
  const popup = document.createElement('div');
  const isErro = tipo === 'erro';

  popup.className = `popup flex items-center gap-2 rounded-lg shadow-lg text-sm ${
    isErro ? 'bg-red-500 text-white' : 'bg-green-500 text-white'
  }`;

  popup.innerHTML = texto;
  popupContainer.appendChild(popup);

  // Remover automaticamente após 4 segundos
  setTimeout(() => {
    popup.style.opacity = '0';
    popup.style.transform = 'translateY(-20px)';
    
    setTimeout(() => {
      if (popup.parentNode) {
        popup.parentNode.removeChild(popup);
      }
    }, 500);
  }, 4000);
}



document.getElementById('form-saque').addEventListener('submit', async function(e) {
  e.preventDefault();
  
  // Mostrar indicador de carregamento
  const btnSubmit = this.querySelector('button[type="submit"]');
  const btnTextoOriginal = btnSubmit.innerHTML;
  btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';
  btnSubmit.disabled = true;

  const formData = new FormData(this);

  try {
    const res = await fetch('saque.php', {
      method: 'POST',
      body: formData
    });

    // Verificar se a resposta é JSON
    const contentType = res.headers.get('content-type');
    
    if (contentType && contentType.includes('application/json')) {
      const json = await res.json();
      
      if (json.status === 'sucesso') {
        mostrarPopup('<i class="fas fa-check-circle"></i> ' + json.mensagem, 'sucesso');
        setTimeout(() => location.reload(), 2000);
      } else {
        mostrarPopup('<i class="fas fa-exclamation-circle"></i> ' + (json.mensagem || 'Erro ao processar saque.'), 'erro');
      }
    } else {
      // Se não for JSON, pegamos o texto da resposta para debug
      const text = await res.text();
      console.error('Resposta não-JSON recebida:', text);
      mostrarPopup('<i class="fas fa-exclamation-circle"></i> Erro de comunicação com o servidor. Verifique o console.', 'erro');
    }
  } catch (erro) {
    console.error('Erro ao processar saque:', erro);
    mostrarPopup('<i class="fas fa-exclamation-circle"></i> Erro de comunicação com o servidor.', 'erro');
  } finally {
    // Restaurar o botão independente do resultado
    btnSubmit.innerHTML = btnTextoOriginal;
    btnSubmit.disabled = false;
  }
});

</script>
    
    
  

    
<script>
document.getElementById("form-saque").addEventListener("submit", function(e) {
    const input = this.querySelector("input[name='valor']");
    input.value = input.value.replace(',', '.');
});


</script>

<script>
  const tipoChave = document.getElementById('tipoChave');
  const chaveInput = document.getElementById('chaveDestino');
  let mascaraAtual = null;

  tipoChave.addEventListener('change', () => {
    chaveInput.value = '';
    if (mascaraAtual) mascaraAtual.destroy(); // remove qualquer máscara ativa

    if (tipoChave.value === 'cpf') {
      chaveInput.placeholder = '000.000.000-00';
      mascaraAtual = IMask(chaveInput, {
        mask: '000.000.000-00'
      });

    } else if (tipoChave.value === 'telefone') {
      chaveInput.placeholder = '(00) 00000-0000';
      mascaraAtual = IMask(chaveInput, {
        mask: '(00) 00000-0000'
      });

    } else if (tipoChave.value === 'email') {
      chaveInput.placeholder = 'seu@email.com';
      // Para e-mail, melhor deixar sem máscara
      mascaraAtual = null;

    } else {
      chaveInput.placeholder = 'Chave aleatória';
      mascaraAtual = null;
    }
  });

  // Inicializa máscara com valor default se existir
  tipoChave.dispatchEvent(new Event('change'));
</script>



    <script>
  const btnDepositos = document.getElementById('btnDepositos');
  const btnSaques = document.getElementById('btnSaques');
  const tabDepositos = document.getElementById('tab-depositos');
  const tabSaques = document.getElementById('tab-saques');

  btnDepositos.addEventListener('click', () => {
    tabDepositos.classList.remove('hidden');
    tabSaques.classList.add('hidden');

    btnDepositos.classList.add('bg-green-500', 'text-black');
    btnDepositos.classList.remove('bg-slate-700', 'text-gray-300');

    btnSaques.classList.remove('bg-green-500', 'text-black');
    btnSaques.classList.add('bg-slate-700', 'text-gray-300');
  });

  btnSaques.addEventListener('click', () => {
    tabSaques.classList.remove('hidden');
    tabDepositos.classList.add('hidden');

    btnSaques.classList.add('bg-green-500', 'text-black');
    btnSaques.classList.remove('bg-slate-700', 'text-gray-300');

    btnDepositos.classList.remove('bg-green-500', 'text-black');
    btnDepositos.classList.add('bg-slate-700', 'text-gray-300');
  });
</script>



  </div>
</div>
 
  



