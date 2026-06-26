<?php
require_once __DIR__ . '/../app.php';

$auth = new Auth();
$auth->requireType('empresa');

$db = Database::getInstance();

$empresa = $db->fetch(
    "SELECT e.* FROM empresas e WHERE e.usuario_id = :uid",
    ['uid' => userId()]
);

// Estatísticas
$stats = [
    'total_investido' => $db->fetch(
        "SELECT COALESCE(SUM(d.valor_empresa), 0) as total FROM diarias d 
         WHERE d.empresa_id = :eid AND d.pago_empresa = 1 
         AND MONTH(d.pago_empresa_at) = MONTH(CURDATE())",
        ['eid' => $empresa['id']]
    )['total'] ?? 0,
    
    'faturas_aberto' => $db->fetch(
        "SELECT COALESCE(SUM(d.valor_empresa), 0) as total FROM diarias d 
         JOIN candidaturas c ON c.diaria_id = d.id 
         WHERE d.empresa_id = :eid AND c.status = 'checkin_realizado' AND d.pago_empresa = 0",
        ['eid' => $empresa['id']]
    )['total'] ?? 0,
    
    'dias_a_pagar' => $db->fetch(
        "SELECT COUNT(*) as total FROM diarias d 
         JOIN candidaturas c ON c.diaria_id = d.id 
         WHERE d.empresa_id = :eid AND c.status = 'checkin_realizado' AND d.pago_empresa = 0",
        ['eid' => $empresa['id']]
    )['total'] ?? 0,
    
    'diarias_mes' => $db->fetch(
        "SELECT COALESCE(SUM(c.vagas_preenchidas), 0) as total FROM (
            SELECT d.id, COUNT(*) as vagas_preenchidas 
            FROM diarias d 
            JOIN candidaturas c ON c.diaria_id = d.id 
            WHERE d.empresa_id = :eid AND c.status = 'checkin_realizado' 
            AND MONTH(d.data_evento) = MONTH(CURDATE())
            GROUP BY d.id
        ) c",
        ['eid' => $empresa['id']]
    )['total'] ?? 0,
];

// Buscar eventos com valores
$eventos = $db->fetchAll(
    "SELECT d.*, 
            (SELECT COUNT(*) FROM candidaturas WHERE diaria_id = d.id AND status = 'checkin_realizado') as presentes,
            d.valor * (SELECT COUNT(*) FROM candidaturas WHERE diaria_id = d.id AND status = 'checkin_realizado') as total_base
     FROM diarias d
     WHERE d.empresa_id = :eid
     ORDER BY d.data_evento DESC",
    ['eid' => $empresa['id']]
);

// Calcular valores
foreach ($eventos as &$e) {
    $taxa = $e['taxa_agencia'] ?? 10;
    if (!$e['valor_empresa'] && $e['presentes'] > 0) {
        $e['valor_empresa'] = $e['valor'] * $e['presentes'] * (1 + $taxa / 100);
    }
    $e['taxa_valor'] = $e['valor_empresa'] - ($e['valor'] * $e['presentes']);
}

