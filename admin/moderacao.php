<?php
require_once __DIR__ . '/../app.php';

$auth = new Auth();
$auth->requireType('admin');

$db = Database::getInstance();

$prestadores = $db->fetchAll(
    "SELECT p.*, u.nome, u.email, u.foto, u.telefone
     FROM prestadores p
     JOIN usuarios u ON p.usuario_id = u.id
     WHERE p.status = 'pendente'
     ORDER BY p.created_at ASC"
);

$user = $auth->getUser();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderação - <?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .moderation-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; margin-top: 24px; }
        .moderation-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .moderation-header { display: flex; gap: 16px; padding: 20px; background: linear-gradient(135deg, #F59E0B, #D97706); color: white; }
        .moderation-avatar img, .moderation-avatar .avatar-placeholder { width: 56px; height: 56px; border-radius: 50%; object-fit: cover; background: rgba(255,255,255,0.3); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 700; }
        .moderation-info h3 { margin: 0 0 4px; font-size: 1rem; }
        .moderation-info p { margin: 0; font-size: 0.875rem; opacity: 0.9; }
        .moderation-body { padding: 16px; }
        .moderation-actions { display: flex; gap: 8px; padding: 16px; background: #f9fafb; }
        .info-item { margin-bottom: 12px; }
        .info-item label { display: block; font-size: 0.75rem; color: #6B7280; margin-bottom: 2px; text-transform: uppercase; }
        .info-item span { font-weight: 500; }
        .funcoes-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 4px; }
        .funcoes-tags .tag { padding: 4px 10px; background: #EEF2FF; color: #4F46E5; border-radius: 20px; font-size: 0.75rem; font-weight: 500; }
    </style>
</head>
<body class="admin-body">
    <?php include 'includes/sidebar.php'; ?>
    <main class="admin-main">
        <?php include 'includes/header.php'; ?>
        <div class="admin-content">
            <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <h1 style="margin: 0;">Moderação de Prestadores</h1>
            </div>

            <?php if (empty($prestadores)): ?>
            <div class="card" style="padding: 60px; text-align: center;">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2" style="margin: 0 auto 16px;">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                <h3 style="color: #10B981;">Tudo em dia!</h3>
                <p class="text-muted">Nenhum prestador pendente de aprovação</p>
            </div>
            <?php else: ?>
            <div class="moderation-grid">
                <?php foreach ($prestadores as $p): ?>
                <div class="moderation-card">
                    <div class="moderation-header">
                        <div class="moderation-avatar">
                            <?php if ($p['foto']): ?>
                            <img src="../uploads/prestadores/<?php echo $p['foto']; ?>" alt="">
                            <?php else: ?>
                            <div class="avatar-placeholder"><?php echo substr($p['nome'], 0, 1); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="moderation-info">
                            <h3><?php echo sanitize($p['nome']); ?></h3>
                            <p><?php echo sanitize($p['email']); ?></p>
                            <p style="font-size: 0.75rem; margin-top: 4px;"><?php echo sanitize($p['telefone'] ?: 'Sem telefone'); ?></p>
                        </div>
                    </div>
                    
                    <div class="moderation-body">
                        <div class="info-item">
                            <label>Nascimento</label>
                            <span><?php echo $p['data_nascimento'] ? formatDate($p['data_nascimento']) : '-'; ?></span>
                        </div>
                        <?php 
                        $funcoes = $p['funcoes'] ? json_decode($p['funcoes'], true) : [];
                        if (!empty($funcoes)): 
                        ?>
                        <div class="info-item">
                            <label>Funções</label>
                            <div class="funcoes-tags">
                                <?php foreach ($funcoes as $f): ?>
                                <span class="tag"><?php echo sanitize($f); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="moderation-actions">
                        <button class="btn btn-success" onclick="aprovar(<?php echo $p['id']; ?>)">Aprovar</button>
                        <button class="btn btn-danger" onclick="rejeitar(<?php echo $p['id']; ?>)">Rejeitar</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
    
    <script src="../assets/js/admin.js"></script>
    <script>
    function aprovar(id) {
        if (!confirm('Aprovar este prestador?')) return;
        fetch('api/moderacao.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'aprovar', id: id})
        }).then(r => r.json()).then(d => {
            if (d.success) { alert('Prestador aprovado!'); location.reload(); }
            else alert(d.error || 'Erro');
        });
    }
    function rejeitar(id) {
        const motivo = prompt('Motivo (opcional):');
        fetch('api/moderacao.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'rejeitar', id: id, motivo: motivo})
        }).then(r => r.json()).then(d => {
            if (d.success) location.reload();
            else alert(d.error || 'Erro');
        });
    }
    </script>
</body>
</html>
