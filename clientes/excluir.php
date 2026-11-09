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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Excluir Cliente</title>
    <link rel="stylesheet" href="../css/formularios.css">
</head>
<body>
<div class="container">
    <div class="cabecalho">
        <h1>Excluir Cliente</h1>
        <p>Não foi possível concluir a exclusão.</p>
    </div>

    <div class="cartao">
        <div class="alerta"><?= htmlspecialchars($mensagem) ?></div>
        <div class="acoes">
            <a href="listar.php" class="btn btn-primario">Voltar para a lista</a>
        </div>
    </div>
</div>
</body>
</html>