<?php
require_once "../conexao.php";

$id = $_GET["id"] ?? null;
if ($id === null) {
    header("Location: listar.php");
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM veiculos WHERE id = :id");
    $stmt->execute([":id" => $id]);
    header("Location: listar.php");
    exit;
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        $mensagem = "Não é possível excluir: este veículo possui ordens de serviço cadastradas. Exclua as ordens primeiro.";
    } else {
        $mensagem = "Erro ao excluir: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Excluir Veículo</title>
</head>
<body>
    <h1>Excluir Veículo</h1>
    <p style="color: red;"><?= htmlspecialchars($mensagem) ?></p>
    <a href="listar.php">Voltar para a lista</a>
</body>
</html>