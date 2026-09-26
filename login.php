<?php
session_start();
require 'conexao.php';

// Criar novo usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['novo_nome'])) {
    $nome = trim($_POST['novo_nome']);
    $stmt = $pdo->prepare("INSERT INTO usuarios (nome) VALUES (?)");
    $stmt->execute([$nome]);
    
    // Fazer login automaticamente com o novo usuário criado
    $_SESSION['usuario_id'] = $pdo->lastInsertId();
    $_SESSION['usuario_nome'] = $nome;
    header("Location: index.php");
    exit;
}

// Fazer login em usuário existente
if (isset($_GET['entrar'])) {
    $id = (int)$_GET['entrar'];
    
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['usuario_nome'] = $user['nome'];
        header("Location: index.php");
        exit;
    }
}

// Buscar todos os usuários
$stmt = $pdo->query("SELECT * FROM usuarios ORDER BY nome ASC");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selecionar Usuário - Controle de Gastos</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=10">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-header">
            <h1><i class="fas fa-wallet"></i> Controle de Gastos</h1>
            <p>Selecione seu perfil ou crie um novo</p>
        </div>

        <div class="usuarios-lista">
            <h3>Quem está acessando?</h3>
            <?php if (count($usuarios) > 0): ?>
                <div class="grid-usuarios">
                    <?php foreach ($usuarios as $u): ?>
                        <a href="login.php?entrar=<?php echo $u['id']; ?>" class="btn-usuario">
                            <div class="avatar"><i class="fas fa-user"></i></div>
                            <span><?php echo htmlspecialchars($u['nome']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: #64748b; margin-bottom: 20px;">Nenhum usuário cadastrado ainda.</p>
            <?php endif; ?>
        </div>

        <div class="criar-usuario">
            <h3>Novo Perfil</h3>
            <form action="login.php" method="POST">
                <div class="input-wrapper">
                    <i class="fas fa-user-plus"></i>
                    <input type="text" name="novo_nome" placeholder="Digite seu nome" required>
                </div>
                <button type="submit" class="btn-submit" style="margin-top: 15px;">Criar Perfil e Entrar</button>
            </form>
        </div>
    </div>
    
    <footer class="app-footer login-footer">
        <p>Desenvolvido por <strong>Marco Antonio Alves de Miranda</strong> - RU 5079998</p>
    </footer>
</body>
</html>
