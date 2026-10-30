<?php
require_once "../includes/protege.php";
require_once "../conexao.php";

$id = $_GET["id"] ?? null;
if ($id === null) {
    header("Location: listar.php");
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = :id");
    $stmt->execute([":id" => $id]);
    header("Location: listar.php");
    exit;
} catch (PDOException $e) {
   
    if ($e->getCode() == 23000) {
        $mensagem = "Não é possível excluir, pois este cliente possui veículos cadastrados. Exclua os veículos primeiro.";
    } else {
        $mensagem = "Erro ao excluir: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Excluir Cliente</title>
</head>
<body>
    <h1>Excluir Cliente</h1>
    <p style="color: red;"><?= htmlspecialchars($mensagem) ?></p>
    <a href="listar.php">Voltar para a lista</a>
</body>
</html>