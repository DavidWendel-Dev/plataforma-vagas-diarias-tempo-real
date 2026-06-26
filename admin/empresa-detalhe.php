<?php
require_once __DIR__ . '/../app.php';

$auth = new Auth();
$auth->requireType('admin');

$db = Database::getInstance();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) redirect('empresas.php');

// Buscar dados da empresa
$empresa = $db->fetch(
    "SELECT e.*, u.nome, u.email, u.foto, u.telefone, u.ativo, u.created_at
     FROM empresas e
     JOIN usuarios u ON e.usuario_id = u.id
     WHERE e.id = :id",
    ['id' => $id]
);

if (!$empresa) redirect('empresas.php');

// Buscar diárias da empresa
$diarias = $db->fetchAll(
    "SELECT d.id, d.titulo, d.funcao, d.data_evento, d.horario_inicio, d.horario_fim,
            d.valor, d.vagas_total, d.vagas_preenchidas, d.status, d.cidade, d.estado,
            d.codigo_checkin,
            (SELECT COUNT(*) FROM candidaturas c WHERE c.diaria_id = d.id AND c.status = 'checkin_realizado') as total_checkins
     FROM diarias d
     WHERE d.empresa_id = :eid
     ORDER BY d.data_evento DESC
     LIMIT 50",
    ['eid' => $id]
);

// Estatísticas
$stats = [
    'total_diarias' => count($diarias),
    'ativas' => 0,
    'concluidas' => 0,
    'canceladas' => 0,
    'total_checkins' => 0,
    'total_gasto' => 0,
];

foreach ($diarias as $d) {
    if ($d['status'] === 'ativa') $stats['ativas']++;
    elseif ($d['status'] === 'concluida') $stats['concluidas']++;
    elseif ($d['status'] === 'cancelada') $stats['canceladas']++;
    $stats['total_checkins'] += $d['total_checkins'];
    $stats['total_gasto'] += $d['valor'] * $d['total_checkins'];
}

// Prestadores únicos que já trabalharam para esta empresa
$prestadoresUnicos = $db->fetchAll(
    "SELECT DISTINCT p.id, u.nome, u.foto, u.telefone, p.nota_media,
            COUNT(c.id) as total_diarias
     FROM candidaturas c
     JOIN diarias d ON c.diaria_id = d.id
     JOIN prestadores p ON c.prestador_id = p.id
     JOIN usuarios u ON p.usuario_id = u.id
     WHERE d.empresa_id = :eid AND c.status = 'checkin_realizado'
     GROUP BY p.id
     ORDER BY total_diarias DESC
     LIMIT 20",
    ['eid' => $id]
);

