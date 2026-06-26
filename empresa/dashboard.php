<?php
require_once __DIR__ . '/../app.php';

$auth = new Auth();
$auth->requireType('empresa');

$db = Database::getInstance();

$empresa = $db->fetch(
    "SELECT e.*, u.nome, u.email, u.foto, u.telefone FROM empresas e JOIN usuarios u ON e.usuario_id = u.id WHERE u.id = :id",
    ['id' => userId()]
);

// Stats
$stats = [
    'diarias_ativas' => $db->fetch("SELECT COUNT(*) as total FROM diarias WHERE empresa_id = :eid AND status = 'ativa' AND data_evento >= CURDATE()", ['eid' => $empresa['id']])['total'] ?? 0,
    'prestadores_agendados' => $db->fetch("SELECT COUNT(*) as total FROM candidaturas c JOIN diarias d ON c.diaria_id = d.id WHERE d.empresa_id = :eid AND c.status = 'confirmada' AND d.data_evento >= CURDATE()", ['eid' => $empresa['id']])['total'] ?? 0,
    'total_gasto' => $db->fetch("SELECT COALESCE(SUM(d.valor), 0) as total FROM candidaturas c JOIN diarias d ON c.diaria_id = d.id WHERE d.empresa_id = :eid AND c.status = 'checkin_realizado'", ['eid' => $empresa['id']])['total'] ?? 0,
    'eventos_mes' => $db->fetch("SELECT COUNT(*) as total FROM diarias WHERE empresa_id = :eid AND MONTH(data_evento) = MONTH(CURDATE())", ['eid' => $empresa['id']])['total'] ?? 0,
];

// Próximas diárias
$proximasDiarias = $db->fetchAll(
    "SELECT d.*, (SELECT COUNT(*) FROM candidaturas WHERE diaria_id = d.id AND status = 'confirmada') as confirmados FROM diarias d WHERE d.empresa_id = :eid AND d.status = 'ativa' AND d.data_evento >= CURDATE() ORDER BY d.data_evento ASC LIMIT 5",
    ['eid' => $empresa['id']]
);

// Evento de hoje
$diariaHoje = $db->fetch(
    "SELECT d.*, d.codigo_checkin FROM diarias d WHERE d.empresa_id = :eid AND d.data_evento = CURDATE() AND d.status = 'ativa' ORDER BY d.horario_inicio ASC LIMIT 1",
    ['eid' => $empresa['id']]
);

$prestadoresHoje = [];
if ($diariaHoje) {
    $prestadoresHoje = $db->fetchAll(
        "SELECT c.id as candidatura_id, c.status, u.nome, u.foto, u.telefone FROM candidaturas c JOIN prestadores p ON c.prestador_id = p.id JOIN usuarios u ON p.usuario_id = u.id WHERE c.diaria_id = :did AND c.status IN ('confirmada', 'checkin_realizado', 'faltou') ORDER BY u.nome",
        ['did' => $diariaHoje['id']]
    );
}

