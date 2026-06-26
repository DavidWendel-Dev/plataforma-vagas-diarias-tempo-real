<?php
require_once __DIR__ . '/../app.php';

$auth = new Auth();
$auth->requireType('admin');

$db = Database::getInstance();

// Estatísticas para o Dashboard
$stats = [
    'diarias_ativas' => $db->fetch(
        "SELECT COUNT(*) as total FROM diarias WHERE status = 'ativa' AND data_evento >= CURDATE()"
    )['total'] ?? 0,
    
    'diarias_mes' => $db->fetch(
        "SELECT COUNT(*) as total FROM diarias WHERE MONTH(data_evento) = MONTH(CURDATE()) AND YEAR(data_evento) = YEAR(CURDATE())"
    )['total'] ?? 0,
    
    'prestadores_pendentes' => $db->fetch(
        "SELECT COUNT(*) as total FROM prestadores WHERE status = 'pendente'"
    )['total'] ?? 0,
    
    'prestadores_ativos' => $db->fetch(
        "SELECT COUNT(*) as total FROM prestadores WHERE status = 'aprovado'"
    )['total'] ?? 0,
    
    'empresas_ativas' => $db->fetch(
        "SELECT COUNT(*) as total FROM empresas WHERE status = 'ativo'"
    )['total'] ?? 0,
    
    'taxa_presenca' => $db->fetch(
        "SELECT 
            ROUND(
                (SELECT COUNT(*) FROM candidaturas WHERE status = 'checkin_realizado') * 100.0 / 
                NULLIF((SELECT COUNT(*) FROM candidaturas WHERE status IN ('checkin_realizado', 'faltou')), 0)
            , 1) as taxa"
    )['taxa'] ?? 0,
    
    'faturamento_mes' => $db->fetch(
        "SELECT COALESCE(SUM(d.valor), 0) as total 
         FROM candidaturas c 
         JOIN diarias d ON c.diaria_id = d.id 
         WHERE c.status = 'checkin_realizado' 
         AND MONTH(d.data_evento) = MONTH(CURDATE()) 
         AND YEAR(d.data_evento) = YEAR(CURDATE())"
    )['total'] ?? 0
];

// Últimas diárias
$ultimasDiarias = $db->fetchAll(
    "SELECT d.*, e.razao_social, u.nome as empresa_nome,
            (SELECT COUNT(*) FROM candidaturas WHERE diaria_id = d.id AND status != 'cancelada') as inscritos
     FROM diarias d
     JOIN empresas e ON d.empresa_id = e.id
     JOIN usuarios u ON e.usuario_id = u.id
     ORDER BY d.created_at DESC
     LIMIT 10"
);

// Prestadores pendentes
$prestadoresPendentes = $db->fetchAll(
    "SELECT p.*, u.nome, u.email, u.foto, u.telefone
     FROM prestadores p
     JOIN usuarios u ON p.usuario_id = u.id
     WHERE p.status = 'pendente'
     ORDER BY p.created_at ASC
     LIMIT 10"
);

$user = $auth->getUser();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - <?php echo APP_NAME; ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../assets/css/style.css?v=5">
    <link rel="stylesheet" href="../assets/css/admin.css?v=5">
