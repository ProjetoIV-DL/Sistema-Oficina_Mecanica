<?php
session_start();
require_once "conexao.php";

if (isset($_SESSION["funcionario_id"])) {
    header("Location: /index.php");
    exit;
}

$erro = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $senha = $_POST["senha"];

    $stmt = $pdo->prepare("SELECT id, nome, senha FROM funcionarios WHERE email = :email");
    $stmt->execute([":email" => $email]);
    $funcionario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($funcionario && password_verify($senha, $funcionario["senha"])) {
        session_regenerate_id(true);
        $_SESSION["funcionario_id"] = $funcionario["id"];
        $_SESSION["funcionario_nome"] = $funcionario["nome"];
        header("Location: /index.php");
        exit;
    } else {
        $erro = "E-mail ou senha incorretos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acesso Restrito - Oficina Mecânica</title>
    <!-- FontAwesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #0f1015;
            --card-bg: #181a20;
            --border-color: #2a2e39;
            --accent: #f59e0b; /* Amarelo industrial */
            --accent-hover: #d97706;
            --text-main: #f3f4f6;
            --text-muted: #9ca3af;
            --error-bg: rgba(239, 68, 68, 0.1);
            --error-border: rgba(239, 68, 68, 0.3);
            --error-text: #ef4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image: radial-gradient(circle at 50% 50%, #1f2430 0%, #0f1015 100%);
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }

        .login-card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 40px 32px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 8px 10px -6px rgba(0, 0, 0, 0.5);
        }

        .login-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .logo-icon {
            font-size: 2.5rem;
            color: var(--accent);
            margin-bottom: 12px;
            display: inline-block;
            background: rgba(245, 158, 11, 0.1);
            padding: 16px;
            border-radius: 50%;
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .login-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-main);
            letter-spacing: -0.5px;
        }

        .login-header p {
            color: var(--text-muted);
            font-size: 0.875rem;
            margin-top: 6px;
        }

        .alert-error {
            background-color: var(--error-bg);
            border: 1px solid var(--error-border);
            color: var(--error-text);
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.875rem;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-muted);
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 1rem;
        }

        .form-control {
            width: 100%;
            background-color: #121318;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 12px 16px 12px 42px;
            color: var(--text-main);
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15);
        }

        .btn-submit {
            width: 100%;
            background-color: var(--accent);
            color: #12100e;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.2s ease, transform 0.1s ease;
            margin-top: 8px;
        }

        .btn-submit:hover {
            background-color: var(--accent-hover);
        }

        .btn-submit:active {
            transform: scale(0.98);
        }

        .login-footer {
            text-align: center;
            margin-top: 24px;
            font-size: 0.75rem;
            color: var(--text-muted);
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <div class="logo-icon">
                <i class="fa-solid fa-wrench"></i>
            </div>
            <h1>Oficina Mecânica</h1>
            <p>Entre com suas credenciais de acesso</p>
        </div>

        <?php if ($erro !== ""): ?>
            <div class="alert-error">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span><?= htmlspecialchars($erro) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">E-mail corporativo</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" name="email" class="form-control" placeholder="nome@oficina.com" required autofocus>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Senha</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="senha" class="form-control" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fa-solid fa-right-to-bracket"></i> Acessar Sistema
            </button>
        </form>
    </div>
    
    <div class="login-footer">
        Sistema de Gestão Automotiva &bull; Área Restrita a Funcionários
    </div>
</div>

</body>
</html>