$user = $auth->getUser();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .admin-sidebar { background: linear-gradient(180deg, #10B981, #059669); }
        .nav-link.active { background: rgba(255,255,255,0.2); }
        .nav-link:hover { background: rgba(255,255,255,0.15); }
        .bg-primary { background: #10B981 !important; }
        .stat-value { color: #10B981; }
        .card-highlight { background: linear-gradient(135deg, #10B981, #059669); color: white; }
        .checkin-code { font-family: monospace; background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 6px; }
    </style>
    <style>
        .dashboard-table { width: 100%; border-collapse: collapse; }
        .dashboard-table th, .dashboard-table td { padding: 12px; text-align: left; }
        
        @media (max-width: 768px) {
            .table-responsive { overflow-x: visible !important; }
            .dashboard-table thead { display: none; }
            .dashboard-table, .dashboard-table tbody, .dashboard-table tr, .dashboard-table td { display: block; width: 100%; }
            .dashboard-table tr { 
                border: 1px solid #E5E7EB; 
                border-radius: 12px; 
                margin-bottom: 12px; 
                padding: 8px;
                background: white;
            }
            .dashboard-table tr:hover { background: #F9FAFB; }
            .dashboard-table td { 
                border: none; 
                padding: 8px 12px; 
                display: flex; 
                justify-content: space-between; 
                align-items: center; 
                gap: 12px;
            }
            .dashboard-table td:before { 
                content: attr(data-label);
                font-weight: 600;
                color: #6B7280;
                font-size: 0.8125rem;
            }
        }
    </style>
</head>
<body class="admin-body">
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="admin-main">
        <?php include 'includes/header.php'; ?>
        
        <div class="admin-content">
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon bg-primary"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/></svg></div>
                    <div class="stat-content"><span class="stat-value"><?php echo $stats['diarias_ativas']; ?></span><span class="stat-label">Diárias Ativas</span></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-success"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
                    <div class="stat-content"><span class="stat-value"><?php echo $stats['prestadores_agendados']; ?></span><span class="stat-label">Prestadores</span></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-warning"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/></svg></div>
                    <div class="stat-content"><span class="stat-value"><?php echo $stats['eventos_mes']; ?></span><span class="stat-label">Eventos no Mês</span></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-info"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
                    <div class="stat-content"><span class="stat-value"><?php echo formatMoney($stats['total_gasto']); ?></span><span class="stat-label">Total Gasto</span></div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <?php if (isPublicarVagasPermitido()): ?>
            <div class="quick-actions">
                <a href="eventos.php?action=nova" class="action-btn action-btn-primary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                    Nova Diária
                </a>
                <a href="historico.php" class="action-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    Histórico
                </a>
                <a href="relatorios.php" class="action-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/></svg>
                    Relatórios
                </a>
            </div>
            <?php endif; ?>
            
            <!-- Evento de Hoje -->
            <?php if ($diariaHoje): ?>
            <div class="card card-highlight" style="margin-bottom: 24px;">
                <div class="card-header" style="border-bottom: 1px solid rgba(255,255,255,0.2);">
                    <div>
                        <h3 style="color: white;">📅 Evento de Hoje</h3>
                        <p style="color: rgba(255,255,255,0.9); margin: 4px 0 0; font-size: 0.875rem;"><?php echo sanitize($diariaHoje['titulo']); ?></p>
                    </div>
                    <div class="checkin-code">Código: <?php echo $diariaHoje['codigo_checkin']; ?></div>
                </div>
                <?php if (!empty($prestadoresHoje)): ?>
                <div style="padding: 0;">
                    <?php foreach ($prestadoresHoje as $p): ?>
                    <div style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-top: 1px solid rgba(255,255,255,0.1);">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; font-weight: 600;"><?php echo substr($p['nome'], 0, 1); ?></div>
                        <div style="flex: 1;"><strong><?php echo sanitize($p['nome']); ?></strong></div>
                        <div>
                            <?php if ($p['status'] === 'checkin_realizado'): ?>
                            <span class="badge badge-success">✓ Presente</span>
                            <?php elseif ($p['status'] === 'faltou'): ?>
                            <span class="badge badge-danger">Faltou</span>
                            <?php else: ?>
                            <button class="btn btn-sm btn-success" onclick="marcar(<?php echo $p['candidatura_id']; ?>, 'checkin_realizado')">✓</button>
                            <button class="btn btn-sm btn-danger" onclick="marcar(<?php echo $p['candidatura_id']; ?>, 'faltou')">✗</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Próximos Eventos -->
            <div class="card">
                <div class="card-header">
                    <h3>Próximos Eventos</h3>
                    <a href="eventos.php" class="link-view-all">Ver todos</a>
                </div>
                <?php if (empty($proximasDiarias)): ?>
                <div class="card-body">
                    <div class="empty-state-small">
                        <p class="text-muted">Nenhum evento agendado</p>
                        <?php if (isPublicarVagasPermitido()): ?>
                        <a href="eventos.php?action=nova" class="btn btn-primary" style="margin-top: 12px;">Criar Diária</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table dashboard-table">
                        <thead><tr><th>Título</th><th>Data</th><th>Função</th><th>Vagas</th><th>Valor</th></tr></thead>
                        <tbody>
                            <?php foreach ($proximasDiarias as $d): ?>
                            <tr>
                                <td data-label="Título"><a href="evento.php?id=<?php echo $d['id']; ?>"><strong><?php echo sanitize($d['titulo']); ?></strong></a></td>
                                <td data-label="Data"><?php echo formatDate($d['data_evento']); ?></td>
                                <td data-label="Função"><?php echo sanitize($d['funcao']); ?></td>
                                <td data-label="Vagas"><?php echo $d['confirmados']; ?>/<?php echo $d['vagas_total']; ?></td>
                                <td data-label="Valor"><strong><?php echo formatMoney($d['valor']); ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <script src="../assets/js/admin.js"></script>
    <script>
    function marcar(id, status) {
        fetch('../api/candidaturas.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'checkin_empresa', candidatura_id: id, status: status})
        }).then(r => r.json()).then(d => { if (d.success) location.reload(); else alert(d.error || 'Erro'); });
    }
    </script>
</body>
</html>
