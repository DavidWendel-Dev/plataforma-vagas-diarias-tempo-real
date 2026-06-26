<?php
/**
 * API: Diárias (CRUD e operações)
 */
require_once __DIR__ . '/../app.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'listar':
        listarDiarias();
        break;
        
    case 'salvar':
        salvarDiaria();
        break;
        
    case 'excluir':
        excluirDiaria();
        break;
        
    case 'duplicar':
        duplicarDiaria();
        break;
        
    case 'cancelar':
        cancelarDiaria();
        break;
        
    case 'compartilhar':
        gerarLinkCompartilhamento();
        break;
        
    case 'verificar_novas':
        verificarNovasVagas();
        break;
        
    default:
        jsonResponse(['error' => 'Ação inválida.'], 400);
}

/**
 * Listar diárias com filtros
 */
function listarDiarias() {
    $status = sanitize($_GET['status'] ?? '');
    $empresaId = (int)($_GET['empresa_id'] ?? 0);
    $dataInicio = sanitize($_GET['data_inicio'] ?? '');
    $dataFim = sanitize($_GET['data_fim'] ?? '');
    $pagina = (int)($_GET['pagina'] ?? 1);
    $porPagina = 20;
    $offset = ($pagina - 1) * $porPagina;
    
    $where = ['1=1'];
    $params = [];
    
    if ($status) {
        $where[] = 'd.status = :status';
        $params['status'] = $status;
    }
    
    if ($empresaId) {
        $where[] = 'd.empresa_id = :empresa_id';
        $params['empresa_id'] = $empresaId;
    }
    
    if ($dataInicio) {
        $where[] = 'd.data_evento >= :data_inicio';
        $params['data_inicio'] = $dataInicio;
    }
    
    if ($dataFim) {
        $where[] = 'd.data_evento <= :data_fim';
        $params['data_fim'] = $dataFim;
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Total
    $total = db()->fetch(
        "SELECT COUNT(*) as total FROM diarias d WHERE {$whereClause}",
        $params
    )['total'];
    
    // Dados
    $diarias = db()->fetchAll(
        "SELECT d.*, e.razao_social, u.nome as empresa_nome,
                (SELECT COUNT(*) FROM candidaturas WHERE diaria_id = d.id AND status != 'cancelada') as inscritos
         FROM diarias d
         JOIN empresas e ON d.empresa_id = e.id
         JOIN usuarios u ON e.usuario_id = u.id
         WHERE {$whereClause}
         ORDER BY d.data_evento DESC, d.created_at DESC
         LIMIT {$offset}, {$porPagina}",
        $params
    );
    
    jsonResponse([
        'success' => true,
        'data' => $diarias,
        'pagination' => [
            'total' => (int)$total,
            'pagina' => $pagina,
            'por_pagina' => $porPagina,
            'total_paginas' => ceil($total / $porPagina)
        ]
    ]);
}

/**
 * Salvar (criar/editar) diária
 */
function salvarDiaria() {
    global $auth;
    
    $id = (int)($_POST['id'] ?? 0);
    $empresaId = (int)($_POST['empresa_id'] ?? 0);
    $titulo = sanitize($_POST['titulo'] ?? '');
    $descricao = sanitize($_POST['descricao'] ?? '');
    $funcao = sanitize($_POST['funcao'] ?? '');
    $valor = (float)($_POST['valor'] ?? 0);
    $formaPagamento = sanitize($_POST['forma_pagamento'] ?? 'na_hora');
    $dataEvento = sanitize($_POST['data_evento'] ?? '');
    $horarioInicio = sanitize($_POST['horario_inicio'] ?? '');
    $horarioFim = sanitize($_POST['horario_fim'] ?? '');
    $vagasTotal = (int)($_POST['vagas_total'] ?? 1);
    $endereco = sanitize($_POST['endereco'] ?? '');
    $latitude = (float)($_POST['latitude'] ?? 0);
    $longitude = (float)($_POST['longitude'] ?? 0);
    $cidade = sanitize($_POST['cidade'] ?? '');
    $estado = sanitize($_POST['estado'] ?? '');
    $observacoes = sanitize($_POST['observacoes'] ?? '');
    
    // Validações
    if (empty($empresaId) || empty($titulo) || empty($funcao) || empty($dataEvento)) {
        jsonResponse(['error' => 'Preencha todos os campos obrigatórios.'], 400);
    }
    
    if ($valor <= 0) {
        jsonResponse(['error' => 'O valor da diária deve ser maior que zero.'], 400);
    }
    
    if ($vagasTotal < 1) {
        jsonResponse(['error' => 'Número de vagas inválido.'], 400);
    }
    
    $data = [
        'empresa_id' => $empresaId,
        'titulo' => $titulo,
        'descricao' => $descricao,
        'funcao' => $funcao,
        'valor' => $valor,
        'forma_pagamento' => $formaPagamento,
        'data_evento' => $dataEvento,
        'horario_inicio' => $horarioInicio,
        'horario_fim' => $horarioFim,
        'vagas_total' => $vagasTotal,
        'endereco' => $endereco,
        'latitude' => $latitude ?: null,
        'longitude' => $longitude ?: null,
        'cidade' => $cidade,
        'estado' => $estado,
        'observacoes' => $observacoes,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    try {
        if ($id > 0) {
            // Editar
            $existing = db()->fetch("SELECT vagas_preenchidas FROM diarias WHERE id = :id", ['id' => $id]);
            
            if (!$existing) {
                jsonResponse(['error' => 'Diária não encontrada.'], 404);
            }
            
            // Verificar se está reduzindo vagas abaixo do preenchido
            if ($vagasTotal < $existing['vagas_preenchidas']) {
                jsonResponse(['error' => 'Não é possível reduzir vagas abaixo do número já preenchido.'], 400);
            }
            
            db()->update('diarias', $data, 'id = :id', ['id' => $id]);
            
            logAction('diaria_editada', 'diarias', $id, null, $data);
            
            jsonResponse(['success' => true, 'message' => 'Diária atualizada com sucesso!']);
        } else {
            // Criar
            $data['status'] = 'ativa';
            $data['codigo_checkin'] = strtoupper(substr(md5(uniqid()), 0, 6));
            $data['created_by'] = userId();
            $data['created_at'] = date('Y-m-d H:i:s');
            
            $novoId = db()->insert('diarias', $data);
            
            logAction('diaria_criada', 'diarias', $novoId, null, $data);
            
            jsonResponse(['success' => true, 'message' => 'Diária criada com sucesso!', 'id' => $novoId]);
        }
    } catch (Exception $e) {
        if (APP_DEBUG) {
            jsonResponse(['error' => $e->getMessage()], 500);
        }
        jsonResponse(['error' => 'Erro ao salvar diária.'], 500);
    }
}

/**
 * Excluir diária
 */
function excluirDiaria() {
    $id = (int)($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        jsonResponse(['error' => 'ID inválido.'], 400);
    }
    
    // Verificar se há candidaturas
    $candidaturas = db()->fetch(
        "SELECT COUNT(*) as total FROM candidaturas WHERE diaria_id = :id AND status != 'cancelada'",
        ['id' => $id]
    );
    
    if ($candidaturas['total'] > 0) {
        jsonResponse(['error' => 'Não é possível excluir uma diária com candidaturas ativas.'], 400);
    }
    
    db()->delete('diarias', 'id = :id', ['id' => $id]);
    
    logAction('diaria_excluida', 'diarias', $id);
    
    jsonResponse(['success' => true, 'message' => 'Diária excluída com sucesso!']);
}

/**
 * Duplicar diária
 */
function duplicarDiaria() {
    $id = (int)($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        jsonResponse(['error' => 'ID inválido.'], 400);
    }
    
    $diaria = db()->fetch("SELECT * FROM diarias WHERE id = :id", ['id' => $id]);
    
    if (!$diaria) {
        jsonResponse(['error' => 'Diária não encontrada.'], 404);
    }
    
    // Remover campos que não devem ser duplicados
    unset($diaria['id'], $diaria['vagas_preenchidas'], $diaria['updated_at']);
    
    $diaria['vagas_preenchidas'] = 0;
    $diaria['status'] = 'ativa';
    $diaria['created_at'] = date('Y-m-d H:i:s');
    $diaria['created_by'] = userId();
    $diaria['titulo'] = 'Cópia: ' . $diaria['titulo'];
    
    $novoId = db()->insert('diarias', $diaria);
    
    logAction('diaria_duplicada', 'diarias', $novoId, null, $diaria);
    
    jsonResponse(['success' => true, 'message' => 'Diária duplicada com sucesso!', 'id' => $novoId]);
}

/**
 * Cancelar diária
 */
function cancelarDiaria() {
    $id = (int)($_POST['id'] ?? 0);
    $motivo = sanitize($_POST['motivo'] ?? '');
    
    if ($id <= 0) {
        jsonResponse(['error' => 'ID inválido.'], 400);
    }
    
    db()->update(
        'diarias',
        ['status' => 'cancelada', 'observacoes' => $motivo],
        'id = :id',
        ['id' => $id]
    );
    
    // Cancelar candidaturas
    db()->update(
        'candidaturas',
        ['status' => 'cancelada'],
        'diaria_id = :diaria_id',
        ['diaria_id' => $id]
    );
    
    logAction('diaria_cancelada', 'diarias', $id);
    
    jsonResponse(['success' => true, 'message' => 'Diária cancelada com sucesso!']);
}

/**
 * Gerar link e texto para compartilhamento
 */
function gerarLinkCompartilhamento() {
    $id = (int)($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        jsonResponse(['error' => 'ID inválido.'], 400);
    }
    
    $diaria = db()->fetch(
        "SELECT d.*, e.razao_social FROM diarias d JOIN empresas e ON d.empresa_id = e.id WHERE d.id = :id",
        ['id' => $id]
    );
    
    if (!$diaria) {
        jsonResponse(['error' => 'Diária não encontrada.'], 404);
    }
    
    $vagasRestantes = $diaria['vagas_total'] - $diaria['vagas_preenchidas'];
    $link = APP_URL . '/app/diaria.php?id=' . $id;
    
    $texto = "🆕 *NOVA DIÁRIA DISPONÍVEL!*\n\n";
    $texto .= "📋 *" . $diaria['titulo'] . "*\n";
    $texto .= "🏢 " . ($diaria['razao_social'] ?: 'Empresa') . "\n";
    $texto .= "💼 " . $diaria['funcao'] . "\n";
    $texto .= "💰 " . formatMoney($diaria['valor']) . "\n";
    $texto .= "📅 " . formatDate($diaria['data_evento']) . "\n";
    $texto .= "⏰ " . $diaria['horario_inicio'] . " - " . $diaria['horario_fim'] . "\n";
    $texto .= "📍 " . $diaria['endereco'] . "\n";
    $texto .= "✅ " . $vagasRestantes . " vaga(s) disponível(is)\n\n";
    $texto .= "📲 Garanta sua vaga agora:\n" . $link;
    
    $whatsappLink = "https://wa.me/?text=" . urlencode($texto);
    
    jsonResponse([
        'success' => true,
        'link' => $link,
        'texto' => $texto,
        'whatsapp' => $whatsappLink
    ]);
}

/**
 * Verificar novas vagas (para notificação no app)
 */
function verificarNovasVagas() {
    $db = Database::getInstance();
    $ultima = (int)($_GET['ultima'] ?? 0);
    
    // Buscar diárias criadas após o timestamp
    $diarias = $db->fetchAll(
        "SELECT id, titulo, funcao, valor, vagas_total, vagas_preenchidas, 
                data_evento, horario_inicio, cidade
         FROM diarias 
         WHERE status = 'ativa' 
         AND data_evento >= CURDATE()
         AND UNIX_TIMESTAMP(created_at) > :ultima
         AND (vagas_total - vagas_preenchidas) > 0
         ORDER BY created_at DESC",
        ['ultima' => $ultima]
    );
    
    jsonResponse([
        'success' => true,
        'novas' => count($diarias),
        'diarias' => $diarias
    ]);
}
