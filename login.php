<?php
require_once __DIR__ . '/app.php';

// Se já estiver logado, redirecionar
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
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar - <?php echo APP_NAME; ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/modern.css?v=6">
    <style>
        .auth-page { min-height: 100vh; display: flex; flex-direction: column; background: linear-gradient(135deg, var(--primary-50), white); }
        .navbar { flex-shrink: 0; }
        .auth-wrapper { flex: 1; display: flex; }
        .auth-sidebar { width: 50%; background: linear-gradient(135deg, var(--primary-600), var(--primary-800)); display: flex; align-items: center; justify-content: center; padding: 48px; color: white; position: relative; overflow: hidden; }
        .auth-sidebar::before { content: ''; position: absolute; inset: 0; background-image: radial-gradient(rgba(255,255,255,0.1) 1px, transparent 1px); background-size: 30px 30px; }
        .auth-sidebar-content { position: relative; z-index: 1; max-width: 400px; }
        .auth-sidebar h1 { font-size: 2.5rem; font-weight: 800; margin-bottom: 16px; }
        .auth-sidebar p { font-size: 1.125rem; opacity: 0.9; line-height: 1.7; }
        .auth-features { margin-top: 32px; }
        .auth-feature { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
        .auth-feature-icon { width: 40px; height: 40px; background: rgba(255,255,255,0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center; }
        
        .auth-main { flex: 1; display: flex; align-items: center; justify-content: center; padding: 48px; }
        .auth-form-container { width: 100%; max-width: 440px; }
        .auth-logo { display: flex; align-items: center; gap: 12px; margin-bottom: 32px; }
        .auth-title { font-size: 1.75rem; font-weight: 700; margin-bottom: 8px; }
        .auth-subtitle { color: var(--gray-500); margin-bottom: 32px; }
        
        .auth-tabs { display: flex; gap: 8px; margin-bottom: 24px; background: var(--gray-100); padding: 4px; border-radius: 10px; }
        .auth-tab { flex: 1; padding: 10px 16px; background: transparent; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; color: var(--gray-500); cursor: pointer; transition: all 0.2s; }
        .auth-tab.active { background: white; color: var(--primary-600); box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        
        .auth-form { display: none; }
        .auth-form.active { display: block; animation: fadeIn 0.3s ease; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 14px; font-weight: 500; margin-bottom: 6px; color: var(--gray-700); }
        .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--border-color); border-radius: 10px; font-size: 16px; transition: all 0.2s; }
        .form-control:focus { outline: none; border-color: var(--primary-500); box-shadow: 0 0 0 4px var(--primary-100); }
        
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        
        .photo-upload { text-align: center; margin-bottom: 24px; }
        .photo-input { display: inline-block; }
        .photo-input input { display: none; }
        .photo-preview { width: 100px; height: 100px; border-radius: 50%; background: var(--gray-100); border: 2px dashed var(--gray-300); display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; color: var(--gray-500); overflow: hidden; }
        .photo-preview:hover { border-color: var(--primary-500); background: var(--primary-50); }
        .photo-preview.has-image { border-style: solid; border-color: var(--primary-500); }
        .photo-preview img { width: 100%; height: 100%; object-fit: cover; }
        .photo-preview span { font-size: 12px; margin-top: 4px; }
        
        .checkbox-group { display: flex; flex-wrap: wrap; gap: 8px; }
        .checkbox-pill { position: relative; }
        .checkbox-pill input { position: absolute; opacity: 0; }
        .checkbox-pill span { display: inline-block; padding: 8px 14px; background: var(--gray-100); border-radius: 20px; font-size: 13px; color: var(--gray-600); cursor: pointer; transition: all 0.2s; }
        .checkbox-pill:hover span { background: var(--gray-200); }
        .checkbox-pill input:checked + span { background: var(--primary-600); color: white; }
        
        .form-message { margin-top: 16px; padding: 12px 16px; border-radius: 8px; font-size: 14px; display: none; }
        .form-message.success { display: block; background: var(--success-50); color: var(--success-600); }
        .form-message.error { display: block; background: var(--danger-50); color: var(--danger-600); }
        
        .demo-info { margin-top: 24px; padding: 16px; background: var(--primary-50); border-radius: 10px; font-size: 13px; text-align: center; }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        @media (max-width: 1024px) { .auth-sidebar { display: none; } .auth-main { padding: 24px; } }
        @media (max-width: 640px) {
            .form-row { grid-template-columns: 1fr; }
            .auth-page { min-height: 100vh; }
            .auth-main { padding: 12px; display: flex; align-items: center; justify-content: center; }
            .auth-form-container { max-width: 100%; }
            .auth-logo { margin-bottom: 12px; }
            .auth-title { font-size: 1.25rem; margin-bottom: 4px; }
            .auth-subtitle { font-size: 0.8125rem; margin-bottom: 12px; }
            .auth-tabs { margin-bottom: 12px; }
            .form-group { margin-bottom: 10px; }
            .form-group label { font-size: 12px; margin-bottom: 3px; }
            .form-control { padding: 8px 12px; font-size: 14px; }
            .photo-upload { margin-bottom: 12px; }
            .photo-preview { width: 70px; height: 70px; }
            .photo-preview span { font-size: 10px; }
            .btn-block { padding: 10px !important; font-size: 14px; }
            .demo-info { margin-top: 12px; padding: 10px; font-size: 11px; }
        }
        /* Bloquear copiar, long press, pull-to-refresh */
        * { -webkit-user-select: none !important; -moz-user-select: none !important; -ms-user-select: none !important; user-select: none !important; -webkit-touch-callout: none !important; -webkit-tap-highlight-color: transparent !important; }
        input, textarea { -webkit-user-select: text !important; -moz-user-select: text !important; user-select: text !important; }
        html { overscroll-behavior-y: none !important; }
        body { overscroll-behavior: none !important; overscroll-behavior-y: none !important; touch-action: pan-y !important; }
    </style>
</head>
<body class="auth-page">
    <!-- Navbar -->
    <nav class="navbar">
        <div class="container navbar-content">
            <a href="<?php echo url(); ?>" class="logo">
                <div class="logo-icon">
                    <img src="<?php echo url('assets/icons/icone.png?v=5'); ?>" alt="RapidJobs" style="width:100%;height:100%;object-fit:contain;">
                </div>
                <span><?php echo APP_NAME; ?></span>
            </a>
            
            <div class="nav-links">
                <a href="<?php echo url(); ?>#buscar" class="nav-link">Buscar Diárias</a>
                <a href="<?php echo url(); ?>#como-funciona" class="nav-link">Como Funciona</a>
                <a href="<?php echo url(); ?>#para-empresas" class="nav-link">Para Empresas</a>
            </div>
            
            <div class="nav-actions">
                <a href="<?php echo url(); ?>" class="btn btn-ghost">Início</a>
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
            <a href="<?php echo url(); ?>#buscar">Buscar Diárias</a>
            <a href="<?php echo url(); ?>#como-funciona">Como Funciona</a>
            <a href="<?php echo url(); ?>#para-empresas">Para Empresas</a>
            <hr>
            <a href="<?php echo url(); ?>" class="btn btn-ghost btn-block">Início</a>
        </nav>
    </div>
    <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>

    <!-- Auth Wrapper -->
    <div class="auth-wrapper">
    <!-- Sidebar -->
    <div class="auth-sidebar">
        <div class="auth-sidebar-content">
            <h1>Encontre as melhores oportunidades</h1>
            <p>Junte-se a milhares de profissionais de eventos e empresas que usam o <?php echo APP_NAME; ?> todos os dias.</p>
            
            <div class="auth-features">
                <div class="auth-feature">
                    <div class="auth-feature-icon">⚡</div>
                    <div>Reserva instantânea de vagas</div>
                </div>
                <div class="auth-feature">
                    <div class="auth-feature-icon">💰</div>
                    <div>Pagamento garantido</div>
                </div>
                <div class="auth-feature">
                    <div class="auth-feature-icon">📍</div>
                    <div>Encontre vagas próximas</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Form -->
    <div class="auth-main">
        <div class="auth-form-container">
            
            <h2 class="auth-title">Bem-vindo de volta</h2>
            <p class="auth-subtitle">Entre na sua conta ou crie uma nova</p>
            
            <!-- Tabs -->
            <div class="auth-tabs">
                <button class="auth-tab active" data-tab="login">Entrar</button>
                <button class="auth-tab" data-tab="prestador">Cadastre-se</button>
                <?php if (isEmpresaCadastroPermitido()): ?>
                <button class="auth-tab" data-tab="empresa">Empresa</button>
                <?php endif; ?>
            </div>
            
            <!-- Login Form -->
            <form id="loginForm" class="auth-form active" data-form="login">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" required placeholder="seu@email.com">
                </div>
                <div class="form-group">
                    <label for="password">Senha</label>
                    <input type="password" id="password" name="password" class="form-control" required placeholder="••••••••">
                </div>
                <button type="submit" class="btn btn-primary btn-block" style="padding: 14px; margin-top: 8px;">
                    <span>Entrar</span>
                </button>
                <div class="form-message"></div>
            </form>
            
            <!-- Prestador Form -->
            <form id="prestadorForm" class="auth-form" data-form="prestador" enctype="multipart/form-data">
                <div class="photo-upload">
                    <div class="photo-input">
                        <input type="file" id="foto_prestador" name="foto" accept="image/*">
                        <div class="photo-preview" id="photoPreview">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                                <circle cx="12" cy="13" r="4"/>
                            </svg>
                            <span>Adicionar foto</span>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="nome_prestador">Nome completo</label>
                    <input type="text" id="nome_prestador" name="nome" class="form-control" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email_prestador">Email</label>
                        <input type="email" id="email_prestador" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="telefone_prestador">WhatsApp Obrigatório</label>
                        <input type="tel" id="telefone_prestador" name="telefone" class="form-control" placeholder="(00) 00000-0000" maxlength="15" inputmode="numeric" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="data_nascimento">Data de nascimento</label>
                        <input type="tel" id="data_nascimento" name="data_nascimento" class="form-control" placeholder="DD/MM/AAAA" maxlength="10" inputmode="numeric" pattern="\d{2}/\d{2}/\d{4}" required>
                        <small style="color:#6B7280;font-size:11px;margin-top:4px;display:block;">Digite: dia/mês/ano</small>
                    </div>
                    <div class="form-group">
                        <label for="senha_prestador">Senha</label>
                        <input type="password" id="senha_prestador" name="password" class="form-control" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block" style="padding: 14px;">Cadastrar</button>
                <div class="form-message"></div>
            </form>
            
            <!-- Empresa Form -->
            <?php if (isEmpresaCadastroPermitido()): ?>
            <form id="empresaForm" class="auth-form" data-form="empresa">
                <div class="form-group">
                    <label for="nome_empresa">Nome fantasia</label>
                    <input type="text" id="nome_empresa" name="nome" class="form-control" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="razao_social">Razão social</label>
                        <input type="text" id="razao_social" name="razao_social" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="cnpj">CNPJ</label>
                        <input type="text" id="cnpj" name="cnpj" class="form-control">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email_empresa">Email</label>
                        <input type="email" id="email_empresa" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="telefone_empresa">WhatsApp Obrigatório</label>
                        <input type="tel" id="telefone_empresa" name="telefone" class="form-control" placeholder="(00) 00000-0000" maxlength="15" inputmode="numeric" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="contato_nome">Pessoa de contato</label>
                        <input type="text" id="contato_nome" name="contato_nome" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="senha_empresa">Senha</label>
                        <input type="password" id="senha_empresa" name="password" class="form-control" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block" style="padding: 14px;">Cadastrar Empresa</button>
                <div class="form-message"></div>
            </form>
            <?php endif; ?>
        </div>
    </div>
    </div><!-- /auth-wrapper -->

    <script src="assets/js/app.js?v=2"></script>
    <script src="assets/js/auth.js?v=2"></script>
    <script>
    // Máscara de WhatsApp (00) 00000-0000 — formata enquanto digita
    function mascaraTelefone(e) {
        let v = e.target.value.replace(/\D/g, '');
        if (v.length > 11) v = v.slice(0, 11);
        
        let r = '';
        if (v.length > 0) {
            r = '(' + v.slice(0, 2);
            if (v.length >= 2) r += ') ';
            if (v.length > 2) {
                if (v.length <= 6) r += v.slice(2);
                else if (v.length <= 10) r += v.slice(2, 6) + '-' + v.slice(6);
                else r += v.slice(2, 7) + '-' + v.slice(7);
            }
        }
        e.target.value = r;
    }
    
    function validarTelefone(el) {
        const digitos = el.value.replace(/\D/g, '');
        
        // Remover mensagem de erro anterior
        let msg = el.parentNode.querySelector('.erro-telefone');
        if (msg) msg.remove();
        el.style.borderColor = '';
        
        // Se está vazio, deixa o required cuidar
        if (digitos.length === 0) return;
        
        if (digitos.length < 11) {
            el.style.borderColor = '#EF4444';
            msg = document.createElement('div');
            msg.className = 'erro-telefone';
            msg.style.cssText = 'color:#EF4444;font-size:12px;margin-top:4px;';
            msg.textContent = '⚠️ O WhatsApp deve ter 11 dígitos (DDD + 9 dígitos). Você digitou ' + digitos.length + '.';
            el.parentNode.appendChild(msg);
        } else if (digitos.length === 11) {
            el.style.borderColor = '#10B981';
        }
    }
    
    // Calcular idade completa (anos) a partir de DD/MM/AAAA
    function calcularIdade(dia, mes, ano) {
        const hoje = new Date();
        let idade = hoje.getFullYear() - ano;
        const mesAtual = hoje.getMonth() + 1;
        const diaAtual = hoje.getDate();
        // Ainda não fez aniversário este ano
        if (mes > mesAtual || (mes === mesAtual && dia > diaAtual)) {
            idade--;
        }
        return idade;
    }
    
    // Validar data de nascimento + idade mínima (18 anos)
    function validarData(el) {
        let msg = el.parentNode.querySelector('.erro-data');
        if (msg) msg.remove();
        el.style.borderColor = '';
        
        if (!el.value) return true;
        
        const partes = el.value.split('/');
        if (partes.length !== 3 || partes[0].length !== 2 || partes[1].length !== 2 || partes[2].length !== 4) {
            el.style.borderColor = '#EF4444';
            msg = document.createElement('div');
            msg.className = 'erro-data';
            msg.style.cssText = 'color:#EF4444;font-size:12px;margin-top:4px;';
            msg.textContent = '⚠️ Data inválida. Digite no formato DD/MM/AAAA.';
            el.parentNode.appendChild(msg);
            return false;
        }
        
        const dia = parseInt(partes[0]);
        const mes = parseInt(partes[1]);
        const ano = parseInt(partes[2]);
        const hoje = new Date();
        
        // Validar dia/mês
        if (dia < 1 || dia > 31 || mes < 1 || mes > 12 || ano < 1900 || ano > hoje.getFullYear()) {
            el.style.borderColor = '#EF4444';
            msg = document.createElement('div');
            msg.className = 'erro-data';
            msg.style.cssText = 'color:#EF4444;font-size:12px;margin-top:4px;';
            msg.textContent = '⚠️ Data inválida. Verifique dia, mês e ano.';
            el.parentNode.appendChild(msg);
            return false;
        }
        
        // Validar dia correto por mês
        const diasPorMes = [31, (ano % 4 === 0 && (ano % 100 !== 0 || ano % 400 === 0)) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        if (dia > diasPorMes[mes - 1]) {
            el.style.borderColor = '#EF4444';
            msg = document.createElement('div');
            msg.className = 'erro-data';
            msg.style.cssText = 'color:#EF4444;font-size:12px;margin-top:4px;';
            msg.textContent = '⚠️ O mês ' + mes + ' não tem ' + dia + ' dias.';
            el.parentNode.appendChild(msg);
            return false;
        }
        
        // Verificar idade mínima (18 anos)
        const idade = calcularIdade(dia, mes, ano);
        if (idade < 18) {
            el.style.borderColor = '#EF4444';
            msg = document.createElement('div');
            msg.className = 'erro-data';
            msg.style.cssText = 'color:#EF4444;font-size:12px;margin-top:4px;';
            msg.textContent = '⚠️ Você tem ' + idade + ' anos. É necessário ter pelo menos 18 anos para se cadastrar.';
            el.parentNode.appendChild(msg);
            return false;
        }
        
        if (idade > 120) {
            el.style.borderColor = '#EF4444';
            msg = document.createElement('div');
            msg.className = 'erro-data';
            msg.style.cssText = 'color:#EF4444;font-size:12px;margin-top:4px;';
            msg.textContent = '⚠️ Data inválida. Verifique o ano de nascimento.';
            el.parentNode.appendChild(msg);
            return false;
        }
        
        // Válido
        el.style.borderColor = '#10B981';
        return true;
    }
    
    // Máscara de data DD/MM/AAAA
    function mascaraData(e) {
        let v = e.target.value.replace(/\D/g, '');
        if (v.length > 8) v = v.slice(0, 8);
        let r = '';
        if (v.length > 0) {
            r = v.slice(0, 2);
            if (v.length >= 2) r += '/';
            if (v.length > 2) r += v.slice(2, 4);
            if (v.length >= 4) r += '/';
            if (v.length > 4) r += v.slice(4, 8);
        }
        e.target.value = r;
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        ['telefone_prestador', 'telefone_empresa'].forEach(function(id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('input', mascaraTelefone);
            // Validar quando sair do campo (blur)
            el.addEventListener('blur', function() { validarTelefone(el); });
            // Limpar erro quando voltar a digitar
            el.addEventListener('focus', function() {
                el.style.borderColor = '';
                let msg = el.parentNode.querySelector('.erro-telefone');
                if (msg) msg.remove();
            });
            el.addEventListener('keydown', function(e) {
                const teclasPermitidas = ['Backspace','Delete','ArrowLeft','ArrowRight','Tab','Enter','Home','End'];
                if (teclasPermitidas.includes(e.key)) return;
                if (!/^\d$/.test(e.key) && !e.ctrlKey && !e.metaKey) e.preventDefault();
                let digitos = el.value.replace(/\D/g, '');
                if (digitos.length >= 11 && /^\d$/.test(e.key)) e.preventDefault();
            });
            el.addEventListener('paste', function(e) {
                e.preventDefault();
                const texto = (e.clipboardData || window.clipboardData).getData('text');
                const digitos = texto.replace(/\D/g, '').slice(0, 11);
                let r = '';
                if (digitos.length > 0) {
                    r = '(' + digitos.slice(0, 2);
                    if (digitos.length >= 2) r += ') ';
                    if (digitos.length > 2) {
                        if (digitos.length <= 6) r += digitos.slice(2);
                        else if (digitos.length <= 10) r += digitos.slice(2, 6) + '-' + digitos.slice(6);
                        else r += digitos.slice(2, 7) + '-' + digitos.slice(7);
                    }
                }
                el.value = r;
                validarTelefone(el);
            });
        });
        
        // Bloquear envio do formulário se telefone for inválido
        document.querySelectorAll('form').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                let valido = true;
                ['telefone_prestador', 'telefone_empresa'].forEach(function(id) {
                    const el = document.getElementById(id);
                    if (!el || !el.value) return;
                    const digitos = el.value.replace(/\D/g, '');
                    if (digitos.length !== 11) {
                        valido = false;
                        validarTelefone(el);
                    }
                });
                if (!valido) e.preventDefault();
                
                // Validar data de nascimento (idade mínima 18)
                const dataInput = document.getElementById('data_nascimento');
                if (dataInput && dataInput.value) {
                    if (!validarData(dataInput)) {
                        valido = false;
                    } else {
                        // Converter para ISO (AAAA-MM-DD) antes de enviar
                        const partes = dataInput.value.split('/');
                        const dia = parseInt(partes[0]);
                        const mes = parseInt(partes[1]);
                        const ano = parseInt(partes[2]);
                        dataInput.value = ano + '-' + (mes < 10 ? '0' + mes : mes) + '-' + (dia < 10 ? '0' + dia : dia);
                    }
                }
                if (!valido) e.preventDefault();
            });
        });
        
        // Máscara de data de nascimento
        const dataInput = document.getElementById('data_nascimento');
        if (dataInput) {
            dataInput.addEventListener('input', mascaraData);
            dataInput.addEventListener('keydown', function(e) {
                const teclasPermitidas = ['Backspace','Delete','ArrowLeft','ArrowRight','Tab','Enter','Home','End'];
                if (teclasPermitidas.includes(e.key)) return;
                if (!/^\d$/.test(e.key) && !e.ctrlKey && !e.metaKey) e.preventDefault();
                let digitos = dataInput.value.replace(/\D/g, '');
                if (digitos.length >= 8 && /^\d$/.test(e.key)) e.preventDefault();
            });
            dataInput.addEventListener('focus', function() {
                dataInput.style.borderColor = '';
                let msg = dataInput.parentNode.querySelector('.erro-data');
                if (msg) msg.remove();
            });
            // Validar data + idade ao sair do campo
            dataInput.addEventListener('blur', function() {
                if (dataInput.value) validarData(dataInput);
            });
        }
    });
    </script>
<script>
// Bloquear copiar, colar, menu de contexto
document.addEventListener('contextmenu', function(e) { e.preventDefault(); return false; });
document.addEventListener('selectstart', function(e) { if(e.target.tagName!=='INPUT'&&e.target.tagName!=='TEXTAREA') { e.preventDefault(); return false; } });
document.addEventListener('dragstart', function(e) { e.preventDefault(); return false; });
document.addEventListener('copy', function(e) { e.preventDefault(); return false; });
document.addEventListener('cut', function(e) { e.preventDefault(); return false; });
document.addEventListener('paste', function(e) { if(e.target.tagName!=='INPUT'&&e.target.tagName!=='TEXTAREA') { e.preventDefault(); return false; } });
</script>
</body>
</html>
