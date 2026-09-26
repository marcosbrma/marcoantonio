<?php
session_start();
require 'conexao.php';

// Verifica se está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$usuario_nome = $_SESSION['usuario_nome'];

// Processar formulário de inserção
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $descricao = $_POST['descricao'] ?? '';
    $valor = $_POST['valor'] ?? 0;
    $tipo = $_POST['tipo'] ?? 'receita';
    $categoria = $_POST['categoria'] ?? 'Outros';
    $data = $_POST['data'] ?? date('Y-m-d');

    if (!empty($descricao) && $valor > 0) {
        $recorrente = isset($_POST['recorrente']) && $_POST['recorrente'] == '1';
        $meses_repetir = $recorrente ? 12 : 1;

        $stmt = $pdo->prepare("INSERT INTO transacoes (descricao, valor, tipo, categoria, data, usuario_id) VALUES (?, ?, ?, ?, ?, ?)");
        
        for ($i = 0; $i < $meses_repetir; $i++) {
            if ($i === 0) {
                $nova_data = $data;
            } else {
                $nova_data = date('Y-m-d', strtotime("+$i months", strtotime($data)));
            }
            $stmt->execute([$descricao, $valor, $tipo, $categoria, $nova_data, $usuario_id]);
        }
        
        $mes_redirecionar = substr($data, 0, 7);
        header("Location: index.php?mes=" . $mes_redirecionar);
        exit;
    }
}

