<?php
require_once __DIR__ . '/../app.php';

$auth = new Auth();
$auth->requireType('admin');

$db = Database::getInstance();

// Salvar configurações
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'salvar') {
    $checkboxes = ['permitir_cadastro_empresas', 'permitir_publicar_vagas', 'cobranca_ativa', 'taxa_urgencia_ativa', 'som_candidatura_admin'];
    
    foreach ($checkboxes as $cb) {
        if (!isset($_POST[$cb])) {
            $_POST[$cb] = '0';
        }
    }
    
    foreach ($_POST as $chave => $valor) {
        if ($chave !== 'action') {
            $exists = $db->fetch("SELECT id FROM configuracoes WHERE chave = :chave", ['chave' => $chave]);
            if ($exists) {
                $db->update('configuracoes', ['valor' => $valor], 'chave = :chave', ['chave' => $chave]);
            } else {
                $db->insert('configuracoes', ['chave' => $chave, 'valor' => $valor]);
            }
        }
    }
    $success = true;
}

$configs = $db->fetchAll("SELECT * FROM configuracoes");
$config = [];
foreach ($configs as $c) {
    $config[$c['chave']] = $c['valor'];
}

$defaults = [
    'idade_minima' => 18,
    'permitir_cadastro_empresas' => '1',
    'permitir_publicar_vagas' => '1',
    'som_candidatura_admin' => '0',
    'mapbox_token' => defined('MAPBOX_TOKEN') ? MAPBOX_TOKEN : '',
    'email_suporte' => '',
    'cobranca_ativa' => '0',
    'modelo_cobranca' => 'spread',
    'margem_padrao' => '20',
    'taxa_fixa_profissional' => '30',
    'taxa_urgencia_ativa' => '0',
    'taxa_urgencia_valor' => '50',
    'prazo_pagamento_empresa' => '7',
    'prazo_pagamento_prestador' => '3'
];

