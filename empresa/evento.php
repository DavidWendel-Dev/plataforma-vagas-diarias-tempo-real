<?php
require_once __DIR__ . '/../app.php';

$auth = new Auth();
$auth->requireType('empresa');

$db = Database::getInstance();

$empresa = $db->fetch("SELECT e.* FROM empresas e WHERE e.usuario_id = :uid", ['uid' => userId()]);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect('eventos.php');
}

// Buscar a diária
$diaria = $db->fetch(
    "SELECT d.*, e.razao_social, u.nome as empresa_nome, u.telefone as empresa_telefone,
            (d.vagas_total - d.vagas_preenchidas) as vagas_restantes
     FROM diarias d
     JOIN empresas e ON d.empresa_id = e.id
     JOIN usuarios u ON e.usuario_id = u.id
     WHERE d.id = :id AND d.empresa_id = :eid",
    ['id' => $id, 'eid' => $empresa['id']]
);

if (!$diaria) {
    redirect('eventos.php');
}

// Buscar candidatos
$candidatos = $db->fetchAll(
    "SELECT c.id as candidatura_id, c.status, c.data_candidatura, c.data_checkin,
            p.id as prestador_id, u.nome, u.foto, u.telefone, p.nota_media, p.total_diarias
     FROM candidaturas c
     JOIN prestadores p ON c.prestador_id = p.id
     JOIN usuarios u ON p.usuario_id = u.id
     WHERE c.diaria_id = :did
     ORDER BY c.data_candidatura DESC",
    ['did' => $id]
);