$user = $auth->getUser();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faturamento - <?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .admin-sidebar{background:linear-gradient(180deg,#10B981,#059669)}.nav-link{color:#fff}.nav-link:hover{background:rgba(255,255,255,0.15)}.nav-link.active{background:rgba(255,255,255,0.2)}
        .money-card{font-size:1.5rem;font-weight:800;color:#10B981}
        .card-warning{background:linear-gradient(135deg,#F59E0B,#D97706);color:white}
        .card-warning .stat-value{color:white}
        .badge-pendente{background:#FEF3C7;color:#92400E}
        .badge-pago{background:#D1FAE5;color:#065F46}
        .extrato-row{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #F3F4F6}
        .extrato-row:last-child{border-bottom:none}
        .taxa-info{background:#F0FDF4;padding:12px;border-radius:8px;margin-top:12px}
    </style>
    <style>
        .pagamentos-table { width: 100%; border-collapse: collapse; }
        .pagamentos-table th, .pagamentos-table td { padding: 12px; text-align: left; }
        
        @media (max-width: 768px) {
            .table-responsive { overflow-x: visible !important; }
            .pagamentos-table thead { display: none; }
            .pagamentos-table, .pagamentos-table tbody, .pagamentos-table tr, .pagamentos-table td { display: block; width: 100%; }
            .pagamentos-table tr { 
                border: 1px solid #E5E7EB; 
                border-radius: 12px; 
                margin-bottom: 12px; 
                padding: 8px;
                background: white;
            }
            .pagamentos-table tr:hover { background: #F9FAFB; }
            .pagamentos-table td { 
                border: none; 
                padding: 8px 12px; 
                display: flex; 
                justify-content: space-between; 
                align-items: center; 
                gap: 12px;
            }
            .pagamentos-table td:before { 
                content: attr(data-label);
                font-weight: 600;
                color: #6B7280;
                font-size: 0.8125rem;
            }
            .row-empty td { display: block !important; text-align: center; }
        }
    </style>
</head>
<body class="admin-body">
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="admin-main">
        <?php include 'includes/header.php'; ?>
        
        <div class="admin-content">
            <div class="page-header" style="margin-bottom:24px">
                <h1 style="margin:0">💰 Faturamento</h1>
            </div>

            <!-- Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon bg-success">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <span class="stat-value money-card"><?php echo formatMoney($stats['total_investido']); ?></span>
                        <span class="stat-label">Total Investido (Mês)</span>
                    </div>
                </div>
                
                <div class="stat-card card-warning">
                    <div class="stat-icon" style="background:rgba(255,255,255,0.2)">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <span class="stat-value money-card"><?php echo formatMoney($stats['faturas_aberto']); ?></span>
                        <span class="stat-label">Faturas em Aberto</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon bg-info">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <span class="stat-value money-card"><?php echo $stats['diarias_mes']; ?></span>
                        <span class="stat-label">Diárias Contratadas</span>
                    </div>
                </div>
            </div>

            <!-- Tabela -->
            <div class="card">
                <div class="card-header">
                    <h3>Faturas / Eventos</h3>
                </div>
                <div class="table-responsive">
                    <table class="table pagamentos-table">
                        <thead>
                            <tr>
                                <th>Evento</th>
                                <th>Data</th>
                                <th>Profissionais</th>
                                <th>Valor Base</th>
                                <th>Taxa Agência</th>
                                <th>Total a Pagar</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($eventos)): ?>
                            <tr class="row-empty"><td colspan="8" class="text-center py-8 text-muted">Nenhum evento encontrado</td></tr>
                            <?php else: ?>
                            <?php foreach ($eventos as $e): ?>
                            <tr>
                                <td data-label="Evento"><strong><?php echo sanitize($e['titulo']); ?></strong></td>
                                <td data-label="Data"><?php echo formatDate($e['data_evento']); ?></td>
                                <td data-label="Profissionais"><?php echo $e['presentes']; ?> profissional(is)</td>
                                <td data-label="Valor Base"><?php echo formatMoney($e['valor'] * $e['presentes']); ?></td>
                                <td data-label="Taxa Agência" style="color:#6B7280"><?php echo formatMoney($e['taxa_valor']); ?> (<?php echo $e['taxa_agencia'] ?? 10; ?>%)</td>
                                <td data-label="Total a Pagar"><strong><?php echo formatMoney($e['valor_empresa']); ?></strong></td>
                                <td data-label="Status">
                                    <?php if ($e['presentes'] == 0): ?>
                                    <span class="badge badge-secondary">Sem check-in</span>
                                    <?php elseif ($e['pago_empresa']): ?>
                                    <span class="badge badge-success">✓ Pago</span>
                                    <?php else: ?>
                                    <span class="badge badge-warning">⏳ Pendente</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Ações">
                                    <button class="btn btn-sm btn-secondary" onclick="verEspelho(<?php echo $e['id']; ?>)">
                                        Ver Espelho
                                    </button>
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

    <!-- Modal Espelho -->
    <div id="modalEspelho" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:200;align-items:center;justify-content:center">
        <div style="background:white;border-radius:16px;width:100%;max-width:500px;max-height:90vh;overflow-y:auto">
            <div style="padding:20px;border-bottom:1px solid #E5E7EB;display:flex;justify-content:space-between;align-items:center">
                <h2 style="margin:0;font-size:1.125rem">📄 Espelho do Evento</h2>
                <button onclick="document.getElementById('modalEspelho').style.display='none'" style="background:none;border:none;font-size:24px;cursor:pointer">&times;</button>
            </div>
            <div id="espelhoContent" style="padding:20px"></div>
        </div>
    </div>

    <script src="../assets/js/admin.js"></script>
    <script>
    function verEspelho(diariaId) {
        fetch('api/espelho.php?diaria_id=' + diariaId)
        .then(r => r.json())
        .then(data => {
            document.getElementById('espelhoContent').innerHTML = data.html;
            document.getElementById('modalEspelho').style.display = 'flex';
        });
    }
    </script>
</body>
</html>
