<?php
require_once "includes/protege.php";
require_once "conexao.php";

$totalClientes = $pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
$totalVeiculos = $pdo->query("SELECT COUNT(*) FROM veiculos")->fetchColumn();
$totalOrdens = $pdo->query("SELECT COUNT(*) FROM ordens_servico")->fetchColumn();
$faturamento = $pdo->query("SELECT COALESCE(SUM(valor), 0) FROM ordens_servico WHERE status = 'Concluída'")->fetchColumn();
$ticketMedio = $pdo->query("SELECT COALESCE(AVG(valor), 0) FROM ordens_servico")->fetchColumn();


$porStatus = $pdo->query("SELECT status, COUNT(*) AS total FROM ordens_servico GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);


$sqlSub = "SELECT nome FROM clientes
           WHERE id IN (
               SELECT v.cliente_id FROM veiculos v
               JOIN ordens_servico o ON o.veiculo_id = v.id
               WHERE o.status <> 'Concluída'
           )
           ORDER BY nome";
$clientesPendentes = $pdo->query($sqlSub)->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Oficina Mecânica</title>
</head>
<body>
    <h1>Sistema de Oficina Mecânica</h1>

    <p>
        <a href="clientes/listar.php">Clientes</a> |
        <a href="veiculos/listar.php">Veículos</a> |
        <a href="ordens/listar.php">Ordens de Serviço</a> |
        <a href="logout.php">Sair</a>
    </p>

    <h2>Resumo</h2>
    <ul>
        <li>Clientes cadastrados: <?= $totalClientes ?></li>
        <li>Veículos cadastrados: <?= $totalVeiculos ?></li>
        <li>Ordens de serviço: <?= $totalOrdens ?></li>
        <li>Faturamento (ordens concluídas): R$ <?= number_format($faturamento, 2, ',', '.') ?></li>
        <li>Valor médio por ordem: R$ <?= number_format($ticketMedio, 2, ',', '.') ?></li>
    </ul>

    <h2>Ordens por status</h2>
    <ul>
        <?php foreach ($porStatus as $s): ?>
            <li><?= htmlspecialchars($s['status']) ?>: <?= $s['total'] ?></li>
        <?php endforeach; ?>
    </ul>

    <h2>Clientes com serviço pendente</h2>
    <?php if (count($clientesPendentes) === 0): ?>
        <p>Nenhum cliente com serviço pendente.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($clientesPendentes as $nome): ?>
                <li><?= htmlspecialchars($nome) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</body>
</html>