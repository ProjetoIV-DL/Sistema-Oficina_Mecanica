<?php
require_once "../includes/protege.php";
require_once "../conexao.php";

$erro = "";

$id = $_GET["id"] ?? null;
if ($id === null) {
    header("Location: listar.php");
    exit;
}

$veiculos = $pdo->query(
    "SELECT v.id, v.placa, v.modelo, c.nome AS dono
     FROM veiculos v
     JOIN clientes c ON v.cliente_id = c.id
     ORDER BY v.modelo"
)->fetchAll(PDO::FETCH_ASSOC);

$statusLista = ["Aberta", "Em andamento", "Concluída"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $veiculo_id = $_POST["veiculo_id"];
    $descricao = trim($_POST["descricao"]);
    $valor = str_replace(",", ".", trim($_POST["valor"]));
    $status = $_POST["status"];
    $data_entrada = $_POST["data_entrada"];

    if ($veiculo_id === "" || $descricao === "" || $valor === "" || $data_entrada === "") {
        $erro = "Veículo, descrição, valor e data são obrigatórios.";
    } elseif (!is_numeric($valor) || $valor < 0) {
        $erro = "Informe um valor válido.";
    } elseif (!in_array($status, $statusLista)) {
        $erro = "Status inválido.";
    } else {
        $sql = "UPDATE ordens_servico
                SET veiculo_id = :veiculo_id, descricao = :descricao, valor = :valor,
                    status = :status, data_entrada = :data_entrada
                WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ":veiculo_id" => $veiculo_id,
            ":descricao" => $descricao,
            ":valor" => $valor,
            ":status" => $status,
            ":data_entrada" => $data_entrada,
            ":id" => $id
        ]);
        header("Location: listar.php");
        exit;
    }
}

$stmt = $pdo->prepare("SELECT * FROM ordens_servico WHERE id = :id");
$stmt->execute([":id" => $id]);
$ordem = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ordem) {
    die("Ordem de serviço não encontrada.");
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Ordem de Serviço</title>
</head>
<body>
    <h1>Editar Ordem de Serviço</h1>

    <?php if ($erro !== ""): ?>
        <p style="color: red;"><?= htmlspecialchars($erro) ?></p>
    <?php endif; ?>

    <form method="POST">
        <p>
            <label>Veículo:<br>
            <select name="veiculo_id" required>
                <?php foreach ($veiculos as $v): ?>
                    <option value="<?= $v['id'] ?>" <?= $v['id'] == $ordem['veiculo_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($v['modelo']) ?> (<?= htmlspecialchars($v['placa']) ?>) - <?= htmlspecialchars($v['dono']) ?>
                    </option>
                <?php endforeach; ?>
            </select></label>
        </p>
        <p>
            <label>Descrição do serviço:<br>
            <input type="text" name="descricao" maxlength="255" size="50" value="<?= htmlspecialchars($ordem['descricao']) ?>" required></label>
        </p>
        <p>
            <label>Valor (R$):<br>
            <input type="text" name="valor" value="<?= number_format($ordem['valor'], 2, ',', '') ?>" required></label>
        </p>
        <p>
            <label>Status:<br>
            <select name="status">
                <?php foreach ($statusLista as $s): ?>
                    <option value="<?= $s ?>" <?= $s === $ordem['status'] ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select></label>
        </p>
        <p>
            <label>Data de entrada:<br>
            <input type="date" name="data_entrada" value="<?= $ordem['data_entrada'] ?>" required></label>
        </p>
        <button type="submit">Atualizar</button>
        <a href="listar.php">Cancelar</a>
    </form>
</body>
</html>