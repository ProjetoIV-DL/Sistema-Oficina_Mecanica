<?php
require_once "includes/protege.php";
require_once "conexao.php";

date_default_timezone_set('America/Sao_Paulo');

$ELEVADORES_TOTAL = 5;

$totalClientes = $pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
$totalVeiculos = $pdo->query("SELECT COUNT(*) FROM veiculos")->fetchColumn();
$totalOrdens = $pdo->query("SELECT COUNT(*) FROM ordens_servico")->fetchColumn();
$faturamento = $pdo->query("SELECT COALESCE(SUM(valor), 0) FROM ordens_servico WHERE status = 'Concluída'")->fetchColumn();
$ticketMedio = $pdo->query("SELECT COALESCE(AVG(valor), 0) FROM ordens_servico")->fetchColumn();

$porStatus = $pdo->query("SELECT status, COUNT(*) AS total FROM ordens_servico GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);

function campo($row, $chaves, $padrao = '')
{
    foreach ($chaves as $k) {
        if (isset($row[$k]) && trim((string)$row[$k]) !== '') {
            return trim((string)$row[$k]);
        }
    }
    return $padrao;
}

function etapa($status)
{
    $s = mb_strtolower(trim((string)$status), 'UTF-8');
    if (strpos($s, 'cancel') !== false) return 'cancelado';
    if (strpos($s, 'conclu') !== false || strpos($s, 'pronto') !== false || strpos($s, 'entreg') !== false) return 'pronto';
    if (strpos($s, 'teste') !== false) return 'testes';
    if (strpos($s, 'andamento') !== false || strpos($s, 'manut') !== false || strpos($s, 'execu') !== false || strpos($s, 'repar') !== false) return 'manutencao';
    return 'diagnostico';
}

$ETAPAS = [
    'diagnostico' => ['nome' => 'Diagnóstico', 'icone' => 'fa-magnifying-glass', 'pct' => 20],
    'manutencao'  => ['nome' => 'Manutenção',  'icone' => 'fa-wrench',             'pct' => 55],
    'testes'      => ['nome' => 'Testes',      'icone' => 'fa-gauge-high',         'pct' => 85],
    'pronto'      => ['nome' => 'Pronto',      'icone' => 'fa-flag-checkered',     'pct' => 100],
];

$veiculos = [];
foreach ($pdo->query("SELECT * FROM veiculos")->fetchAll(PDO::FETCH_ASSOC) as $v) {
    $veiculos[$v['id']] = $v;
}

$ordens = $pdo->query(
    "SELECT o.*, c.nome AS cliente_nome
       FROM ordens_servico o
       JOIN veiculos v ON v.id = o.veiculo_id
       JOIN clientes c ON c.id = v.cliente_id
     ORDER BY o.id DESC
     LIMIT 200"
)->fetchAll(PDO::FETCH_ASSOC);

$CHAVES_DATA = ['data_abertura', 'data_entrada', 'data_os', 'data', 'criado_em', 'created_at', 'dt_abertura'];

foreach ($ordens as &$o) {
    $v = isset($veiculos[$o['veiculo_id']]) ? $veiculos[$o['veiculo_id']] : [];

    $modelo = trim(campo($v, ['marca']) . ' ' . campo($v, ['modelo']));
    $o['_modelo']   = $modelo !== '' ? $modelo : 'Veículo #' . $o['veiculo_id'];
    $o['_placa']    = strtoupper(campo($v, ['placa'], '———'));
    $o['_mecanico'] = campo($o, ['mecanico', 'mecanico_responsavel', 'responsavel'], 'Não atribuído');
    $o['_pecas']    = campo($o, ['pecas', 'pecas_necessarias', 'pecas_faltando'], '—');
    $o['_etapa']    = etapa($o['status']);

    $ts = null;
    $bruto = campo($o, $CHAVES_DATA);
    if ($bruto !== '' && strtotime($bruto) !== false) $ts = strtotime($bruto);
    $o['_ts'] = $ts;

    $u = mb_strtolower(campo($o, ['urgencia', 'prioridade', 'nivel_urgencia']), 'UTF-8');
    if ($u !== '') {
        if (preg_match('/crit|urgen|alta/u', $u))      $urg = 'crit';
        elseif (preg_match('/m[eé]dia|aten/u', $u))    $urg = 'aten';
        else                                         $urg = 'norm';
    } elseif ($ts !== null && !in_array($o['_etapa'], ['pronto', 'cancelado'])) {
        $dias = floor((time() - $ts) / 86400);
        $urg = $dias >= 7 ? 'crit' : ($dias >= 3 ? 'aten' : 'norm');
    } elseif ($ts !== null) {
        $urg = 'norm';
    } else {
        $urg = 'nd';
    }
    $o['_urg'] = $urg;
}
unset($o);

$URG_ROTULO = ['crit' => 'Crítica', 'aten' => 'Atenção', 'norm' => 'Normal', 'nd' => '—'];

$quadro = ['diagnostico' => [], 'manutencao' => [], 'testes' => [], 'pronto' => []];
$prontosRecentes = [];
foreach ($ordens as $o) {
    if ($o['_etapa'] === 'cancelado') continue;
    if ($o['_etapa'] === 'pronto') {
        if (count($quadro['pronto']) >= 4) continue;
        $prontosRecentes[] = $o;
    }
    $quadro[$o['_etapa']][] = $o;
}

$ativas = count($quadro['diagnostico']) + count($quadro['manutencao']) + count($quadro['testes']);
$ocupados = min($ELEVADORES_TOTAL, count($quadro['manutencao']) + count($quadro['testes']));
$criticas = 0;
foreach (['diagnostico', 'manutencao', 'testes'] as $e) {
    foreach ($quadro[$e] as $o) if ($o['_urg'] === 'crit') $criticas++;
}

$hoje = date('Y-m-d');
$ordensDia = array_values(array_filter($ordens, function ($o) use ($hoje) {
    return $o['_ts'] !== null && date('Y-m-d', $o['_ts']) === $hoje;
}));
$tituloTabela = 'Ordens de serviço do dia';
if (count($ordensDia) === 0) {
    $ordensDia = array_slice($ordens, 0, 12);
    $tituloTabela = 'Últimas ordens de serviço';
}

$avisos = [];
foreach ($ordens as $o) {
    if (in_array($o['_etapa'], ['pronto', 'cancelado'])) continue;
    $ref = 'OS #' . $o['id'] . ' · ' . $o['_modelo'];
    $quando = $o['_ts'] ? date('d/m H:i', $o['_ts']) : '';
    $st = mb_strtolower($o['status'], 'UTF-8');

    if ($o['_urg'] === 'crit') {
        $avisos[] = ['n' => 0, 'cls' => 'bad', 'ico' => 'fa-triangle-exclamation', 'txt' => $ref . ' com urgência crítica.', 'quando' => $quando];
    }
    if (strpos($st, 'orçament') !== false || strpos($st, 'orcament') !== false || strpos($st, 'aprova') !== false) {
        $avisos[] = ['n' => 1, 'cls' => 'info', 'ico' => 'fa-file-signature', 'txt' => $ref . ' precisa de aprovação de orçamento.', 'quando' => $quando];
    }
    if ($o['_pecas'] !== '—') {
        $avisos[] = ['n' => 1, 'cls' => 'info', 'ico' => 'fa-box-open', 'txt' => 'Peças para ' . $ref . ': ' . $o['_pecas'] . '.', 'quando' => $quando];
    }
}
foreach ($prontosRecentes as $o) {
    $avisos[] = ['n' => 2, 'cls' => 'ok', 'ico' => 'fa-circle-check', 'txt' => 'OS #' . $o['id'] . ' · ' . $o['_modelo'] . ' pronto para entrega.', 'quando' => $o['_ts'] ? date('d/m H:i', $o['_ts']) : ''];
}
usort($avisos, function ($a, $b) { return $a['n'] <=> $b['n']; });
$avisos = array_slice($avisos, 0, 8);

function moeda($n) { return number_format((float)$n, 2, ',', '.'); }
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Painel Geral | Oficina Mecânica</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        :root {
            --canvas: #0f1015;
            --card: #181a20;
            --card-hover: #21242d;
            --text: #f3f4f6;
            --muted: #9ca3af;
            --accent: #f59e0b;
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

        .app {
            display: grid;
            grid-template-columns: 88px minmax(0, 1fr) 340px;
            min-height: 100vh;
        }

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

        .gauges {
            width: 100%;
            padding: 0 10px;
            display: flex; flex-direction: column;
            gap: 12px;
        }

        .gauge {
            background: #121318;
            border: 1px solid var(--line);
            border-radius: 10px;
            text-align: center;
            padding: 8px 4px;
        }
        .gauge b {
            display: block;
            font-family: var(--mono);
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text);
        }

        .gauge span {
            display: block;
            font-size: 0.58rem;
            color: var(--muted);
            text-transform: uppercase;
            margin-top: 2px;
            letter-spacing: 0;
            white-space: nowrap;
        }
        .gauge i { display: block; height: 4px; margin-top: 6px; background: #1c1f26; border-radius: 2px; overflow: hidden; }
        .gauge i s { display: block; height: 100%; background: var(--accent); text-decoration: none; border-radius: 2px; }
        .gauge.bad b { color: var(--red); }
        .gauge.bad i s { background: var(--red); }
        .gauge.ok b { color: var(--green); }
        .gauge.ok i s { background: var(--green); }

        .main { padding: 30px; min-width: 0; display: flex; flex-direction: column; gap: 24px; }

        .head { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; background: var(--card); padding: 24px; border: 1px solid var(--line); border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2); }
        .head h1 { font-size: 1.5rem; font-weight: 700; color: var(--text); letter-spacing: -0.5px; }
        .head p { color: var(--muted); font-size: 0.9rem; margin-top: 2px; }
        .head .data { font-family: var(--mono); font-size: 0.85rem; color: var(--accent); background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.2); padding: 6px 12px; border-radius: 8px; font-weight: 600; }

        .box { background: var(--card); border: 1px solid var(--line); border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2); }

        .kpis { display: grid; grid-template-columns: 2fr 1.2fr 1fr 1fr 1fr; background: var(--card); border: 1px solid var(--line); border-radius: 16px; overflow: hidden; }
        .kpi { padding: 20px; border-right: 1px solid var(--line); min-width: 0; display: flex; flex-direction: column; justify-content: center; }
        .kpi:last-child { border-right: 0; }
        .kpi .l { color: var(--muted); font-size: 0.78rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; }
        .kpi .n { font-family: var(--mono); font-weight: 700; font-size: 1.4rem; margin-top: 6px; color: var(--text); white-space: nowrap; }
        .kpi.big .n { font-size: 1.8rem; color: var(--green); }
        .kpi .n small { font-size: 0.55em; font-weight: 400; color: var(--muted); margin-right: 2px; }

        .sec-title { display: flex; align-items: center; gap: 10px; font-size: 1.05rem; font-weight: 700; margin-bottom: 14px; color: var(--text); }
        .sec-title i { color: var(--accent); font-size: 0.95rem; background: rgba(245, 158, 11, 0.1); padding: 8px; border-radius: 8px; border: 1px solid rgba(245, 158, 11, 0.2); }
        .sec-title .aux { margin-left: auto; font-weight: 500; font-size: 0.82rem; color: var(--muted); background: #121318; padding: 4px 10px; border-radius: 6px; border: 1px solid var(--line); }

        .board { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; }

        .col { border: 1px solid var(--line); border-radius: 12px; background: var(--card); display: flex; flex-direction: column; min-height: 280px; max-height: 520px; overflow: hidden; }
        .col-head { display: flex; align-items: center; gap: 8px; padding: 12px 16px; background: #121318; border-bottom: 1px solid var(--line); font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .col-head i { color: var(--c); font-size: 0.9rem; }
        .col-head .qtd { margin-left: auto; font-family: var(--mono); font-weight: 600; font-size: 0.75rem; background: var(--card); padding: 2px 8px; border-radius: 6px; border: 1px solid var(--line); color: var(--text); }
        .col-body { padding: 12px; display: flex; flex-direction: column; gap: 10px; flex: 1; min-height: 0; overflow-y: auto; scrollbar-width: thin; scrollbar-color: #3a4152 transparent; }

        .e-diagnostico { --c: var(--muted); }
        .e-manutencao  { --c: var(--cyan); }
        .e-testes      { --c: var(--accent); }
        .e-pronto      { --c: var(--green); }
        .e-cancelado   { --c: var(--line); }

        .car { background: #121318; border: 1px solid var(--line); border-left: 4px solid var(--c); border-radius: 8px; padding: 12px; transition: transform 0.2s; flex-shrink: 0; }
        .car:hover { border-color: var(--accent); }
        .car.crit { border-left-color: var(--red); background: rgba(239, 68, 68, 0.03); }
        .car .m { font-weight: 700; font-size: 0.9rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--text); }
        .car .row { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-top: 8px; }
        .plate { font-family: var(--mono); font-size: 0.8rem; font-weight: 700; letter-spacing: 0.05em; padding: 2px 8px; background: var(--card); border: 1px solid var(--line); border-radius: 4px; color: var(--text); }
        .os { font-family: var(--mono); font-size: 0.75rem; color: var(--muted); font-weight: 600; }
        .mec { margin-top: 8px; font-size: 0.8rem; color: var(--muted); display: flex; align-items: center; gap: 8px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
        .mec i { color: var(--accent); }
        .prog { display: flex; align-items: center; gap: 8px; margin-top: 10px; }
        .prog .bar { flex: 1; height: 6px; background: var(--card); border-radius: 3px; overflow: hidden; border: 1px solid var(--line); }
        .prog .bar span { display: block; height: 100%; background: var(--c); border-radius: 3px; }
        .prog em { font-style: normal; font-family: var(--mono); font-size: 0.72rem; color: var(--muted); font-weight: 600; }

        .vazio { color: var(--muted); font-size: 0.85rem; padding: 30px 10px; text-align: center; font-style: italic; }

        .chips { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; }
        .chip { font-size: 0.8rem; color: var(--muted); background: var(--card); border: 1px solid var(--line); border-radius: 8px; padding: 6px 12px; display: flex; align-items: center; gap: 6px; font-weight: 500; }
        .chip b { font-family: var(--mono); color: var(--text); background: #121318; padding: 2px 6px; border-radius: 4px; border: 1px solid var(--line); }

        .tbl-wrap { overflow-x: auto; border-radius: 16px; }
        table { width: 100%; border-collapse: collapse; font-size: 0.875rem; text-align: left; }
        th { font-weight: 600; color: var(--muted); background: #121318; padding: 14px 18px; border-bottom: 1px solid var(--line); white-space: nowrap; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 14px 18px; border-bottom: 1px solid var(--line); white-space: nowrap; color: var(--text); }
        tbody tr:last-child td { border-bottom: 0; }
        tbody tr:hover { background: rgba(255, 255, 255, 0.02); }
        td.num, th.num { text-align: right; }
        td.pecas { max-width: 220px; overflow: hidden; text-overflow: ellipsis; color: var(--muted); }

        .st { display: inline-flex; align-items: center; gap: 8px; font-weight: 500; }
        .st::before { content: ""; width: 8px; height: 8px; background: var(--c); border-radius: 50%; box-shadow: 0 0 8px var(--c); }

        .urg { font-family: var(--mono); font-size: 0.75rem; font-weight: 600; padding: 4px 10px; border-radius: 6px; display: inline-block; }
        .urg.crit { color: var(--red); background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); }
        .urg.aten { color: var(--accent); background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.2); }
        .urg.norm { color: var(--green); background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); }

        .foot { margin-top: auto; padding-top: 10px; text-align: center; color: var(--muted); font-size: 0.8rem; }

        .feed {
            position: sticky; top: 0; height: 100vh;
            overflow-y: auto;
            padding: 24px 20px;
            background: var(--card);
            border-left: 1px solid var(--line);
            display: flex; flex-direction: column; gap: 24px;
        }

        .feed h2 { font-size: 0.95rem; font-weight: 700; display: flex; align-items: center; gap: 10px; margin-bottom: 14px; color: var(--text); }
        .feed h2 i { color: var(--accent); font-size: 0.9rem; background: rgba(245, 158, 11, 0.1); padding: 8px; border-radius: 8px; border: 1px solid rgba(245, 158, 11, 0.2); }
        .feed h2 .cnt { margin-left: auto; font-family: var(--mono); font-weight: 600; font-size: 0.75rem; background: #121318; padding: 3px 8px; border-radius: 6px; border: 1px solid var(--line); color: var(--text); }

        .alerts { list-style: none; display: flex; flex-direction: column; gap: 10px; max-height: 340px; overflow-y: auto; padding-right: 4px; scrollbar-width: thin; scrollbar-color: #3a4152 transparent; }
        .alert { display: flex; gap: 12px; padding: 12px; background: #121318; border: 1px solid var(--line); border-left: 4px solid var(--c); border-radius: 10px; font-size: 0.83rem; align-items: flex-start; flex-shrink: 0; }
        .alert > i { color: var(--c); font-size: 1rem; margin-top: 2px; }
        .alert time { display: block; margin-top: 4px; font-family: var(--mono); font-size: 0.7rem; color: var(--muted); }
        .alert.bad  { --c: var(--red); background: rgba(239, 68, 68, 0.03); }
        .alert.info { --c: var(--cyan); background: rgba(56, 189, 248, 0.03); }
        .alert.ok   { --c: var(--green); background: rgba(16, 185, 129, 0.03); }

        .vazio-feed { color: var(--muted); font-size: 0.85rem; padding: 12px; text-align: center; background: #121318; border-radius: 8px; border: 1px solid var(--line); font-style: italic; }

        @media (max-width: 1280px) {
            .app { grid-template-columns: 88px minmax(0, 1fr); }
            .feed { grid-column: 2; position: static; height: auto; border-left: 0; border-top: 1px solid var(--line); flex-direction: row; flex-wrap: wrap; }
            .feed > section { flex: 1 1 300px; }
        }

        @media (max-width: 980px) {
            .board { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .kpis { grid-template-columns: 1fr 1fr; }
            .kpi { border-bottom: 1px solid var(--line); }
            .kpi.big { grid-column: 1 / -1; }
        }

        @media (max-width: 760px) {
            .app { grid-template-columns: 1fr; }
            .rail { position: static; height: auto; flex-direction: row; flex-wrap: wrap; justify-content: center; padding: 12px; border-right: 0; border-bottom: 1px solid var(--line); }
            .rail-logo { margin: 0; }
            .rail-nav { flex-direction: row; width: auto; padding: 0; }
            .rail-nav a { width: 42px; height: 42px; }
            .gauges { flex-direction: row; justify-content: center; width: 100%; margin: 0; padding: 0; }
            .gauge { flex: 1; max-width: 90px; }
            .rail-out { margin-top: 0; width: 42px; height: 42px; }
            .feed { grid-column: 1; }
            .main { padding: 16px; }
        }

        @media (max-width: 520px) {
            .board { grid-template-columns: 1fr; }
            .kpis { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="app">

    <aside class="rail">
        <a href="index.php" class="rail-logo" title="Oficina Mecânica" aria-label="Oficina Mecânica"><i class="fa-solid fa-wrench"></i></a>

        <nav class="rail-nav" aria-label="Navegação principal">
            <a href="index.php" class="active" title="Painel" aria-label="Painel" aria-current="page"><i class="fa-solid fa-gauge-high"></i></a>
            <a href="clientes/listar.php" title="Clientes" aria-label="Clientes"><i class="fa-solid fa-users"></i></a>
            <a href="veiculos/listar.php" title="Veículos" aria-label="Veículos"><i class="fa-solid fa-car"></i></a>
            <a href="ordens/listar.php" title="Ordens de Serviço" aria-label="Ordens de Serviço"><i class="fa-solid fa-clipboard-list"></i></a>
        </nav>

        <div class="gauges">
            <div class="gauge" title="Elevadores ocupados: <?= $ocupados ?>/<?= $ELEVADORES_TOTAL ?>">
                <b><?= $ocupados ?>/<?= $ELEVADORES_TOTAL ?></b>
                <span>Elevadores</span>
                <i><s style="width: <?= $ELEVADORES_TOTAL > 0 ? round($ocupados / $ELEVADORES_TOTAL * 100) : 0 ?>%"></s></i>
            </div>
            <div class="gauge" title="Ordens em andamento">
                <b><?= $ativas ?></b>
                <span>Ativas</span>
            </div>
            <div class="gauge <?= $criticas > 0 ? 'bad' : '' ?>" title="Ordens com urgência crítica">
                <b><?= $criticas ?></b>
                <span>Críticas</span>
            </div>
            <div class="gauge ok" title="Prontos para entrega">
                <b><?= count($quadro['pronto']) ?></b>
                <span>Prontos</span>
            </div>
        </div>

        <a href="logout.php" class="rail-out" title="Sair do Sistema" aria-label="Sair"><i class="fa-solid fa-right-from-bracket"></i></a>
    </aside>

    <main class="main">

        <div class="head">
            <div>
                <h1>Painel de Controle</h1>
                <p>Monitoramento em tempo real do fluxo de atendimento e serviços</p>
            </div>
            <div class="data"><i class="fa-regular fa-calendar"></i> <?= date('d/m/Y') ?></div>
        </div>

        <section class="kpis" aria-label="Resumo Financeiro e Cadastral">
            <div class="kpi big">
                <div class="l">Faturamento (Concluídas)</div>
                <div class="n"><small>R$</small><?= moeda($faturamento) ?></div>
            </div>
            <div class="kpi">
                <div class="l">Ticket Médio / OS</div>
                <div class="n"><small>R$</small><?= moeda($ticketMedio) ?></div>
            </div>
            <div class="kpi">
                <div class="l">Clientes</div>
                <div class="n"><?= (int)$totalClientes ?></div>
            </div>
            <div class="kpi">
                <div class="l">Veículos</div>
                <div class="n"><?= (int)$totalVeiculos ?></div>
            </div>
            <div class="kpi">
                <div class="l">Ordens Total</div>
                <div class="n"><?= (int)$totalOrdens ?></div>
            </div>
        </section>

        <section>
            <div class="sec-title">
                <i class="fa-solid fa-car-side"></i> Veículos na Oficina (Fluxo de Trabalho)
                <span class="aux">Atualizado agora</span>
            </div>

            <div class="board">
                <?php foreach ($ETAPAS as $chave => $et): ?>
                    <div class="col e-<?= $chave ?>">
                        <div class="col-head">
                            <i class="fa-solid <?= $et['icone'] ?>"></i> <?= $et['nome'] ?>
                            <span class="qtd"><?= count($quadro[$chave]) ?></span>
                        </div>
                        <div class="col-body">
                            <?php if (count($quadro[$chave]) === 0): ?>
                                <div class="vazio">Nenhum veículo nesta etapa</div>
                            <?php endif; ?>

                            <?php foreach ($quadro[$chave] as $o): ?>
                                <article class="car <?= $o['_urg'] === 'crit' && $chave !== 'pronto' ? 'crit' : '' ?>">
                                    <div class="m" title="<?= htmlspecialchars($o['_modelo']) ?>"><?= htmlspecialchars($o['_modelo']) ?></div>
                                    <div class="row">
                                        <span class="plate"><?= htmlspecialchars($o['_placa']) ?></span>
                                        <span class="os">OS #<?= (int)$o['id'] ?></span>
                                    </div>
                                    <div class="mec"><i class="fa-solid fa-user-gear"></i> <?= htmlspecialchars($o['_mecanico']) ?></div>
                                    <div class="prog">
                                        <div class="bar"><span style="width: <?= $et['pct'] ?>%"></span></div>
                                        <em><?= $et['pct'] ?>%</em>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section>
            <div class="sec-title">
                <i class="fa-solid fa-clipboard-list"></i> <?= $tituloTabela ?>
                <span class="aux"><?= count($ordensDia) ?> registros listados</span>
            </div>

            <?php if (count($porStatus) > 0): ?>
                <div class="chips">
                    <?php foreach ($porStatus as $s): ?>
                        <span class="chip"><?= htmlspecialchars($s['status']) ?><b><?= (int)$s['total'] ?></b></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="box tbl-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>OS</th>
                            <th>Placa</th>
                            <th>Veículo</th>
                            <th>Cliente</th>
                            <th>Status</th>
                            <th>Peças Necessárias</th>
                            <th>Urgência</th>
                            <th class="num">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($ordensDia) === 0): ?>
                            <tr><td colspan="8" class="vazio">Nenhuma ordem de serviço registrada no momento.</td></tr>
                        <?php endif; ?>

                        <?php foreach ($ordensDia as $o): ?>
                            <tr>
                                <td class="mono"><b>#<?= (int)$o['id'] ?></b></td>
                                <td class="mono"><?= htmlspecialchars($o['_placa']) ?></td>
                                <td><?= htmlspecialchars($o['_modelo']) ?></td>
                                <td><?= htmlspecialchars($o['cliente_nome']) ?></td>
                                <td><span class="st e-<?= $o['_etapa'] ?>"><?= htmlspecialchars($o['status']) ?></span></td>
                                <td class="pecas" title="<?= htmlspecialchars($o['_pecas']) ?>"><?= htmlspecialchars($o['_pecas']) ?></td>
                                <td><span class="urg <?= $o['_urg'] ?>"><?= $URG_ROTULO[$o['_urg']] ?></span></td>
                                <td class="num mono">R$ <?= moeda(isset($o['valor']) ? $o['valor'] : 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="foot">Sistema de Gestão Automotiva &bull; Módulo Administrativo</div>
    </main>

    <aside class="feed">
        <section>
            <h2><i class="fa-solid fa-tower-broadcast"></i> Alertas Operacionais <span class="cnt"><?= count($avisos) ?></span></h2>

            <?php if (count($avisos) === 0): ?>
                <p class="vazio-feed">Nenhum alerta pendente no momento.</p>
            <?php else: ?>
                <ul class="alerts">
                    <?php foreach ($avisos as $a): ?>
                        <li class="alert <?= $a['cls'] ?>">
                            <i class="fa-solid <?= $a['ico'] ?>"></i>
                            <div>
                                <span><?= htmlspecialchars($a['txt']) ?></span>
                                <?php if ($a['quando'] !== ''): ?><time><?= htmlspecialchars($a['quando']) ?></time><?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </aside>

</div>
</body>
</html>