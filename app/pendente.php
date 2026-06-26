<?php
// Este arquivo é incluído pelo index.php - não verificar login novamente
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#4F46E5">
    <style>
        * { -webkit-user-select: none !important; -moz-user-select: none !important; -ms-user-select: none !important; user-select: none !important; -webkit-touch-callout: none !important; -webkit-tap-highlight-color: transparent !important; }
        input, textarea { -webkit-user-select: text !important; -moz-user-select: text !important; user-select: text !important; }
        html { overscroll-behavior-y: none !important; }
        body { overscroll-behavior: none !important; overscroll-behavior-y: none !important; touch-action: pan-y !important; }
    </style>
    <title>Aguardando Aprovação - <?php echo APP_NAME; ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container" style="max-width: 400px;">
        <div class="auth-card" style="text-align: center;">
            <div class="logo-icon" style="margin: 0 auto 20px; background: var(--warning);">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>
            
            <h2 style="margin-bottom: 10px; color: var(--warning);">Aguardando Aprovação</h2>
            
            <p style="color: var(--text-secondary); margin-bottom: 20px;">
                Seu cadastro foi realizado com sucesso!<br><br>
                Aguarde a administração aprovar seu perfil para começar a ver as diárias disponíveis.
            </p>
            
            <div style="background: var(--gray-100); padding: 16px; border-radius: 8px; margin-bottom: 20px;">
                <p style="margin: 0; font-size: 0.875rem; color: var(--text-secondary);">
                    <strong>Status:</strong> Aguardando análise
                </p>
            </div>
            
            <a href="../api/auth.php?action=logout" class="btn btn-secondary btn-block">
                Sair
            </a>
            
            <p style="margin-top: 20px; font-size: 0.75rem; color: var(--text-muted);">
                Em caso de dúvidas, entre em contato com o administrador.
            </p>
        </div>
    </div>
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