$user = $auth->getUser();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empresa - <?php echo sanitize($empresa['razao_social'] ?: $empresa['nome']); ?> - <?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        body { overflow-x: hidden; }
        .empresa-banner {
            background: linear-gradient(135deg, #10B981, #059669);
            color: white;
            padding: 32px 24px;
            border-radius: 16px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .empresa-banner-avatar {
            width: 80px; height: 80px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem; font-weight: 700;
            overflow: hidden;
            flex-shrink: 0;
        }
        .empresa-banner-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .empresa-banner-info h1 { margin: 0 0 4px; font-size: 1.75rem; }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            border-left: 4px solid #E5E7EB;
        }
        .stat-card.success { border-left-color: #10B981; }
        .stat-card.warning { border-left-color: #F59E0B; }
        .stat-card.danger { border-left-color: #EF4444; }
        .stat-card.info { border-left-color: #3B82F6; }
        .stat-value { font-size: 1.75rem; font-weight: 700; color: #111827; }
        .stat-label { font-size: 0.75rem; color: #6B7280; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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
            width: 44px; height: 44px;
            background: #D1FAE5;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .info-icon svg { width: 20px; height: 20px; color: #10B981; }
        .info-label { font-size: 11px; color: #6B7280; text-transform: uppercase; letter-spacing: 0.5px; }
        .info-value { font-size: 0.9375rem; font-weight: 600; color: #111827; word-break: break-all; }
        
        .section-card { background: white; border-radius: 16px; overflow: hidden; margin-bottom: 24px; }
        .section-header { padding: 20px; border-bottom: 1px solid #E5E7EB; }
        .section-header h3 { margin: 0; }
        
        .diaria-item {
            padding: 16px 20px;
            border-bottom: 1px solid #F3F4F6;
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .diaria-item:last-child { border-bottom: none; }
        .diaria-info { flex: 1; min-width: 0; }
        .diaria-titulo { font-weight: 600; color: #111827; }
        .diaria-meta { font-size: 0.8125rem; color: #6B7280; margin-top: 2px; word-break: break-word; overflow-wrap: break-word; }
        
        .prestador-item {
            padding: 12px 20px;
            border-bottom: 1px solid #F3F4F6;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .prestador-item:last-child { border-bottom: none; }
        .prestador-avatar {
            width: 40px; height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #10B981, #059669);
            color: white;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700;
            flex-shrink: 0;
            overflow: hidden;
        }
        .prestador-avatar img { width: 100%; height: 100%; object-fit: cover; }
        
        .whatsapp-link {
            display: inline-flex;
            color: #16A34A;
            text-decoration: none;
            vertical-align: middle;
        }
        
        .empty-state { padding: 40px; text-align: center; color: #6B7280; }
        
        @media (max-width: 768px) {
            .admin-content { padding: 16px; }
            .empresa-banner { padding: 20px 16px; flex-direction: column; text-align: center; gap: 12px; }
            .empresa-banner-info h1 { font-size: 1.25rem; }
            .empresa-banner-info p { font-size: 0.8125rem; word-break: break-all; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 8px; }
            .stat-value { font-size: 1.25rem; }
            .info-grid { grid-template-columns: 1fr; gap: 10px; }
            .info-card { padding: 12px; }
            .info-value { font-size: 0.875rem; word-break: break-all; }
            .diaria-item { flex-direction: column; align-items: flex-start; padding: 12px 16px; gap: 8px; }
            .diaria-meta { font-size: 0.75rem; }
            .section-header { padding: 14px 16px; }
            .prestador-item { padding: 10px 16px; }
        }
    </style>
</head>
<body class="admin-body">
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="admin-main">
        <?php include 'includes/header.php'; ?>
        
        <div class="admin-content">
            <a href="empresas.php" class="btn btn-secondary" style="margin-bottom: 16px;">← Voltar para Empresas</a>
            
            <!-- Banner -->
            <div class="empresa-banner">
                <div class="empresa-banner-avatar">
                    <?php if ($empresa['foto']): ?>
                    <img src="../uploads/prestadores/<?php echo $empresa['foto']; ?>" alt="">
                    <?php else: ?>
                    <?php echo substr($empresa['razao_social'] ?: $empresa['nome'], 0, 1); ?>
                    <?php endif; ?>
                </div>
                <div class="empresa-banner-info">
                    <h1><?php echo sanitize($empresa['razao_social'] ?: $empresa['nome']); ?></h1>
                    <p style="opacity: 0.9; margin: 0;">✉️ <?php echo sanitize($empresa['email']); ?></p>
                    <p style="opacity: 0.9; margin: 4px 0 0;">
                        Status: <strong><?php echo $empresa['ativo'] ? 'Ativo' : 'Suspenso'; ?></strong> •
                        Cadastro: <?php echo formatDate($empresa['created_at'], 'd/m/Y'); ?>
                    </p>
                </div>
            </div>
            
            <!-- Estatísticas -->
            <div class="stats-grid">
                <div class="stat-card success">
                    <div class="stat-value"><?php echo $stats['total_diarias']; ?></div>
                    <div class="stat-label">Total Diárias</div>
                </div>
                <div class="stat-card info">
                    <div class="stat-value"><?php echo $stats['ativas']; ?></div>
                    <div class="stat-label">Ativas</div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-value"><?php echo $stats['total_checkins']; ?></div>
                    <div class="stat-label">Check-ins</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($prestadoresUnicos); ?></div>
                    <div class="stat-label">Prestadores Únicos</div>
                </div>
                <div class="stat-card danger">
                    <div class="stat-value"><?php echo $stats['canceladas']; ?></div>
                    <div class="stat-label">Canceladas</div>
                </div>
                <div class="stat-card success">
                    <div class="stat-value" style="font-size: 1.25rem;"><?php echo formatMoney($stats['total_gasto']); ?></div>
                    <div class="stat-label">Total Investido</div>
                </div>
            </div>
            
            <!-- Dados da empresa -->
            <div class="info-grid">
                <div class="info-card">
                    <div class="info-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.36 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.34 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    </div>
                    <div>
                        <div class="info-label">WhatsApp</div>
                        <div class="info-value">
                            <?php echo sanitize($empresa['telefone'] ?: '-'); ?>
                            <?php if (!empty($empresa['telefone'])):
                                $whats = preg_replace('/\D+/', '', $empresa['telefone']);
                                if (strlen($whats) === 11) $whats = '55' . $whats;
                            ?>
                            <a href="https://wa.me/<?php echo $whats; ?>" target="_blank" class="whatsapp-link" title="Falar no WhatsApp">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.980.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v3M12 14v3M16 14v3"/></svg>
                    </div>
                    <div>
                        <div class="info-label">Razão Social</div>
                        <div class="info-value"><?php echo sanitize($empresa['razao_social'] ?: '-'); ?></div>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/></svg>
                    </div>
                    <div>
                        <div class="info-label">CNPJ</div>
                        <div class="info-value"><?php echo sanitize($empresa['cnpj'] ?: '-'); ?></div>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </div>
                    <div>
                        <div class="info-label">Pessoa de Contato</div>
                        <div class="info-value"><?php echo sanitize($empresa['contato_nome'] ?: '-'); ?></div>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    </div>
                    <div>
                        <div class="info-label">Email</div>
                        <div class="info-value"><?php echo sanitize($empresa['email']); ?></div>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div>
                        <div class="info-label">Cadastrada em</div>
                        <div class="info-value"><?php echo formatDate($empresa['created_at'], 'd/m/Y H:i'); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Diárias criadas -->
            <div class="section-card">
                <div class="section-header">
                    <h3>📋 Diárias Criadas (<?php echo count($diarias); ?>)</h3>
                </div>
                
                <?php if (empty($diarias)): ?>
                <div class="empty-state">
                    <p>Nenhuma diária criada ainda</p>
                </div>
                <?php else: ?>
                    <?php foreach ($diarias as $d): ?>
                    <div class="diaria-item">
                        <div class="diaria-info">
                            <div class="diaria-titulo">
                                <a href="diaria-detalhe.php?id=<?php echo $d['id']; ?>" style="color: inherit; text-decoration: none;">
                                    <?php echo sanitize($d['titulo']); ?>
                                </a>
                            </div>
                            <div class="diaria-meta">
                                📅 <?php echo formatDate($d['data_evento'], 'd/m/Y'); ?> às <?php echo formatTime($d['horario_inicio']); ?> •
                                💵 <?php echo formatMoney($d['valor']); ?> •
                                👥 <?php echo $d['total_checkins']; ?>/<?php echo $d['vagas_preenchidas']; ?>/<?php echo $d['vagas_total']; ?> (check-ins/inscritos/total) •
                                🎯 <?php echo sanitize($d['funcao']); ?> •
                                📍 <?php echo sanitize($d['cidade'] ?? '-'); ?>/<?php echo sanitize($d['estado'] ?? '-'); ?> •
                                🔑 <?php echo sanitize($d['codigo_checkin'] ?? '-'); ?>
                            </div>
                        </div>
                        <span class="badge badge-<?php echo $d['status'] === 'ativa' ? 'success' : ($d['status'] === 'cancelada' ? 'danger' : 'secondary'); ?>">
                            <?php echo ucfirst($d['status']); ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Prestadores que já trabalharam -->
            <div class="section-card">
                <div class="section-header">
                    <h3>👥 Prestadores que já Trabalharam (<?php echo count($prestadoresUnicos); ?>)</h3>
                </div>
                
                <?php if (empty($prestadoresUnicos)): ?>
                <div class="empty-state">
                    <p>Nenhum prestador trabalhou para esta empresa ainda</p>
                </div>
                <?php else: ?>
                    <?php foreach ($prestadoresUnicos as $p): ?>
                    <div class="prestador-item">
                        <div class="prestador-avatar">
                            <?php if ($p['foto']): ?>
                            <img src="../uploads/prestadores/<?php echo $p['foto']; ?>" alt="">
                            <?php else: ?>
                            <?php echo substr($p['nome'], 0, 1); ?>
                            <?php endif; ?>
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <a href="prestador-detalhe.php?id=<?php echo $p['id']; ?>" style="color: #111827; text-decoration: none; font-weight: 600;">
                                <?php echo sanitize($p['nome']); ?>
                            </a>
                            <div style="font-size: 0.8125rem; color: #6B7280;">
                                ⭐ <?php echo number_format($p['nota_media'], 1, ',', '.'); ?> •
                                <?php echo $p['total_diarias']; ?> diária(s) nesta empresa •
                                📞 <?php echo sanitize($p['telefone'] ?: '-'); ?>
                                <?php if (!empty($p['telefone'])):
                                    $whats = preg_replace('/\D+/', '', $p['telefone']);
                                    if (strlen($whats) === 11) $whats = '55' . $whats;
                                ?>
                                <a href="https://wa.me/<?php echo $whats; ?>" target="_blank" class="whatsapp-link" title="WhatsApp">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.980.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <script src="../assets/js/admin.js"></script>
</body>
</html>
