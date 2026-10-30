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
    <title>Editar Cliente</title>
</head>
<body>
    <h1>Editar Cliente</h1>

    <?php if ($erro !== ""): ?>
        <p style="color: red;"><?= $erro ?></p>
    <?php endif; ?>

    <form method="POST">
        <p>
            <label>Nome:<br>
            <input type="text" name="nome" value="<?= htmlspecialchars($cliente['nome']) ?>" required></label>
        </p>
        <p>
            <label>Telefone:<br>
            <input type="text" name="telefone" value="<?= htmlspecialchars($cliente['telefone']) ?>" required></label>
        </p>
        <p>
            <label>E-mail:<br>
            <input type="email" name="email" value="<?= htmlspecialchars($cliente['email']) ?>"></label>
        </p>
        <button type="submit">Atualizar</button>
        <a href="listar.php">Cancelar</a>
    </form>
</body>
</html>