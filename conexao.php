<?php
// Configurações locais (XAMPP/Laragon)
// Para o InfinityFree, substitua pelos dados do painel:
$host = 'localhost';
$dbname = 'controle_gastos';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    // Configura para mostrar erros em ambiente local
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
?>
