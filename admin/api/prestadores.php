<?php
require_once __DIR__ . '/../../app.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn() || userTipo() !== 'admin') {
    jsonResponse(['error' => 'Acesso negado'], 401);
}

$db = Database::getInstance();

// Aceitar JSON ou POST normal
$input = json_decode(file_get_contents('php://input'), true);
if ($input) {
    $_POST = array_merge($_POST, $input);
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'aprovar':
    case 'rejeitar':
    case 'suspender':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) jsonResponse(['error' => 'ID inválido'], 400);
        
        $status = $action === 'aprovar' ? 'aprovado' : ($action === 'rejeitar' ? 'rejeitado' : 'suspenso');
        
        $db->update('prestadores', ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $id]);
        
        jsonResponse(['success' => true]);
        break;
    
    default:
        jsonResponse(['error' => 'Ação inválida'], 400);
}
