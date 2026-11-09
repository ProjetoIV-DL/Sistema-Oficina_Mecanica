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

$statusLista = ["Diagnóstico", "Manutenção", "Testes", "Concluída"];

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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar Ordem de Serviço</title>
    <link rel="stylesheet" href="../css/formularios.css">
</head>
<body>
<div class="container">
    <div class="cabecalho">
        <h1>Editar Ordem de Serviço</h1>
        <p>Altere os dados do serviço e clique em atualizar.</p>
    </div>

    <div class="cartao">
        <?php if ($erro !== ""): ?>
            <div class="alerta"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="campo">
                <label for="veiculo_id">Veículo</label>
                <select id="veiculo_id" name="veiculo_id" required>
                    <?php foreach ($veiculos as $v): ?>
                        <option value="<?= $v['id'] ?>" <?= $v['id'] == $ordem['veiculo_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($v['modelo']) ?> (<?= htmlspecialchars($v['placa']) ?>) - <?= htmlspecialchars($v['dono']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="descricao">Descrição do serviço</label>
                <input type="text" id="descricao" name="descricao" maxlength="255" value="<?= htmlspecialchars($ordem['descricao']) ?>" required>
            </div>
            <div class="grade">
                <div class="campo">
                    <label for="valor">Valor (R$)</label>
                    <input type="text" id="valor" name="valor" value="<?= number_format($ordem['valor'], 2, ',', '') ?>" required>
                </div>
                <div class="campo">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <?php foreach ($statusLista as $s): ?>
                            <option value="<?= $s ?>" <?= $s === $ordem['status'] ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="campo">
                <label for="data_entrada">Data de entrada</label>
                <input type="date" id="data_entrada" name="data_entrada" value="<?= $ordem['data_entrada'] ?>" required>
            </div>
            <div class="acoes">
                <button type="submit" class="btn btn-primario">Atualizar</button>
                <a href="listar.php" class="btn btn-secundario">Cancelar</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>