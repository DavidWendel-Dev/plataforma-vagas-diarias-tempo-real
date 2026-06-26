<?php
require_once __DIR__ . '/../app.php';

$auth = new Auth();
$auth->requireType('admin');

$db = Database::getInstance();

// Estatísticas
$stats = [
    'total_diarias' => $db->fetch("SELECT COUNT(*) as total FROM diarias")['total'],
    'total_prestadores' => $db->fetch("SELECT COUNT(*) as total FROM prestadores WHERE status = 'aprovado'")['total'],
    'total_empresas' => $db->fetch("SELECT COUNT(*) as total FROM empresas")['total'],
    'faturamento_total' => $db->fetch(
        "SELECT COALESCE(SUM(d.valor), 0) as total FROM candidaturas c 
         JOIN diarias d ON c.diaria_id = d.id WHERE c.status = 'checkin_realizado'"
    )['total'],
];

// Top empresas
$topEmpresas = $db->fetchAll(
    "SELECT e.razao_social, COUNT(d.id) as total_diarias, SUM(d.vagas_preenchidas) as vagas
     FROM empresas e
     JOIN diarias d ON d.empresa_id = e.id
     GROUP BY e.id
     ORDER BY total_diarias DESC
     LIMIT 5"
);

// Top prestadores
$topPrestadores = $db->fetchAll(
    "SELECT u.nome, COUNT(c.id) as total_diarias, AVG(d.nota_prestador) as media
     FROM prestadores p
     JOIN usuarios u ON p.usuario_id = u.id
     JOIN candidaturas c ON c.prestador_id = p.id AND c.status = 'checkin_realizado'
     JOIN diarias d ON c.diaria_id = d.id
     GROUP BY p.id
     ORDER BY total_diarias DESC
     LIMIT 5"
);

$user = $auth->getUser();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - <?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-body">
    <?php include 'includes/sidebar.php'; ?>
    <main class="admin-main">
        <?php include 'includes/header.php'; ?>
        <div class="admin-content">
            <div class="page-header" style="margin-bottom: 24px;">
                <h1 style="margin: 0;">Relatórios</h1>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon bg-primary">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <span class="stat-value"><?php echo $stats['total_diarias']; ?></span>
                        <span class="stat-label">Total de Diárias</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-success">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <span class="stat-value"><?php echo $stats['total_prestadores']; ?></span>
                        <span class="stat-label">Prestadores Ativos</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-warning">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <span class="stat-value"><?php echo $stats['total_empresas']; ?></span>
                        <span class="stat-label">Empresas Cadastradas</span>
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
                        <span class="stat-value"><?php echo formatMoney($stats['faturamento_total']); ?></span>
                        <span class="stat-label">Faturamento Total</span>
                    </div>
                </div>
            </div>

            <div class="dashboard-grid">
                <!-- Top Empresas -->
                <div class="card">
                    <div class="card-header">
                        <h3>Top Empresas</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Empresa</th>
                                    <th>Diárias</th>
                                    <th>Vagas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($topEmpresas)): ?>
                                <tr><td colspan="3" class="text-center py-4 text-muted">Sem dados</td></tr>
                                <?php else: ?>
                                <?php foreach ($topEmpresas as $e): ?>
                                <tr>
                                    <td><?php echo sanitize($e['razao_social']); ?></td>
                                    <td><strong><?php echo $e['total_diarias']; ?></strong></td>
                                    <td><?php echo $e['vagas'] ?? 0; ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Top Prestadores -->
                <div class="card">
                    <div class="card-header">
                        <h3>Top Prestadores</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Diárias</th>
                                    <th>Nota</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($topPrestadores)): ?>
                                <tr><td colspan="3" class="text-center py-4 text-muted">Sem dados</td></tr>
                                <?php else: ?>
                                <?php foreach ($topPrestadores as $p): ?>
                                <tr>
                                    <td><?php echo sanitize($p['nome']); ?></td>
                                    <td><strong><?php echo $p['total_diarias']; ?></strong></td>
                                    <td><?php echo $p['media'] ? number_format($p['media'], 1) : '-'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script src="../assets/js/admin.js"></script>
</body>
</html>
