<?php
require_once "../includes/protege.php";
require_once "../conexao.php";

$sql = "SELECT v.id, v.placa, v.marca, v.modelo, v.ano, c.nome AS dono
        FROM veiculos v
        JOIN clientes c ON v.cliente_id = c.id
        ORDER BY v.marca, v.modelo";
$veiculos = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Veículos</title>
</head>
<body>
    <h1>Veículos</h1>
    <p><a href="cadastrar.php">+ Novo veículo</a></p>

    <table border="1" cellpadding="6">
        <tr>
            <th>ID</th>
            <th>Placa</th>
            <th>Marca</th>
            <th>Modelo</th>
            <th>Ano</th>
            <th>Dono</th>
            <th>Ações</th>
        </tr>
        <?php foreach ($veiculos as $v): ?>
        <tr>
            <td><?= $v['id'] ?></td>
            <td><?= htmlspecialchars($v['placa']) ?></td>
            <td><?= htmlspecialchars($v['marca']) ?></td>
            <td><?= htmlspecialchars($v['modelo']) ?></td>
            <td><?= $v['ano'] ?></td>
            <td><?= htmlspecialchars($v['dono']) ?></td>
            <td>
                <a href="editar.php?id=<?= $v['id'] ?>">Editar</a> |
                <a href="excluir.php?id=<?= $v['id'] ?>" onclick="return confirm('Tem certeza que deseja excluir este veículo?')">Excluir</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>