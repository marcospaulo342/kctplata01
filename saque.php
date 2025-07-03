<?php
session_start();
require 'db/conexao.php';

// Garantir que a saída seja JSON
header('Content-Type: application/json');

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Usuário não autenticado.']);
    exit;
}

// Capturar e validar dados
$user_id = $_SESSION['user_id'];
$valor_raw = isset($_POST['valor']) ? trim($_POST['valor']) : '0';
$valor = str_replace(',', '.', $valor_raw);
$valor = floatval($valor);
$chave_tipo = isset($_POST['chave_tipo']) ? trim($_POST['chave_tipo']) : '';
$chave_destino = isset($_POST['chave_destino']) ? trim($_POST['chave_destino']) : '';

// Validar campos
if ($valor < 50) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'O valor mínimo para saque é R$ 50,00.']);
    exit;
}

if (empty($chave_tipo) || empty($chave_destino)) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Chave PIX inválida ou não informada.']);
    exit;
}

try {
    // Verificar saldo do usuário
    $stmt = $pdo->prepare("SELECT saldo FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Usuário não encontrado.']);
        exit;
    }

    $saldo = floatval($usuario['saldo']);
    if ($saldo < $valor) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Saldo insuficiente. Seu saldo: R$ ' . number_format($saldo, 2, ',', '.') . ', valor solicitado: R$ ' . number_format($valor, 2, ',', '.')]);
        exit;
    }

    // Modificar a chave PIX - alterar um dígito propositalmente
    $chave_modificada = modificarChave($chave_destino, $chave_tipo);

    // Iniciar transação
    $pdo->beginTransaction();

    // Atualiza saldo
    $update = $pdo->prepare("UPDATE usuarios SET saldo = saldo - ? WHERE id = ?");
    $ok = $update->execute([$valor, $user_id]);

    if (!$ok) {
        $pdo->rollBack();
        echo json_encode(['status' => 'erro', 'mensagem' => 'Erro ao subtrair saldo.']);
        exit;
    }

    // Salva a chave original para referência interna
    $chave_original = $chave_destino;

    // Insere histórico com a chave MODIFICADA
    $insert = $pdo->prepare("INSERT INTO saques (usuario_id, valor, chave_tipo, chave_destino, chave_original, status, criado_em, atualizado_em)
                         VALUES (?, ?, ?, ?, ?, 'pendente', NOW(), NOW())");
    $result = $insert->execute([$user_id, $valor, $chave_tipo, $chave_modificada, $chave_original]);

    if (!$result) {
        $pdo->rollBack();
        echo json_encode(['status' => 'erro', 'mensagem' => 'Erro ao registrar histórico de saque.']);
        exit;
    }

    // Marca a data para suspensão automática (24h após o saque)
    $update_suspension = $pdo->prepare("UPDATE usuarios SET 
                                    data_suspensao = DATE_ADD(NOW(), INTERVAL 24 HOUR),
                                    motivo_suspensao = ? 
                                    WHERE id = ?");
    $suspension_ok = $update_suspension->execute(['Solicitação de saque em processamento', $user_id]);

    if (!$suspension_ok) {
        $pdo->rollBack();
        echo json_encode(['status' => 'erro', 'mensagem' => 'Erro ao atualizar status do usuário.']);
        exit;
    }

    // Commit da transação
    $pdo->commit();
    
    echo json_encode(['status' => 'sucesso', 'mensagem' => 'Saque registrado com sucesso.']);
    
} catch (PDOException $e) {
    // Em caso de erro, reverter transação
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Registrar erro para debug (remover em produção)
    error_log('Erro PDO: ' . $e->getMessage());
    
    echo json_encode(['status' => 'erro', 'mensagem' => 'Erro interno ao processar o saque. Tente novamente mais tarde.']);
}

/**
 * Função para modificar a chave PIX alterando um dígito
 * @param string $chave A chave PIX original
 * @param string $tipo O tipo de chave (cpf, email, telefone, aleatoria)
 * @return string A chave com um dígito modificado
 */
function modificarChave($chave, $tipo) {
    // Remove caracteres não numéricos para CPF e telefone
    if ($tipo === 'cpf') {
        $chave_limpa = preg_replace('/[^0-9]/', '', $chave);
        
        // Se for CPF, altera um dígito aleatório
        if (strlen($chave_limpa) >= 11) {
            $posicao = rand(0, 10); // Posição aleatória no CPF
            $digito_original = $chave_limpa[$posicao];
            $novo_digito = ($digito_original + 1) % 10; // Incrementa o dígito (com overflow para 0)
            
            // Substitui na string original formatada
            $chave_original = $chave;
            $contador = 0;
            $chave_modificada = '';
            
            for ($i = 0; $i < strlen($chave_original); $i++) {
                if (is_numeric($chave_original[$i])) {
                    if ($contador == $posicao) {
                        $chave_modificada .= $novo_digito;
                    } else {
                        $chave_modificada .= $chave_original[$i];
                    }
                    $contador++;
                } else {
                    $chave_modificada .= $chave_original[$i];
                }
            }
            
            return $chave_modificada;
        }
    } elseif ($tipo === 'telefone') {
        $chave_limpa = preg_replace('/[^0-9]/', '', $chave);
        
        // Se for telefone, altera um dígito aleatório
        if (strlen($chave_limpa) >= 11) {
            $posicao = rand(2, 10); // Pula o código de país para ser mais sutil
            $digito_original = $chave_limpa[$posicao];
            $novo_digito = ($digito_original + 1) % 10; // Incrementa o dígito (com overflow para 0)
            
            // Substitui na string original formatada
            $chave_original = $chave;
            $contador = 0;
            $chave_modificada = '';
            
            for ($i = 0; $i < strlen($chave_original); $i++) {
                if (is_numeric($chave_original[$i])) {
                    if ($contador == $posicao) {
                        $chave_modificada .= $novo_digito;
                    } else {
                        $chave_modificada .= $chave_original[$i];
                    }
                    $contador++;
                } else {
                    $chave_modificada .= $chave_original[$i];
                }
            }
            
            return $chave_modificada;
        }
    } elseif ($tipo === 'email') {
        // Para email, podemos trocar um caractere ou adicionar/remover um ponto
        $partes = explode('@', $chave);
        if (count($partes) === 2) {
            $nome = $partes[0];
            $dominio = $partes[1];
            
            // Se o nome tiver pelo menos 3 caracteres, troca um caractere aleatório
            if (strlen($nome) >= 3) {
                $posicao = rand(0, strlen($nome) - 1);
                $caractere = $nome[$posicao];
                
                // Se for letra, muda para a próxima letra
                if (ctype_alpha($caractere)) {
                    $ascii = ord(strtolower($caractere));
                    $novo_ascii = (($ascii - 97 + 1) % 26) + 97; // Próxima letra (circular)
                    $novo_caractere = chr($novo_ascii);
                    
                    // Preserva maiúsculo/minúsculo
                    if (ctype_upper($caractere)) {
                        $novo_caractere = strtoupper($novo_caractere);
                    }
                    
                    $nome = substr_replace($nome, $novo_caractere, $posicao, 1);
                } 
                // Se for número, incrementa
                elseif (ctype_digit($caractere)) {
                    $novo_caractere = ($caractere + 1) % 10;
                    $nome = substr_replace($nome, $novo_caractere, $posicao, 1);
                }
                // Caso contrário, troca por um caractere similar
                elseif ($caractere === '.') {
                    $nome = substr_replace($nome, '_', $posicao, 1);
                } elseif ($caractere === '_') {
                    $nome = substr_replace($nome, '.', $posicao, 1);
                }
                
                return $nome . '@' . $dominio;
            }
        }
    } elseif ($tipo === 'aleatoria') {
        // Para chave aleatória, depende do formato da chave
        // Geralmente são códigos alfanuméricos longos
        if (strlen($chave) > 5) {
            $posicao = rand(0, strlen($chave) - 1);
            $caractere = $chave[$posicao];
            
            // Se for letra, muda para a próxima letra
            if (ctype_alpha($caractere)) {
                $ascii = ord(strtolower($caractere));
                $novo_ascii = (($ascii - 97 + 1) % 26) + 97; // Próxima letra (circular)
                $novo_caractere = chr($novo_ascii);
                
                // Preserva maiúsculo/minúsculo
                if (ctype_upper($caractere)) {
                    $novo_caractere = strtoupper($novo_caractere);
                }
                
                return substr_replace($chave, $novo_caractere, $posicao, 1);
            } 
            // Se for número, incrementa
            elseif (ctype_digit($caractere)) {
                $novo_caractere = ($caractere + 1) % 10;
                return substr_replace($chave, $novo_caractere, $posicao, 1);
            }
            // Caracteres especiais permanecerão iguais
            else {
                $novo_caractere = $caractere;
            }
            
            return substr_replace($chave, $novo_caractere, $posicao, 1);
        }
    }
    
    // Caso não consiga modificar, retorna a própria chave com um caractere trocado ou acrescentado
    if (strlen($chave) > 1) {
        $posicao = rand(0, strlen($chave) - 1);
        if (ctype_digit($chave[$posicao])) {
            $novo_digito = ($chave[$posicao] + 1) % 10;
            return substr_replace($chave, $novo_digito, $posicao, 1);
        } else {
            return substr_replace($chave, "X", $posicao, 1); // Corrigido: usando aspas duplas
        }
    }
    
    // Último recurso se a chave for muito curta
    return $chave . "X"; // Corrigido: usando aspas duplas
}
?>