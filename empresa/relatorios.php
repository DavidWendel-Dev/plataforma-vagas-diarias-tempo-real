<?php
require_once __DIR__ . '/../app.php';
$auth = new Auth();
$auth->requireType('empresa');
$db = Database::getInstance();
$empresa = $db->fetch("SELECT e.* FROM empresas e WHERE e.usuario_id = :uid", ['uid' => userId()]);

$stats = [
    'total_eventos' => $db->fetch("SELECT COUNT(*) as total FROM diarias WHERE empresa_id = :eid", ['eid' => $empresa['id']])['total'] ?? 0,
    'total_presentes' => $db->fetch("SELECT COUNT(*) as total FROM candidaturas c JOIN diarias d ON c.diaria_id = d.id WHERE d.empresa_id = :eid AND c.status = 'checkin_realizado'", ['eid' => $empresa['id']])['total'] ?? 0,
    'total_gasto' => $db->fetch("SELECT COALESCE(SUM(d.valor),0) as total FROM candidaturas c JOIN diarias d ON c.diaria_id = d.id WHERE d.empresa_id = :eid AND c.status = 'checkin_realizado'", ['eid' => $empresa['id']])['total'] ?? 0,
    'media_prestadores' => $db->fetch("SELECT COALESCE(AVG(sub.total),0) as media FROM (SELECT COUNT(*) as total FROM candidaturas c JOIN diarias d ON c.diaria_id = d.id WHERE d.empresa_id = :eid GROUP BY d.id) sub", ['eid' => $empresa['id']])['media'] ?? 0,
];

$porFuncao = $db->fetchAll("SELECT d.funcao, COUNT(*) as total, SUM(d.vagas_preenchidas) as prestadores FROM diarias d WHERE d.empresa_id = :eid GROUP BY d.funcao ORDER BY total DESC", ['eid' => $empresa['id']]);

$user = $auth->getUser();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Relatórios - <?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>.admin-sidebar{background:linear-gradient(180deg,#10B981,#059669)}.nav-link{color:#fff}.nav-link:hover{background:rgba(255,255,255,0.15)}.nav-link.active{background:rgba(255,255,255,0.2)}.stat-value{color:#10B981}.bg-primary{background:#10B981!important}</style>
</head>
<body class="admin-body">
    <?php include 'includes/sidebar.php'; ?>
    <main class="admin-main">
        <?php include 'includes/header.php'; ?>
        <div class="admin-content">
            <div class="page-header" style="margin-bottom:24px"><h1 style="margin:0">Relatórios</h1></div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon bg-primary"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/></svg></div>
                    <div class="stat-content"><span class="stat-value"><?php echo $stats['total_eventos']; ?></span><span class="stat-label">Total de Eventos</span></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-success"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
                    <div class="stat-content"><span class="stat-value"><?php echo $stats['total_presentes']; ?></span><span class="stat-label">Prestadores</span></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-info"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
                    <div class="stat-content"><span class="stat-value"><?php echo formatMoney($stats['total_gasto']); ?></span><span class="stat-label">Total Gasto</span></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-warning"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/></svg></div>
                    <div class="stat-content"><span class="stat-value"><?php echo number_format($stats['media_prestadores'], 1); ?></span><span class="stat-label">Média/Evento</span></div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header"><h3>Por Função</h3></div>
                <div class="table-responsive">
                    <table class="table">
                        <thead><tr><th>Função</th><th>Eventos</th><th>Prestadores</th></tr></thead>
                        <tbody>
                            <?php if (empty($porFuncao)): ?>
                            <tr><td colspan="3" class="text-center py-8 text-muted">Sem dados</td></tr>
                            <?php else: ?>
                            <?php foreach ($porFuncao as $f): ?>
                            <tr><td><?php echo sanitize($f['funcao']); ?></td><td><?php echo $f['total']; ?></td><td><?php echo $f['prestadores']; ?></td></tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    <script src="../assets/js/admin.js"></script>
</body>
</html>
