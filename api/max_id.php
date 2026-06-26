<?php
/**
 * API: Retorna o maior ID de diária ativa
 * Usado para sincronizar o cliente com o estado atual do banco
 */
require_once __DIR__ . '/../app.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store, must-revalidate');

$db = Database::getInstance();

$result = $db->fetch(
    "SELECT MAX(id) as max_id FROM diarias WHERE status = 'ativa' AND data_evento >= CURDATE()"
);

$maxId = (int)($result['max_id'] ?? 0);

// TAMBÉM retornar se houve mudança desde o último check
$lastCheck = isset($_GET['since']) ? (int)$_GET['since'] : 0;
$hasNew = $maxId > $lastCheck;

echo json_encode([
    'success' => true,
    'max_id' => $maxId,
    'has_new' => $hasNew,
    'count' => $hasNew ? ($maxId - $lastCheck) : 0
], JSON_UNESCAPED_UNICODE);
