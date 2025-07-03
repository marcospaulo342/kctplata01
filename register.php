<?php
session_start();
require 'db/conexao.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nome = $_POST['nome'];
  $email = $_POST['email'];
  $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);

  // Verifica se o email já existe
  $check = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
  $check->execute([$email]);
  if ($check->rowCount() > 0) {
    $erro = "⚠️ Este e-mail já está cadastrado. Tente recuperar a conta ou use outro e-mail.";
  } else {
    $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");
    $stmt->execute([$nome, $email, $senha]);

    $_SESSION['user_id'] = $pdo->lastInsertId();
    $_SESSION['user_nome'] = $nome;

    header("Location: painel.php");
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Registrar-se - Jadoo Play</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.38.0/tabler-icons.min.css" rel="stylesheet">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
    
    * { 
      font-family: 'Inter', sans-serif; 
      box-sizing: border-box;
    }

    body {
  position: relative;
  min-height: 100vh;
  overflow-x: hidden;
  background: #000;
}

body::before {
  content: '';
  position: fixed;
  top: 0; left: 0;
  width: 100%; height: 100%;
  background: url('https://img.freepik.com/fotos-gratis/imagem-aproximada-de-uma-mesa-de-jogo-em-um-dos-cassinos-de-las-vegas_181624-44655.jpg?semt=ais_hybrid&w=740') center/cover no-repeat;
  background-attachment: fixed;
  filter: blur(6px) brightness(0.4);
  z-index: -2;
}


    @keyframes particleFloat {
      0%, 100% { opacity: 1; transform: translateY(0px); }
      50% { opacity: 0.7; transform: translateY(-10px); }
    }

    /* Main container */
    .main-container {
      background: rgba(15, 20, 25, 0.85);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(0, 255, 127, 0.15);
      box-shadow: 
        0 25px 50px rgba(0, 0, 0, 0.5),
        0 0 0 1px rgba(255, 255, 255, 0.03) inset,
        0 0 20px rgba(0, 255, 127, 0.1);
      position: relative;
      overflow: hidden;
    }

    /* Promotional banner */
    .promo-banner {
      background: linear-gradient(135deg, 
        rgba(0, 255, 127, 0.15) 0%, 
        rgba(0, 255, 100, 0.1) 50%, 
        rgba(0, 204, 102, 0.15) 100%);
      border: 1px solid rgba(0, 255, 127, 0.3);
      position: relative;
      overflow: hidden;
    }

    .promo-banner::before {
      content: '';
      position: absolute;
      top: 0; left: -100%;
      width: 100%; height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
      animation: shimmer 3s infinite;
    }

    @keyframes shimmer {
      0% { left: -100%; }
      100% { left: 100%; }
    }

    /* Logo styling */
    .logo-container {
      position: relative;
      display: inline-block;
    }

    .logo-container::after {
      content: '';
      position: absolute;
      top: -5px; left: -5px; right: -5px; bottom: -5px;
      background: linear-gradient(45deg, 
        rgba(0, 255, 127, 0.2), 
        transparent, 
        rgba(0, 255, 127, 0.2));
      border-radius: 50%;
      animation: logoGlow 2s ease-in-out infinite alternate;
      z-index: -1;
    }

    @keyframes logoGlow {
      0% { opacity: 0.3; transform: scale(1); }
      100% { opacity: 0.7; transform: scale(1.05); }
    }

    /* Modern input styling */
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
      font-weight: 400;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      backdrop-filter: blur(10px);
    }

    .modern-input:focus {
      background: rgba(20, 25, 30, 0.9);
      border-color: #00ff7f;
      box-shadow: 
        0 0 0 3px rgba(0, 255, 127, 0.2),
        0 4px 12px rgba(0, 255, 127, 0.15);
      outline: none;
      transform: translateY(-1px);
    }

    .modern-input::placeholder {
      color: rgba(255, 255, 255, 0.5);
      font-weight: 400;
    }

    .modern-input:focus::placeholder {
      color: rgba(255, 255, 255, 0.7);
    }

    .input-icon {
      position: absolute;
      left: 1rem;
      top: 50%;
      transform: translateY(-50%);
      font-size: 1.2rem;
      color: rgba(255, 255, 255, 0.6);
      transition: all 0.3s ease;
      z-index: 2;
    }

    .input-container:focus-within .input-icon {
      color: #00ff7f;
      transform: translateY(-50%) scale(1.1);
      filter: drop-shadow(0 0 8px rgba(0, 255, 127, 0.5));
    }

    /* Phone input special styling */
    .phone-input {
      padding-left: 4.5rem;
    }

    .phone-prefix {
      position: absolute;
      left: 3.2rem;
      top: 50%;
      transform: translateY(-50%);
      color: #00ff7f;
      font-weight: 600;
      font-size: 0.95rem;
      z-index: 2;
    }

    /* Button styling */
    .register-btn {
      background: linear-gradient(135deg, #00ff7f 0%, #00cc66 100%);
      color: #001a0d;
      font-weight: 700;
      padding: 1rem 2rem;
      border-radius: 8px;
      width: 100%;
      border: none;
      cursor: pointer;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
      box-shadow: 0 4px 20px rgba(0, 255, 127, 0.3);
      font-size: 1rem;
    }

    .register-btn::before {
      content: '';
      position: absolute;
      top: 0; left: -100%;
      width: 100%; height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
      transition: left 0.5s;
    }

    .register-btn:hover {
      background: linear-gradient(135deg, #00ff9f 0%, #00ff7f 100%);
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(0, 255, 127, 0.4);
    }

    .register-btn:hover::before {
      left: 100%;
    }

    .register-btn:active {
      transform: translateY(0);
    }

    /* Custom checkbox */
    .custom-checkbox {
      appearance: none;
      width: 18px;
      height: 18px;
      border: 2px solid rgba(255, 255, 255, 0.3);
      border-radius: 4px;
      background: rgba(20, 25, 30, 0.6);
      cursor: pointer;
      position: relative;
      transition: all 0.3s ease;
      flex-shrink: 0;
    }

    .custom-checkbox:checked {
      background: linear-gradient(135deg, #00ff7f, #00cc66);
      border-color: #00ff7f;
      transform: scale(1.05);
    }

    .custom-checkbox:checked::after {
      content: '✓';
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      color: #001a0d;
      font-weight: bold;
      font-size: 11px;
    }

    /* Links */
    .login-link, .terms-link {
      color: #00ff7f;
      text-decoration: none;
      font-weight: 500;
      transition: all 0.3s ease;
    }

    .login-link:hover, .terms-link:hover {
      color: #00ff9f;
      text-shadow: 0 0 8px rgba(0, 255, 127, 0.6);
    }

    /* Authorization badge */
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

    /* Responsive adjustments */
    @media (max-width: 640px) {
      .main-container {
        margin: 0.5rem;
        padding: 1.5rem;
        border-radius: 16px;
      }
      
      .modern-input {
        padding: 0.9rem 0.9rem 0.9rem 3rem;
        font-size: 0.9rem;
      }
      
      .phone-input {
        padding-left: 4.2rem;
      }
      
      .input-icon {
        left: 0.9rem;
        font-size: 1.1rem;
      }
      
      .phone-prefix {
        left: 3rem;
        font-size: 0.9rem;
      }
    }

    /* Additional code option */
    .expandable-option {
      color: rgba(255, 255, 255, 0.7);
      font-size: 0.9rem;
      cursor: pointer;
      transition: color 0.3s ease;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .expandable-option:hover {
      color: #00ff7f;
    }

    .expandable-option i {
      transition: transform 0.3s ease;
    }

    .expandable-option.expanded i {
      transform: rotate(180deg);
    }

    /* Divider */
    .divider {
      position: relative;
      text-align: center;
      margin: 1.5rem 0;
    }

    .divider::before {
      content: '';
      position: absolute;
      top: 50%;
      left: 0;
      right: 0;
      height: 1px;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    }

    .divider span {
      background: rgba(15, 20, 25, 0.9);
      padding: 0 1rem;
      color: rgba(255, 255, 255, 0.6);
      font-size: 0.85rem;
    }

    /* Google button */
    .google-btn {
      background: rgba(30, 35, 40, 0.8);
      color: #ffffff;
      border: 1.5px solid rgba(255, 255, 255, 0.2);
      padding: 0.8rem 1.5rem;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.75rem;
      font-weight: 500;
      backdrop-filter: blur(10px);
    }

    .google-btn:hover {
      background: rgba(40, 45, 50, 0.9);
      border-color: rgba(255, 255, 255, 0.3);
      transform: translateY(-1px);
    }
  </style>
</head>
<body>

  <!-- Main content -->
  <div class="min-h-screen flex items-center justify-center px-4 py-6 relative z-10">
    <div class="main-container rounded-2xl p-6 w-full max-w-md">

      <!-- Logo and title -->
      <div class="text-center mb-6">
        <div class="logo-container">
          <img src="https://i.imgur.com/5OFpP6O.png" alt="AFUN Logo" class="w-16 h-16 mx-auto mb-3 rounded-xl shadow-2xl object-contain" />
        </div>
        
        <!-- Authorization badge -->
        <div class="flex justify-center mb-4">
          <div class="auth-badge">
            <i class="ti ti-shield-check text-yellow-400"></i>
            <span>Autorização SPA/MF Nº 2.104-31</span>
          </div>
        </div>
      </div>

      <!-- Promotional banner -->
      <div class="promo-banner rounded-xl p-4 mb-6 text-center">
        <div class="text-2xl font-bold text-green-400 mb-1">25% DE CASHBACK</div>
        <div class="text-sm text-gray-300 font-medium">DIÁRIO E SEMANAL</div>
      </div>

      <h2 class="text-xl font-bold text-white mb-6">Registrar-se</h2>

      <!-- Registration form -->
      <form method="POST">

                <!-- Nome (keeping original structure but visually hidden or integrated) -->
        <div class="input-container">
          <i class="ti ti-id-badge input-icon"></i>
          <input 
            type="text" 
            name="nome" 
            required 
            class="modern-input" 
            placeholder="Nome completo" 
          />
        </div>
        
        <!-- Email -->
        <div class="input-container">
          <i class="ti ti-at input-icon"></i>
          <input 
            type="email" 
            name="email" 
            required 
            class="modern-input" 
            placeholder="E-mail" 
          />
        </div>

        <!-- Password -->
        <div class="input-container">
          <i class="ti ti-shield-lock input-icon"></i>
          <input 
            type="password" 
            name="senha" 
            required 
            class="modern-input" 
            placeholder="Senha" 
          />
        </div>

        <!-- Terms checkbox -->
        <label class="flex items-start gap-3 cursor-pointer mb-4">
          <input type="checkbox" required class="custom-checkbox mt-1" />
          <span class="text-sm text-gray-300 leading-relaxed">
            Tenho mais de 18 anos e aceito a 
            <a href="#" class="terms-link">Política de Privacidade</a> 
            e os 
            <a href="#" class="terms-link">Termos e Condições</a>
          </span>
        </label>

        <!-- Promotions checkbox -->
        <label class="flex items-start gap-3 cursor-pointer mb-6">
          <input type="checkbox" checked class="custom-checkbox mt-1" />
          <span class="text-sm text-gray-300">
            Receba promoções por e-mail
          </span>
        </label>

        <!-- Register button -->
        <button type="submit" class="register-btn">
          <span class="text-lg font-bold">Registrar-se</span>
        </button>
      </form>

      <!-- Login section -->
      <div class="text-center mt-6">
        <p class="text-gray-400 text-sm mb-4">
          Já tem conta? 
          <a href="login.php" class="login-link font-semibold">Entrar</a>
        </p>



  <!-- Original script maintained -->
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

      // Expandable code option functionality
      const expandableOption = document.querySelector('.expandable-option');
      if (expandableOption) {
        expandableOption.addEventListener('click', () => {
          expandableOption.classList.toggle('expanded');
        });
      }
    });
  </script>
</body>
</html>











 