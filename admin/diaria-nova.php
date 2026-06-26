<?php
require_once __DIR__ . '/../app.php';

$auth = new Auth();
$auth->requireType('admin');

$db = Database::getInstance();

// Carregar empresas para o select
$empresas = $db->fetchAll(
    "SELECT e.id, e.razao_social, u.nome FROM empresas e JOIN usuarios u ON e.usuario_id = u.id WHERE e.status = 'ativo' ORDER BY e.razao_social"
);

// Carregar funções disponíveis
$funcoesConfig = $db->fetch("SELECT valor FROM configuracoes WHERE chave = 'funcoes_disponiveis'");
$funcoes = $funcoesConfig ? json_decode($funcoesConfig['valor'], true) : ['Garçom', 'Segurança', 'Recepcionista', 'Bartender', 'Limpeza', 'Montador'];

// Carregar configurações de cobrança
$cobrancaAtiva = getConfig('cobranca_ativa', '0') === '1';
$modeloCobranca = getConfig('modelo_cobranca', 'spread');
$margemPadrao = (float)getConfig('margem_padrao', 20);
$taxaFixa = (float)getConfig('taxa_fixa_profissional', 30);

// Adicionar nova função via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_funcao') {
    header('Content-Type: application/json');
    $novaFuncao = trim(sanitize($_POST['funcao'] ?? ''));
    if (empty($novaFuncao)) {
        echo json_encode(['error' => 'Nome da função é obrigatório']);
        exit;
    }
    if (!in_array($novaFuncao, $funcoes)) {
        $funcoes[] = $novaFuncao;
        // Usar update direto pois registro já existe
        $db->update('configuracoes', ['valor' => json_encode($funcoes)], 'chave = :chave', ['chave' => 'funcoes_disponiveis']);
    }
    echo json_encode(['success' => true, 'funcoes' => $funcoes]);
    exit;
}

// Modo edição
$diaria = null;
$isEdit = false;

if (isset($_GET['id'])) {
    $diariaId = (int)$_GET['id'];
    $diaria = $db->fetch(
        "SELECT * FROM diarias WHERE id = :id",
        ['id' => $diariaId]
    );
    
    if ($diaria) {
        $isEdit = true;
    }
}

