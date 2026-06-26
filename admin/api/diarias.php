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
} else {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
}

switch ($action) {
    case 'cancelar':
        if ($id <= 0) jsonResponse(['error' => 'ID inválido'], 400);

        $diaria = $db->fetch("SELECT id, status FROM diarias WHERE id = :id", ['id' => $id]);
        if (!$diaria) jsonResponse(['error' => 'Diária não encontrada'], 404);
        if ($diaria['status'] === 'cancelada') jsonResponse(['error' => 'Diária já está cancelada'], 400);

        $db->update('diarias', [
            'status' => 'cancelada',
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $id]);

        jsonResponse(['success' => true, 'message' => 'Diária cancelada']);
        break;

    case 'excluir':
        if ($id <= 0) jsonResponse(['error' => 'ID inválido'], 400);

        $diaria = $db->fetch("SELECT id FROM diarias WHERE id = :id", ['id' => $id]);
        if (!$diaria) jsonResponse(['error' => 'Diária não encontrada'], 404);

        // Remover candidaturas relacionadas primeiro
        $db->delete('candidaturas', 'diaria_id = :id', ['id' => $id]);
        $db->delete('diarias', 'id = :id', ['id' => $id]);

        jsonResponse(['success' => true, 'message' => 'Diária excluída']);
        break;

    default:
        jsonResponse(['error' => 'Ação inválida: ' . $action], 400);
}
