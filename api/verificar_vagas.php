<?php
/**
 * API: Verificar Novas Vagas (Tempo Real)
 * Retorna diárias com ID maior que o último ID conhecido
 */
require_once __DIR__ . '/../app.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache, must-revalidate');

$db = Database::getInstance();

// Receber o último ID conhecido
$ultimoId = (int)($_GET['ultimo_id'] ?? 0);

// Buscar diárias com ID maior
$diarias = $db->fetchAll(
    "SELECT d.id, d.titulo, d.funcao, d.valor, d.vagas_total, d.vagas_preenchidas, 
            d.data_evento, d.horario_inicio, d.horario_fim, d.cidade, d.endereco,
            e.razao_social, u.nome as empresa_nome
     FROM diarias d
     JOIN empresas e ON d.empresa_id = e.id
     JOIN usuarios u ON e.usuario_id = u.id
     WHERE d.status = 'ativa' 
     AND d.data_evento >= CURDATE()
     AND d.id > :ultimo_id
     AND (d.vagas_total - d.vagas_preenchidas) > 0
     ORDER BY d.id DESC",
    ['ultimo_id' => $ultimoId]
);

echo json_encode([
    'success' => true,
    'total' => count($diarias),
    'diarias' => $diarias
], JSON_UNESCAPED_UNICODE);
