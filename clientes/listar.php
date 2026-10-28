<?php
require_once "../conexao.php";

$sql = "SELECT id, nome, telefone, email FROM clientes ORDER BY nome";
$clientes = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Clientes</title>
</head>
<body>
    <h1>Clientes</h1>
    <p><a href="cadastrar.php">+ Novo cliente</a></p>

    <table border="1" cellpadding="6">
        <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>Telefone</th>
            <th>E-mail</th>
        </tr>
        <?php foreach ($clientes as $c): ?>
        <tr>
            <td><?= $c['id'] ?></td>
            <td><?= htmlspecialchars($c['nome']) ?></td>
            <td><?= htmlspecialchars($c['telefone']) ?></td>
            <td><?= htmlspecialchars($c['email']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>