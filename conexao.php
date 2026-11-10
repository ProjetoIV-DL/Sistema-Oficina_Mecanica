<?php

$host = "sql300.infinityfree.com";
$banco = "if0_43404760_oficina";
$usuario = "if0_43404760";
$senha = "LrNvrx3JgnLLX0g";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$banco;charset=utf8mb4",
        $usuario,
        $senha
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}
