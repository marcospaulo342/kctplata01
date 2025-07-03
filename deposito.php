<?php
require 'db/conexao.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1) {
    die("ID inválido.");
}

$stmt = $pdo->prepare("SELECT * FROM depositos WHERE id = ?");
$stmt->execute([$id]);
$deposito = $stmt->fetch();

$stmt = $pdo->prepare("SELECT valor FROM configuracoes WHERE chave = 'link_suporte_telegram'");
$stmt->execute();
$link_suporte = $stmt->fetchColumn();



if (!$deposito) {
    die("Depósito não encontrado.");
}

$valor = $deposito['valor'];
$transactionId = $deposito['cod_externo'];
$qrcodeUrl = $deposito['pix_codigo'] ?? '';
$qrcodeImage = $deposito['qrcode'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pagamento PIX</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.38.0/tabler-icons.min.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  
  <style>
    body {
      background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
      min-height: 100vh;
    }

    .glass-card {
      background: rgba(15, 23, 42, 0.85);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(34, 197, 94, 0.2);
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    }

    .qr-glow {
      box-shadow: 0 0 50px rgba(34, 197, 94, 0.3);
    }

    .pix-pulse {
      animation: pix-pulse 2s infinite;
    }

    @keyframes pix-pulse {
      0%, 100% { 
        box-shadow: 0 0 20px rgba(34, 197, 94, 0.4);
        transform: scale(1);
      }
      50% { 
        box-shadow: 0 0 40px rgba(34, 197, 94, 0.6);
        transform: scale(1.02);
      }
    }

    .status-animation {
      animation: status-float 3s ease-in-out infinite;
    }

    @keyframes status-float {
      0%, 100% { transform: translateY(0px); }
      50% { transform: translateY(-5px); }
    }

    .copy-button-hover {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .copy-button-hover:hover {
      transform: translateY(-2px);
      box-shadow: 0 20px 40px rgba(34, 197, 94, 0.4);
    }

    .floating-elements::before {
      content: '';
      position: absolute;
      top: 20%;
      left: 10%;
      width: 60px;
      height: 60px;
      background: radial-gradient(circle, rgba(34, 197, 94, 0.1) 0%, transparent 70%);
      border-radius: 50%;
      animation: float 6s ease-in-out infinite;
    }

    .floating-elements::after {
      content: '';
      position: absolute;
      bottom: 20%;
      right: 10%;
      width: 80px;
      height: 80px;
      background: radial-gradient(circle, rgba(59, 130, 246, 0.05) 0%, transparent 70%);
      border-radius: 50%;
      animation: float 8s ease-in-out infinite reverse;
    }

    @media (min-width: 640px) {
      .floating-elements::before {
        width: 100px;
        height: 100px;
      }
      
      .floating-elements::after {
        width: 150px;
        height: 150px;
      }
    }

    @keyframes float {
      0%, 100% { transform: translateY(0px) rotate(0deg); }
      50% { transform: translateY(-20px) rotate(180deg); }
    }

    .gradient-text {
      background: linear-gradient(135deg, #22c55e, #10b981, #059669);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .code-input {
      background: linear-gradient(135deg, rgba(15, 23, 42, 0.8) 0%, rgba(30, 41, 59, 0.6) 100%);
      border: 1px solid rgba(34, 197, 94, 0.3);
    }

    .code-input:focus {
      border-color: rgba(34, 197, 94, 0.6);
      box-shadow: 0 0 20px rgba(34, 197, 94, 0.2);
    }

    .loading-dots::after {
      content: '';
      animation: loading-dots 1.5s infinite;
    }

    @keyframes loading-dots {
      0%, 20% { content: '.'; }
      40% { content: '..'; }
      60%, 100% { content: '...'; }
    }
  </style>
</head>

<body class="min-h-screen flex items-center justify-center px-3 py-4 sm:p-4 relative overflow-hidden">
  
  <!-- Floating background elements -->
  <div class="floating-elements absolute inset-0 pointer-events-none"></div>

  <!-- Main Payment Container -->
  <div class="w-full max-w-sm sm:max-w-lg mx-auto">
    
    <!-- Header Section -->
    <div class="text-center mb-4 sm:mb-8">
      <!-- Back to Panel Button -->
      <div class="flex justify-start mb-4">
        <a href="painel.php" class="inline-flex items-center gap-2 bg-slate-800/60 hover:bg-slate-700/60 text-gray-300 hover:text-white px-3 sm:px-4 py-2 rounded-xl border border-slate-600/50 hover:border-green-500/30 transition-all duration-300 shadow-lg hover:shadow-xl">
          <i class="fas fa-arrow-left text-sm"></i>
          <span class="text-xs sm:text-sm font-medium">Voltar ao inicio</span>
        </a>
      </div>
      
      <div class="inline-flex items-center gap-2 sm:gap-3 mb-3 sm:mb-4">
        <div class="bg-gradient-to-br from-green-500 to-emerald-600 p-2 sm:p-3 rounded-xl sm:rounded-2xl shadow-2xl">
          <i class="fab fa-pix text-white text-lg sm:text-2xl"></i>
        </div>
        <div class="text-left">
          <h1 class="text-xl sm:text-2xl font-bold gradient-text">Pagamento PIX</h1>
          <p class="text-gray-400 text-xs sm:text-sm">Escaneie ou copie o código</p>
        </div>
      </div>
      
      <!-- Transaction ID -->
      <div class="inline-flex items-center gap-2 bg-slate-800/50 px-3 sm:px-4 py-1.5 sm:py-2 rounded-full border border-green-500/20">
        <i class="fas fa-hashtag text-green-400 text-xs"></i>
        <span class="text-gray-300 text-xs sm:text-sm">ID:</span>
        <span class="text-green-400 font-mono text-xs sm:text-sm"><?= strtoupper(substr($transactionId, 0, 10)) ?></span>
      </div>
    </div>

    <!-- Main Payment Card -->
    <div class="glass-card rounded-2xl sm:rounded-3xl p-4 sm:p-8 space-y-5 sm:space-y-8">
      
      <!-- QR Code Section -->
      <div class="text-center">
        <div class="relative inline-block">
          <!-- QR Code Container -->
          <div class="bg-white p-3 sm:p-6 rounded-xl sm:rounded-2xl shadow-2xl qr-glow pix-pulse">
            <img src="<?= $qrcodeImage ?>" alt="QR Code PIX" class="w-32 h-32 sm:w-48 sm:h-48 mx-auto" />
          </div>
          
          <!-- PIX Logo Overlay -->
          <div class="absolute -bottom-2 sm:-bottom-3 left-1/2 transform -translate-x-1/2">
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-3 py-1.5 sm:px-4 sm:py-2 rounded-full shadow-lg">
              <span class="text-white font-bold text-xs sm:text-sm">PIX</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Amount Display -->
      <div class="text-center">
        <p class="text-gray-400 text-xs sm:text-sm mb-1 sm:mb-2">Valor a pagar</p>
        <div class="text-2xl sm:text-4xl font-black gradient-text mb-1 sm:mb-2 break-words">
          <?= 'R$ ' . number_format($valor, 2, ',', '.') ?>
        </div>
        <div class="flex items-center justify-center gap-2 text-green-400">
          <i class="fas fa-shield-check text-xs sm:text-sm"></i>
          <span class="text-xs">Pagamento seguro</span>
        </div>
      </div>

      <!-- PIX Code Section -->
      <div class="space-y-3 sm:space-y-4">
        <div class="text-center">
          <h3 class="text-white font-semibold text-sm sm:text-base mb-1 sm:mb-2 flex items-center justify-center gap-2">
            <i class="fas fa-qrcode text-green-400 text-sm"></i>
            Código PIX
          </h3>
          <p class="text-gray-400 text-xs sm:text-sm">Copie e cole no seu app de pagamento</p>
        </div>
        
        <!-- Code Input -->
        <div class="relative">
          <input 
            id="pixCode" 
            readonly 
            value="<?= $qrcodeUrl ?>"
            class="code-input w-full text-white text-xs sm:text-sm font-mono px-3 sm:px-4 py-3 sm:py-4 rounded-lg sm:rounded-xl border focus:outline-none transition-all duration-300 break-all"
          />
          <div class="absolute right-2 sm:right-3 top-3 sm:top-1/2 sm:transform sm:-translate-y-1/2">
            <i class="fas fa-code text-green-400 text-sm"></i>
          </div>
        </div>

        <!-- Copy Button -->
        <button 
          onclick="copiarCodigo()" 
          type="button"
          class="copy-button-hover w-full bg-gradient-to-r from-green-500 via-emerald-500 to-green-600 hover:from-green-600 hover:via-emerald-600 hover:to-green-700 text-white font-bold py-3 sm:py-4 px-4 sm:px-6 rounded-lg sm:rounded-xl shadow-2xl flex items-center justify-center gap-2 sm:gap-3 relative overflow-hidden group">
          
          <!-- Button background animation -->
          <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
          
          <!-- Button content -->
          <i class="fas fa-copy text-base sm:text-lg relative z-10"></i>
          <span class="relative z-10 text-sm sm:text-lg">Copiar código PIX</span>
          <i class="fas fa-arrow-right text-base sm:text-lg relative z-10 group-hover:translate-x-1 transition-transform"></i>
        </button>
      </div>

      <!-- Status Section -->
      <div class="text-center">
        <div class="inline-flex items-center gap-2 sm:gap-3 bg-yellow-500/10 px-4 sm:px-6 py-3 sm:py-4 rounded-xl sm:rounded-2xl border border-yellow-500/20 status-animation">
          <div class="relative">
            <div class="w-3 h-3 sm:w-4 sm:h-4 bg-yellow-400 rounded-full animate-pulse"></div>
            <div class="absolute inset-0 w-3 h-3 sm:w-4 sm:h-4 bg-yellow-400 rounded-full animate-ping opacity-75"></div>
          </div>
          <div class="text-left">
            <p class="text-yellow-300 font-semibold text-xs sm:text-sm">Aguardando pagamento</p>
            <p class="text-yellow-400/80 text-xs loading-dots">Processando</p>
          </div>
        </div>
      </div>

      <!-- Help Section -->
      <div class="bg-slate-800/30 p-3 sm:p-4 rounded-lg sm:rounded-xl border border-slate-700/50">
        <h4 class="text-white font-semibold text-xs sm:text-sm mb-2 sm:mb-3 flex items-center gap-2">
          <i class="fas fa-lightbulb text-yellow-400 text-xs sm:text-sm"></i>
          Como pagar?
        </h4>
        <div class="space-y-1 sm:space-y-2 text-xs text-gray-400">
          <div class="flex items-start gap-2">
            <span class="text-green-400 font-bold">1.</span>
            <span>Abra o app do seu banco</span>
          </div>
          <div class="flex items-start gap-2">
            <span class="text-green-400 font-bold">2.</span>
            <span>Escaneie o QR Code ou copie o código</span>
          </div>
          <div class="flex items-start gap-2">
            <span class="text-green-400 font-bold">3.</span>
            <span>Confirme o pagamento</span>
          </div>
        </div>
      </div>

    </div>

    <!-- Footer -->
    <div class="text-center mt-4 sm:mt-6">
      <div class="flex items-center justify-center gap-3 sm:gap-4 text-xs text-gray-500">
        <div class="flex items-center gap-1">
          <i class="fas fa-lock text-xs"></i>
          <span>Seguro</span>
        </div>
        <div class="w-px h-3 sm:h-4 bg-gray-600"></div>
        <div class="flex items-center gap-1">
          <i class="fas fa-clock text-xs"></i>
          <span>Instantâneo</span>
        </div>
        <div class="w-px h-3 sm:h-4 bg-gray-600"></div>
        <div class="flex items-center gap-1">
          <i class="fas fa-check-circle text-xs"></i>
          <span>Confiável</span>
        </div>
      </div>
    </div>

  </div>

  <!-- Success Popup -->
  <div id="popup" class="fixed top-4 sm:top-8 left-1/2 transform -translate-x-1/2 bg-gradient-to-r from-green-500 to-emerald-600 text-white font-semibold px-4 sm:px-6 py-2 sm:py-3 rounded-xl sm:rounded-2xl shadow-2xl opacity-0 transition-all duration-500 z-[9999] flex items-center gap-2 sm:gap-3 pointer-events-none">
    <div class="bg-white/20 p-1 rounded-full">
      <i class="fas fa-check text-xs sm:text-sm"></i>
    </div>
    <span id="popupText" class="text-sm sm:text-base">PIX copiado com sucesso!</span>
  </div>

  <script>
    function copiarCodigo() {
      const pixCode = document.getElementById('pixCode');
      const popup = document.getElementById('popup');
      
      // Copiar código
      pixCode.select();
      pixCode.setSelectionRange(0, 99999);
      navigator.clipboard.writeText(pixCode.value).then(() => {
        // Mostrar popup
        popup.style.opacity = '1';
        popup.style.transform = 'translate(-50%, 0) scale(1)';
        
        // Esconder popup após 3 segundos
        setTimeout(() => {
          popup.style.opacity = '0';
          popup.style.transform = 'translate(-50%, -20px) scale(0.95)';
        }, 3000);
      });
    }

    // Auto-refresh page every 10 seconds to check payment status
    setInterval(() => {
      // Adicione aqui a lógica de verificação de pagamento se necessário
    }, 10000);
  </script>

</body>
</html>
  

  <script>

    function checkPaymentStatus() {
      const urlToCheck = "./status.php?id=<?= $transactionId ?>";

      const interval = setInterval(() => {
        fetch(urlToCheck)
          .then(response => {
            if (!response.ok) throw new Error("Erro na requisição: " + response.status);
            return response.text();
          })
          .then(status => {
            if (status.trim().toUpperCase() === "PAGO") {
              clearInterval(interval);
              const statusEl = document.getElementById("verificando");
if (statusEl) {
  statusEl.innerHTML = "<i class='ti ti-check text-green-400'></i> Pagamento aprovado!";
}

                "<i class='ti ti-check text-green-400'></i> Pagamento aprovado!";
              setTimeout(() => {
                window.location.href = "painel.php";
              }, 1500);
            }
          })
          .catch(error => console.error("Erro ao verificar status:", error));
      }, 2000);
    }

    checkPaymentStatus();
  </script><script>
function copiarCodigo() {
  const codigo = document.getElementById("pixCode");
  navigator.clipboard.writeText(codigo.value).then(() => {
    const popup = document.getElementById("popup");
    popup.classList.remove("hidden");

    // Reinicia animação se clicar múltiplas vezes
    popup.classList.remove("animate-fade-slide");
    void popup.offsetWidth;
    popup.classList.add("animate-fade-slide");

    // Some depois de 2.5s
    setTimeout(() => {
      popup.classList.add("hidden");
    }, 2500);
  });
}

</script>

<script>
function copiarCodigo() {
  const input = document.getElementById("pixCode");
  input.select();
  input.setSelectionRange(0, 99999);
  document.execCommand("copy");

  const popup = document.getElementById("popup");
  popup.classList.remove("opacity-0");
  popup.classList.add("opacity-100");

  setTimeout(() => {
    popup.classList.add("opacity-0");
    popup.classList.remove("opacity-100");
  }, 2000);
}
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

</body>
</html>
  
  
  
  
  
  
  
  
  
  
  
 