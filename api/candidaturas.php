<?php
// API Simples - candidaturas e check-in
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

// Usar $_REQUEST que combina GET+POST+COOKIE (funciona com FormData e JSON)
$action = $_REQUEST['action'] ?? '';
$diariaId = isset($_REQUEST['diaria_id']) ? (int)$_REQUEST['diaria_id'] : 0;
$prestadorId = isset($_REQUEST['prestador_id']) ? (int)$_REQUEST['prestador_id'] : 0;
$candidaturaId = isset($_REQUEST['candidatura_id']) ? (int)$_REQUEST['candidatura_id'] : 0;
$codigo = isset($_REQUEST['codigo']) ? trim($_REQUEST['codigo']) : '';
$status = isset($_REQUEST['status']) ? trim($_REQUEST['status']) : '';

// Log para debug
error_log('=== candidaturas.php ===');
error_log('$_REQUEST: ' . json_encode($_REQUEST));
error_log('action: ' . $action . ', diaria_id: ' . $diariaId . ', prestador_id: ' . $prestadorId . ', candidatura_id: ' . $candidaturaId);
error_log('================================');

if (!$action) {
    echo json_encode(['error' => 'Ação inválida'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Carregar app.php para usar Database com configs do .env
require_once __DIR__ . '/../app.php';

// Usar PDO via Database::getInstance() (já configurado pelo app.php)
$db = Database::getInstance();
$pdo = $db->getConnection();

switch ($action) {
    case 'garantir':
        garantirVaga($pdo, $diariaId, $prestadorId);
        break;
    
    case 'cancelar':
        cancelarCandidatura($pdo, $diariaId, $prestadorId);
        break;
    
    case 'checkin':
        realizarCheckin($pdo, $candidaturaId, $codigo);
        break;
    
    case 'checkin_empresa':
        marcarCheckinEmpresa($pdo, $candidaturaId, $status);
        break;
    
    default:
        echo json_encode(['error' => 'Ação inválida'], JSON_UNESCAPED_UNICODE);
}

function garantirVaga($pdo, $diariaId, $prestadorId) {
    if ($diariaId <= 0 || $prestadorId <= 0) {
        echo json_encode(['error' => 'Dados inválidos'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM diarias WHERE id = ? AND status = 'ativa'");
        $stmt->execute([$diariaId]);
        $diaria = $stmt->fetch();
        
        if (!$diaria) {
            echo json_encode(['error' => 'Diária não encontrada'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        if ($diaria['vagas_preenchidas'] >= $diaria['vagas_total']) {
            echo json_encode(['error' => 'Vagas esgotadas'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $stmt = $pdo->prepare("SELECT id FROM candidaturas WHERE diaria_id = ? AND prestador_id = ? AND status != 'cancelada'");
        $stmt->execute([$diariaId, $prestadorId]);
        
        if ($stmt->fetch()) {
            echo json_encode(['error' => 'Você já está inscrito'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $stmt = $pdo->prepare("INSERT INTO candidaturas (diaria_id, prestador_id, status) VALUES (?, ?, 'confirmada')");
        $stmt->execute([$diariaId, $prestadorId]);
        
        $stmt = $pdo->prepare("UPDATE diarias SET vagas_preenchidas = vagas_preenchidas + 1 WHERE id = ?");
        $stmt->execute([$diariaId]);
        
        echo json_encode(['success' => true, 'message' => 'Vaga garantida!'], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        error_log('Erro garantirVaga: ' . $e->getMessage());
        echo json_encode(['error' => 'Erro no banco'], JSON_UNESCAPED_UNICODE);
    }
}

function cancelarCandidatura($pdo, $diariaId, $prestadorId) {
    if ($diariaId <= 0 || $prestadorId <= 0) {
        echo json_encode(['error' => 'Dados inválidos'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM candidaturas WHERE diaria_id = ? AND prestador_id = ? AND status = 'confirmada'");
        $stmt->execute([$diariaId, $prestadorId]);
        $candidatura = $stmt->fetch();
        
        if (!$candidatura) {
            echo json_encode(['error' => 'Candidatura não encontrada'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $stmt = $pdo->prepare("UPDATE candidaturas SET status = 'cancelada' WHERE id = ?");
        $stmt->execute([$candidatura['id']]);
        
        $stmt = $pdo->prepare("UPDATE diarias SET vagas_preenchidas = GREATEST(0, vagas_preenchidas - 1) WHERE id = ?");
        $stmt->execute([$diariaId]);
        
        echo json_encode(['success' => true, 'message' => 'Candidatura cancelada'], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        error_log('Erro cancelar: ' . $e->getMessage());
        echo json_encode(['error' => 'Erro no banco'], JSON_UNESCAPED_UNICODE);
    }
}

function realizarCheckin($pdo, $candidaturaId, $codigo) {
    if ($candidaturaId <= 0 || empty($codigo)) {
        echo json_encode(['error' => 'Dados inválidos'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    try {
        // Buscar candidatura com o código_checkin da diária
        $stmt = $pdo->prepare("SELECT c.*, d.codigo_checkin, d.status as diaria_status 
                               FROM candidaturas c 
                               JOIN diarias d ON c.diaria_id = d.id 
                               WHERE c.id = ? AND c.status = 'confirmada'");
        $stmt->execute([$candidaturaId]);
        $candidatura = $stmt->fetch();
        
        if (!$candidatura) {
            echo json_encode(['error' => 'Candidatura não encontrada ou já confirmada'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Verificar código (case-insensitive)
        if (strcasecmp($candidatura['codigo_checkin'], $codigo) !== 0) {
            echo json_encode(['error' => 'Código de check-in incorreto'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Atualizar status para checkin_realizado e registrar data_checkin
        $stmt = $pdo->prepare("UPDATE candidaturas SET status = 'checkin_realizado', data_checkin = NOW() WHERE id = ?");
        $stmt->execute([$candidaturaId]);
        
        echo json_encode(['success' => true, 'message' => 'Check-in realizado com sucesso!'], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        error_log('Erro checkin: ' . $e->getMessage());
        echo json_encode(['error' => 'Erro no banco'], JSON_UNESCAPED_UNICODE);
    }
}

function marcarCheckinEmpresa($pdo, $candidaturaId, $status) {
    if ($candidaturaId <= 0) {
        echo json_encode(['error' => 'Dados inválidos'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Validar status
    $statusValido = ['confirmada', 'checkin_realizado', 'faltou'];
    if (!in_array($status, $statusValido)) {
        echo json_encode(['error' => 'Status inválido'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    try {
        // Verificar se candidatura existe
        $stmt = $pdo->prepare("SELECT id FROM candidaturas WHERE id = ?");
        $stmt->execute([$candidaturaId]);
        
        if (!$stmt->fetch()) {
            echo json_encode(['error' => 'Candidatura não encontrada'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Atualizar status
        if ($status === 'checkin_realizado') {
            // Em check-in, registrar data_checkin
            $stmt = $pdo->prepare("UPDATE candidaturas SET status = ?, data_checkin = NOW() WHERE id = ?");
            $stmt->execute([$status, $candidaturaId]);
        } else {
            // Em confirmada ou faltou, limpar data_checkin se voltar para confirmada
            if ($status === 'confirmada') {
                $stmt = $pdo->prepare("UPDATE candidaturas SET status = ?, data_checkin = NULL WHERE id = ?");
                $stmt->execute([$status, $candidaturaId]);
            } else {
                $stmt = $pdo->prepare("UPDATE candidaturas SET status = ? WHERE id = ?");
                $stmt->execute([$status, $candidaturaId]);
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Status atualizado!'], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        error_log('Erro checkin_empresa: ' . $e->getMessage());
        echo json_encode(['error' => 'Erro no banco'], JSON_UNESCAPED_UNICODE);
    }
}
?>
