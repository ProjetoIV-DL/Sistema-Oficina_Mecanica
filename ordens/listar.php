<?php
require_once "../includes/protege.php";
require_once "../conexao.php";

$sql = "SELECT o.id, o.descricao, o.valor, o.status, o.data_entrada,
               v.placa, v.modelo, c.nome AS dono
        FROM ordens_servico o
        JOIN veiculos v ON o.veiculo_id = v.id
        JOIN clientes c ON v.cliente_id = c.id
        ORDER BY o.data_entrada DESC, o.id DESC";
$ordens = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
$totalOrdens = count($ordens);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ordens de Serviço | Oficina Mecânica</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        :root {
            --canvas: #0f1015;
            --card: #181a20;
            --text: #f3f4f6;
            --muted: #9ca3af;
            --accent: #f59e0b; /* Amarelo industrial */
            --accent-hover: #d97706;
            --cyan: #38bdf8;
            --red: #ef4444;
            --green: #10b981;
            --line: #2a2e39;

            --mono: 'JetBrains Mono', monospace;
            --sans: 'Inter', sans-serif;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: var(--sans);
            background: var(--canvas);
            color: var(--text);
            line-height: 1.5;
        }

        a { color: inherit; text-decoration: none; }
        .mono { font-family: var(--mono); }
        :focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }

        /* ---------- Layout Principal ---------- */
        .app {
            display: grid;
            grid-template-columns: 88px minmax(0, 1fr);
            min-height: 100vh;
        }

        /* ---------- Barra Lateral (Rail) ---------- */
        .rail {
            position: sticky;
            top: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
            padding: 20px 0;
            background: var(--card);
            border-right: 1px solid var(--line);
            z-index: 10;
        }

        .rail-logo {
            width: 48px; height: 48px;
            display: grid; place-items: center;
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.3);
            border-radius: 12px;
            color: var(--accent);
            font-size: 1.2rem;
            margin-bottom: 8px;
            transition: transform 0.2s;
        }
        .rail-logo:hover { transform: scale(1.05); }

        .rail-nav { display: flex; flex-direction: column; gap: 8px; width: 100%; padding: 0 12px; }

        .rail-nav a {
            position: relative;
            height: 46px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--muted);
            font-size: 1.15rem;
            border-radius: 10px;
            transition: all 0.2s ease;
        }

        .rail-nav a:hover { color: var(--text); background: rgba(255, 255, 255, 0.04); }
        .rail-nav a.active { color: var(--accent); background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.2); }

        .rail-out {
            margin-top: auto;
            width: 46px; height: 46px;
            display: grid; place-items: center;
            color: var(--red);
            background: rgba(239, 68, 68, 0.05);
            border: 1px solid rgba(239, 68, 68, 0.15);
            border-radius: 10px;
            transition: all 0.2s;
        }
        .rail-out:hover { background: rgba(239, 68, 68, 0.15); color: #f87171; }

        /* ---------- Área Central ---------- */
        .main { padding: 30px; min-width: 0; display: flex; flex-direction: column; gap: 24px; }

        .head { 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            gap: 16px; 
            flex-wrap: wrap; 
            background: var(--card); 
            padding: 24px; 
            border: 1px solid var(--line); 
            border-radius: 16px; 
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2); 
        }
        .head h1 { font-size: 1.5rem; font-weight: 700; color: var(--text); letter-spacing: -0.5px; }
        .head p { color: var(--muted); font-size: 0.9rem; margin-top: 2px; }

        .btn-primary {
            background-color: var(--accent);
            color: #12100e;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            padding: 10px 18px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background-color 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        .btn-primary:hover { background-color: var(--accent-hover); }

        .box { background: var(--card); border: 1px solid var(--line); border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2); }

        .sec-title { display: flex; align-items: center; gap: 10px; font-size: 1.05rem; font-weight: 700; margin-bottom: 14px; color: var(--text); }
        .sec-title i { color: var(--accent); font-size: 0.95rem; background: rgba(245, 158, 11, 0.1); padding: 8px; border-radius: 8px; border: 1px solid rgba(245, 158, 11, 0.2); }
        .sec-title .aux { margin-left: auto; font-weight: 500; font-size: 0.82rem; color: var(--muted); background: #121318; padding: 4px 10px; border-radius: 6px; border: 1px solid var(--line); }

        /* Tabela Densa e Moderna */
        .tbl-wrap { overflow-x: auto; border-radius: 16px; }
        table { width: 100%; border-collapse: collapse; font-size: 0.875rem; text-align: left; }
        th { font-weight: 600; color: var(--muted); background: #121318; padding: 14px 18px; border-bottom: 1px solid var(--line); white-space: nowrap; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 14px 18px; border-bottom: 1px solid var(--line); white-space: nowrap; color: var(--text); }
        tbody tr:last-child td { border-bottom: 0; }
        tbody tr:hover { background: rgba(255, 255, 255, 0.02); }
        td.num, th.num { text-align: right; }
        
        .plate { font-family: var(--mono); font-size: 0.75rem; font-weight: 700; letter-spacing: 0.05em; padding: 2px 6px; background: #121318; border: 1px solid var(--line); border-radius: 4px; color: var(--muted); margin-left: 4px; }
        
        .st { display: inline-flex; align-items: center; gap: 8px; font-weight: 500; font-size: 0.8rem; padding: 4px 10px; background: #121318; border: 1px solid var(--line); border-radius: 6px; }
        
        .actions-cell { display: flex; gap: 12px; align-items: center; }
        .action-link { font-size: 0.85rem; font-weight: 600; transition: color 0.2s; display: inline-flex; align-items: center; gap: 6px; }
        .action-edit { color: var(--cyan); }
        .action-edit:hover { color: #7dd3fc; }
        .action-delete { color: var(--red); }
        .action-delete:hover { color: #f87171; }

        .vazio { color: var(--muted); font-size: 0.85rem; padding: 40px; text-align: center; font-style: italic; }

        .foot { margin-top: auto; padding-top: 10px; text-align: center; color: var(--muted); font-size: 0.8rem; }

        /* Responsividade */
        @media (max-width: 760px) {
            .app { grid-template-columns: 1fr; }
            .rail { position: static; height: auto; flex-direction: row; flex-wrap: wrap; justify-content: center; padding: 12px; border-right: 0; border-bottom: 1px solid var(--line); }
            .rail-nav { flex-direction: row; width: auto; padding: 0; }
            .rail-nav a { width: 42px; height: 42px; }
            .rail-out { margin-top: 0; width: 42px; height: 42px; }
            .main { padding: 16px; }
        }
    </style>
</head>
<body>
<div class="app">

    <!-- Barra Lateral (Rail) -->
    <aside class="rail">
        <a href="../index.php" class="rail-logo" title="Oficina Mecânica" aria-label="Oficina Mecânica"><i class="fa-solid fa-wrench"></i></a>

        <nav class="rail-nav" aria-label="Navegação principal">
            <a href="../index.php" title="Painel" aria-label="Painel"><i class="fa-solid fa-gauge-high"></i></a>
            <a href="../clientes/listar.php" title="Clientes" aria-label="Clientes"><i class="fa-solid fa-users"></i></a>
            <a href="../veiculos/listar.php" title="Veículos" aria-label="Veículos"><i class="fa-solid fa-car"></i></a>
            <a href="listar.php" class="active" title="Ordens de Serviço" aria-label="Ordens de Serviço" aria-current="page"><i class="fa-solid fa-clipboard-list"></i></a>
        </nav>

        <a href="../logout.php" class="rail-out" title="Sair do Sistema" aria-label="Sair"><i class="fa-solid fa-right-from-bracket"></i></a>
    </aside>

    <!-- Área Central -->
    <main class="main">

        <div class="head">
            <div>
                <h1>Ordens de Serviço</h1>
                <p>Controle geral de manutenções, status e valores dos serviços</p>
            </div>
            <a href="cadastrar.php" class="btn-primary">
                <i class="fa-solid fa-plus"></i> Nova Ordem
            </a>
        </div>

        <section>
            <div class="sec-title">
                <i class="fa-solid fa-file-invoice-dollar"></i> Listagem de Ordens
                <span class="aux"><?= $totalOrdens ?> registros</span>
            </div>

            <div class="box tbl-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>OS</th>
                            <th>Data</th>
                            <th>Veículo</th>
                            <th>Dono</th>
                            <th>Descrição</th>
                            <th class="num">Valor</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ordens)): ?>
                            <tr><td colspan="8" class="vazio">Nenhuma ordem de serviço registrada no momento.</td></tr>
                        <?php else: ?>
                            <?php foreach ($ordens as $o): ?>
                                <tr>
                                    <td class="mono"><b>#<?= (int)$o['id'] ?></b></td>
                                    <td class="mono"><?= date("d/m/Y", strtotime($o['data_entrada'])) ?></td>
                                    <td>
                                        <?= htmlspecialchars($o['modelo']) ?>
                                        <span class="plate"><?= htmlspecialchars($o['placa']) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($o['dono']) ?></td>
                                    <td><?= htmlspecialchars($o['descricao']) ?></td>
                                    <td class="num mono">R$ <?= number_format($o['valor'], 2, ',', '.') ?></td>
                                    <td><span class="st"><?= htmlspecialchars($o['status']) ?></span></td>
                                    <td>
                                        <div class="actions-cell">
                                            <a href="editar.php?id=<?= $o['id'] ?>" class="action-link action-edit" title="Editar OS">
                                                <i class="fa-solid fa-pen-to-square"></i> Editar
                                            </a>
                                            <a href="excluir.php?id=<?= $o['id'] ?>" class="action-link action-delete" onclick="return confirm('Tem certeza que deseja excluir esta ordem?')" title="Excluir OS">
                                                <i class="fa-solid fa-trash-can"></i> Excluir
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="foot">Sistema de Gestão Automotiva &bull; Módulo de Ordens de Serviço</div>
    </main>

</div>
</body>
</html>