</head>
<body class="admin-body">
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="admin-main">
        <?php include 'includes/header.php'; ?>
        
        <div class="admin-content">
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon bg-primary">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <span class="stat-value"><?php echo $stats['diarias_ativas']; ?></span>
                        <span class="stat-label">Diárias Ativas</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon bg-success">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <span class="stat-value"><?php echo $stats['prestadores_ativos']; ?></span>
                        <span class="stat-label">Prestadores Ativos</span>
                    </div>
                    <?php if ($stats['prestadores_pendentes'] > 0): ?>
                    <a href="moderacao.php" class="stat-badge badge-warning">
                        <?php echo $stats['prestadores_pendentes']; ?> pendentes
                    </a>
                    <?php endif; ?>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon bg-warning">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                            <polyline points="9 22 9 12 15 12 15 22"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <span class="stat-value"><?php echo $stats['empresas_ativas']; ?></span>
                        <span class="stat-label">Empresas Parceiras</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon bg-info">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <span class="stat-value"><?php echo formatMoney($stats['faturamento_mes']); ?></span>
                        <span class="stat-label">Faturamento do Mês</span>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="diaria-nova.php" class="action-btn action-btn-primary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="16"/>
                        <line x1="8" y1="12" x2="16" y2="12"/>
                    </svg>
                    Nova Diária
                </a>
                <a href="empresas.php" class="action-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="8.5" cy="7" r="4"/>
                        <line x1="20" y1="8" x2="20" y2="14"/>
                        <line x1="23" y1="11" x2="17" y2="11"/>
                    </svg>
                    Nova Empresa
                </a>
                <a href="moderacao.php" class="action-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="8.5" cy="7" r="4"/>
                        <polyline points="17 11 19 13 23 9"/>
                    </svg>
                    Moderação
                    <?php if ($stats['prestadores_pendentes'] > 0): ?>
                    <span class="badge badge-warning"><?php echo $stats['prestadores_pendentes']; ?></span>
                    <?php endif; ?>
                </a>
                <a href="relatorios.php" class="action-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="20" x2="18" y2="10"/>
                        <line x1="12" y1="20" x2="12" y2="4"/>
                        <line x1="6" y1="20" x2="6" y2="14"/>
                    </svg>
                    Relatórios
                </a>
            </div>
            
            <div class="dashboard-grid">
                <!-- Últimas Diárias -->
                <div class="card">
                    <div class="card-header">
                        <h3>Últimas Diárias</h3>
                        <a href="diarias.php" class="link-view-all">Ver todas</a>
                    </div>
                    <div class="card-body p-0">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Título</th>
                                    <th>Empresa</th>
                                    <th>Data</th>
                                    <th>Vagas</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($ultimasDiarias)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        Nenhuma diária cadastrada
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($ultimasDiarias as $diaria): ?>
                                <tr>
                                    <td data-label="Título">
                                        <a href="diaria-editar.php?id=<?php echo $diaria['id']; ?>">
                                            <?php echo sanitize($diaria['titulo']); ?>
                                        </a>
                                    </td>
                                    <td data-label="Empresa"><?php echo sanitize($diaria['empresa_nome'] ?? $diaria['razao_social']); ?></td>
                                    <td data-label="Data"><?php echo formatDate($diaria['data_evento']); ?></td>
                                    <td data-label="Vagas">
                                        <span class="vagas-count">
                                            <?php echo $diaria['inscritos']; ?>/<?php echo $diaria['vagas_total']; ?>
                                        </span>
                                    </td>
                                    <td data-label="Status">
                                        <span class="badge badge-<?php echo $diaria['status'] === 'ativa' ? 'success' : ($diaria['status'] === 'cancelada' ? 'danger' : 'warning'); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $diaria['status'])); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Prestadores Pendentes -->
                <div class="card">
                    <div class="card-header">
                        <h3>Prestadores Pendentes</h3>
                        <a href="moderacao.php" class="link-view-all">Ver todos</a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($prestadoresPendentes)): ?>
                        <div class="empty-state-small">
                            <p class="text-muted">Nenhum prestador pendente de aprovação</p>
                        </div>
                        <?php else: ?>
                        <?php foreach ($prestadoresPendentes as $prestador): ?>
                        <div class="pending-item">
                            <div class="pending-avatar">
                                <?php if ($prestador['foto']): ?>
                                <img src="../uploads/prestadores/<?php echo $prestador['foto']; ?>" alt="">
                                <?php else: ?>
                                <div class="avatar-placeholder">
                                    <?php echo substr($prestador['nome'], 0, 1); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="pending-info">
                                <strong><?php echo sanitize($prestador['nome']); ?></strong>
                                <span><?php echo sanitize($prestador['email']); ?></span>
                            </div>
                            <div class="pending-actions">
                                <button class="btn btn-sm btn-success" onclick="aprovarPrestador(<?php echo $prestador['id']; ?>)">
                                    Aprovar
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="rejeitarPrestador(<?php echo $prestador['id']; ?>)">
                                    Rejeitar
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <script src="../assets/js/admin.js"></script>
    <script>
    function aprovarPrestador(id) {
        if (confirm('Aprovar este prestador?')) {
            fetch('api/moderacao.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'aprovar', id: id})
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error || 'Erro ao aprovar');
                }
            });
        }
    }
    
    function rejeitarPrestador(id) {
        const motivo = prompt('Motivo da rejeição (opcional):');
        fetch('api/moderacao.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'rejeitar', id: id, motivo: motivo})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Erro ao rejeitar');
            }
        });
    }
    </script>
</body>
</html>
