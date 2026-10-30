<?php
require_once "../includes/protege.php";
require_once "../conexao.php";

$id = $_GET["id"] ?? null;
if ($id !== null) {
    $stmt = $pdo->prepare("DELETE FROM ordens_servico WHERE id = :id");
    $stmt->execute([":id" => $id]);
}

header("Location: listar.php");
exit;