<?php
require_once __DIR__ . '/app.php';

// Se já estiver logado, redirecionar para o painel correto
$auth = new Auth();
if ($auth->isLoggedIn()) {
    $user = $auth->getUser();
    $redirect = match($user['tipo']) {
        'admin' => APP_URL . '/admin/dashboard.php',
        'empresa' => APP_URL . '/empresa/dashboard.php',
        'prestador' => APP_URL . '/app/index.php',
        default => APP_URL
    };
    redirect($redirect);
}

$db = Database::getInstance();
$stats = getStats();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Plataforma completa para gestão de eventos. Conectamos empresas a profissionais qualificados - garçons, seguranças, recepcionistas e muito mais. Publique vagas, receba candidaturas e gerencie seus eventos em um só lugar.">
    <meta name="theme-color" content="#6366F1">
    
    <title><?php echo APP_NAME; ?> - Plataforma de Gestão de Eventos e Profissionais</title>
    
    <!-- PWA -->
    <link rel="manifest" href="manifest.json?v=5">
    <link rel="icon" type="image/svg+xml" href="assets/icons/icon.svg?v=5">
    <link rel="icon" type="image/png" sizes="192x192" href="assets/icons/icon-192.png?v=5">
    <link rel="icon" type="image/png" sizes="96x96" href="assets/icons/icon-96.png?v=5">
    <link rel="apple-touch-icon" href="assets/icons/icon-192.png?v=5">
    <link rel="apple-touch-icon" sizes="152x152" href="assets/icons/icon-152.png?v=5">
    <link rel="apple-touch-icon" sizes="192x192" href="assets/icons/icon-192.png?v=5">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="RapidJobs">
    <meta name="mobile-web-app-capable" content="yes">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/modern.css?v=5">
    <style>
        * { -webkit-user-select: none !important; -moz-user-select: none !important; -ms-user-select: none !important; user-select: none !important; -webkit-touch-callout: none !important; -webkit-tap-highlight-color: transparent !important; }
        input, textarea { -webkit-user-select: text !important; -moz-user-select: text !important; user-select: text !important; }
        html { overscroll-behavior-y: none !important; }
        body { overscroll-behavior: none !important; overscroll-behavior-y: none !important; touch-action: pan-y !important; }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container navbar-content">
            <a href="<?php echo url(); ?>" class="logo">
                <div class="logo-icon">
                    <img src="<?php echo url('assets/icons/icone.png?v=5'); ?>" alt="RapidJobs" style="width:100%;height:100%;object-fit:contain;">
                </div>
                <span><?php echo APP_NAME; ?></span>
            </a>
            
            <div class="nav-links">
                <a href="#solucoes" class="nav-link">Soluções</a>
                <a href="#como-funciona" class="nav-link">Como Funciona</a>
                <a href="#para-empresas" class="nav-link">Para Empresas</a>
                <a href="#para-profissionais" class="nav-link">Para Profissionais</a>
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

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-bg">
            <div class="hero-gradient"></div>
            <div class="hero-pattern"></div>
        </div>
        
        <div class="container hero-content">
            <div class="hero-text">
                <span class="hero-badge">🏆 Eventos sob medida, do início ao fim</span>
                <h1>Organizamos seu evento completo com <span class="gradient-text">profissionais qualificados</span></h1>
                <p class="hero-subtitle">
                    A <?php echo APP_NAME; ?> cuida de tudo: planejamento, recrutamento de
                    garçons, seguranças, recepcionistas e toda equipe necessária. Você apenas
                    nos diz o que precisa e nós entregamos o evento pronto.
                </p>

                <div class="hero-cta-group" style="display:flex;gap:12px;flex-wrap:wrap;margin-top:24px;">
                    <a href="<?php echo url('login.php'); ?>" class="btn btn-primary btn-lg">
                        Contratar serviços
                    </a>
                    <a href="#para-profissionais" class="btn btn-outline btn-lg">
                        Sou profissional
                    </a>
                </div>
            </div>
            
            <div class="hero-visual">
                <div class="hero-cards">
                    <div class="floating-card card-1">
                        <div class="floating-card-icon">🎯</div>
                        <div class="floating-card-text">
                            <span>Gestão completa</span>
                            <small>Do cadastro ao pagamento</small>
                        </div>
                    </div>
                    <div class="floating-card card-2">
                        <div class="floating-card-icon">�</div>
                        <div class="floating-card-text">
                            <span>+<?php echo max($stats['prestadores_ativos'], 1); ?> profissionais</span>
                            <small>Verificados e qualificados</small>
                        </div>
                    </div>
                    <div class="floating-card card-3">
                        <div class="floating-card-icon">⚡</div>
                        <div class="floating-card-text">
                            <span>Check-in digital</span>
                            <small>Controle total do evento</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section - só exibe quando houver dados reais -->
    <?php
    $temStats = ($stats['diarias_ativas'] > 0 || $stats['prestadores_ativos'] > 0 || $stats['empresas_ativas'] > 0 || $stats['trabalhos_realizados'] > 0);
    ?>
    <?php if ($temStats): ?>
    <section class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <?php if ($stats['prestadores_ativos'] > 0): ?>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['prestadores_ativos']; ?>+</div>
                    <div class="stat-label">Profissionais Ativos</div>
                </div>
                <?php endif; ?>
                <?php if ($stats['empresas_ativas'] > 0): ?>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['empresas_ativas']; ?>+</div>
                    <div class="stat-label">Empresas Parceiras</div>
                </div>
                <?php endif; ?>
                <?php if ($stats['trabalhos_realizados'] > 0): ?>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['trabalhos_realizados']; ?>+</div>
                    <div class="stat-label">Trabalhos Realizados</div>
                </div>
                <?php endif; ?>
                <?php if ($stats['diarias_ativas'] > 0): ?>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['diarias_ativas']; ?></div>
                    <div class="stat-label">Vagas Disponíveis</div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Soluções -->
    <section id="solucoes" class="section how-section">
        <div class="container">
            <div class="section-header center">
                <h2>Nossas Soluções</h2>
                <p>Tudo o que você precisa para gerenciar eventos e profissionais em um só lugar</p>
            </div>
            
            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                            <line x1="12" y1="22.08" x2="12" y2="12"/>
                        </svg>
                    </div>
                    <h3>Gestão de Eventos</h3>
                    <p>Crie e gerencie eventos completos - casamentos, corporativos, festas e muito mais. Defina funções, vagas e valores.</p>
                </div>
                
                <div class="step-card">
                    <div class="step-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </div>
                    <h3>Recrutamento de Equipe</h3>
                    <p>Recrutamos garçons, seguranças, recepcionistas e toda equipe necessária para o seu evento. Profissionais verificados.</p>
                </div>
                
                <div class="step-card">
                    <div class="step-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 11l3 3L22 4"/>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                        </svg>
                    </div>
                    <h3>Check-in Digital</h3>
                    <p>Controle de presença no dia do evento por código. Saiba exatamente quem compareceu e quem faltou.</p>
                </div>
                
                <div class="step-card">
                    <div class="step-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </div>
                    <h3>Pagamentos Garantidos</h3>
                    <p>Controle financeiro completo - da cobrança à empresa ao repasse para o profissional. Transparente e seguro.</p>
                </div>

                <div class="step-card">
                    <div class="step-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 3v18h18"/>
                            <path d="M18 17V9"/>
                            <path d="M13 17V5"/>
                            <path d="M8 17v-3"/>
                        </svg>
                    </div>
                    <h3>Relatórios e Métricas</h3>
                    <p>Acompanhe em tempo real: eventos ativos, profissionais confirmados, faltas e taxa de comparecimento.</p>
                </div>

                <div class="step-card">
                    <div class="step-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                        </svg>
                    </div>
                    <h3>Notificações em Tempo Real</h3>
                    <p>Receba alertas instantâneos de novas candidaturas, confirmações e atualizações dos seus eventos.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How it Works -->
    <section id="como-funciona" class="section how-section">
        <div class="container">
            <div class="section-header center">
                <h2>Como Funciona</h2>
                <p>É simples e rápido começar</p>
            </div>
            
            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <div class="step-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="8.5" cy="7" r="4"/>
                            <line x1="20" y1="8" x2="20" y2="14"/>
                            <line x1="23" y1="11" x2="17" y2="11"/>
                        </svg>
                    </div>
                    <h3>Crie sua conta</h3>
                    <p>Cadastre-se gratuitamente em menos de 2 minutos. Adicione sua foto e informações básicas.</p>
                </div>
                
                <div class="step-card">
                    <div class="step-number">2</div>
                    <div class="step-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                    </div>
                    <h3>Encontre diárias</h3>
                    <p>Navegue pelas vagas disponíveis, filtre por função, valor ou localização. Use o mapa para ver oportunidades próximas.</p>
                </div>
                
                <div class="step-card">
                    <div class="step-number">3</div>
                    <div class="step-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                    </div>
                    <h3>Garanta sua vaga</h3>
                    <p>Com um clique, reserve sua vaga instantaneamente. Receba confirmação e detalhes do evento.</p>
                </div>
                
                <div class="step-card">
                    <div class="step-number">4</div>
                    <div class="step-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </div>
                    <h3>Trabalhe e receba</h3>
                    <p>Compareça ao evento, faça check-in e receba seu pagamento. Simples assim!</p>
                </div>
            </div>
        </div>
    </section>

    <!-- For Companies -->
    <section id="para-empresas" class="section companies-section">
        <div class="container">
            <div class="companies-content">
                <div class="companies-text">
                    <span class="section-badge">Para Empresas</span>
                    <h2>Encontre profissionais qualificados para seus eventos</h2>
                    <p class="companies-desc">
                        Gerencie suas contratações de forma simples e eficiente. 
                        Publique vagas, receba candidaturas e acompanhe tudo em tempo real.
                    </p>
                    
                    <ul class="companies-features">
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            <span>Check-in digital no dia do evento</span>
                        </li>
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            <span>Fotos dos prestadores para identificação</span>
                        </li>
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            <span>Sistema de avaliação pós-evento</span>
                        </li>
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            <span>Relatórios e métricas em tempo real</span>
                        </li>
                    </ul>
                    
                    <a href="<?php echo url('login.php'); ?>" class="btn btn-primary btn-lg">
                        Cadastrar minha empresa
                    </a>
                </div>
                
                <div class="companies-visual">
                    <div class="companies-card">
                        <div class="companies-card-header">
                            <h4>Próximo Evento</h4>
                            <span class="status-badge active">Ativo</span>
                        </div>
                        <div class="companies-card-body">
                            <div class="event-info">
                                <div class="event-item">
                                    <span class="event-label">Evento</span>
                                    <span class="event-value">Casamento Silva</span>
                                </div>
                                <div class="event-item">
                                    <span class="event-label">Data</span>
                                    <span class="event-value">25/06/2026</span>
                                </div>
                                <div class="event-item">
                                    <span class="event-label">Confirmados</span>
                                    <span class="event-value">8/10 garçons</span>
                                </div>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 80%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- For Professionals -->
    <section id="para-profissionais" class="section companies-section">
        <div class="container">
            <div class="companies-content" style="flex-direction: row-reverse;">
                <div class="companies-text">
                    <span class="section-badge">Para Profissionais</span>
                    <h2>Trabalhe nos melhores eventos da sua região</h2>
                    <p class="companies-desc">
                        Cadastre-se e receba oportunidades de trabalho em eventos.
                        Garçons, seguranças, recepcionistas, equipe de apoio e muito mais.
                        Você escolhe quando e onde trabalhar.
                    </p>
                    
                    <ul class="companies-features">
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            <span>Cadastre-se grátis em 2 minutos</span>
                        </li>
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            <span>Escolha os eventos que quer trabalhar</span>
                        </li>
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            <span>Receba pagamentos garantidos</span>
                        </li>
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            <span>App com check-in digital e agenda</span>
                        </li>
                    </ul>
                    
                    <a href="<?php echo url('login.php'); ?>" class="btn btn-primary btn-lg">
                        Criar conta de profissional
                    </a>
                </div>
                
                <div class="companies-visual">
                    <div class="companies-card">
                        <div class="companies-card-header">
                            <h4>Próximo Trabalho</h4>
                            <span class="status-badge active">Confirmado</span>
                        </div>
                        <div class="companies-card-body">
                            <div class="event-info">
                                <div class="event-item">
                                    <span class="event-label">Função</span>
                                    <span class="event-value">Garçom</span>
                                </div>
                                <div class="event-item">
                                    <span class="event-label">Data</span>
                                    <span class="event-value">25/06/2026</span>
                                </div>
                                <div class="event-item">
                                    <span class="event-label">Pagamento</span>
                                    <span class="event-value">R$ 180,00</span>
                                </div>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 100%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2>Pronto para começar?</h2>
                <p>Junte-se a milhares de profissionais e empresas que já usam o <?php echo APP_NAME; ?></p>
                <div class="cta-buttons">
                    <a href="<?php echo url('login.php'); ?>" class="btn btn-white btn-lg">
                        Sou Prestador
                    </a>
                    <a href="<?php echo url('login.php'); ?>" class="btn btn-outline-white btn-lg">
                        Sou Empresa
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <a href="<?php echo url(); ?>" class="logo">
                        <div class="logo-icon">
                            <img src="<?php echo url('assets/icons/icone.png?v=5'); ?>" alt="RapidJobs" style="width:100%;height:100%;object-fit:contain;">
                        </div>
                        <span><?php echo APP_NAME; ?></span>
                    </a>
                    <p>Conectando profissionais de eventos a empresas desde 2024.</p>
                </div>
                
                <div class="footer-links">
                    <h4>Plataforma</h4>
                    <ul>
                        <li><a href="<?php echo url('buscar.php'); ?>">Buscar Diárias</a></li>
                        <li><a href="#como-funciona">Como Funciona</a></li>
                        <li><a href="#para-empresas">Para Empresas</a></li>
                        <li><a href="<?php echo url('login.php'); ?>">Cadastrar</a></li>
                    </ul>
                </div>
                
                <div class="footer-links">
                    <h4>Suporte</h4>
                    <ul>
                        <li><a href="#">Central de Ajuda</a></li>
                        <li><a href="#">Termos de Uso</a></li>
                        <li><a href="#">Privacidade</a></li>
                        <li><a href="#">Contato</a></li>
                    </ul>
                </div>
                
                <div class="footer-links">
                    <h4>Acesso</h4>
                    <ul>
                        <li><a href="<?php echo url('login.php'); ?>">Entrar</a></li>
                        <li><a href="<?php echo url('admin/dashboard.php'); ?>">Admin</a></li>
                    </ul>
                </div>
                
                <div class="footer-links">
                    <h4>Baixe o App</h4>
                    <p style="color:#94A3B8;font-size:0.875rem;margin-bottom:12px;">Instale nosso app no celular para acessar mais rápido.</p>
                    <button id="btnInstalarApp" class="btn-instalar-app" type="button">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" style="width:20px;height:20px;">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="7 10 12 15 17 10"/>
                            <line x1="12" y1="15" x2="12" y2="3"/>
                        </svg>
                        <span>Instalar App</span>
                    </button>
                    <div id="appInstaladoMsg" style="display:none;color:#10B981;font-size:0.8125rem;margin-top:8px;">
                        ✓ App já está instalado!
                    </div>
                    <div id="appInstalacaoManual" style="display:none;color:#94A3B8;font-size:0.75rem;margin-top:8px;">
                        💡 Para instalar: menu do navegador (⋮) → <strong>Adicionar à tela inicial</strong>.
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    <!-- Popup de instalação PWA -->
    <div id="popupInstalacao" style="position:fixed;bottom:20px;left:50%;transform:translateX(-50%) translateY(150%);background:white;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,0.15);padding:16px;max-width:340px;width:calc(100% - 40px);z-index:9998;transition:transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);border:1px solid #E5E7EB;">
        <button id="fecharPopup" style="position:absolute;top:8px;right:8px;background:none;border:none;font-size:20px;color:#9CA3AF;cursor:pointer;width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;line-height:1;">×</button>
        <div style="display:flex;gap:12px;align-items:center;">
            <div style="width:48px;height:48px;background:white;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;">
                <img src="<?php echo url('assets/icons/icone.png?v=5'); ?>" alt="" style="width:100%;height:100%;object-fit:contain;">
            </div>
            <div style="flex:1;">
                <strong style="display:block;font-size:15px;color:#111827;">Instalar App</strong>
                <span style="display:block;font-size:12px;color:#6B7280;margin-top:2px;">Acesso rápido na tela inicial</span>
            </div>
        </div>
        <button id="btnInstalarPopup" type="button" style="margin-top:12px;width:100%;padding:10px;background:linear-gradient(135deg,#4F46E5,#7C3AED);color:white;border:none;border-radius:10px;font-weight:600;font-size:14px;cursor:pointer;">Instalar agora</button>
    </div>

    <!-- Mobile Menu -->
    <div class="mobile-menu" id="mobileMenu">
        <div class="mobile-menu-header">
            <a href="<?php echo url(); ?>" class="logo">
                <div class="logo-icon">
                    <img src="<?php echo url('assets/icons/icone.png?v=5'); ?>" alt="RapidJobs" style="width:100%;height:100%;object-fit:contain;">
                </div>
                <span><?php echo APP_NAME; ?></span>
            </a>
            <button class="mobile-menu-close" aria-label="Fechar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <nav class="mobile-nav">
            <a href="#buscar">Buscar Diárias</a>
            <a href="#como-funciona">Como Funciona</a>
            <a href="#para-empresas">Para Empresas</a>
            <hr>
            <a href="<?php echo url('login.php'); ?>" class="btn btn-ghost btn-block">Entrar</a>
            <a href="<?php echo url('login.php'); ?>" class="btn btn-primary btn-block">Cadastrar</a>
        </nav>
    </div>
    <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>

    <script src="assets/js/app.js"></script>
    <script>
    // Registrar Service Worker (PWA)
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('sw.js')
                .then(function(reg) {
                    console.log('✓ SW registrado:', reg.scope);
                    // Detectar atualização do SW
                    reg.addEventListener('updatefound', function() {
                        var newWorker = reg.installing;
                        newWorker.addEventListener('statechange', function() {
                            if (newWorker.state === 'activated' && navigator.serviceWorker.controller) {
                                console.log('✓ SW atualizado, recarregando...');
                                window.location.reload();
                            }
                        });
                    });
                })
                .catch(function(err) { console.log('Erro SW:', err); });
        });
        // Recarregar quando o SW tomar controle
        navigator.serviceWorker.addEventListener('controllerchange', function() {
            window.location.reload();
        });
    }
    
    // Capturar evento de instalação PWA (beforeinstallprompt)
    let deferredPrompt = null;
    const btnInstalar = document.getElementById('btnInstalarApp');
    const msgInstalado = document.getElementById('appInstaladoMsg');
    const msgManual = document.getElementById('appInstalacaoManual');
    
    // Detectar se já está instalado (standalone)
    function jaInstalado() {
        return (window.matchMedia('(display-mode: standalone)').matches) ||
               (window.navigator.standalone === true);
    }
    
    // Se já estiver instalado, esconder botão e mostrar msg
    if (jaInstalado()) {
        btnInstalar.style.display = 'none';
        msgInstalado.style.display = 'block';
    }
    
    // Capturar o evento do navegador (Chrome/Edge/Android)
    window.addEventListener('beforeinstallprompt', function(e) {
        e.preventDefault();
        deferredPrompt = e;
        console.log('✓ PWA pronto para instalar');
    });
    
    // Clique no botão — sempre clicável
    btnInstalar.addEventListener('click', function() {
        if (deferredPrompt) {
            // Navegador suporta instalação automática
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then(function(choice) {
                if (choice.outcome === 'accepted') {
                    btnInstalar.style.display = 'none';
                    msgInstalado.style.display = 'block';
                    console.log('✓ App instalado');
                }
                deferredPrompt = null;
            });
        } else {
            // Navegador não suporta (iOS/Safari/Firefox) → mostrar instrução manual
            msgManual.style.display = 'block';
        }
    });
    
    // Detectar instalação concluída
    window.addEventListener('appinstalled', function() {
        btnInstalar.style.display = 'none';
        msgInstalado.style.display = 'block';
    });

    // === Popup de instalação automática ===
    const popup = document.getElementById('popupInstalacao');
    const btnInstalarPopup = document.getElementById('btnInstalarPopup');
    const btnFecharPopup = document.getElementById('fecharPopup');

    // Fechar popup
    btnFecharPopup.addEventListener('click', function() {
        localStorage.setItem('popup_pwa_fechado', '1');
        esconderPopup();
    });
    function esconderPopup() {
        popup.style.transform = 'translateX(-50%) translateY(150%)';
    }
    function mostrarPopup() {
        popup.style.transform = 'translateX(-50%) translateY(0)';
    }

    // Botão "Instalar agora" — mesmas ações do botão do footer
    btnInstalarPopup.addEventListener('click', function() {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then(function(choice) {
                if (choice.outcome === 'accepted') {
                    console.log('✓ App instalado via popup');
                }
                deferredPrompt = null;
                esconderPopup();
            });
        } else {
            // Navegador não suporta → mostrar instrução manual do footer
            if (msgManual) msgManual.style.display = 'block';
            // Scroll suave até o footer
            document.getElementById('btnInstalarApp').scrollIntoView({ behavior: 'smooth' });
            esconderPopup();
        }
    });

    // Mostrar popup após 3s se não estiver instalado e não foi fechado antes
    setTimeout(function() {
        if (!jaInstalado() && !localStorage.getItem('popup_pwa_fechado')) {
            mostrarPopup();
        }
    }, 3000);
    </script>
