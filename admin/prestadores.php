<?php
require_once __DIR__ . '/../app.php';

$auth = new Auth();
$auth->requireType('admin');

$db = Database::getInstance();

// Prestadores com filtros
$status = $_GET['status'] ?? '';
$busca = sanitize($_GET['q'] ?? '');

$where = '1=1';
$params = [];

if ($status) {
    $where .= ' AND p.status = :status';
    $params['status'] = $status;
}

if ($busca) {
    $where .= ' AND (u.nome LIKE :q1 OR u.email LIKE :q2)';
    $params['q1'] = "%{$busca}%";
    $params['q2'] = "%{$busca}%";
}

$prestadores = $db->fetchAll(
    "SELECT p.*, u.nome, u.email, u.foto, u.telefone, u.ativo,
            (SELECT COUNT(*) FROM candidaturas c WHERE c.prestador_id = p.id) as total_diarias
     FROM prestadores p
     JOIN usuarios u ON p.usuario_id = u.id
     WHERE {$where}
     ORDER BY p.created_at DESC",
    $params
);

$user = $auth->getUser();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prestadores - <?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .grid-prestadores { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 24px; }
        .prestador-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .prestador-header { display: flex; gap: 16px; padding: 20px; background: linear-gradient(135deg, #6366F1, #4F46E5); color: white; }
        .prestador-avatar img, .prestador-avatar .avatar-placeholder { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; font-weight: 700; }
        .prestador-info h3 { margin: 0 0 4px; font-size: 1rem; }
        .prestador-info p { margin: 0 0 8px; font-size: 0.875rem; opacity: 0.9; }
        .prestador-body { padding: 16px; }
        .info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f3f4f6; }
        .prestador-actions { padding: 16px; background: #f9fafb; display: flex; gap: 8px; }
    </style>
</head>
<body class="admin-body">
    <?php include 'includes/sidebar.php'; ?>
    <main class="admin-main">
        <?php include 'includes/header.php'; ?>
        <div class="admin-content">
            <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <h1 style="margin: 0;">Prestadores</h1>
            </div>

            <div class="filters" style="display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap;">
                <form method="GET" style="display: flex; gap: 12px; flex-wrap: wrap;">
                    <input type="text" name="q" placeholder="Buscar por nome ou email..." value="<?php echo $busca; ?>" class="form-control" style="width: 250px;">
                    
                    <select name="status" class="form-control" style="width: auto;">
                        <option value="">Todos</option>
                        <option value="pendente" <?php echo $status === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                        <option value="aprovado" <?php echo $status === 'aprovado' ? 'selected' : ''; ?>>Aprovado</option>
                        <option value="rejeitado" <?php echo $status === 'rejeitario' ? 'selected' : ''; ?>>Rejeitado</option>
                    </select>
                    
                    <button type="submit" class="btn btn-secondary">Buscar</button>
                    <a href="prestadores.php" class="btn btn-ghost">Limpar</a>
                </form>
            </div>

            <div class="grid-prestadores">
                <?php if (empty($prestadores)): ?>
                <div class="card" style="padding: 40px; text-align: center;">
                    <p class="text-muted">Nenhum prestador encontrado</p>
                </div>
                <?php else: ?>
                <?php foreach ($prestadores as $p): ?>
                <div class="prestador-card">
                    <div class="prestador-header">
                        <div class="prestador-avatar">
                            <?php if ($p['foto']): ?>
                            <img src="../uploads/prestadores/<?php echo $p['foto']; ?>" alt="">
                            <?php else: ?>
                            <div class="avatar-placeholder"><?php echo substr($p['nome'], 0, 1); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="prestador-info">
                            <h3><?php echo sanitize($p['nome']); ?></h3>
                            <p><?php echo sanitize($p['email']); ?></p>
                            <span class="badge badge-<?php echo $p['status'] === 'aprovado' ? 'success' : ($p['status'] === 'pendente' ? 'warning' : 'danger'); ?>">
                                <?php echo ucfirst($p['status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="prestador-body">
                        <div class="info-row">
                            <span>Telefone:</span>
                            <strong><?php echo $p['telefone'] ?: '-'; ?></strong>
                        </div>
                        <div class="info-row">
                            <span>Nascimento:</span>
                            <strong><?php echo $p['data_nascimento'] ? formatDate($p['data_nascimento']) : '-'; ?></strong>
                        </div>
                        <div class="info-row">
                            <span>Diárias:</span>
                            <strong><?php echo $p['total_diarias']; ?></strong>
                        </div>
                    </div>
                    <div class="prestador-actions">
                        <a href="prestador-detalhe.php?id=<?php echo $p['id']; ?>" class="btn btn-secondary btn-sm">Ver Detalhes</a>
                        <?php if ($p['status'] === 'pendente'): ?>
                        <button class="btn btn-success btn-sm" onclick="aprovar(<?php echo $p['id']; ?>)">Aprovar</button>
                        <button class="btn btn-danger btn-sm" onclick="rejeitar(<?php echo $p['id']; ?>)">Rejeitar</button>
                        <?php elseif ($p['status'] === 'aprovado'): ?>
                        <button class="btn btn-warning btn-sm" onclick="suspender(<?php echo $p['id']; ?>)">Suspender</button>
                        <?php elseif ($p['status'] === 'rejeitado'): ?>
                        <button class="btn btn-success btn-sm" onclick="aprovar(<?php echo $p['id']; ?>)">Reativar</button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <script src="../assets/js/admin.js"></script>
    <script>
    function aprovar(id) {
        fetch('api/prestadores.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({action: 'aprovar', id: id}) })
        .then(r => r.json()).then(d => { if (d.success) location.reload(); else alert(d.error); });
    }
    function rejeitar(id) {
        if (!confirm('Rejeitar este prestador?')) return;
        fetch('api/prestadores.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({action: 'rejeitar', id: id}) })
        .then(r => r.json()).then(d => { if (d.success) location.reload(); else alert(d.error); });
    }
    function suspender(id) {
        if (!confirm('Suspender este prestador?')) return;
        fetch('api/prestadores.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({action: 'suspender', id: id}) })
        .then(r => r.json()).then(d => { if (d.success) location.reload(); else alert(d.error); });
    }
    </script>
</body>
</html>
