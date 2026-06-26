<?php
// API para listar e marcar notificações como lidas
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once __DIR__ . '/../app.php';

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Não autorizado'], JSON_UNESCAPED_UNICODE);
    exit;
}

$db = Database::getInstance();
$action = $_REQUEST['action'] ?? 'listar';

switch ($action) {
    case 'listar':
        // Listar 20 notificações mais recentes
        $notifs = $db->fetchAll(
            "SELECT id, tipo, titulo, mensagem, link, lida, 
                    DATE_FORMAT(created_at, '%d/%m/%Y %H:%i') as data, 
                    TIMESTAMPDIFF(MINUTE, created_at, NOW()) as minutos
             FROM notificacoes 
             WHERE usuario_id = ? 
             ORDER BY created_at DESC 
             LIMIT 20",
            [userId()]
        );
        
        // Contar não lidas
        $count = $db->fetch(
            "SELECT COUNT(*) as total FROM notificacoes WHERE usuario_id = ? AND lida = 0",
            [userId()]
        );
        
        echo json_encode([
            'success' => true,
            'notificacoes' => $notifs,
            'nao_lidas' => (int)($count['total'] ?? 0)
        ], JSON_UNESCAPED_UNICODE);
        break;
    
    case 'marcar_lida':
        $id = (int)($_REQUEST['id'] ?? 0);
        if ($id > 0) {
            $db->update('notificacoes', 
                ['lida' => 1],
                'id = :id AND usuario_id = :uid',
                ['id' => $id, 'uid' => userId()]
            );
        }
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
        break;
    
    case 'marcar_todas_lidas':
        $db->update('notificacoes', 
            ['lida' => 1],
            'usuario_id = :uid AND lida = 0',
            ['uid' => userId()]
        );
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
        break;
    
    case 'limpar':
        $db->delete('notificacoes', 'usuario_id = :uid', ['uid' => userId()]);
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
        break;
    
    default:
        echo json_encode(['error' => 'Ação inválida'], JSON_UNESCAPED_UNICODE);
}
?>
