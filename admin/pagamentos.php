<?php
require_once __DIR__ . '/../app.php';

$auth = new Auth();
$auth->requireType('admin');

$db = Database::getInstance();

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;
    
    if (isset($input['action'])) {
        switch ($input['action']) {
            case 'confirmar_recebimento':
                $diariaId = (int)($input['diaria_id'] ?? 0);
                if ($diariaId > 0) {
                    $db->update('diarias', [
                        'pago_empresa' => 1,
                        'pago_empresa_at' => date('Y-m-d H:i:s')
                    ], 'id = :id', ['id' => $diariaId]);
                    jsonResponse(['success' => true]);
                }
                break;
                
            case 'pagar_prestador':
                $diariaId = (int)($input['diaria_id'] ?? 0);
                $prestadorId = (int)($input['prestador_id'] ?? 0);
                if ($diariaId > 0 && $prestadorId > 0) {
                    // Verificar se já existe
                    $existe = $db->fetch(
                        "SELECT id FROM pagamentos_prestadores WHERE diaria_id = :did AND prestador_id = :pid",
                        ['did' => $diariaId, 'pid' => $prestadorId]
                    );
                    
                    if (!$existe) {
                        $diaria = $db->fetch("SELECT valor FROM diarias WHERE id = :id", ['id' => $diariaId]);
                        $db->insert('pagamentos_prestadores', [
                            'diaria_id' => $diariaId,
                            'prestador_id' => $prestadorId,
                            'valor' => $diaria['valor'],
                            'status' => 'pago',
                            'pago_at' => date('Y-m-d H:i:s')
                        ]);
                    } else {
                        $db->update('pagamentos_prestadores', [
                            'status' => 'pago',
                            'pago_at' => date('Y-m-d H:i:s')
                        ], 'id = :id', ['id' => $existe['id']]);
                    }
                    jsonResponse(['success' => true]);
                }
                break;
        }
    }
}

// Estatísticas financeiras
$stats = [
    'faturamento_mes' => $db->fetch(
        "SELECT COALESCE(SUM(d.valor_empresa), 0) as total FROM diarias d 
         JOIN candidaturas c ON c.diaria_id = d.id 
         WHERE c.status = 'checkin_realizado' 
         AND MONTH(d.data_evento) = MONTH(CURDATE()) AND YEAR(d.data_evento) = YEAR(CURDATE())"
    )['total'] ?? 0,
    
    'repasse_prestadores' => $db->fetch(
        "SELECT COALESCE(SUM(d.valor), 0) as total FROM candidaturas c 
         JOIN diarias d ON c.diaria_id = d.id 
         WHERE c.status = 'checkin_realizado' 
         AND MONTH(d.data_evento) = MONTH(CURDATE())"
    )['total'] ?? 0,
    
    'pago_empresas' => $db->fetch(
        "SELECT COALESCE(SUM(d.valor_empresa), 0) as total FROM diarias d 
         WHERE d.pago_empresa = 1 
         AND MONTH(d.pago_empresa_at) = MONTH(CURDATE())"
    )['total'] ?? 0,
    
    'pago_prestadores' => $db->fetch(
        "SELECT COALESCE(SUM(pp.valor), 0) as total FROM pagamentos_prestadores pp 
         WHERE pp.status = 'pago' 
         AND MONTH(pp.pago_at) = MONTH(CURDATE())"
    )['total'] ?? 0,
];

$stats['lucro_agencia'] = $stats['faturamento_mes'] - $stats['repasse_prestadores'];
$stats['pendencias'] = $db->fetch(
    "SELECT COUNT(DISTINCT d.id) as total FROM diarias d 
     JOIN candidaturas c ON c.diaria_id = d.id 
     WHERE c.status = 'checkin_realizado' AND d.pago_empresa = 0"
)['total'] ?? 0;

// Buscar diárias com check-in realizado
$diarias = $db->fetchAll(
    "SELECT d.*, e.razao_social, e.cnpj,
            (SELECT COUNT(*) FROM candidaturas WHERE diaria_id = d.id AND status = 'checkin_realizado') as presentes,
            d.valor * (SELECT COUNT(*) FROM candidaturas WHERE diaria_id = d.id AND status = 'checkin_realizado') as total_prestadores
     FROM diarias d
     JOIN empresas e ON d.empresa_id = e.id
     WHERE d.id IN (SELECT DISTINCT diaria_id FROM candidaturas WHERE status = 'checkin_realizado')
     ORDER BY d.data_evento DESC"
);

