<?php
session_start();
require 'conexao.php';

$erro = '';
$sucesso = '';

// Processar Login
if (isset($_POST['acao']) && $_POST['acao'] === 'login') {
    $nome = trim($_POST['nome_login'] ?? '');
    $senha = trim($_POST['senha_login'] ?? '');

    if (!empty($nome) && !empty($senha)) {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE nome = ?");
        $stmt->execute([$nome]);
        $user = $stmt->fetch();

        if ($user) {
            // Verifica a senha (suporta o hash novo ou a senha padrão antiga '123456')
            $senha_valida = false;
            
            if (password_verify($senha, $user['senha'])) {
                $senha_valida = true;
            } elseif ($senha === $user['senha']) {
                $senha_valida = true; // Para as contas antigas criadas antes da atualização
            }

            if ($senha_valida) {
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['usuario_nome'] = $user['nome'];
                header("Location: index.php");
                exit;
            } else {
                $erro = "Senha incorreta!";
            }
        } else {
            $erro = "Usuário não encontrado!";
        }
    } else {
        $erro = "Preencha todos os campos para entrar.";
    }
}

// Processar Novo Cadastro
if (isset($_POST['acao']) && $_POST['acao'] === 'cadastrar') {
    $nome = trim($_POST['nome_cadastro'] ?? '');
    $senha = trim($_POST['senha_cadastro'] ?? '');
    $senha_confirmacao = trim($_POST['senha_confirmacao'] ?? '');

    if (!empty($nome) && !empty($senha) && !empty($senha_confirmacao)) {
        if ($senha !== $senha_confirmacao) {
            $erro = "As senhas não coincidem. Tente novamente!";
        } else {
            // Verificar se o nome já existe
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE nome = ?");
            $stmt->execute([$nome]);
            if ($stmt->fetch()) {
                $erro = "Este nome de usuário já está em uso. Escolha outro.";
            } else {
                // Criptografa a senha por segurança
                $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO usuarios (nome, senha) VALUES (?, ?)");
                $stmt->execute([$nome, $senhaHash]);
                
                $sucesso = "Conta criada com sucesso! Faça o login acima.";
            }
        }
    } else {
        $erro = "Preencha seu nome e confirme a senha.";
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Controle de Gastos</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=13">
    <style>
        .input-wrapper { position: relative; }
    </style>
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-header">
            <h1><i class="fas fa-wallet"></i> Controle de Gastos</h1>
            <p>Acesse sua conta ou cadastre-se</p>
        </div>
        
        <?php if ($erro): ?>
            <div class="dica-inteligente dica-perigo" style="margin-bottom: 20px; text-align: left;">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($erro); ?>
            </div>
        <?php endif; ?>

        <?php if ($sucesso): ?>
            <div class="dica-inteligente dica-sucesso" style="margin-bottom: 20px; text-align: left;">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($sucesso); ?>
            </div>
        <?php endif; ?>

        <!-- Formulário de Login -->
        <form method="POST" action="login.php" style="margin-bottom: 30px; text-align: left;">
            <input type="hidden" name="acao" value="login">
            <div class="input-group">
                <label style="font-size: 0.9rem; font-weight: 500; color: #475569; display: block; margin-bottom: 5px;">Usuário</label>
                <div class="input-wrapper">
                    <i class="fas fa-user"></i>
                    <input type="text" name="nome_login" placeholder="Digite seu nome" required style="width: 100%; padding: 12px 12px 12px 40px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: 'Poppins', sans-serif;">
                </div>
            </div>
            <div class="input-group">
                <label style="font-size: 0.9rem; font-weight: 500; color: #475569; display: block; margin-bottom: 5px;">Senha</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="senha_login" name="senha_login" placeholder="Digite sua senha" required style="width: 100%; padding: 12px 12px 12px 40px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: 'Poppins', sans-serif;">
                </div>
            </div>
            <button type="submit" class="btn-submit" style="width: 100%;">Entrar</button>
        </form>

        <div class="criar-usuario" style="text-align: left; padding-top: 20px; border-top: 1px solid #e2e8f0;">
            <h3 style="margin-bottom: 15px; color: #334155; font-size: 1.1rem; text-align: center;">Ainda não tem conta?</h3>
            <form method="POST" action="login.php">
                <input type="hidden" name="acao" value="cadastrar">
                <div class="input-group">
                    <div class="input-wrapper">
                        <i class="fas fa-user-plus"></i>
                        <input type="text" name="nome_cadastro" placeholder="Novo nome de usuário" required style="width: 100%; padding: 12px 12px 12px 40px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: 'Poppins', sans-serif;">
                    </div>
                </div>
                <div class="input-group">
                    <div class="input-wrapper">
                        <i class="fas fa-key"></i>
                        <input type="password" id="senha_cadastro" name="senha_cadastro" placeholder="Crie uma senha" required style="width: 100%; padding: 12px 12px 12px 40px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: 'Poppins', sans-serif;">
                    </div>
                </div>
                <div class="input-group">
                    <div class="input-wrapper">
                        <i class="fas fa-check-double"></i>
                        <input type="password" id="senha_confirmacao" name="senha_confirmacao" placeholder="Confirme sua senha" required style="width: 100%; padding: 12px 12px 12px 40px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: 'Poppins', sans-serif;">
                    </div>
                </div>
                <button type="submit" class="btn-submit" style="width: 100%; background-color: #3b82f6;">Criar Conta Segura</button>
            </form>
        </div>
    </div>
    
    <footer class="app-footer login-footer">
        <p>Desenvolvido por <strong>Marco Antonio Alves de Miranda</strong> - RU 5079998</p>
    </footer>
</body>
</html>
