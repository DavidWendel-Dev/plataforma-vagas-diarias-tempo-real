<?php
require_once __DIR__ . '/../app.php';

$auth = new Auth();
$auth->requireType('admin');

$db = Database::getInstance();

// Filtros
$status = $_GET['status'] ?? '';
$empresa = (int)($_GET['empresa'] ?? 0);

$where = '1=1';
$params = [];

if ($status) {
    $where .= ' AND d.status = :status';
    $params['status'] = $status;
}

if ($empresa) {
    $where .= ' AND d.empresa_id = :empresa';
    $params['empresa'] = $empresa;
}

$diarias = $db->fetchAll(
    "SELECT d.*, e.razao_social, u.nome as empresa_nome,
            (d.vagas_total - d.vagas_preenchidas) as vagas_restantes
     FROM diarias d
     JOIN empresas e ON d.empresa_id = e.id
     JOIN usuarios u ON e.usuario_id = u.id
     WHERE {$where}
     ORDER BY d.data_evento DESC, d.created_at DESC",
    $params
);

$empresas = $db->fetchAll("SELECT e.id, e.razao_social FROM empresas e ORDER BY e.razao_social");

$user = $auth->getUser();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diárias - <?php echo APP_NAME; ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../assets/css/style.css?v=3">
    <link rel="stylesheet" href="../assets/css/admin.css?v=3">
</head>
<body class="admin-body">
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="admin-main">
        <?php include 'includes/header.php'; ?>
        
        <div class="admin-content">
            <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <h1 style="margin: 0;">Diárias</h1>
                <a href="diaria-nova.php" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 8px;">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>
                    </svg>
                    Nova Diária
                </a>
            </div>

            <div class="filters" style="display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap;">
                <form method="GET" style="display: flex; gap: 12px; flex-wrap: wrap;">
                    <select name="status" class="form-control" style="width: auto;">
                        <option value="">Todos os status</option>
                        <option value="ativa" <?php echo $status === 'ativa' ? 'selected' : ''; ?>>Ativa</option>
                        <option value="finalizada" <?php echo $status === 'finalizada' ? 'selected' : ''; ?>>Finalizada</option>
                        <option value="cancelada" <?php echo $status === 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                    </select>
                    
                    <select name="empresa" class="form-control" style="width: auto;">
                        <option value="">Todas as empresas</option>
                        <?php foreach ($empresas as $e): ?>
                        <option value="<?php echo $e['id']; ?>" <?php echo $empresa == $e['id'] ? 'selected' : ''; ?>>
                            <?php echo sanitize($e['razao_social']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <button type="submit" class="btn btn-secondary">Filtrar</button>
                    <a href="diarias.php" class="btn btn-ghost">Limpar</a>
                </form>
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th>Empresa</th>
                                <th>Função</th>
                                <th>Data</th>
                                <th>Horário</th>
                                <th>Valor</th>
                                <th>Vagas</th>
                                <th>Código Check-in</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($diarias)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-8 text-muted">
                                    Nenhuma diária encontrada
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($diarias as $d): ?>
                            <tr>
                                <td data-label="Título">
                                    <a href="diaria-detalhe.php?id=<?php echo $d['id']; ?>"><strong><?php echo sanitize($d['titulo']); ?></strong></a>
                                </td>
                                <td data-label="Empresa"><?php echo sanitize($d['razao_social'] ?: $d['empresa_nome']); ?></td>
                                <td data-label="Função"><?php echo sanitize($d['funcao']); ?></td>
                                <td data-label="Data"><?php echo formatDate($d['data_evento']); ?></td>
                                <td data-label="Horário"><?php echo formatTime($d['horario_inicio']); ?> - <?php echo formatTime($d['horario_fim']); ?></td>
                                <td data-label="Valor"><strong><?php echo formatMoney($d['valor']); ?></strong></td>
                                <td data-label="Vagas">
                                    <span class="<?php echo $d['vagas_restantes'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo $d['vagas_preenchidas']; ?>/<?php echo $d['vagas_total']; ?>
                                    </span>
                                </td>
                                <td data-label="Check-in">
                                    <?php if (!empty($d['codigo_checkin'])): ?>
                                    <code style="background:#FEF3C7;color:#92400E;padding:4px 10px;border-radius:6px;font-weight:700;font-size:0.875rem;letter-spacing:2px"><?php echo sanitize($d['codigo_checkin']); ?></code>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Status">
                                    <span class="badge badge-<?php echo $d['status'] === 'ativa' ? 'success' : ($d['status'] === 'cancelada' ? 'danger' : 'secondary'); ?>">
                                        <?php echo ucfirst($d['status']); ?>
                                    </span>
                                </td>
                                <td data-label="Ações">
                                    <div style="display: flex; gap: 8px; align-items: center;">
                                        <a href="diaria-detalhe.php?id=<?php echo $d['id']; ?>" class="btn-icon-sm" title="Ver Detalhes" style="background:#EEF2FF;color:#4F46E5;padding:6px;border-radius:8px;display:inline-flex;text-decoration:none;">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                <circle cx="12" cy="12" r="3"/>
                                            </svg>
                                        </a>
                                        <a href="diaria-editar.php?id=<?php echo $d['id']; ?>" class="btn-icon-sm" title="Editar" style="background:#FEF3C7;color:#92400E;padding:6px;border-radius:8px;display:inline-flex;text-decoration:none;">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                            </svg>
                                        </a>
                                        <a href="https://wa.me/?text=<?php echo urlencode('Vaga disponível: ' . $d['titulo'] . ' | ' . $d['funcao'] . ' | ' . formatDate($d['data_evento']) . ' às ' . formatTime($d['horario_inicio']) . ' | ' . formatMoney($d['valor']) . ' | Local: ' . $d['cidade'] . '/' . $d['estado']); ?>" target="_blank" class="btn-icon-sm" title="Compartilhar no WhatsApp" style="background:#DCFCE7;color:#16A34A;padding:6px;border-radius:8px;display:inline-flex;text-decoration:none;">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.980.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                            </svg>
                                        </a>
                                        <?php if ($d['status'] === 'ativa'): ?>
                                        <button class="btn-icon-sm" title="Cancelar Diária" onclick="cancelar(<?php echo $d['id']; ?>)" style="background:#FEE2E2;color:#991B1B;padding:6px;border-radius:8px;border:none;cursor:pointer;display:inline-flex;">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                <circle cx="12" cy="12" r="10"/>
                                                <line x1="15" y1="9" x2="9" y2="15"/>
                                                <line x1="9" y1="9" x2="15" y2="15"/>
                                            </svg>
                                        </button>
                                        <?php endif; ?>
                                    </div>
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
    
    <script src="../assets/js/admin.js"></script>
    <script>
    function cancelar(id) {
        if (!confirm('Tem certeza que deseja EXCLUIR esta diária? Esta ação não pode ser desfeita.')) return;
        fetch('api/diarias.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'excluir', id: id})
        }).then(r => r.json()).then(d => {
            if (d.success) location.reload();
            else alert(d.error || 'Erro ao excluir');
        });
    }
    </script>
</body>
</html>
