<?php
require_once __DIR__ . '/app.php';

$db = Database::getInstance();

// Filtros
$q = sanitize($_GET['q'] ?? '');
$funcao = sanitize($_GET['funcao'] ?? '');
$cidade = sanitize($_GET['cidade'] ?? '');

// Query base
$sql = "SELECT d.*, e.razao_social, u.nome as empresa_nome,
        (d.vagas_total - d.vagas_preenchidas) as vagas_restantes
        FROM diarias d
        JOIN empresas e ON d.empresa_id = e.id
        JOIN usuarios u ON e.usuario_id = u.id
        WHERE d.status = 'ativa' AND d.data_evento >= CURDATE()";

$params = [];

if ($q) {
    $sql .= " AND (d.titulo LIKE :q OR d.funcao LIKE :q OR d.cidade LIKE :q OR e.razao_social LIKE :q)";
    $params['q'] = "%{$q}%";
}

if ($funcao) {
    $sql .= " AND d.funcao = :funcao";
    $params['funcao'] = $funcao;
}

if ($cidade) {
    $sql .= " AND d.cidade LIKE :cidade";
    $params['cidade'] = "%{$cidade}%";
}

$sql .= " HAVING vagas_restantes > 0 ORDER BY d.data_evento ASC, d.created_at DESC";

$diarias = $db->fetchAll($sql, $params);

// Buscar funções para filtro
$funcoes = $db->fetchAll(
    "SELECT DISTINCT funcao FROM diarias WHERE status = 'ativa' AND data_evento >= CURDATE() ORDER BY funcao"
);

