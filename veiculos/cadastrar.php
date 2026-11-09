<?php
require_once "../includes/protege.php";
require_once "../conexao.php";

$erro = "";

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
            $sql = "INSERT INTO veiculos (cliente_id, placa, marca, modelo, ano)
                    VALUES (:cliente_id, :placa, :marca, :modelo, :ano)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ":cliente_id" => $cliente_id,
                ":placa" => $placa,
                ":marca" => $marca,
                ":modelo" => $modelo,
                ":ano" => $ano
            ]);
            header("Location: listar.php");
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $erro = "Já existe um veículo cadastrado com esta placa.";
            } else {
                $erro = "Erro ao salvar: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cadastrar Veículo</title>
    <link rel="stylesheet" href="../css/formularios.css">
</head>
<body>
<div class="container">
    <div class="cabecalho">
        <h1>Cadastrar Veículo</h1>
        <p>Informe o dono e os dados do veículo.</p>
    </div>

    <div class="cartao">
        <?php if ($erro !== ""): ?>
            <div class="alerta"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="campo">
                <label for="cliente_id">Dono</label>
                <select id="cliente_id" name="cliente_id" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($clientes as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grade">
                <div class="campo">
                    <label for="placa">Placa</label>
                    <input type="text" id="placa" name="placa" maxlength="10" required>
                </div>
                <div class="campo">
                    <label for="ano">Ano</label>
                    <input type="number" id="ano" name="ano" min="1900" max="2100">
                </div>
            </div>
            <div class="grade">
                <div class="campo">
                    <label for="marca">Marca</label>
                    <input type="text" id="marca" name="marca" required>
                </div>
                <div class="campo">
                    <label for="modelo">Modelo</label>
                    <input type="text" id="modelo" name="modelo" required>
                </div>
            </div>
            <div class="acoes">
                <button type="submit" class="btn btn-primario">Salvar</button>
                <a href="listar.php" class="btn btn-secundario">Cancelar</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>