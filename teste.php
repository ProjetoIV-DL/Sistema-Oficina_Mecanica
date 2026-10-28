<?php
$pdo = new PDO("mysql:host=db;dbname=oficina;charset=utf8mb4", "root", "root");
$clientes = $pdo->query("SELECT nome, telefone FROM clientes")->fetchAll(PDO::FETCH_ASSOC);

echo "<h1>Conexão OK!</h1>";
foreach ($clientes as $c) {
    echo $c['nome'] . " - " . $c['telefone'] . "<br>";
}