<script>
// Bloquear copiar, colar, menu de contexto, long press
document.addEventListener('contextmenu', function(e) { e.preventDefault(); return false; });
document.addEventListener('selectstart', function(e) { if(e.target.tagName!=='INPUT'&&e.target.tagName!=='TEXTAREA') { e.preventDefault(); return false; } });
document.addEventListener('dragstart', function(e) { e.preventDefault(); return false; });
document.addEventListener('copy', function(e) { e.preventDefault(); return false; });
document.addEventListener('cut', function(e) { e.preventDefault(); return false; });
document.addEventListener('paste', function(e) { if(e.target.tagName!=='INPUT'&&e.target.tagName!=='TEXTAREA') { e.preventDefault(); return false; } });

// Bloquear pull-to-refresh
document.addEventListener('touchmove', function(e) {
    if (window.scrollY === 0 && e.touches[0].clientY > 0) {
        var startY = e.touches[0].clientY;
        var moveY = e.touches[0].clientY;
        if (moveY > startY) {
            e.preventDefault();
        }
    }
}, { passive: false });

// Bloquear gestos de long press
var timer;
document.addEventListener('touchstart', function(e) {
    if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA' && e.target.tagName !== 'A' && e.target.tagName !== 'BUTTON') {
        timer = setTimeout(function() {
            e.preventDefault();
        }, 500);
    }
}, { passive: false });
document.addEventListener('touchend', function() { clearTimeout(timer); });
document.addEventListener('touchmove', function() { clearTimeout(timer); });
</script>
</body>
</html>