// Buscar cidades para filtro
$cidades = $db->fetchAll(
    "SELECT DISTINCT cidade FROM diarias WHERE status = 'ativa' AND data_evento >= CURDATE() AND cidade IS NOT NULL AND cidade != '' ORDER BY cidade"
);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar Diárias - <?php echo APP_NAME; ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/modern.css">
    <style>
        .search-page { padding-top: 100px; min-height: 100vh; background: var(--bg-secondary); }
        .search-header { background: white; padding: 24px 0; border-bottom: 1px solid var(--border-color); margin-bottom: 32px; }
        .search-title { font-size: 1.5rem; font-weight: 700; margin-bottom: 16px; }
        
        .filters-bar { display: flex; gap: 12px; flex-wrap: wrap; }
        .filter-select { padding: 10px 36px 10px 14px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px; background: white; appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%236B7280' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10l-5 5z'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; }
        
        .results-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .results-count { color: var(--gray-600); font-size: 14px; }
        
        .diarias-list { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
        
        @media (max-width: 1024px) { .diarias-list { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 768px) { .diarias-list { grid-template-columns: 1fr; } .filters-bar { flex-direction: column; } }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container navbar-content">
            <a href="<?php echo url(); ?>" class="logo">
                <div class="logo-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                </div>
                <span><?php echo APP_NAME; ?></span>
            </a>
            
            <div class="nav-links">
                <a href="<?php echo url('buscar.php'); ?>" class="nav-link" style="color: var(--primary-600);">Buscar</a>
                <a href="<?php echo url('#como-funciona'); ?>" class="nav-link">Como Funciona</a>
                <a href="<?php echo url('#para-empresas'); ?>" class="nav-link">Para Empresas</a>
            </div>
            
            <div class="nav-actions">
                <a href="<?php echo url('login.php'); ?>" class="btn btn-ghost">Entrar</a>
                <a href="<?php echo url('login.php'); ?>" class="btn btn-primary">Cadastrar</a>
            </div>
            
            <button class="mobile-menu-btn" aria-label="Menu">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <line x1="3" y1="12" x2="21" y2="12"/>
                    <line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
            </button>
        </div>
    </nav>

    <div class="search-page">
        <!-- Search Header -->
        <div class="search-header">
            <div class="container">
                <h1 class="search-title">Buscar Diárias</h1>
                
                <form method="GET" class="filters-bar">
                    <input type="text" name="q" placeholder="Buscar..." value="<?php echo sanitize($q); ?>" 
                           style="padding: 10px 14px; border: 1px solid var(--border-color); border-radius: 8px; width: 300px;">
                    
                    <select name="funcao" class="filter-select">
                        <option value="">Todas as funções</option>
                        <?php foreach ($funcoes as $f): ?>
                        <option value="<?php echo sanitize($f['funcao']); ?>" <?php echo $funcao === $f['funcao'] ? 'selected' : ''; ?>>
                            <?php echo sanitize($f['funcao']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="cidade" class="filter-select">
                        <option value="">Todas as cidades</option>
                        <?php foreach ($cidades as $c): ?>
                        <option value="<?php echo sanitize($c['cidade']); ?>" <?php echo $cidade === $c['cidade'] ? 'selected' : ''; ?>>
                            <?php echo sanitize($c['cidade']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="<?php echo url('buscar.php'); ?>" class="btn btn-ghost">Limpar</a>
                </form>
            </div>
        </div>
        
        <div class="container">
            <div class="results-header">
                <span class="results-count"><?php echo count($diarias); ?> diária(s) encontrada(s)</span>
            </div>
            
            <?php if (empty($diarias)): ?>
            <div class="empty-state">
                <div class="empty-icon">🔍</div>
                <h3>Nenhuma diária encontrada</h3>
                <p>Tente ajustar os filtros ouCadastre-se para receber alertas de novas oportunidades!</p>
                <a href="<?php echo url('login.php'); ?>" class="btn btn-primary btn-lg">Criar conta</a>
            </div>
            <?php else: ?>
            <div class="diarias-list">
                <?php foreach ($diarias as $diaria): ?>
                <article class="diaria-card">
                    <div class="diaria-card-header">
                        <div class="diaria-empresa">
                            <div class="empresa-avatar">
                                <?php echo substr($diaria['razao_social'] ?: 'E', 0, 1); ?>
                            </div>
                            <span class="empresa-nome"><?php echo sanitize($diaria['razao_social'] ?: 'Empresa'); ?></span>
                        </div>
                        <div class="diaria-badge <?php echo $diaria['forma_pagamento'] === 'na_hora' ? 'badge-success' : 'badge-default'; ?>">
                            <?php echo $diaria['forma_pagamento'] === 'na_hora' ? '⚡ Na hora' : 'Posterior'; ?>
                        </div>
                    </div>
                    
                    <div class="diaria-card-body">
                        <h3 class="diaria-titulo"><?php echo sanitize($diaria['titulo']); ?></h3>
                        
                        <div class="diaria-meta">
                            <div class="meta-item">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2"/>
                                    <line x1="16" y1="2" x2="16" y2="6"/>
                                    <line x1="8" y1="2" x2="8" y2="6"/>
                                </svg>
                                <span><?php echo formatDate($diaria['data_evento']); ?></span>
                            </div>
                            <div class="meta-item">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <polyline points="12 6 12 12 16 14"/>
                                </svg>
                                <span><?php echo formatTime($diaria['horario_inicio']); ?> - <?php echo formatTime($diaria['horario_fim']); ?></span>
                            </div>
                            <div class="meta-item">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                    <circle cx="12" cy="10" r="3"/>
                                </svg>
                                <span><?php echo sanitize($diaria['cidade'] ?: 'Local não informado'); ?></span>
                            </div>
                        </div>
                        
                        <div class="diaria-footer">
                            <div class="diaria-valor">
                                <span class="valor"><?php echo formatMoney($diaria['valor']); ?></span>
                                <span class="periodo">por diária</span>
                            </div>
                            <div class="diaria-vagas">
                                <span class="vagas-count"><?php echo $diaria['vagas_restantes']; ?></span>
                                <span class="vagas-label">vaga(s)</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="diaria-card-action">
                        <div class="funcao-badge"><?php echo sanitize($diaria['funcao']); ?></div>
                        <a href="<?php echo url('diaria.php?id=' . $diaria['id']); ?>" class="btn btn-primary btn-block">
                            Ver Detalhes e Candidatar
                        </a>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer Simplificado -->
    <footer class="footer">
        <div class="container">
            <div class="footer-bottom" style="border-top: none; padding-top: 0;">
                <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    <!-- Som de notificação -->
    <audio id="somNotificacao" preload="auto">
        <source src="assets/som/som.mp3" type="audio/mpeg">
    </audio>
    <script src="assets/js/notificacao-popup.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
