<?php
if (php_sapi_name() !== "cli") {
    die("Execute este arquivo pelo terminal.");
}

require_once "conexao.php";

if ($argc < 4) {
    die("Uso: php criar_funcionario.php \"Nome\" email senha\n");
}

$nome = $argv[1];
$email = $argv[2];
$senha = $argv[3];

$hash = password_hash($senha, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("INSERT INTO funcionarios (nome, email, senha) VALUES (:nome, :email, :senha)");
    $stmt->execute([":nome" => $nome, ":email" => $email, ":senha" => $hash]);
    echo "Funcionário criado com sucesso.\n";
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}