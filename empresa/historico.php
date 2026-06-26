<?php
require_once __DIR__ . '/../app.php';
$auth = new Auth();
$auth->requireType('empresa');
$db = Database::getInstance();
$empresa = $db->fetch("SELECT e.* FROM empresas e WHERE e.usuario_id = :uid", ['uid' => userId()]);

$historico = $db->fetchAll(
    "SELECT d.*, (SELECT COUNT(*) FROM candidaturas c WHERE c.diaria_id = d.id AND c.status = 'checkin_realizado') as presentes FROM diarias d WHERE d.empresa_id = :eid ORDER BY d.data_evento DESC",
    ['eid' => $empresa['id']]
);

$user = $auth->getUser();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Histórico - <?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>.admin-sidebar{background:linear-gradient(180deg,#10B981,#059669)}.nav-link{color:#fff}.nav-link:hover{background:rgba(255,255,255,0.15)}.nav-link.active{background:rgba(255,255,255,0.2)}</style>
    <style>
        .historico-table { width: 100%; border-collapse: collapse; }
        .historico-table th, .historico-table td { padding: 12px; text-align: left; }
        
        @media (max-width: 768px) {
            .table-responsive { overflow-x: visible !important; }
            .historico-table thead { display: none; }
            .historico-table, .historico-table tbody, .historico-table tr, .historico-table td { display: block; width: 100%; }
            .historico-table tr { 
                border: 1px solid #E5E7EB; 
                border-radius: 12px; 
                margin-bottom: 12px; 
                padding: 8px;
                background: white;
            }
            .historico-table tr:hover { background: #F9FAFB; }
            .historico-table td { 
                border: none; 
                padding: 8px 12px; 
                display: flex; 
                justify-content: space-between; 
                align-items: center; 
                gap: 12px;
            }
            .historico-table td:before { 
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
            <div class="page-header" style="margin-bottom:24px"><h1 style="margin:0">Histórico</h1></div>
            <div class="card">
                <div class="table-responsive">
                    <table class="table historico-table">
                        <thead><tr><th>Título</th><th>Data</th><th>Função</th><th>Presentes</th><th>Valor Total</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php if (empty($historico)): ?>
                            <tr class="row-empty"><td colspan="6" class="text-center py-8 text-muted">Nenhum evento no histórico</td></tr>
                            <?php else: ?>
                            <?php foreach ($historico as $h): ?>
                            <tr>
                                <td data-label="Título"><strong><?php echo sanitize($h['titulo']); ?></strong></td>
                                <td data-label="Data"><?php echo formatDate($h['data_evento']); ?></td>
                                <td data-label="Função"><?php echo sanitize($h['funcao']); ?></td>
                                <td data-label="Presentes"><?php echo $h['presentes']; ?></td>
                                <td data-label="Valor Total"><strong><?php echo formatMoney($h['valor'] * $h['presentes']); ?></strong></td>
                                <td data-label="Status"><span class="badge badge-<?php echo $h['status']==='ativa'?'success':($h['status']==='cancelada'?'danger':'secondary')?>"><?php echo ucfirst($h['status']);?></span></td>
                            </tr>
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
