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
    <title>Editar Veículo</title>
</head>
<body>
    <h1>Editar Veículo</h1>

    <?php if ($erro !== ""): ?>
        <p style="color: red;"><?= htmlspecialchars($erro) ?></p>
    <?php endif; ?>

    <form method="POST">
        <p>
            <label>Dono:<br>
            <select name="cliente_id" required>
                <?php foreach ($clientes as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $c['id'] == $veiculo['cliente_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select></label>
        </p>
        <p>
            <label>Placa:<br>
            <input type="text" name="placa" maxlength="10" value="<?= htmlspecialchars($veiculo['placa']) ?>" required></label>
        </p>
        <p>
            <label>Marca:<br>
            <input type="text" name="marca" value="<?= htmlspecialchars($veiculo['marca']) ?>" required></label>
        </p>
        <p>
            <label>Modelo:<br>
            <input type="text" name="modelo" value="<?= htmlspecialchars($veiculo['modelo']) ?>" required></label>
        </p>
        <p>
            <label>Ano:<br>
            <input type="number" name="ano" min="1900" max="2100" value="<?= $veiculo['ano'] ?>"></label>
        </p>
        <button type="submit">Atualizar</button>
        <a href="listar.php">Cancelar</a>
    </form>
</body>
</html>