// Calcular valor_empresa se não existir
foreach ($diarias as &$d) {
    if (!$d['valor_empresa']) {
        $taxa = $d['taxa_agencia'] ?? 10;
        $d['valor_empresa'] = $d['valor'] * $d['presentes'] * (1 + $taxa / 100);
    }
    $d['lucro_agencia'] = $d['valor_empresa'] - ($d['valor'] * $d['presentes']);
}

$user = $auth->getUser();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamentos - <?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .money-positive { color: #10B981; }
        .money-negative { color: #EF4444; }
        .money-card { font-size: 1.75rem; font-weight: 800; }
        .card-lucro { background: linear-gradient(135deg, #10B981, #059669); color: white; }
        .card-lucro .stat-value { color: white; }
        .card-pendente { background: linear-gradient(135deg, #F59E0B, #D97706); color: white; }
        .card-pendente .stat-value { color: white; }
        .badge-pendente { background: #FEF3C7; color: #92400E; }
        .badge-pago { background: #D1FAE5; color: #065F46; }
        .btn-sm { padding: 6px 12px; font-size: 0.75rem; }
        .extrato-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #F3F4F6; }
        .extrato-row:last-child { border-bottom: none; }
        .extrato-label { color: #6B7280; }
        .extrato-value { font-weight: 600; }
        
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .money-card { font-size: 1.25rem; }
            .stat-label { font-size: 0.65rem; }
            .table th:nth-child(2), .table td:nth-child(2),
            .table th:nth-child(3), .table td:nth-child(3),
            .table th:nth-child(5), .table td:nth-child(5),
            .table th:nth-child(6), .table td:nth-child(6) { display: none; }
            .table th, .table td { padding: 8px; font-size: 0.8125rem; }
            .quick-actions { flex-wrap: wrap; }
            .action-btn { flex: 1; min-width: calc(50% - 6px); text-align: center; }
        }
    </style>
</head>
<body class="admin-body">
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="admin-main">
        <?php include 'includes/header.php'; ?>
        
        <div class="admin-content">
            <div class="page-header" style="margin-bottom: 24px;">
                <h1 style="margin: 0;">💰 Gestão Financeira</h1>
            </div>

            <!-- Cards de Resumo -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon bg-info">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <span class="stat-value money-card"><?php echo formatMoney($stats['faturamento_mes']); ?></span>
                        <span class="stat-label">Faturamento Bruto (Mês)</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon bg-warning">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <span class="stat-value money-card money-negative">-<?php echo formatMoney($stats['repasse_prestadores']); ?></span>
                        <span class="stat-label">Repasse a Prestadores</span>
                    </div>
                </div>
                
                <div class="stat-card card-lucro">
                    <div class="stat-icon" style="background: rgba(255,255,255,0.2)">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                            <polyline points="17 6 23 6 23 12"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <span class="stat-value money-card"><?php echo formatMoney($stats['lucro_agencia']); ?></span>
                        <span class="stat-label">🎯 Lucro da Agência</span>
                    </div>
                </div>
                
                <div class="stat-card card-pendente">
                    <div class="stat-icon" style="background: rgba(255,255,255,0.2)">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <span class="stat-value money-card"><?php echo $stats['pendencias']; ?></span>
                        <span class="stat-label">Pendências de Pagamento</span>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="quick-actions" style="margin-bottom: 24px;">
                <button class="action-btn action-btn-primary active" onclick="filtrar('todos')" id="btn-todos">Todos</button>
                <button class="action-btn" onclick="filtrar('receber')" id="btn-receber">A Receber</button>
                <button class="action-btn" onclick="filtrar('pagar')" id="btn-pagar">A Pagar</button>
                <button class="action-btn" onclick="filtrar('concluidos')" id="btn-concluidos">Concluídos</button>
            </div>

            <!-- Tabela -->
            <div class="card">
                <div class="card-header">
                    <h3>Fluxo de Pagamentos</h3>
                </div>
                <div class="table-responsive">
                    <table class="table" id="tabela-pagamentos">
                        <thead>
                            <tr>
                                <th>Evento</th>
                                <th>Empresa</th>
                                <th>Data</th>
                                <th>Prestadores</th>
                                <th>Valor Total</th>
                                <th>Repasse</th>
                                <th>Lucro</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($diarias)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-8 text-muted">
                                    Nenhum evento com pagamentos
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($diarias as $d): ?>
                            <?php
                            $status = 'pendente';
                            if ($d['pago_empresa']) {
                                $todosPagos = $db->fetch(
                                    "SELECT COUNT(*) as pendentes FROM pagamentos_prestadores 
                                     WHERE diaria_id = :did AND status = 'pendente'",
                                    ['did' => $d['id']]
                                )['pendentes'] ?? 0;
                                $status = $todosPagos > 0 ? 'pago_empresa' : 'concluido';
                            }
                            ?>
                            <tr data-status="<?php echo $status; ?>">
                                <td><strong><?php echo sanitize($d['titulo']); ?></strong></td>
                                <td><?php echo sanitize($d['razao_social']); ?></td>
                                <td><?php echo formatDate($d['data_evento']); ?></td>
                                <td><?php echo $d['presentes']; ?></td>
                                <td><strong><?php echo formatMoney($d['valor_empresa']); ?></strong></td>
                                <td class="money-negative"><?php echo formatMoney($d['valor'] * $d['presentes']); ?></td>
                                <td class="money-positive"><strong><?php echo formatMoney($d['lucro_agencia']); ?></strong></td>
                                <td>
                                    <span class="badge badge-<?php echo $status === 'concluido' ? 'success' : ($status === 'pendente' ? 'warning' : 'info'); ?>">
                                        <?php 
                                        echo $status === 'concluido' ? '✓ Concluído' : 
                                             ($status === 'pendente' ? '⏳ A receber' : 'Pago (A pagar prestadores)'); 
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-secondary" onclick="verExtrato(<?php echo $d['id']; ?>)">
                                        Extrato
                                    </button>
                                    <?php if (!$d['pago_empresa']): ?>
                                    <button class="btn btn-sm btn-success" onclick="confirmarRecebimento(<?php echo $d['id']; ?>)">
                                        Recebido
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Extrato -->
    <div id="modalExtrato" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:200;align-items:center;justify-content:center">
        <div style="background:white;border-radius:16px;width:100%;max-width:500px;max-height:90vh;overflow-y:auto">
            <div style="padding:20px;border-bottom:1px solid #E5E7EB;display:flex;justify-content:space-between;align-items:center">
                <h2 style="margin:0;font-size:1.125rem">Extrato do Evento</h2>
                <button onclick="document.getElementById('modalExtrato').style.display='none'" style="background:none;border:none;font-size:24px;cursor:pointer">&times;</button>
            </div>
            <div id="extratoContent" style="padding:20px"></div>
        </div>
    </div>

    <script src="../assets/js/admin.js"></script>
    <script>
    function filtrar(filtro) {
        document.querySelectorAll('.action-btn').forEach(b => b.classList.remove('active'));
        document.getElementById('btn-' + filtro).classList.add('active');
        
        document.querySelectorAll('#tabela-pagamentos tbody tr').forEach(row => {
            if (filtro === 'todos') {
                row.style.display = '';
            } else {
                const status = row.dataset.status;
                if (filtro === 'receber' && status === 'pendente') row.style.display = '';
                else if (filtro === 'pagar' && status === 'pago_empresa') row.style.display = '';
                else if (filtro === 'concluidos' && status === 'concluido') row.style.display = '';
                else row.style.display = 'none';
            }
        });
    }
    
    function verExtrato(diariaId) {
        fetch('api/financeiro.php?action=extrato&diaria_id=' + diariaId)
        .then(r => r.json())
        .then(data => {
            document.getElementById('extratoContent').innerHTML = data.html;
            document.getElementById('modalExtrato').style.display = 'flex';
        });
    }
    
    function confirmarRecebimento(diariaId) {
        if (!confirm('Confirmar recebimento do valor da empresa?')) return;
        
        fetch('pagamentos.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'confirmar_recebimento', diaria_id: diariaId})
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) location.reload();
            else alert(d.error || 'Erro');
        });
    }
    
    function pagarPrestador(diariaId, prestadorId) {
        if (!confirm('Confirmar pagamento ao prestador?')) return;
        
        fetch('pagamentos.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'pagar_prestador', diaria_id: diariaId, prestador_id: prestadorId})
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) location.reload();
            else alert(d.error || 'Erro');
        });
    }
    </script>
</body>
</html>