$user = $auth->getUser();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isEdit ? 'Editar' : 'Nova'; ?> Diária - <?php echo APP_NAME; ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Mapbox GL JS -->
    <script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet">
    
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-body">
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="admin-main">
        <?php include 'includes/header.php'; ?>
        
        <div class="admin-content">
            <div class="form-card">
                <div class="form-card-header">
                    <h2><?php echo $isEdit ? 'Editar Diária' : 'Nova Diária'; ?></h2>
                    <p>Preencha os dados da vaga de trabalho</p>
                </div>
                
                <form id="diariaForm" class="form-card-body">
                    <input type="hidden" name="id" value="<?php echo $diaria['id'] ?? ''; ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="empresa_id">Empresa <span class="required">*</span></label>
                            <select name="empresa_id" id="empresa_id" class="form-control" required>
                                <option value="">Selecione uma empresa</option>
                                <?php foreach ($empresas as $emp): ?>
                                <option value="<?php echo $emp['id']; ?>" <?php echo ($diaria['empresa_id'] ?? '') == $emp['id'] ? 'selected' : ''; ?>>
                                    <?php echo sanitize($emp['razao_social'] ?: $emp['nome']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="funcao">Função <span class="required">*</span></label>
                            <div style="display: flex; gap: 8px; align-items: flex-end;">
                                <select name="funcao" id="funcao" class="form-control" required style="flex: 1;">
                                    <option value="">Selecione a função</option>
                                    <?php foreach ($funcoes as $funcao): ?>
                                    <option value="<?php echo $funcao; ?>" <?php echo ($diaria['funcao'] ?? '') == $funcao ? 'selected' : ''; ?>>
                                        <?php echo $funcao; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalFuncao').style.display='flex'" title="Adicionar nova função">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="titulo">Título da Vaga <span class="required">*</span></label>
                        <input type="text" name="titulo" id="titulo" class="form-control" 
                               value="<?php echo sanitize($diaria['titulo'] ?? ''); ?>" 
                               placeholder="Ex: Garçom para Casamento" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="descricao">Descrição</label>
                        <textarea name="descricao" id="descricao" class="form-control" rows="3" 
                                  placeholder="Detalhes adicionais sobre a vaga..."><?php echo sanitize($diaria['descricao'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-row cols-3">
                        <div class="form-group">
                            <label for="valor">Valor da Diária <span class="required">*</span></label>
                            <input type="number" name="valor" id="valor" class="form-control" 
                                   value="<?php echo $diaria['valor'] ?? ''; ?>" 
                                   step="0.01" min="0" placeholder="0.00" required>
                            <small class="text-muted">Valor pago ao prestador</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="forma_pagamento">Pagamento</label>
                            <select name="forma_pagamento" id="forma_pagamento" class="form-control">
                                <option value="na_hora" <?php echo ($diaria['forma_pagamento'] ?? '') == 'na_hora' ? 'selected' : ''; ?>>
                                    Na hora
                                </option>
                                <option value="posterior" <?php echo ($diaria['forma_pagamento'] ?? '') == 'posterior' ? 'selected' : ''; ?>>
                                    Posterior
                                </option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="vagas_total">Número de Vagas <span class="required">*</span></label>
                            <input type="number" name="vagas_total" id="vagas_total" class="form-control" 
                                   value="<?php echo $diaria['vagas_total'] ?? 1; ?>" 
                                   min="1" max="100" required>
                        </div>
                    </div>
                    
                    <?php if ($cobrancaAtiva): ?>
                    <!-- Box de Cálculo de Cobrança -->
                    <div id="cobrancaCalc" class="card" style="margin-bottom: 24px; background: linear-gradient(135deg, #EEF2FF, #E0E7FF); border: 2px solid #A5B4FC;">
                        <div class="card-body" style="padding: 16px;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                                <span style="font-size: 20px;">💰</span>
                                <strong style="color: #4F46E5;">Cálculo Automático de Cobrança</strong>
                                <span class="badge" style="background: #C7D2FE; color: #4338CA; font-size: 11px;">
                                    <?php 
                                    $modelos = [
                                        'spread' => 'Margem ' . $margemPadrao . '%',
                                        'taxa_fixa' => 'Taxa Fixa R$' . number_format($taxaFixa, 2),
                                        'ambos' => 'Margem ' . $margemPadrao . '% + Taxa R$' . number_format($taxaFixa, 2),
                                        'personalizado' => 'Personalizado'
                                    ];
                                    echo $modelos[$modeloCobranca] ?? $modeloCobranca;
                                    ?>
                                </span>
                            </div>
                            
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; text-align: center;">
                                <div>
                                    <div style="font-size: 12px; color: #6B7280; margin-bottom: 4px;">Valor Prestador</div>
                                    <div id="calcValorPrestador" style="font-size: 20px; font-weight: 700; color: #6B7280;">R$ 0,00</div>
                                    <div style="font-size: 11px; color: #9CA3AF;">por profissional</div>
                                </div>
                                <div>
                                    <div style="font-size: 12px; color: #6B7280; margin-bottom: 4px;">Taxa Agência</div>
                                    <div id="calcTaxaAgencia" style="font-size: 20px; font-weight: 700; color: #10B981;">R$ 0,00</div>
                                    <div style="font-size: 11px; color: #9CA3AF;">seu lucro</div>
                                </div>
                                <div>
                                    <div style="font-size: 12px; color: #6B7280; margin-bottom: 4px;">Total Empresa</div>
                                    <div id="calcTotalEmpresa" style="font-size: 20px; font-weight: 700; color: #4F46E5;">R$ 0,00</div>
                                    <div style="font-size: 11px; color: #9CA3AF;">a cobrar</div>
                                </div>
                            </div>
                            
                            <div id="cobrancaDetalhes" style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #C7D2FE; font-size: 13px; color: #6366F1;">
                                <!-- Detalhes preenchidos via JS -->
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-row cols-3">
                        <div class="form-group">
                            <label for="data_evento">Data do Evento <span class="required">*</span></label>
                            <input type="date" name="data_evento" id="data_evento" class="form-control" 
                                   value="<?php echo $diaria['data_evento'] ?? ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="horario_inicio">Horário Início <span class="required">*</span></label>
                            <input type="time" name="horario_inicio" id="horario_inicio" class="form-control" 
                                   value="<?php echo $diaria['horario_inicio'] ?? ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="horario_fim">Horário Fim <span class="required">*</span></label>
                            <input type="time" name="horario_fim" id="horario_fim" class="form-control" 
                                   value="<?php echo $diaria['horario_fim'] ?? ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="endereco">Endereço do Evento <span class="required">*</span></label>
                        <input type="text" name="endereco" id="endereco" class="form-control" 
                               value="<?php echo sanitize($diaria['endereco'] ?? ''); ?>" 
                               placeholder="Rua, número, bairro, cidade" required>
                        <input type="hidden" name="latitude" id="latitude" value="<?php echo $diaria['latitude'] ?? ''; ?>">
                        <input type="hidden" name="longitude" id="longitude" value="<?php echo $diaria['longitude'] ?? ''; ?>">
                        <p class="form-hint">O endereço será geocodificado automaticamente para exibição no mapa</p>
                    </div>
                    
                    <!-- Map Preview -->
                    <div class="form-group">
                        <label>Localização no Mapa</label>
                        <div class="map-preview">
                            <div id="map"></div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="cidade">Cidade</label>
                            <input type="text" name="cidade" id="cidade" class="form-control" 
                                   value="<?php echo sanitize($diaria['cidade'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="estado">Estado</label>
                            <input type="text" name="estado" id="estado" class="form-control" 
                                   value="<?php echo sanitize($diaria['estado'] ?? ''); ?>" maxlength="2">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="observacoes">Observações</label>
                        <textarea name="observacoes" id="observacoes" class="form-control" rows="2" 
                                  placeholder="Informações adicionais..."><?php echo sanitize($diaria['observacoes'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <a href="diarias.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <span><?php echo $isEdit ? 'Salvar Alterações' : 'Criar Diária'; ?></span>
                            <div class="btn-loader hidden"></div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <script>
    // Mapbox Config
    mapboxgl.accessToken = '<?php echo MAPBOX_TOKEN; ?>';
    
    // Initialize Map
    let map;
    let marker;
    const defaultLat = <?php echo $diaria['latitude'] ?? '-23.5505'; ?>;
    const defaultLng = <?php echo $diaria['longitude'] ?? '-46.6333'; ?>;
    
    document.addEventListener('DOMContentLoaded', function() {
        initMap();
        initGeocoding();
        initForm();
    });
    
    function initMap() {
        map = new mapboxgl.Map({
            container: 'map',
            style: 'mapbox://styles/mapbox/streets-v12',
            center: [defaultLng, defaultLat],
            zoom: 14
        });
        
        map.addControl(new mapboxgl.NavigationControl(), 'top-right');
        
        // Add marker if coordinates exist
        if (defaultLat && defaultLng) {
            addMarker(defaultLng, defaultLat);
        }
        
        // Click to set location
        map.on('click', function(e) {
            setCoordinates(e.lngLat.lat, e.lngLat.lng);
        });
    }
    
    function addMarker(lng, lat) {
        if (marker) {
            marker.remove();
        }
        
        marker = new mapboxgl.Marker({ draggable: true })
            .setLngLat([lng, lat])
            .addTo(map);
        
        marker.on('dragend', function() {
            const lngLat = marker.getLngLat();
            setCoordinates(lngLat.lat, lngLat.lng);
        });
    }
    
    function setCoordinates(lat, lng) {
        document.getElementById('latitude').value = lat;
        document.getElementById('longitude').value = lng;
        addMarker(lng, lat);
        map.flyTo({ center: [lng, lat], zoom: 15 });
    }
    
    function initGeocoding() {
        const enderecoInput = document.getElementById('endereco');
        let geocodeTimeout;
        
        enderecoInput.addEventListener('input', function() {
            clearTimeout(geocodeTimeout);
            
            geocodeTimeout = setTimeout(() => {
                const endereco = this.value.trim();
                if (endereco.length >= 5) {
                    geocodeAddress(endereco);
                }
            }, 800);
        });
    }
    
    async function geocodeAddress(address) {
        try {
            const response = await fetch(
                `https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(address)}.json?access_token=${mapboxgl.accessToken}&country=BR&limit=1`
            );
            
            const data = await response.json();
            
            if (data.features && data.features.length > 0) {
                const feature = data.features[0];
                const [lng, lat] = feature.center;
                
                setCoordinates(lat, lng);
                
                // Update address fields if available
                const context = feature.context || [];
                context.forEach(item => {
                    if (item.id.startsWith('place')) {
                        document.getElementById('cidade').value = item.text;
                    }
                    if (item.id.startsWith('region')) {
                        document.getElementById('estado').value = item.short_code.replace('BR-', '');
                    }
                });
            }
        } catch (error) {
            console.error('Geocoding error:', error);
        }
    }
    
    <?php if ($cobrancaAtiva): ?>
    // Cálculo de cobrança em tempo real
    function initCobrancaCalc() {
        const valorInput = document.getElementById('valor');
        const vagasInput = document.getElementById('vagas_total');
        const dataInput = document.getElementById('data_evento');
        
        const modelo = '<?php echo $modeloCobranca; ?>';
        const margem = <?php echo $margemPadrao; ?>;
        const taxaFixa = <?php echo $taxaFixa; ?>;
        
        function calcular() {
            const valor = parseFloat(valorInput.value) || 0;
            const vagas = parseInt(vagasInput.value) || 1;
            const data = dataInput.value;
            
            let taxaAgencia = 0;
            let valorEmpresa = valor;
            let detalhes = '';
            
            if (modelo === 'spread') {
                taxaAgencia = valor * (margem / 100);
                valorEmpresa = valor + taxaAgencia;
                detalhes = vagas + ' prof. x R$ ' + valor.toFixed(2) + ' + ' + margem + '% margem';
            } else if (modelo === 'taxa_fixa') {
                taxaAgencia = taxaFixa * vagas;
                valorEmpresa = (valor * vagas) + taxaAgencia;
                detalhes = vagas + ' prof. x R$ ' + valor.toFixed(2) + ' + Taxa R$ ' + taxaFixa.toFixed(2) + ' cada';
            } else if (modelo === 'ambos') {
                const taxaSpread = valor * (margem / 100);
                taxaAgencia = taxaSpread + (taxaFixa * vagas);
                valorEmpresa = (valor * vagas) + taxaAgencia;
                detalhes = vagas + ' prof. x R$ ' + valor.toFixed(2) + ' + ' + margem + '% + Taxa R$ ' + (taxaFixa * vagas).toFixed(2);
            } else {
                return;
            }
            
            // Taxa urgência
            if (data) {
                const hoje = new Date();
                const dataEvt = new Date(data + 'T00:00:00');
                const horasAte = (dataEvt - hoje) / (1000 * 60 * 60);
                if (horasAte > 0 && horasAte < 48) {
                    const taxaUrg = <?php echo getConfig('taxa_urgencia_valor', 50); ?> * vagas;
                    taxaAgencia += taxaUrg;
                    valorEmpresa += taxaUrg;
                    detalhes += ' + Urgência R$ ' + taxaUrg.toFixed(2);
                }
            }
            
            document.getElementById('calcValorPrestador').textContent = 'R$ ' + valor.toFixed(2).replace('.', ',');
            document.getElementById('calcTaxaAgencia').textContent = 'R$ ' + taxaAgencia.toFixed(2).replace('.', ',');
            document.getElementById('calcTotalEmpresa').textContent = 'R$ ' + valorEmpresa.toFixed(2).replace('.', ',');
            document.getElementById('cobrancaDetalhes').textContent = detalhes;
        }
        
        valorInput.addEventListener('input', calcular);
        vagasInput.addEventListener('input', calcular);
        dataInput.addEventListener('change', calcular);
        if (valorInput.value) calcular();
    }
    <?php endif; ?>
    
    function initForm() {
        const form = document.getElementById('diariaForm');
        
        // Inicializar cálculo de cobrança se ativo
        <?php if ($cobrancaAtiva): ?>
        initCobrancaCalc();
        <?php endif; ?>
        
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btn = form.querySelector('button[type="submit"]');
            const btnText = btn.querySelector('span');
            const btnLoader = btn.querySelector('.btn-loader');
            
            // Validate coordinates
            const lat = document.getElementById('latitude').value;
            const lng = document.getElementById('longitude').value;
            
            if (!lat || !lng) {
                alert('Por favor, insira um endereço válido para geocodificar a localização.');
                return;
            }
            
            // Show loading
            btn.disabled = true;
            btnText.style.opacity = '0';
            btnLoader.classList.remove('hidden');
            
            const formData = new FormData(form);
            formData.append('action', 'salvar');
            
            try {
                const response = await fetch('../api/diarias.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    window.location.href = 'diarias.php?success=1';
                } else {
                    alert(data.error || 'Erro ao salvar diária.');
                }
            } catch (error) {
                alert('Erro de conexão. Tente novamente.');
            }
            
            // Hide loading
            btn.disabled = false;
            btnText.style.opacity = '1';
            btnLoader.classList.add('hidden');
        });
    }
    
    // Adicionar nova função
    async function addFuncao(e) {
        e.preventDefault();
        const input = document.getElementById('novaFuncao');
        const funcao = input.value.trim();
        if (!funcao) return alert('Digite o nome da função');
        
        try {
            const res = await fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=add_funcao&funcao=' + encodeURIComponent(funcao)
            });
            const data = await res.json();
            
            if (data.success) {
                // Atualizar select
                const select = document.getElementById('funcao');
                select.innerHTML = '<option value="">Selecione a função</option>';
                data.funcoes.forEach(f => {
                    const opt = document.createElement('option');
                    opt.value = f;
                    opt.textContent = f;
                    if (f === funcao) opt.selected = true;
                    select.appendChild(opt);
                });
                document.getElementById('modalFuncao').style.display = 'none';
                input.value = '';
            } else {
                alert(data.error || 'Erro');
            }
        } catch (err) {
            alert('Erro de conexão');
        }
    }
    </script>

    <!-- Modal Nova Função -->
    <div id="modalFuncao" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:200;align-items:center;justify-content:center;">
        <div style="background:white;border-radius:16px;padding:24px;width:100%;max-width:400px;">
            <h3 style="margin:0 0 16px;font-size:1.125rem;">Nova Função</h3>
            <form onsubmit="addFuncao(event)">
                <input type="text" id="novaFuncao" class="form-control" placeholder="Nome da função" required style="margin-bottom:16px;">
                <div style="display:flex;gap:12px;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalFuncao').style.display='none'" style="flex:1;">Cancelar</button>
                    <button type="submit" class="btn btn-primary" style="flex:1;">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
