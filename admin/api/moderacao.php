<?php
require_once __DIR__ . '/../../app.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn() || userTipo() !== 'admin') {
    jsonResponse(['error' => 'Acesso negado'], 401);
}

$db = Database::getInstance();

// Ler dados (JSON ou POST normal)
$input = json_decode(file_get_contents('php://input'), true);
if ($input) {
    $action = $input['action'] ?? '';
    $id = (int)($input['id'] ?? 0);
    $motivo = sanitize($input['motivo'] ?? '');
} else {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $motivo = sanitize($_POST['motivo'] ?? '');
}

switch ($action) {
    case 'aprovar':
        if ($id <= 0) jsonResponse(['error' => 'ID inválido'], 400);
        
        $db->update('prestadores', ['status' => 'aprovado', 'updated_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $id]);
        
        jsonResponse(['success' => true, 'message' => 'Prestador aprovado']);
        break;
    
    case 'rejeitar':
        if ($id <= 0) jsonResponse(['error' => 'ID inválido'], 400);
        
        $db->update('prestadores', [
            'status' => 'rejeitado',
            'motivo_rejeicao' => $motivo,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $id]);
        
        jsonResponse(['success' => true, 'message' => 'Prestador rejeitado']);
        break;
    
    default:
        jsonResponse(['error' => 'Ação inválida: ' . $action], 400);
}
