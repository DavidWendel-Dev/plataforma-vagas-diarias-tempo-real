<?php
require_once __DIR__ . '/../app.php';
$auth = new Auth();
$auth->requireType('empresa');
$db = Database::getInstance();

$empresa = $db->fetch("SELECT e.* FROM empresas e WHERE e.usuario_id = :uid", ['uid' => userId()]);

// Cobrança ativa?
$cobrancaAtiva = getConfig('cobranca_ativa', '0') === '1';

// Criar evento
if (isPublicarVagasPermitido() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'criar') {
    $valor = (float)$_POST['valor'];
    $vagas = (int)$_POST['vagas_total'];
    $dataEvento = $_POST['data_evento'];
    
    // Calcular cobrança se ativa
    if ($cobrancaAtiva) {
        $cobranca = calcularCobranca($valor, $vagas, $dataEvento);
        $valorEmpresa = $cobranca['valor_empresa'];
        $taxaAgencia = $cobranca['taxa_agencia'];
        $modeloCobranca = $cobranca['modelo'];
    } else {
        $valorEmpresa = $valor;
        $taxaAgencia = 0;
        $modeloCobranca = 'sem_taxa';
    }
    
    $db->insert('diarias', [
        'empresa_id' => $empresa['id'],
        'titulo' => sanitize($_POST['titulo']),
        'funcao' => sanitize($_POST['funcao']),
        'valor' => $valor,
        'valor_empresa' => $valorEmpresa,
        'taxa_agencia' => $taxaAgencia,
        'modelo_cobranca' => $modeloCobranca,
        'vagas_total' => $vagas,
        'vagas_preenchidas' => 0,
        'data_evento' => $dataEvento,
        'horario_inicio' => $_POST['horario_inicio'],
        'horario_fim' => $_POST['horario_fim'],
        'endereco' => sanitize($_POST['endereco']),
        'cidade' => sanitize($_POST['cidade'] ?? ''),
        'estado' => sanitize($_POST['estado'] ?? ''),
        'latitude' => $_POST['latitude'] ?: null,
        'longitude' => $_POST['longitude'] ?: null,
        'forma_pagamento' => 'na_hora',
        'codigo_checkin' => strtoupper(substr(md5(uniqid()), 0, 6)),
        'status' => 'ativa',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    redirect('eventos.php?success=1');
}

$eventos = $db->fetchAll("SELECT d.*, (d.vagas_total - d.vagas_preenchidas) as vagas_restantes FROM diarias d WHERE d.empresa_id = :eid ORDER BY d.data_evento DESC", ['eid' => $empresa['id']]);

$funcoesConfig = $db->fetch("SELECT valor FROM configuracoes WHERE chave = 'funcoes_disponiveis'");
$funcoes = $funcoesConfig ? json_decode($funcoesConfig['valor'], true) : ['Garçom', 'Segurança', 'Recepcionista', 'Bartender', 'Limpeza', 'Montador'];

$user = $auth->getUser();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eventos - <?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>.admin-sidebar{background:linear-gradient(180deg,#10B981,#059669)}.nav-link{color:#fff}.nav-link:hover{background:rgba(255,255,255,0.15)}.nav-link.active{background:rgba(255,255,255,0.2)}</style>
    <style>
        /* Tabela responsiva em cards no mobile */
        .eventos-table { width: 100%; border-collapse: collapse; }
        .eventos-table th, .eventos-table td { padding: 12px; text-align: left; }
        
        @media (max-width: 768px) {
            .table-responsive { overflow-x: visible !important; }
            .eventos-table thead { display: none; }
            .eventos-table, .eventos-table tbody, .eventos-table tr, .eventos-table td { display: block; width: 100%; }
            .eventos-table tr { 
                border: 1px solid #E5E7EB; 
                border-radius: 12px; 
                margin-bottom: 12px; 
                padding: 8px;
                background: white;
            }
            .eventos-table tr:hover { background: #F9FAFB; }
            .eventos-table td { 
                border: none; 
                padding: 8px 12px; 
                display: flex; 
                justify-content: space-between; 
                align-items: center; 
                gap: 12px;
            }
            .eventos-table td:before { 
                content: attr(data-label);
                font-weight: 600;
                color: #6B7280;
                font-size: 0.8125rem;
            }
            .eventos-table td:empty { display: none; }
            .row-empty td { display: block !important; text-align: center; }
        }
    </style>
</head>
<body class="admin-body">
    <?php include 'includes/sidebar.php'; ?>
    <main class="admin-main">
        <?php include 'includes/header.php'; ?>
        <div class="admin-content">
            <div class="page-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
                <h1 style="margin:0;">Eventos</h1>
                <?php if (isPublicarVagasPermitido()): ?>
                <button class="btn btn-primary" onclick="document.getElementById('modalNova').style.display='flex'">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:8px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                    Nova Diária
                </button>
                <?php endif; ?>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success" style="background:#D1FAE5;color:#065F46;padding:12px 16px;border-radius:8px;margin-bottom:16px">✓ Evento criado com sucesso!</div>
            <?php endif; ?>
            
            <?php if (!isPublicarVagasPermitido()): ?>
            <div class="alert alert-info" style="background:#FEF3C7;color:#92400E;padding:12px 16px;border-radius:8px;margin-bottom:16px;display:flex;align-items:center;gap:10px">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <div>
                    <strong>Criação desativada</strong><br>
                    <small>A criação de eventos foi desativada pelo admin. Suas diárias já criadas (incluindo as criadas pelo admin para você) aparecem abaixo.</small>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="table-responsive">
                    <table class="table eventos-table">
                        <thead><tr><th>Título</th><th>Função</th><th>Data</th><th>Vagas</th><th>Valor</th><th>Código Check-in</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php if (empty($eventos)): ?>
                            <tr class="row-empty"><td colspan="7" class="text-center py-8 text-muted">Nenhum evento cadastrado</td></tr>
                            <?php else: ?>
                            <?php foreach ($eventos as $e): ?>
                            <tr style="cursor:pointer" onclick="window.location.href='evento.php?id=<?php echo $e['id']; ?>'">
                                <td data-label="Título"><strong><?php echo sanitize($e['titulo']); ?></strong></td>
                                <td data-label="Função"><?php echo sanitize($e['funcao']); ?></td>
                                <td data-label="Data"><?php echo formatDate($e['data_evento']); ?></td>
                                <td data-label="Vagas"><span class="<?php echo $e['vagas_restantes']>0?'text-success':'text-danger'?>"><?php echo $e['vagas_preenchidas'];?>/<?php echo $e['vagas_total'];?></span></td>
                                <td data-label="Valor"><?php echo formatMoney($e['valor']); ?></td>
                                <td data-label="Código Check-in">
                                    <?php if ($e['codigo_checkin']): ?>
                                    <code style="background:#FEF3C7;color:#92400E;padding:4px 10px;border-radius:6px;font-weight:700;font-size:0.875rem;letter-spacing:2px"><?php echo sanitize($e['codigo_checkin']); ?></code>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Status"><span class="badge badge-<?php echo $e['status']==='ativa'?'success':($e['status']==='cancelada'?'danger':'secondary')?>"><?php echo ucfirst($e['status']);?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Modal Nova Diária -->
    <div id="modalNova" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:200;align-items:center;justify-content:center">
        <div style="background:white;border-radius:16px;width:100%;max-width:500px;max-height:90vh;overflow-y:auto">
            <div style="padding:20px;border-bottom:1px solid #E5E7EB;display:flex;justify-content:space-between;align-items:center">
                <h2 style="margin:0;font-size:1.25rem">Nova Diária</h2>
                <button onclick="document.getElementById('modalNova').style.display='none'" style="background:none;border:none;font-size:24px;cursor:pointer">&times;</button>
            </div>
            <form method="POST" style="padding:20px">
                <input type="hidden" name="action" value="criar">
                <div class="form-group"><label>Título *</label><input type="text" name="titulo" class="form-control" required></div>
                <div class="form-group"><label>Função *</label><select name="funcao" class="form-control" required>
                    <option value="">Selecione</option>
                    <?php foreach ($funcoes as $f): ?><option value="<?php echo $f;?>"><?php echo $f;?></option><?php endforeach; ?>
                </select></div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group"><label>Valor *</label><input type="number" name="valor" class="form-control" step="0.01" required></div>
                    <div class="form-group"><label>Vagas *</label><input type="number" name="vagas_total" class="form-control" min="1" value="1" required></div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
                    <div class="form-group"><label>Data *</label><input type="date" name="data_evento" class="form-control" required></div>
                    <div class="form-group"><label>Início *</label><input type="time" name="horario_inicio" class="form-control" required></div>
                    <div class="form-group"><label>Fim *</label><input type="time" name="horario_fim" class="form-control" required></div>
                </div>
                <div class="form-group"><label>Endereço *</label><input type="text" name="endereco" class="form-control" required></div>
                <input type="hidden" name="latitude"><input type="hidden" name="longitude">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group"><label>Cidade</label><input type="text" name="cidade" class="form-control"></div>
                    <div class="form-group"><label>Estado</label><input type="text" name="estado" class="form-control" maxlength="2"></div>
                </div>
                <div style="display:flex;gap:12px;margin-top:20px">
                    <button type="button" onclick="document.getElementById('modalNova').style.display='none'" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Criar</button>
                </div>
            </form>
        </div>
    </div>
    <script src="../assets/js/admin.js"></script>
</body>
</html>
