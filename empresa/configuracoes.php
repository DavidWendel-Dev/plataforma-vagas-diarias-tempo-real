<?php
require_once __DIR__ . '/../app.php';

$auth = new Auth();
$auth->requireType('empresa');

$db = Database::getInstance();

$empresa = $db->fetch(
    "SELECT e.*, u.nome, u.email, u.telefone FROM empresas e JOIN usuarios u ON e.usuario_id = u.id WHERE u.id = :uid",
    ['uid' => userId()]
);

$user = $auth->getUser();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil - DiáriasApp</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #F3F4F6; min-height: 100vh; padding-bottom: 80px; }
        
        .header-profile {
            background: linear-gradient(135deg, #10B981, #059669);
            color: white;
            padding: 48px 20px 80px;
            text-align: center;
        }
        .header-profile h1 { font-size: 1.25rem; margin-bottom: 4px; }
        .header-profile p { font-size: 0.875rem; opacity: 0.9; }
        
        .profile-card {
            background: white;
            border-radius: 20px;
            margin: -50px 16px 20px;
            padding: 24px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            text-align: center;
        }
        
        .avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #10B981, #059669);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 800;
            margin: 0 auto 16px;
        }
        
        .profile-name { font-size: 1.25rem; font-weight: 700; margin-bottom: 4px; }
        .profile-email { color: #6B7280; font-size: 0.875rem; }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #F3F4F6;
        }
        .stat-item { text-align: center; }
        .stat-value { font-size: 1.5rem; font-weight: 800; color: #10B981; }
        .stat-label { font-size: 0.75rem; color: #6B7280; margin-top: 2px; }
        
        .content { padding: 0 16px; }
        
        .card {
            background: white;
            border-radius: 16px;
            margin-bottom: 16px;
            overflow: hidden;
        }
        
        .card-section { padding: 16px; border-bottom: 1px solid #F3F4F6; }
        .card-section:last-child { border-bottom: none; }
        .card-section-title { font-size: 0.75rem; color: #6B7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; }
        
        .info-row { display: flex; justify-content: space-between; padding: 8px 0; }
        .info-row span:first-child { color: #6B7280; }
        .info-row span:last-child { font-weight: 600; }
        
        .btn-sair {
            display: block;
            width: 100%;
            padding: 16px;
            background: #FEE2E2;
            color: #991B1B;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
        }
        
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            display: flex;
            justify-content: space-around;
            padding: 8px 0 20px;
            box-shadow: 0 -4px 20px rgba(0,0,0,0.08);
        }
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            color: #9CA3AF;
            text-decoration: none;
            font-size: 0.75rem;
            padding: 8px 16px;
            border-radius: 12px;
        }
        .nav-item svg { width: 24px; height: 24px; }
        .nav-item.active { color: #10B981; background: #ECFDF5; }
    </style>
</head>
<body>
    <header class="header-profile">
        <h1>Meu Perfil</h1>
        <p>Gerencie suas informações</p>
    </header>
    
    <div class="profile-card">
        <div class="avatar"><?php echo substr($empresa['nome'], 0, 1); ?></div>
        <div class="profile-name"><?php echo sanitize($empresa['nome']); ?></div>
        <div class="profile-email"><?php echo sanitize($empresa['email']); ?></div>
        
        <div class="stats-row">
            <div class="stat-item">
                <div class="stat-value"><?php echo $db->fetch("SELECT COUNT(*) as t FROM diarias WHERE empresa_id = :id", ['id' => $empresa['id']])['t']; ?></div>
                <div class="stat-label">Eventos</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $db->fetch("SELECT COALESCE(SUM(d.valor),0) as t FROM candidaturas c JOIN diarias d ON c.diaria_id = d.id WHERE d.empresa_id = :id AND c.status='checkin_realizado'", ['id' => $empresa['id']])['t']; ?></div>
                <div class="stat-label">Total Gasto</div>
            </div>
        </div>
    </div>
    
    <div class="content">
        <div class="card">
            <div class="card-section">
                <div class="card-section-title">Informações da Empresa</div>
                <div class="info-row">
                    <span>Razão Social</span>
                    <span><?php echo sanitize($empresa['razao_social'] ?: '-'); ?></span>
                </div>
                <div class="info-row">
                    <span>CNPJ</span>
                    <span><?php echo sanitize($empresa['cnpj'] ?: '-'); ?></span>
                </div>
                <div class="info-row">
                    <span>Telefone</span>
                    <span><?php echo sanitize($empresa['telefone'] ?: '-'); ?></span>
                </div>
            </div>
            
            <div class="card-section">
                <div class="card-section-title">Conta</div>
                <a href="../api/auth.php?action=logout" class="btn-sair">Sair da Conta</a>
            </div>
        </div>
    </div>
    
    <nav class="bottom-nav">
        <a href="dashboard.php" class="nav-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
            <span>Início</span>
        </a>
        <a href="eventos.php" class="nav-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/></svg>
            <span>Eventos</span>
        </a>
        <a href="historico.php" class="nav-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <span>Histórico</span>
        </a>
        <a href="configuracoes.php" class="nav-item active">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/></svg>
            <span>Perfil</span>
        </a>
    </nav>
</body>
</html>