// Processar exclusão
if (isset($_GET['excluir'])) {
    $id = (int) $_GET['excluir'];
    $stmt = $pdo->prepare("DELETE FROM transacoes WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id, $usuario_id]);
    
    $mes = $_GET['mes'] ?? date('Y-m');
    header("Location: index.php?mes=" . $mes);
    exit;
}

// Filtro de Mês
$mes_selecionado = $_GET['mes'] ?? date('Y-m');
$data_inicio = $mes_selecionado . '-01';
$data_fim = date('Y-m-t', strtotime($data_inicio)); 
$hoje = date('Y-m-d');

// Buscar transações
$stmt = $pdo->prepare("SELECT * FROM transacoes WHERE usuario_id = ? AND data BETWEEN ? AND ? ORDER BY data ASC");
$stmt->execute([$usuario_id, $data_inicio, $data_fim]);
$transacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupamentos
$realizadas = [];
$a_vencer = [];
$total_receitas = 0; // Somente pagas
$total_despesas = 0; // Somente pagas
$previsto_receitas = 0; // Futuras
$previsto_despesas = 0; // Futuras
$total_geral_receitas = 0; // Pagas + Futuras
$total_geral_despesas = 0; // Pagas + Futuras
$despesas_por_categoria = [];

foreach ($transacoes as $t) {
    // Totais Gerais (para os cards e gráficos)
    if ($t['tipo'] === 'receita') {
        $total_geral_receitas += $t['valor'];
    } else {
        $total_geral_despesas += $t['valor'];
        // Gráfico inclui TODAS as despesas do mês (pagas e a vencer)
        $cat = $t['categoria'] ?? 'Outros';
        $despesas_por_categoria[$cat] = ($despesas_por_categoria[$cat] ?? 0) + $t['valor'];
    }

    // Separação temporal
    if ($t['data'] <= $hoje) {
        $realizadas[] = $t;
        if ($t['tipo'] === 'receita') $total_receitas += $t['valor'];
        else $total_despesas += $t['valor'];
    } else {
        $a_vencer[] = $t;
        if ($t['tipo'] === 'receita') $previsto_receitas += $t['valor'];
        else $previsto_despesas += $t['valor'];
    }
}

$realizadas = array_reverse($realizadas);
$saldo_atual = $total_receitas - $total_despesas;
$saldo_previsto = $saldo_atual + $previsto_receitas - $previsto_despesas;

// Dica Inteligente de Educação Financeira
$dica_classe = "dica-neutra";
$dica_icone = "fas fa-info-circle";
$dica_mensagem = "Comece a registrar suas movimentações financeiras do mês.";

if ($total_geral_receitas > 0) {
    $percentual_gasto = ($total_geral_despesas / $total_geral_receitas) * 100;
    if ($percentual_gasto > 100) {
        $dica_classe = "dica-perigo";
        $dica_icone = "fas fa-exclamation-triangle";
        $dica_mensagem = "Alerta: O total de gastos do mês (" . number_format($percentual_gasto, 0) . "%) superou a receita. Orçamento no vermelho!";
    } elseif ($percentual_gasto > 80) {
        $dica_classe = "dica-alerta";
        $dica_icone = "fas fa-exclamation-circle";
        $dica_mensagem = "Cuidado: Suas despesas representam " . number_format($percentual_gasto, 0) . "% das receitas deste mês.";
    } else {
        $dica_classe = "dica-sucesso";
        $dica_icone = "fas fa-check-circle";
        $dica_mensagem = "Excelente! Seus gastos estão em " . number_format($percentual_gasto, 0) . "%. Você está conseguindo poupar!";
    }
} elseif ($total_geral_despesas > 0) {
    $dica_classe = "dica-alerta";
    $dica_icone = "fas fa-exclamation-circle";
    $dica_mensagem = "Você tem gastos planejados neste mês, mas nenhuma receita registrada.";
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Gastos</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="style.css?v=12">
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="header-title">
                <h1><i class="fas fa-wallet"></i> Controle de Gastos</h1>
                <p class="ocultar-impressao">Dashboard Financeiro Pessoal</p>
            </div>
            
            <div class="user-info ocultar-impressao">
                <span><i class="fas fa-user-circle"></i> Olá, <?php echo htmlspecialchars($usuario_nome); ?></span>
                <a href="exportar.php?mes=<?php echo $mes_selecionado; ?>" class="btn-print"><i class="fas fa-file-excel"></i> Exportar Excel</a>
                <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Sair</a>
            </div>
            
            <div class="filtro-mes ocultar-impressao" style="width: 100%;">
                <form action="index.php" method="GET">
                    <label for="mes"><i class="fas fa-calendar-alt"></i> Mês de Referência:</label>
                    <input type="month" id="mes" name="mes" value="<?php echo $mes_selecionado; ?>" onchange="this.form.submit()">
                </form>
            </div>
        </header>
        
        <!-- Dica de Educação Financeira -->
        <div class="dica-inteligente <?php echo $dica_classe; ?>">
            <i class="<?php echo $dica_icone; ?>"></i>
            <span><?php echo $dica_mensagem; ?></span>
        </div>
        
        <div class="resumo">
            <div class="card receita">
                <div class="card-info">
                    <h3>Total de Receitas</h3>
                    <p>R$ <?php echo number_format($total_geral_receitas, 2, ',', '.'); ?></p>
                </div>
            </div>
            <div class="card despesa">
                <div class="card-info">
                    <h3>Total de Despesas</h3>
                    <p>R$ <?php echo number_format($total_geral_despesas, 2, ',', '.'); ?></p>
                </div>
            </div>
            <div class="card saldo <?php echo $saldo_atual >= 0 ? 'positivo' : 'negativo'; ?>">
                <div class="card-info">
                    <h3>Saldo Atual (Hoje)</h3>
                    <p>R$ <?php echo number_format($saldo_atual, 2, ',', '.'); ?></p>
                </div>
            </div>
            <div class="card previsto">
                <div class="card-info">
                    <h3>Saldo Previsto (Fim do mês)</h3>
                    <p class="<?php echo $saldo_previsto >= 0 ? 'valor-receita' : 'valor-despesa'; ?>">
                        R$ <?php echo number_format($saldo_previsto, 2, ',', '.'); ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="col-esquerda">
                <!-- Gráficos -->
                <div class="card-grafico">
                    <h3>Despesas por Categoria</h3>
                    <?php if (count($despesas_por_categoria) > 0): ?>
                        <div class="canvas-container">
                            <canvas id="graficoCategorias"></canvas>
                        </div>
                    <?php else: ?>
                        <div class="empty-chart">
                            <p>Não há despesas para exibir o gráfico.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Formulário -->
                <div class="formulario ocultar-impressao">
                    <h2>Inserir Lançamento</h2>
                    <form action="index.php" method="POST">
                        <div class="input-group">
                            <label>Descrição</label>
                            <div class="input-wrapper">
                                <i class="fas fa-tag"></i>
                                <input type="text" name="descricao" placeholder="Ex: Conta de Luz, Salário" required>
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
                            <label>Tipo</label>
                            <div class="input-wrapper">
                                <i class="fas fa-exchange-alt"></i>
                                <select name="tipo" required id="tipoSelect">
                                    <option value="despesa">Despesa (Saída)</option>
                                    <option value="receita">Receita (Entrada)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="input-group">
                            <label>Categoria</label>
                            <div class="input-wrapper">
                                <i class="fas fa-list"></i>
                                <select name="categoria" required>
                                    <option value="Alimentação">Alimentação</option>
                                    <option value="Moradia">Moradia</option>
                                    <option value="Transporte">Transporte</option>
                                    <option value="Saúde">Saúde</option>
                                    <option value="Educação">Educação</option>
                                    <option value="Lazer">Lazer</option>
                                    <option value="Salário/Renda">Salário/Renda</option>
                                    <option value="Outros" selected>Outros</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="input-group">
                            <label>Data de Vencimento/Pagamento</label>
                            <div class="input-wrapper">
                                <i class="fas fa-calendar-day"></i>
                                <input type="date" name="data" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        
                        <div class="input-group checkbox-group">
                            <label class="checkbox-label" style="display: flex; align-items: center; cursor: pointer; color: #475569; font-weight: 500; font-size: 0.9rem; margin-top: 10px;">
                                <input type="checkbox" name="recorrente" value="1" style="width: auto; margin-right: 10px; cursor: pointer; transform: scale(1.2);">
                                Lançamento Recorrente (Repetir por 1 ano)
                            </label>
                        </div>
                        
                        <button type="submit" class="btn-submit">Adicionar ao Sistema</button>
                    </form>
                </div>
            </div>

            <div class="col-direita">
                <!-- Contas a Vencer (Planilha) -->
                <div class="historico contas-vencer">
                    <h2><i class="far fa-clock"></i> Contas a Vencer / A Receber</h2>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Vencimento</th>
                                    <th>Descrição</th>
                                    <th>Categoria</th>
                                    <th>Status</th>
                                    <th>Valor</th>
                                    <th class="ocultar-impressao">Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($a_vencer) > 0): ?>
                                    <?php foreach ($a_vencer as $t): ?>
                                        <tr>
                                            <td><?php echo date('d/m', strtotime($t['data'])); ?></td>
                                            <td><strong><?php echo htmlspecialchars($t['descricao']); ?></strong></td>
                                            <td><span class="badge concluido"><?php echo htmlspecialchars($t['categoria'] ?? 'Outros'); ?></span></td>
                                            <td>
                                                <span class="badge pendente">A Vencer</span>
                                            </td>
                                            <td class="valor-<?php echo $t['tipo']; ?>">
                                                <?php echo $t['tipo'] === 'receita' ? '+' : '-'; ?> R$ <?php echo number_format($t['valor'], 2, ',', '.'); ?>
                                            </td>
                                            <td class="ocultar-impressao" style="text-align: right;">
                                                <a href="index.php?excluir=<?php echo $t['id']; ?>&mes=<?php echo $mes_selecionado; ?>" class="btn-excluir" onclick="return confirm('Excluir lançamento?');">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="empty-state">
                                            <p>Nenhuma conta a vencer.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Histórico Realizado -->
                <div class="historico" style="margin-top: 25px;">
                    <h2><i class="fas fa-check-circle"></i> Lançamentos Realizados</h2>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Descrição</th>
                                    <th>Categoria</th>
                                    <th>Valor</th>
                                    <th class="ocultar-impressao">Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($realizadas) > 0): ?>
                                    <?php foreach ($realizadas as $t): ?>
                                        <tr>
                                            <td><?php echo date('d/m', strtotime($t['data'])); ?></td>
                                            <td><strong><?php echo htmlspecialchars($t['descricao']); ?></strong></td>
                                            <td><span class="badge concluido"><?php echo htmlspecialchars($t['categoria'] ?? 'Outros'); ?></span></td>
                                            <td class="valor-<?php echo $t['tipo']; ?>">
                                                <?php echo $t['tipo'] === 'receita' ? '+' : '-'; ?> R$ <?php echo number_format($t['valor'], 2, ',', '.'); ?>
                                            </td>
                                            <td class="ocultar-impressao" style="text-align: right;">
                                                <a href="index.php?excluir=<?php echo $t['id']; ?>&mes=<?php echo $mes_selecionado; ?>" class="btn-excluir" onclick="return confirm('Excluir lançamento?');">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="empty-state">
                                            <p>Nenhum lançamento realizado.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Script do Chart.js para o Gráfico de Categorias -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        <?php if (count($despesas_por_categoria) > 0): ?>
        const ctxCat = document.getElementById('graficoCategorias').getContext('2d');
        new Chart(ctxCat, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_keys($despesas_por_categoria)); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_values($despesas_por_categoria)); ?>,
                    backgroundColor: [
                        '#f43f5e', '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#14b8a6', '#64748b'
                    ],
                    borderWidth: 1,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { font: { family: "'Poppins', sans-serif", size: 11 } }
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
    
    <footer class="app-footer">
        <p>Desenvolvido por <strong>Marco Antonio Alves de Miranda</strong> - RU 5079998</p>
    </footer>
</body>
</html>
