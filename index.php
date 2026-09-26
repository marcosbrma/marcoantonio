<?php
require 'conexao.php';

// Processar formulário de inserção
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $descricao = $_POST['descricao'] ?? '';
    $valor = $_POST['valor'] ?? 0;
    $tipo = $_POST['tipo'] ?? 'receita';
    $data = $_POST['data'] ?? date('Y-m-d');

    if (!empty($descricao) && $valor > 0) {
        $stmt = $pdo->prepare("INSERT INTO transacoes (descricao, valor, tipo, data) VALUES (?, ?, ?, ?)");
        $stmt->execute([$descricao, $valor, $tipo, $data]);
        header("Location: index.php");
        exit;
    }
}

// Processar exclusão
if (isset($_GET['excluir'])) {
    $id = (int) $_GET['excluir'];
    $stmt = $pdo->prepare("DELETE FROM transacoes WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: index.php");
    exit;
}

// Buscar transações
$stmt = $pdo->query("SELECT * FROM transacoes ORDER BY data DESC");
$transacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular saldos
$total_receitas = 0;
$total_despesas = 0;
foreach ($transacoes as $t) {
    if ($t['tipo'] === 'receita') {
        $total_receitas += $t['valor'];
    } else {
        $total_despesas += $t['valor'];
    }
}
$saldo = $total_receitas - $total_despesas;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Controle Financeiro</title>
    
    <!-- Fontes do Google -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Ícones do FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="overlay"></div>
    <div class="container">
        <header class="header">
            <h1><i class="fas fa-chart-line"></i> Controle Financeiro Pro</h1>
            <p>Gestão Inteligente de Gastos Pessoais</p>
        </header>
        
        <div class="resumo">
            <div class="card receita">
                <div class="card-icon"><i class="fas fa-arrow-up"></i></div>
                <div class="card-info">
                    <h3>Receitas</h3>
                    <p>R$ <?php echo number_format($total_receitas, 2, ',', '.'); ?></p>
                </div>
            </div>
            <div class="card despesa">
                <div class="card-icon"><i class="fas fa-arrow-down"></i></div>
                <div class="card-info">
                    <h3>Despesas</h3>
                    <p>R$ <?php echo number_format($total_despesas, 2, ',', '.'); ?></p>
                </div>
            </div>
            <div class="card saldo <?php echo $saldo >= 0 ? 'positivo' : 'negativo'; ?>">
                <div class="card-icon"><i class="fas fa-wallet"></i></div>
                <div class="card-info">
                    <h3>Saldo Atual</h3>
                    <p>R$ <?php echo number_format($saldo, 2, ',', '.'); ?></p>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="formulario">
                <h2><i class="fas fa-plus-circle"></i> Nova Transação</h2>
                <form action="index.php" method="POST">
                    <div class="input-group">
                        <label>Descrição</label>
                        <div class="input-wrapper">
                            <i class="fas fa-tag"></i>
                            <input type="text" name="descricao" placeholder="Ex: Salário, Conta de Luz" required>
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <label>Valor (R$)</label>
                        <div class="input-wrapper">
                            <i class="fas fa-dollar-sign"></i>
                            <input type="number" step="0.01" name="valor" placeholder="0.00" required>
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <label>Tipo de Transação</label>
                        <div class="input-wrapper">
                            <i class="fas fa-exchange-alt"></i>
                            <select name="tipo" required>
                                <option value="receita">Receita (Entrada)</option>
                                <option value="despesa">Despesa (Saída)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <label>Data</label>
                        <div class="input-wrapper">
                            <i class="fas fa-calendar-alt"></i>
                            <input type="date" name="data" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Registrar Transação</button>
                </form>
            </div>

            <div class="historico">
                <h2><i class="fas fa-list-alt"></i> Histórico Recente</h2>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Descrição</th>
                                <th>Tipo</th>
                                <th>Valor</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($transacoes) > 0): ?>
                                <?php foreach ($transacoes as $t): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($t['data'])); ?></td>
                                        <td><strong><?php echo htmlspecialchars($t['descricao']); ?></strong></td>
                                        <td>
                                            <span class="badge <?php echo $t['tipo']; ?>">
                                                <?php echo $t['tipo'] === 'receita' ? '<i class="fas fa-arrow-up"></i> Receita' : '<i class="fas fa-arrow-down"></i> Despesa'; ?>
                                            </span>
                                        </td>
                                        <td class="valor-<?php echo $t['tipo']; ?>">R$ <?php echo number_format($t['valor'], 2, ',', '.'); ?></td>
                                        <td>
                                            <a href="index.php?excluir=<?php echo $t['id']; ?>" class="btn-excluir" onclick="return confirm('Tem certeza que deseja excluir?');" title="Excluir">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="empty-state">
                                        <i class="fas fa-inbox fa-3x"></i>
                                        <p>Nenhuma transação registrada ainda.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