$user = $auth->getUser();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evento - <?php echo sanitize($diaria['titulo']); ?> - <?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .admin-sidebar { background: linear-gradient(180deg, #10B981, #059669); }
        .nav-link { color: #fff; }
        .nav-link:hover { background: rgba(255,255,255,0.15); }
        .nav-link.active { background: rgba(255,255,255,0.2); }
        
        .evento-banner {
            background: linear-gradient(135deg, #10B981, #059669);
            color: white;
            padding: 32px 24px;
            border-radius: 16px;
            margin-bottom: 24px;
        }
        
        .evento-titulo { font-size: 1.75rem; font-weight: 700; margin-bottom: 8px; }
        
        .evento-badges { display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap; }
        .badge-evento { padding: 6px 14px; background: rgba(255,255,255,0.2); border-radius: 20px; font-size: 0.8125rem; font-weight: 600; }
        
        .codigo-card {
            background: white;
            border: 2px dashed #10B981;
            border-radius: 12px;
            padding: 20px;
            margin: 16px 0;
            text-align: center;
        }
        
        .codigo-label {
            font-size: 0.75rem;
            color: #6B7280;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }
        
        .codigo-value {
            font-family: 'Courier New', monospace;
            font-size: 2rem;
            font-weight: 700;
            color: #10B981;
            letter-spacing: 6px;
        }
        
        .codigo-info {
            margin-top: 12px;
            font-size: 0.8125rem;
            color: #6B7280;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .info-card {
            background: white;
            border-radius: 12px;
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .info-icon {
            width: 44px;
            height: 44px;
            background: #D1FAE5;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .info-icon svg { width: 20px; height: 20px; color: #10B981; }
        
        .info-label { font-size: 11px; color: #6B7280; text-transform: uppercase; letter-spacing: 0.5px; }
        .info-value { font-size: 0.9375rem; font-weight: 600; color: #111827; }
        
        .candidatos-list {
            background: white;
            border-radius: 16px;
            overflow: hidden;
        }
        
        .candidatos-header {
            padding: 20px;
            border-bottom: 1px solid #E5E7EB;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .candidato-item {
            padding: 16px 20px;
            border-bottom: 1px solid #F3F4F6;
            display: flex;
            align-items: center;
            gap: 14px;
        }
        
        .candidato-item:last-child { border-bottom: none; }
        
        .candidato-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, #10B981, #059669);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            flex-shrink: 0;
            overflow: hidden;
        }
        
        .candidato-avatar img { width: 100%; height: 100%; object-fit: cover; }
        
        .candidato-info { flex: 1; }
        .candidato-nome { font-weight: 600; color: #111827; }
        .candidato-meta { font-size: 0.8125rem; color: #6B7280; margin-top: 2px; }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-confirmada { background: #D1FAE5; color: #065F46; }
        .status-checkin_realizado { background: #DBEAFE; color: #1E40AF; }
        .status-cancelada { background: #FEE2E2; color: #991B1B; }
        .status-faltou { background: #FEF3C7; color: #92400E; }
        
        .acoes-row { display: flex; gap: 8px; }
        
        .btn-sm {
            padding: 6px 12px;
            border: none;
            border-radius: 8px;
            font-size: 0.8125rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .btn-sm-success { background: #10B981; color: white; }
        .btn-sm-danger { background: #EF4444; color: white; }
        .btn-sm-secondary { background: #F3F4F6; color: #374151; border: 1px solid #E5E7EB; }
        
        .empty-state { padding: 40px; text-align: center; color: #6B7280; }
    </style>
</head>
<body class="admin-body">
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="admin-main">
        <?php include 'includes/header.php'; ?>
        
        <div class="admin-content">
            <a href="eventos.php" class="btn btn-secondary" style="margin-bottom: 16px;">← Voltar para Eventos</a>
            
            <!-- Banner do evento -->
            <div class="evento-banner">
                <div class="evento-badges">
                    <span class="badge-evento"><?php echo sanitize($diaria['funcao']); ?></span>
                    <span class="badge-evento">📅 <?php echo formatDate($diaria['data_evento'], 'd/m/Y'); ?></span>
                    <span class="badge-evento">🕐 <?php echo formatTime($diaria['horario_inicio']); ?> - <?php echo formatTime($diaria['horario_fim']); ?></span>
                    <span class="badge-evento badge-<?php echo $diaria['status'] === 'ativa' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($diaria['status']); ?></span>
                </div>
                <h1 class="evento-titulo"><?php echo sanitize($diaria['titulo']); ?></h1>
                <p style="opacity: 0.9; margin-top: 4px;">📍 <?php echo sanitize($diaria['endereco']); ?> - <?php echo sanitize($diaria['cidade'] ?? ''); ?>/<?php echo sanitize($diaria['estado'] ?? ''); ?></p>
            </div>
            
            <!-- Código de Check-in -->
            <div class="codigo-card">
                <div class="codigo-label">Código de Check-in</div>
                <div class="codigo-value"><?php echo $diaria['codigo_checkin'] ? sanitize($diaria['codigo_checkin']) : '-'; ?></div>
                <div class="codigo-info">
                    Compartilhe este código com os prestadores no local do evento para confirmar presença.
                </div>
                <?php if ($diaria['codigo_checkin']): ?>
                <button onclick="copiarCodigo('<?php echo $diaria['codigo_checkin']; ?>')" class="btn-sm btn-sm-secondary" style="margin-top: 12px;">
                    📋 Copiar código
                </button>
                <?php endif; ?>
            </div>
            
            <!-- Info Grid -->
            <div class="info-grid">
                <div class="info-card">
                    <div class="info-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/></svg>
                    </div>
                    <div>
                        <div class="info-label">Data do Evento</div>
                        <div class="info-value"><?php echo formatDate($diaria['data_evento'], 'd/m/Y'); ?></div>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div>
                        <div class="info-label">Horário</div>
                        <div class="info-value"><?php echo formatTime($diaria['horario_inicio']); ?> - <?php echo formatTime($diaria['horario_fim']); ?></div>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </div>
                    <div>
                        <div class="info-label">Valor por Diária</div>
                        <div class="info-value"><?php echo formatMoney($diaria['valor']); ?></div>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                    </div>
                    <div>
                        <div class="info-label">Vagas</div>
                        <div class="info-value"><?php echo $diaria['vagas_preenchidas']; ?> / <?php echo $diaria['vagas_total']; ?> preenchidas</div>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    </div>
                    <div>
                        <div class="info-label">Local</div>
                        <div class="info-value"><?php echo sanitize($diaria['cidade'] ?? '-'); ?>/<?php echo sanitize($diaria['estado'] ?? '-'); ?></div>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.36 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.34 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    </div>
                    <div>
                        <div class="info-label">Contato</div>
                        <div class="info-value"><?php echo sanitize($diaria['empresa_telefone'] ?? '-'); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Descrição -->
            <?php if (!empty($diaria['descricao'])): ?>
            <div class="card" style="margin-bottom: 24px;">
                <div class="card-header"><h3>Descrição</h3></div>
                <div class="card-body">
                    <p style="color: #4B5563; line-height: 1.6;"><?php echo nl2br(sanitize($diaria['descricao'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($diaria['observacoes'])): ?>
            <div class="card" style="margin-bottom: 24px;">
                <div class="card-header"><h3>Observações</h3></div>
                <div class="card-body">
                    <p style="color: #4B5563; line-height: 1.6;"><?php echo nl2br(sanitize($diaria['observacoes'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Candidatos/Prestadores -->
            <div class="candidatos-list">
                <div class="candidatos-header">
                    <h3 style="margin: 0;">Prestadores Inscritos (<?php echo count($candidatos); ?>)</h3>
                    <span class="text-muted"><?php echo $diaria['vagas_preenchidas']; ?> / <?php echo $diaria['vagas_total']; ?> vagas</span>
                </div>
                
                <?php if (empty($candidatos)): ?>
                <div class="empty-state">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#D1D5DB" stroke-width="2" style="margin: 0 auto 12px;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                    <p>Nenhum prestador inscrito ainda</p>
                </div>
                <?php else: ?>
                    <?php foreach ($candidatos as $c): ?>
                    <div class="candidato-item">
                        <div class="candidato-avatar">
                            <?php if ($c['foto']): ?>
                            <img src="../uploads/prestadores/<?php echo $c['foto']; ?>" alt="">
                            <?php else: ?>
                            <?php echo substr($c['nome'], 0, 1); ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="candidato-info">
                            <div class="candidato-nome"><?php echo sanitize($c['nome']); ?></div>
                            <div class="candidato-meta">
                                ⭐ <?php echo number_format($c['nota_media'], 1, ',', '.'); ?> • 
                                <?php echo $c['total_diarias']; ?> diária(s) •
                                Inscrito em <?php echo formatDate($c['data_candidatura'], 'd/m/Y H:i'); ?>
                                <?php if ($c['telefone']): ?>
                                • 📞 <a href="tel:<?php echo $c['telefone']; ?>" style="color: #10B981;"><?php echo sanitize($c['telefone']); ?></a>
                                <?php
                                    // Remove tudo que não for dígito e adiciona 55 (DDI Brasil)
                                    $whats = preg_replace('/\D+/', '', $c['telefone']);
                                    if (strlen($whats) === 11) $whats = '55' . $whats;
                                ?>
                                <a href="https://wa.me/<?php echo $whats; ?>" target="_blank" title="Falar no WhatsApp" style="color:#16A34A;text-decoration:none;display:inline-flex;vertical-align:middle;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.980.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                    </svg>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <span class="status-badge status-<?php echo $c['status']; ?>">
                            <?php
                            $statusLabel = [
                                'confirmada' => 'Confirmada',
                                'checkin_realizado' => '✓ Check-in',
                                'cancelada' => 'Cancelada',
                                'faltou' => 'Faltou'
                            ];
                            echo $statusLabel[$c['status']] ?? $c['status'];
                            ?>
                        </span>
                        
                        <div class="acoes-row">
                            <?php if ($c['status'] === 'confirmada' && $diaria['status'] === 'ativa'): ?>
                            <button class="btn-sm btn-sm-success" onclick="marcarCheckin(<?php echo $c['candidatura_id']; ?>)" title="Marcar presença">
                                ✓ Presente
                            </button>
                            <button class="btn-sm btn-sm-danger" onclick="marcarFalta(<?php echo $c['candidatura_id']; ?>)" title="Marcar falta">
                                ✗ Falta
                            </button>
                            <?php endif; ?>
                            
                            <?php if ($c['status'] === 'checkin_realizado'): ?>
                            <button class="btn-sm btn-sm-secondary" onclick="reverterCheckin(<?php echo $c['candidatura_id']; ?>)" title="Reverter check-in">
                                ↺ Reverter
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <?php if ($diaria['vagas_restantes'] > 0 && $diaria['status'] === 'ativa'): ?>
            <div style="text-align: center; padding: 16px; color: #6B7280; margin-top: 16px;">
                <small>Ainda há <?php echo $diaria['vagas_restantes']; ?> vaga(s) disponível(is). Novos prestadores podem se inscrever no app.</small>
            </div>
            <?php endif; ?>
        </div>
    </main>
    
    <script>
    function copiarCodigo(codigo) {
        navigator.clipboard.writeText(codigo).then(() => {
            alert('Código copiado: ' + codigo);
        }).catch(() => {
            prompt('Copie o código:', codigo);
        });
    }
    
    async function marcarCheckin(id) {
        if (!confirm('Confirmar presença deste prestador?')) return;
        
        const formData = new FormData();
        formData.append('action', 'checkin_empresa');
        formData.append('candidatura_id', id);
        formData.append('status', 'checkin_realizado');
        
        const res = await fetch('../api/candidaturas.php', { method: 'POST', body: formData });
        const d = await res.json();
        if (d.success) location.reload();
        else alert(d.error || 'Erro');
    }
    
    async function marcarFalta(id) {
        if (!confirm('Marcar falta para este prestador?')) return;
        
        const formData = new FormData();
        formData.append('action', 'checkin_empresa');
        formData.append('candidatura_id', id);
        formData.append('status', 'faltou');
        
        const res = await fetch('../api/candidaturas.php', { method: 'POST', body: formData });
        const d = await res.json();
        if (d.success) location.reload();
        else alert(d.error || 'Erro');
    }
    
    async function reverterCheckin(id) {
        if (!confirm('Reverter o check-in deste prestador?')) return;
        
        const formData = new FormData();
        formData.append('action', 'checkin_empresa');
        formData.append('candidatura_id', id);
        formData.append('status', 'confirmada');
        
        const res = await fetch('../api/candidaturas.php', { method: 'POST', body: formData });
        const d = await res.json();
        if (d.success) location.reload();
        else alert(d.error || 'Erro');
    }
    </script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>
