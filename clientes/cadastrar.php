<?php
require_once "../includes/protege.php";
require_once "../conexao.php";

$erro = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = trim($_POST["nome"]);
    $telefone = trim($_POST["telefone"]);
    $email = trim($_POST["email"]);

    if ($nome === "" || $telefone === "") {
        $erro = "Nome e telefone são obrigatórios.";
    } else {
        $sql = "INSERT INTO clientes (nome, telefone, email) VALUES (:nome, :telefone, :email)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ":nome" => $nome,
            ":telefone" => $telefone,
            ":email" => $email
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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cadastrar Cliente</title>
    <link rel="stylesheet" href="../css/formularios.css">
</head>
<body>
<div class="container">
    <div class="cabecalho">
        <h1>Cadastrar Cliente</h1>
        <p>Preencha os dados de contato do novo cliente.</p>
    </div>

    <div class="cartao">
        <?php if ($erro !== ""): ?>
            <div class="alerta"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="campo">
                <label for="nome">Nome</label>
                <input type="text" id="nome" name="nome" required>
            </div>
            <div class="grade">
                <div class="campo">
                    <label for="telefone">Telefone</label>
                    <input type="text" id="telefone" name="telefone" required>
                </div>
                <div class="campo">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email">
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