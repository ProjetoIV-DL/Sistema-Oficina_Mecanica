<?php
require_once "../conexao.php";

$erro = "";

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
        $sql = "INSERT INTO ordens_servico (veiculo_id, descricao, valor, status, data_entrada)
                VALUES (:veiculo_id, :descricao, :valor, :status, :data_entrada)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ":veiculo_id" => $veiculo_id,
            ":descricao" => $descricao,
            ":valor" => $valor,
            ":status" => $status,
            ":data_entrada" => $data_entrada
        ]);
        header("Location: listar.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Nova Ordem de Serviço</title>
</head>
<body>
    <h1>Nova Ordem de Serviço</h1>

    <?php if ($erro !== ""): ?>
        <p style="color: red;"><?= htmlspecialchars($erro) ?></p>
    <?php endif; ?>

    <form method="POST">
        <p>
            <label>Veículo:<br>
            <select name="veiculo_id" required>
                <option value="">Selecione...</option>
                <?php foreach ($veiculos as $v): ?>
                    <option value="<?= $v['id'] ?>">
                        <?= htmlspecialchars($v['modelo']) ?> (<?= htmlspecialchars($v['placa']) ?>) - <?= htmlspecialchars($v['dono']) ?>
                    </option>
                <?php endforeach; ?>
            </select></label>
        </p>
        <p>
            <label>Descrição do serviço:<br>
            <input type="text" name="descricao" maxlength="255" size="50" required></label>
        </p>
        <p>
            <label>Valor (R$):<br>
            <input type="text" name="valor" placeholder="250,00" required></label>
        </p>
        <p>
            <label>Status:<br>
            <select name="status">
                <?php foreach ($statusLista as $s): ?>
                    <option value="<?= $s ?>"><?= $s ?></option>
                <?php endforeach; ?>
            </select></label>
        </p>
        <p>
            <label>Data de entrada:<br>
            <input type="date" name="data_entrada" value="<?= date('Y-m-d') ?>" required></label>
        </p>
        <button type="submit">Salvar</button>
        <a href="listar.php">Cancelar</a>
    </form>
</body>
</html>