foreach ($defaults as $k => $v) {
    if (!isset($config[$k])) {
        $config[$k] = $v;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - <?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=3">
    <link rel="stylesheet" href="../assets/css/admin.css?v=3">
    <style>
        .tabs-container { margin-bottom: 24px; }
        .tabs { display: flex; gap: 0; border-bottom: 2px solid #E5E7EB; }
        .tab-btn { padding: 14px 24px; background: none; border: none; font-size: 14px; font-weight: 500; color: #6B7280; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all 0.2s; }
        .tab-btn:hover { color: #4F46E5; }
        .tab-btn.active { color: #4F46E5; border-bottom-color: #4F46E5; }
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }
        
        /* Toggle Switch */
        .toggle-wrapper { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; background: #F9FAFB; border-radius: 8px; }
        .toggle-info h4 { margin: 0 0 4px; font-size: 15px; }
        .toggle-info p { margin: 0; color: #6B7280; font-size: 13px; }
        .toggle { position: relative; width: 48px; height: 26px; }
        .toggle input { opacity: 0; width: 0; height: 0; }
        .toggle-slider { position: absolute; cursor: pointer; inset: 0; background-color: #D1D5DB; transition: .3s; border-radius: 26px; }
        .toggle-slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 3px; bottom: 3px; background-color: white; transition: .3s; border-radius: 50%; }
        .toggle input:checked + .toggle-slider { background-color: #4F46E5; }
        .toggle input:checked + .toggle-slider:before { transform: translateX(22px); }
        
        /* Modelo Cards */
        .modelo-grid { display: grid; gap: 12px; }
        .modelo-card { border: 2px solid #E5E7EB; border-radius: 12px; padding: 20px; cursor: pointer; transition: all 0.2s; }
        .modelo-card:hover { border-color: #A5B4FC; }
        .modelo-card.selected { border-color: #4F46E5; background: linear-gradient(135deg, #EEF2FF, #E0E7FF); }
        .modelo-card input { display: none; }
        .modelo-header { display: flex; align-items: center; gap: 12px; margin-bottom: 8px; }
        .modelo-icon { width: 40px; height: 40px; background: #F3F4F6; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .modelo-card.selected .modelo-icon { background: #C7D2FE; }
        .modelo-title { font-weight: 600; font-size: 15px; }
        .modelo-desc { color: #6B7280; font-size: 13px; margin: 0; }
        .modelo-config { margin-top: 16px; padding-top: 16px; border-top: 1px solid #E5E7EB; }
        
        .cobranca-disabled { opacity: 0.4; pointer-events: none; }
        
        .info-alert { background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 8px; padding: 16px; margin-bottom: 20px; }
        .info-alert h4 { margin: 0 0 8px; color: #1E40AF; font-size: 14px; }
        .info-alert p { margin: 0; color: #3B82F6; font-size: 13px; }
    </style>
</head>
<body class="admin-body">
    <?php include 'includes/sidebar.php'; ?>
    <main class="admin-main">
        <?php include 'includes/header.php'; ?>
        <div class="admin-content">
            <h1 style="margin-bottom: 24px;">Configurações</h1>

            <?php if (isset($success)): ?>
            <div class="alert alert-success" style="background: #D1FAE5; color: #065F46; padding: 14px 18px; border-radius: 8px; margin-bottom: 20px;">
                Salvo com sucesso!
            </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="action" value="salvar">

                <div class="tabs-container">
                    <div class="tabs">
                        <button type="button" class="tab-btn active" data-tab="cadastros">Cadastros</button>
                        <button type="button" class="tab-btn" data-tab="cobranca">💰 Formas de Cobrança</button>
                        <button type="button" class="tab-btn" data-tab="gerais">Gerais</button>
                    </div>
                </div>

                <!-- Tab: Cadastros -->
                <div id="tab-cadastros" class="tab-panel active">
                    <div class="card">
                        <div class="card-header"><h3>Controle de Cadastros</h3></div>
                        <div class="card-body" style="display: grid; gap: 16px;">
                            <div class="toggle-wrapper">
                                <div class="toggle-info">
                                    <h4>Permitir Cadastro de Empresas</h4>
                                    <p>Empresas podem se cadastrar na página de login</p>
                                </div>
                                <label class="toggle">
                                    <input type="checkbox" name="permitir_cadastro_empresas" value="1" <?= $config['permitir_cadastro_empresas'] === '1' ? 'checked' : '' ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            
                            <div class="toggle-wrapper">
                                <div class="toggle-info">
                                    <h4>Permitir Publicação de Vagas</h4>
                                    <p>Empresas podem criar diárias pelo painel</p>
                                </div>
                                <label class="toggle">
                                    <input type="checkbox" name="permitir_publicar_vagas" value="1" <?= $config['permitir_publicar_vagas'] === '1' ? 'checked' : '' ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            
                            <div class="toggle-wrapper">
                                <div class="toggle-info">
                                    <h4>🔔 Som de Nova Candidatura (Admin)</h4>
                                    <p>Toca um som automaticamente quando um prestador aceita uma vaga</p>
                                </div>
                                <label class="toggle">
                                    <input type="checkbox" name="som_candidatura_admin" value="1" <?= ($config['som_candidatura_admin'] ?? '0') === '1' ? 'checked' : '' ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Cobrança -->
                <div id="tab-cobranca" class="tab-panel">
                    <div class="info-alert">
                        <h4>💡 Como funciona?</h4>
                        <p>Quando ativado, o sistema calcula automaticamente o valor a cobrar da empresa, separando o repasse ao prestador e seu lucro.</p>
                    </div>

                    <div class="card" style="margin-bottom: 20px;">
                        <div class="card-header"><h3>Ativar Sistema de Cobrança</h3></div>
                        <div class="card-body">
                            <div class="toggle-wrapper">
                                <div class="toggle-info">
                                    <h4>Ativar Cobrança Automática</h4>
                                    <p>Quando desativado, valores para empresa e prestador são iguais (sem lucro)</p>
                                </div>
                                <label class="toggle">
                                    <input type="checkbox" name="cobranca_ativa" value="1" <?= $config['cobranca_ativa'] === '1' ? 'checked' : '' ?> id="toggleCobranca">
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div id="cobrancaConfig" class="<?= $config['cobranca_ativa'] !== '1' ? 'cobranca-disabled' : '' ?>">
                        <div class="card" style="margin-bottom: 20px;">
                            <div class="card-header"><h3>Modelo de Cobrança</h3></div>
                            <div class="card-body">
                                <div class="modelo-grid">
                                    <div class="modelo-card <?= $config['modelo_cobranca'] === 'spread' ? 'selected' : '' ?>" data-modelo="spread">
                                        <input type="radio" name="modelo_cobranca" value="spread" <?= $config['modelo_cobranca'] === 'spread' ? 'checked' : '' ?>>
                                        <div class="modelo-header">
                                            <div class="modelo-icon">📊</div>
                                            <div class="modelo-title">SPREAD (Margem sobre Diária)</div>
                                        </div>
                                        <p class="modelo-desc">Ex: Prestador recebe R$150, empresa paga R$180 (margem 20%)</p>
                                        <div class="modelo-config" style="<?= $config['modelo_cobranca'] !== 'spread' ? 'display:none' : '' ?>">
                                            <label style="font-size:13px; font-weight:500;">Margem padrão (%)</label>
                                            <input type="number" name="margem_padrao" value="<?= $config['margem_padrao'] ?>" class="form-control" style="max-width:120px; margin-top:6px;">
                                        </div>
                                    </div>

                                    <div class="modelo-card <?= $config['modelo_cobranca'] === 'taxa_fixa' ? 'selected' : '' ?>" data-modelo="taxa_fixa">
                                        <input type="radio" name="modelo_cobranca" value="taxa_fixa" <?= $config['modelo_cobranca'] === 'taxa_fixa' ? 'checked' : '' ?>>
                                        <div class="modelo-header">
                                            <div class="modelo-icon">💵</div>
                                            <div class="modelo-title">TAXA FIXA POR PROFISSIONAL</div>
                                        </div>
                                        <p class="modelo-desc">Ex: Cobra R$30 por cada profissional encaminhado</p>
                                        <div class="modelo-config" style="<?= $config['modelo_cobranca'] !== 'taxa_fixa' ? 'display:none' : '' ?>">
                                            <label style="font-size:13px; font-weight:500;">Taxa fixa (R$)</label>
                                            <input type="number" name="taxa_fixa_profissional" value="<?= $config['taxa_fixa_profissional'] ?>" class="form-control" step="0.01" style="max-width:120px; margin-top:6px;">
                                        </div>
                                    </div>

                                    <div class="modelo-card <?= $config['modelo_cobranca'] === 'ambos' ? 'selected' : '' ?>" data-modelo="ambos">
                                        <input type="radio" name="modelo_cobranca" value="ambos" <?= $config['modelo_cobranca'] === 'ambos' ? 'checked' : '' ?>>
                                        <div class="modelo-header">
                                            <div class="modelo-icon">📈</div>
                                            <div class="modelo-title">AMBOS (Spread + Taxa Fixa)</div>
                                        </div>
                                        <p class="modelo-desc">Combina margem percentual + taxa fixa por profissional</p>
                                        <div class="modelo-config" style="<?= $config['modelo_cobranca'] !== 'ambos' ? 'display:none' : '' ?>">
                                            <div style="display:flex; gap:20px;">
                                                <div>
                                                    <label style="font-size:13px; font-weight:500;">Margem (%)</label>
                                                    <input type="number" name="margem_padrao" value="<?= $config['margem_padrao'] ?>" class="form-control" style="width:100px; margin-top:6px;">
                                                </div>
                                                <div>
                                                    <label style="font-size:13px; font-weight:500;">Taxa Fixa (R$)</label>
                                                    <input type="number" name="taxa_fixa_profissional" value="<?= $config['taxa_fixa_profissional'] ?>" class="form-control" step="0.01" style="width:100px; margin-top:6px;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modelo-card <?= $config['modelo_cobranca'] === 'personalizado' ? 'selected' : '' ?>" data-modelo="personalizado">
                                        <input type="radio" name="modelo_cobranca" value="personalizado" <?= $config['modelo_cobranca'] === 'personalizado' ? 'checked' : '' ?>>
                                        <div class="modelo-header">
                                            <div class="modelo-icon">✏️</div>
                                            <div class="modelo-title">PERSONALIZADO</div>
                                        </div>
                                        <p class="modelo-desc">Define o valor manualmente em cada evento (mais flexível)</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card" style="margin-bottom: 20px;">
                            <div class="card-header"><h3>Configurações Adicionais</h3></div>
                            <div class="card-body">
                                <div class="toggle-wrapper" style="margin-bottom: 16px;">
                                    <div class="toggle-info">
                                        <h4>Taxa de Urgência</h4>
                                        <p>Cobra taxa extra para eventos com menos de 48h de antecedência</p>
                                    </div>
                                    <label class="toggle">
                                        <input type="checkbox" name="taxa_urgencia_ativa" value="1" <?= $config['taxa_urgencia_ativa'] === '1' ? 'checked' : '' ?>>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                                
                                <div class="form-group">
                                    <label>Valor da Taxa de Urgência (R$)</label>
                                    <input type="number" name="taxa_urgencia_valor" value="<?= $config['taxa_urgencia_valor'] ?>" class="form-control" step="0.01" style="max-width: 150px;">
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                    <div class="form-group">
                                        <label>Prazo para Empresa Pagar (dias)</label>
                                        <input type="number" name="prazo_pagamento_empresa" value="<?= $config['prazo_pagamento_empresa'] ?>" class="form-control" style="max-width: 100px;">
                                    </div>
                                    <div class="form-group">
                                        <label>Prazo para Pagar Prestador (dias)</label>
                                        <input type="number" name="prazo_pagamento_prestador" value="<?= $config['prazo_pagamento_prestador'] ?>" class="form-control" style="max-width: 100px;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Gerais -->
                <div id="tab-gerais" class="tab-panel">
                    <div class="card">
                        <div class="card-header"><h3>Configurações Gerais</h3></div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Idade Mínima para Cadastro</label>
                                <input type="number" name="idade_minima" value="<?= $config['idade_minima'] ?>" class="form-control" style="max-width: 100px;">
                            </div>
                            <div class="form-group">
                                <label>Token Mapbox</label>
                                <input type="text" name="mapbox_token" value="<?= htmlspecialchars($config['mapbox_token']) ?>" class="form-control">
                                <small class="text-muted">Obtenha em <a href="https://account.mapbox.com/" target="_blank">account.mapbox.com</a></small>
                            </div>
                            <div class="form-group">
                                <label>Email de Suporte</label>
                                <input type="email" name="email_suporte" value="<?= htmlspecialchars($config['email_suporte']) ?>" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg" style="margin-top: 16px;">Salvar Configurações</button>
            </form>
        </div>
    </main>
    
    <script src="../assets/js/admin.js"></script>
    <script>
    // Tabs
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
        });
    });
    
    // Toggle Cobrança
    document.getElementById('toggleCobranca').addEventListener('change', function() {
        document.getElementById('cobrancaConfig').classList.toggle('cobranca-disabled', !this.checked);
    });
    
    // Modelo Selection
    document.querySelectorAll('.modelo-card').forEach(card => {
        card.addEventListener('click', function() {
            document.querySelectorAll('.modelo-card').forEach(c => {
                c.classList.remove('selected');
                c.querySelector('.modelo-config') && (c.querySelector('.modelo-config').style.display = 'none');
            });
            this.classList.add('selected');
            this.querySelector('input').checked = true;
            const config = this.querySelector('.modelo-config');
            if (config) config.style.display = 'block';
        });
    });
    </script>
</body>
</html>
