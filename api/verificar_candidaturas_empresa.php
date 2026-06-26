<?php
// API para verificar novas candidaturas - toca som no painel empresa
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once __DIR__ . '/../app.php';

// Verificar se é empresa
if (!isLoggedIn() || userTipo() !== 'empresa') {
    echo json_encode(['error' => 'Não autorizado'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Verificar se o som está ativado nas configurações globais
// A empresa usa a mesma config do admin para som de nova candidatura
$ativo = getConfig('som_candidatura_admin', '0');
if ($ativo !== '1') {
    echo json_encode(['ativo' => false, 'som' => false], JSON_UNESCAPED_UNICODE);
    exit;
}

$db = Database::getInstance();

// Obter ID da empresa logada
$empresa = $db->fetch("SELECT id FROM empresas WHERE usuario_id = :uid", ['uid' => userId()]);
if (!$empresa) {
    echo json_encode(['error' => 'Empresa não encontrada'], JSON_UNESCAPED_UNICODE);
    exit;
}
$empresaId = (int)$empresa['id'];

// Pegar o último ID de candidatura visto
$ultimoId = isset($_GET['ultimo_id']) ? (int)$_GET['ultimo_id'] : 0;

// Se for 0, inicializar com o ID atual (apenas das diárias desta empresa)
if ($ultimoId === 0) {
    // Buscar todas as candidaturas confirmadas dessa empresa (para gerar notificações pendentes)
    $existentes = $db->fetchAll(
        "SELECT c.id, c.diaria_id, d.titulo, u.nome as prestador_nome
         FROM candidaturas c
         JOIN diarias d ON c.diaria_id = d.id
         JOIN prestadores p ON c.prestador_id = p.id
         JOIN usuarios u ON p.usuario_id = u.id
         WHERE c.status = 'confirmada' AND d.empresa_id = ?
         ORDER BY c.id ASC",
        [$empresaId]
    );
    
    // Gerar notificações para as candidaturas que ainda não têm
    foreach ($existentes as $c) {
        $link = 'evento.php?id=' . $c['diaria_id'];
        $existe = $db->fetch(
            "SELECT id FROM notificacoes WHERE usuario_id = ? AND tipo = 'candidatura_aceita' AND link = ? AND DATE(created_at) = CURDATE()",
            [userId(), $link]
        );
        if (!$existe) {
            $db->insert('notificacoes', [
                'usuario_id' => userId(),
                'tipo' => 'candidatura_aceita',
                'titulo' => 'Novo candidato!',
                'mensagem' => $c['prestador_nome'] . ' aceitou: ' . $c['titulo'],
                'link' => $link
            ]);
        }
    }
    
    $row = $db->fetch(
        "SELECT MAX(c.id) as max_id 
         FROM candidaturas c 
         JOIN diarias d ON c.diaria_id = d.id 
         WHERE d.empresa_id = ?",
        [$empresaId]
    );
    $novoId = $row && $row['max_id'] ? (int)$row['max_id'] : 0;
    echo json_encode([
        'ativo' => true,
        'som' => false,
        'ultimo_id' => $novoId
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Buscar candidaturas novas APENAS das diárias desta empresa
$candidaturasNovas = $db->fetchAll(
    "SELECT c.id, c.diaria_id, d.titulo, u.nome as prestador_nome
     FROM candidaturas c
     JOIN diarias d ON c.diaria_id = d.id
     JOIN prestadores p ON c.prestador_id = p.id
     JOIN usuarios u ON p.usuario_id = u.id
     WHERE c.id > ? AND c.status = 'confirmada' AND d.empresa_id = ?
     ORDER BY c.id ASC
     LIMIT 5",
    [$ultimoId, $empresaId]
);

$temNovo = !empty($candidaturasNovas);
$novoMaxId = $ultimoId;
if ($temNovo) {
    $novoMaxId = (int)$candidaturasNovas[count($candidaturasNovas) - 1]['id'];
    
    // Salvar notificação para a empresa logada (usuario_id da empresa)
    foreach ($candidaturasNovas as $c) {
        $msg = $c['prestador_nome'] . ' aceitou: ' . $c['titulo'];
        $link = 'evento.php?id=' . $c['diaria_id'];
        // Verificar duplicidade usando o link (que contém diaria_id)
        $existe = $db->fetch(
            "SELECT id FROM notificacoes WHERE usuario_id = ? AND tipo = 'candidatura_aceita' AND link = ? AND DATE(created_at) = CURDATE()",
            [userId(), $link]
        );
        if (!$existe) {
            $db->insert('notificacoes', [
                'usuario_id' => userId(),
                'tipo' => 'candidatura_aceita',
                'titulo' => 'Novo candidato!',
                'mensagem' => $msg,
                'link' => $link
            ]);
        }
    }
}

echo json_encode([
    'ativo' => true,
    'som' => $temNovo,
    'ultimo_id' => $novoMaxId,
    'candidaturas' => $candidaturasNovas
], JSON_UNESCAPED_UNICODE);
?>
