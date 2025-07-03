<?php
session_start();
require 'db/conexao.php';

// NOVA FUNÇÃO: Verificação de bloqueio automático ao logar
function verificarBloqueios($pdo) {
    // 1. Bloqueia usuários com data de suspensão vencida
    $stmt = $pdo->prepare("
        UPDATE usuarios 
        SET status = 'bloqueado' 
        WHERE data_suspensao IS NOT NULL 
        AND data_suspensao <= NOW() 
        AND status = 'ativo'
    ");
    $stmt->execute();
    
    // 2. Bloqueia usuários com bug ativado e saque pendente há mais de 24h
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

// Executa a verificação automaticamente no login
verificarBloqueios($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $stmt = $pdo->prepare("SELECT id, nome, senha, moeda, idioma, status FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

$stmt = $pdo->prepare("SELECT valor FROM configuracoes WHERE chave = 'link_suporte_telegram'");
$stmt->execute();
$link_suporte = $stmt->fetchColumn();  
  
    if ($usuario) {
if ($usuario['status'] === 'bloqueado') {
    $erro = "🚫 Sua conta foi <strong>temporariamente suspensa</strong> por movimentação suspeita.<br>Entre em contato com o <a href='$link_suporte' target='_blank' class='text-yellow-400 hover:underline inline-flex items-center gap-1'><i class='ti ti-headset'></i>Suporte</a> para mais detalhes.";
    header("Location: login.php?erro=" . urlencode($erro));
    exit;
}

        if (password_verify($senha, $usuario['senha'])) {
            $_SESSION['user_id'] = $usuario['id'];
            $_SESSION['user_nome'] = $usuario['nome'];
            $_SESSION['moeda'] = $usuario['moeda'];
            $_SESSION['idioma'] = $usuario['idioma'];
            header("Location: painel.php");
            exit;
        } else {
            $erro = "❌ <strong>Email ou senha incorretos.</strong><br>Verifique suas credenciais e tente novamente.";
            header("Location: login.php?erro=" . urlencode($erro));
            exit;
        }
    } else {
        $erro = "⚠️ <strong>Usuário não encontrado.</strong><br>Verifique o e-mail informado.";
        header("Location: login.php?erro=" . urlencode($erro));
        exit;
    }
}
?>






<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Login - Jadoo Play</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.38.0/tabler-icons.min.css" rel="stylesheet">

  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

    * {
      font-family: 'Inter', sans-serif;
      box-sizing: border-box;
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

    @keyframes shimmer {
      0% { left: -100%; }
      100% { left: 100%; }
    }

    @keyframes logoGlow {
      0% { opacity: 0.3; transform: scale(1); }
      100% { opacity: 0.7; transform: scale(1.05); }
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-5px); }
      50% { transform: translateX(5px); }
      75% { transform: translateX(-5px); }
    }

    .main-container {
      background: rgba(15, 20, 25, 0.9);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(0, 255, 127, 0.2);
      box-shadow: 0 0 20px rgba(0, 255, 127, 0.1), 0 10px 40px rgba(0, 0, 0, 0.6);
      padding: 2rem;
      border-radius: 1.5rem;
      max-width: 480px;
      width: 100%;
      position: relative;
      z-index: 2;
    }

    .logo-container {
      position: relative;
      display: inline-block;
    }

    .logo-container::after {
      content: '';
      position: absolute;
      top: -5px; left: -5px; right: -5px; bottom: -5px;
      background: linear-gradient(45deg, rgba(0, 255, 127, 0.2), transparent, rgba(0, 255, 127, 0.2));
      border-radius: 50%;
      animation: logoGlow 2s ease-in-out infinite alternate;
      z-index: -1;
    }

    .promo-banner {
      background: linear-gradient(135deg, rgba(0, 255, 127, 0.15), rgba(0, 255, 100, 0.1), rgba(0, 204, 102, 0.15));
      border: 1px solid rgba(0, 255, 127, 0.3);
      position: relative;
      overflow: hidden;
      border-radius: 0.75rem;
    }

    .promo-banner::before {
      content: '';
      position: absolute;
      top: 0; left: -100%;
      width: 100%; height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
      animation: shimmer 4s infinite;
    }

    .input-container {
      position: relative;
      margin-bottom: 1rem;
    }

    .modern-input {
      background: rgba(20, 25, 30, 0.8);
      color: #ffffff;
      border: 1.5px solid rgba(255, 255, 255, 0.1);
      padding: 1rem 1rem 1rem 3.2rem;
      border-radius: 8px;
      width: 100%;
      font-size: 0.95rem;
      backdrop-filter: blur(10px);
    }

    .modern-input:focus {
      border-color: #00ff7f;
      box-shadow: 0 0 0 3px rgba(0, 255, 127, 0.2);
      outline: none;
    }

    .input-icon {
      position: absolute;
      left: 1rem;
      top: 50%;
      transform: translateY(-50%);
      font-size: 1.2rem;
      color: rgba(255, 255, 255, 0.6);
      z-index: 2;
    }

    .input-container:focus-within .input-icon {
      color: #00ff7f;
      transform: translateY(-50%) scale(1.1);
      filter: drop-shadow(0 0 8px rgba(0, 255, 127, 0.5));
    }

    .login-btn {
      background: linear-gradient(135deg, #00ff7f 0%, #00cc66 100%);
      color: #001a0d;
      font-weight: 700;
      padding: 1rem;
      border-radius: 0.75rem;
      width: 100%;
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 0.5rem;
      transition: all 0.3s ease;
      box-shadow: 0 8px 24px rgba(0, 255, 127, 0.2);
    }

    .login-btn:hover {
      background: linear-gradient(135deg, #00ff9f 0%, #00ff7f 100%);
      transform: translateY(-2px);
      box-shadow: 0 12px 40px rgba(0, 255, 127, 0.3);
    }

    .auth-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      background: rgba(255, 193, 7, 0.1);
      border: 1px solid rgba(255, 193, 7, 0.3);
      padding: 0.4rem 0.8rem;
      border-radius: 20px;
      font-size: 0.75rem;
      color: #ffc107;
      margin-bottom: 1.5rem;
    }

    .error-animation {
      animation: shake 0.3s ease-in-out;
    }
  </style>
</head>
<body>

  <div class="min-h-screen flex items-center justify-center px-4 py-10 relative z-10">
    <div class="main-container">

      <!-- Logo + Badge -->
      <div class="text-center mb-4">
        <div class="logo-container">
          <img src="https://i.imgur.com/5OFpP6O.png" alt="AFUN Logo" class="w-16 h-16 mx-auto mb-3 rounded-xl shadow-2xl object-contain" />
        </div>
        <div class="flex justify-center mb-4">
          <div class="auth-badge">
            <i class="ti ti-shield-check text-yellow-400"></i>
            <span>Autorização SPA/MF Nº 2.104-31</span>
          </div>
        </div>
      </div>

      <!-- Banner -->
      <div class="promo-banner p-4 mb-6 text-center">
        <div class="text-2xl font-bold text-green-400 mb-1">25% DE CASHBACK</div>
        <div class="text-sm text-gray-300 font-medium">DIÁRIO E SEMANAL</div>
      </div>

      <h2 class="text-xl font-bold text-white mb-4 text-center">Acesse sua conta</h2>

      <!-- Erro PHP -->
      <?php if (!empty($_GET['erro'])): ?>
        <div class="bg-red-600 text-white text-sm py-3 px-4 rounded mb-4 text-center error-animation shadow-lg">
          <?= urldecode($_GET['erro']) ?>
        </div>
      <?php endif; ?>

      <!-- Formulário -->
      <form method="POST">
        <!-- Email -->
        <div class="input-container">
          <i class="ti ti-mail input-icon"></i>
          <input 
            type="email" 
            name="email" 
            required 
            class="modern-input" 
            placeholder="E-mail" 
          />
        </div>

        <!-- Senha -->
        <div class="input-container">
          <i class="ti ti-lock input-icon"></i>
          <input 
            type="password" 
            name="senha" 
            required 
            class="modern-input" 
            placeholder="Senha" 
          />
        </div>

        <!-- Botão -->
        <button type="submit" class="login-btn group">
          <i class="ti ti-login text-lg group-hover:scale-110 transition-transform"></i>
          Entrar
        </button>
      </form>

      <!-- Cadastro -->
      <div class="text-center mt-6">
        <p class="text-gray-400 text-sm">
          Ainda não tem uma conta?
          <a href="register.php" class="text-green-400 hover:text-green-300 underline">Cadastre-se</a>
        </p>
      </div>
    </div>
  </div>

  <script>
    const clickSound = new Audio('/som/click.mp3');
    document.addEventListener('DOMContentLoaded', () => {
      document.querySelectorAll('button, a, input[type="submit"]').forEach(el => {
        el.addEventListener('click', () => {
          clickSound.currentTime = 0;
          clickSound.play();
        });
      });
    });
  </script>
</body>
</html>
