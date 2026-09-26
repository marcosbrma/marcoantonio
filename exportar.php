<?php
session_start();
require 'conexao.php';

// Verifica se está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$mes_selecionado = $_GET['mes'] ?? date('Y-m');
$data_inicio = $mes_selecionado . '-01';
$data_fim = date('Y-m-t', strtotime($data_inicio)); 

// Buscar as transações do mês e do usuário
$stmt = $pdo->prepare("SELECT data, descricao, categoria, tipo, valor FROM transacoes WHERE usuario_id = ? AND data BETWEEN ? AND ? ORDER BY data ASC");
$stmt->execute([$usuario_id, $data_inicio, $data_fim]);
$transacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Definir os cabeçalhos para forçar o download de um arquivo CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="planilha_gastos_' . $mes_selecionado . '.csv"');

// Abrir a saída direta do PHP (funciona como um arquivo virtual)
$saida = fopen('php://output', 'w');

// Adicionar o BOM (Byte Order Mark) para o Excel reconhecer os acentos do português (UTF-8)
fprintf($saida, chr(0xEF).chr(0xBB).chr(0xBF));

// Cabeçalho da planilha (usando ponto e vírgula, que é o padrão do Excel no Brasil)
fputcsv($saida, ['Data de Vencimento', 'Descrição', 'Categoria', 'Tipo', 'Valor (R$)'], ';');

$total_receitas = 0;
$total_despesas = 0;

// Inserir as linhas de transações
foreach ($transacoes as $t) {
    // Formatar os dados para o padrão brasileiro
    $data_formatada = date('d/m/Y', strtotime($t['data']));
    $categoria = $t['categoria'] ?? 'Outros';
    
    // Formatar tipo e somar aos totais
    if ($t['tipo'] === 'receita') {
        $tipo_formatado = 'Entrada';
        $total_receitas += $t['valor'];
    } else {
        $tipo_formatado = 'Saída';
        $total_despesas += $t['valor'];
    }
    
    // Formatar o valor com vírgula para o Excel entender como número no Brasil
    $valor_formatado = number_format($t['valor'], 2, ',', '');

    fputcsv($saida, [$data_formatada, $t['descricao'], $categoria, $tipo_formatado, $valor_formatado], ';');
}

// Linha em branco para separar
fputcsv($saida, ['', '', '', '', ''], ';');

// Linhas de Totais no final da planilha
$saldo_final = $total_receitas - $total_despesas;
fputcsv($saida, ['', '', '', 'TOTAL RECEITAS:', number_format($total_receitas, 2, ',', '')], ';');
fputcsv($saida, ['', '', '', 'TOTAL DESPESAS:', number_format($total_despesas, 2, ',', '')], ';');
fputcsv($saida, ['', '', '', 'SALDO DO MÊS:', number_format($saldo_final, 2, ',', '')], ';');

fclose($saida);
exit;
?>
