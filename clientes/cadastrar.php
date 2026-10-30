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
    <title>Cadastrar Cliente</title>
</head>
<body>
    <h1>Cadastrar Cliente</h1>

    <?php if ($erro !== ""): ?>
        <p style="color: red;"><?= $erro ?></p>
    <?php endif; ?>

    <form method="POST">
        <p>
            <label>Nome:<br>
            <input type="text" name="nome" required></label>
        </p>
        <p>
            <label>Telefone:<br>
            <input type="text" name="telefone" required></label>
        </p>
        <p>
            <label>E-mail:<br>
            <input type="email" name="email"></label>
        </p>
        <button type="submit">Salvar</button>
        <a href="listar.php">Cancelar</a>
    </form>
</body>
</html>