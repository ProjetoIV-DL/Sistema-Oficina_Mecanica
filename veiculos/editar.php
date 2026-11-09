<?php
require_once "../includes/protege.php";
require_once "../conexao.php";

$erro = "";

$id = $_GET["id"] ?? null;
if ($id === null) {
    header("Location: listar.php");
    exit;
}

$clientes = $pdo->query("SELECT id, nome FROM clientes ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $cliente_id = $_POST["cliente_id"];
    $placa = strtoupper(trim($_POST["placa"]));
    $marca = trim($_POST["marca"]);
    $modelo = trim($_POST["modelo"]);
    $ano = $_POST["ano"] !== "" ? $_POST["ano"] : null;

    if ($cliente_id === "" || $placa === "" || $marca === "" || $modelo === "") {
        $erro = "Dono, placa, marca e modelo são obrigatórios.";
    } else {
        try {
            $sql = "UPDATE veiculos
                    SET cliente_id = :cliente_id, placa = :placa, marca = :marca, modelo = :modelo, ano = :ano
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ":cliente_id" => $cliente_id,
                ":placa" => $placa,
                ":marca" => $marca,
                ":modelo" => $modelo,
                ":ano" => $ano,
                ":id" => $id
            ]);
            header("Location: listar.php");
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $erro = "Já existe um veículo cadastrado com esta placa.";
            } else {
                $erro = "Erro ao atualizar: " . $e->getMessage();
            }
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM veiculos WHERE id = :id");
$stmt->execute([":id" => $id]);
$veiculo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$veiculo) {
    die("Veículo não encontrado.");
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar Veículo</title>
    <link rel="stylesheet" href="../css/formularios.css">
</head>
<body>
<div class="container">
    <div class="cabecalho">
        <h1>Editar Veículo</h1>
        <p>Altere os dados e clique em atualizar.</p>
    </div>

    <div class="cartao">
        <?php if ($erro !== ""): ?>
            <div class="alerta"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="campo">
                <label for="cliente_id">Dono</label>
                <select id="cliente_id" name="cliente_id" required>
                    <?php foreach ($clientes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $c['id'] == $veiculo['cliente_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grade">
                <div class="campo">
                    <label for="placa">Placa</label>
                    <input type="text" id="placa" name="placa" maxlength="10" value="<?= htmlspecialchars($veiculo['placa']) ?>" required>
                </div>
                <div class="campo">
                    <label for="ano">Ano</label>
                    <input type="number" id="ano" name="ano" min="1900" max="2100" value="<?= $veiculo['ano'] ?>">
                </div>
            </div>
            <div class="grade">
                <div class="campo">
                    <label for="marca">Marca</label>
                    <input type="text" id="marca" name="marca" value="<?= htmlspecialchars($veiculo['marca']) ?>" required>
                </div>
                <div class="campo">
                    <label for="modelo">Modelo</label>
                    <input type="text" id="modelo" name="modelo" value="<?= htmlspecialchars($veiculo['modelo']) ?>" required>
                </div>
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