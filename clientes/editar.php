<?php
require_once "../includes/protege.php";
require_once "../conexao.php";

$erro = "";

$id = $_GET["id"] ?? null;
if ($id === null) {
    header("Location: listar.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = trim($_POST["nome"]);
    $telefone = trim($_POST["telefone"]);
    $email = trim($_POST["email"]);

    if ($nome === "" || $telefone === "") {
        $erro = "Nome e telefone são obrigatórios.";
    } else {
        $sql = "UPDATE clientes SET nome = :nome, telefone = :telefone, email = :email WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ":nome" => $nome,
            ":telefone" => $telefone,
            ":email" => $email,
            ":id" => $id
        ]);

        header("Location: listar.php");
        exit;
    }
}

$stmt = $pdo->prepare("SELECT id, nome, telefone, email FROM clientes WHERE id = :id");
$stmt->execute([":id" => $id]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    die("Cliente não encontrado.");
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar Cliente</title>
    <link rel="stylesheet" href="../css/formularios.css">
</head>
<body>
<div class="container">
    <div class="cabecalho">
        <h1>Editar Cliente</h1>
        <p>Altere os dados e clique em atualizar.</p>
    </div>

    <div class="cartao">
        <?php if ($erro !== ""): ?>
            <div class="alerta"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="campo">
                <label for="nome">Nome</label>
                <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($cliente['nome']) ?>" required>
            </div>
            <div class="grade">
                <div class="campo">
                    <label for="telefone">Telefone</label>
                    <input type="text" id="telefone" name="telefone" value="<?= htmlspecialchars($cliente['telefone']) ?>" required>
                </div>
                <div class="campo">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($cliente['email']) ?>">
                </div>
            </div>
            <div class="acoes">
                <button type="submit" class="btn btn-primario">Atualizar</button>
                <a href="listar.php" class="btn btn-secundario">Cancelar</a>
            </div>
        </form>
    </div>
</d