<?php
require_once "../conexao.php";

$sql = "SELECT o.id, o.descricao, o.valor, o.status, o.data_entrada,
               v.placa, v.modelo, c.nome AS dono
        FROM ordens_servico o
        JOIN veiculos v ON o.veiculo_id = v.id
        JOIN clientes c ON v.cliente_id = c.id
        ORDER BY o.data_entrada DESC, o.id DESC";
$ordens = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Ordens de Serviço</title>
</head>
<body>
    <h1>Ordens de Serviço</h1>
    <p><a href="cadastrar.php">+ Nova ordem</a></p>

    <table border="1" cellpadding="6">
        <tr>
            <th>ID</th>
            <th>Data</th>
            <th>Veículo</th>
            <th>Dono</th>
            <th>Descrição</th>
            <th>Valor</th>
            <th>Status</th>
            <th>Ações</th>
        </tr>
        <?php foreach ($ordens as $o): ?>
        <tr>
            <td><?= $o['id'] ?></td>
            <td><?= date("d/m/Y", strtotime($o['data_entrada'])) ?></td>
            <td><?= htmlspecialchars($o['modelo']) ?> (<?= htmlspecialchars($o['placa']) ?>)</td>
            <td><?= htmlspecialchars($o['dono']) ?></td>
            <td><?= htmlspecialchars($o['descricao']) ?></td>
            <td>R$ <?= number_format($o['valor'], 2, ',', '.') ?></td>
            <td><?= htmlspecialchars($o['status']) ?></td>
            <td>
                <a href="editar.php?id=<?= $o['id'] ?>">Editar</a> |
                <a href="excluir.php?id=<?= $o['id'] ?>" onclick="return confirm('Tem certeza que deseja excluir esta ordem?')">Excluir</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>