<?php
// API para verificar novas candidaturas - toca som no painel admin
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once __DIR__ . '/../app.php';

// Verificar se é admin
if (!isLoggedIn() || userTipo() !== 'admin') {
    echo json_encode(['error' => 'Não autorizado'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Verificar se o som está ativado nas configurações
$ativo = getConfig('som_candidatura_admin', '0');
if ($ativo !== '1') {
    echo json_encode(['ativo' => false, 'som' => false], JSON_UNESCAPED_UNICODE);
    exit;
}

$db = Database::getInstance();

// Pegar o último ID de candidatura visto (enviado pelo JS)
$ultimoId = isset($_GET['ultimo_id']) ? (int)$_GET['ultimo_id'] : 0;

// Se for 0, pegar o ID atual e retornar (sem tocar som, só inicializar)
// Mas gerar notificações das candidaturas já existentes (que ainda não têm)
if ($ultimoId === 0) {
    $adminUser = $db->fetch("SELECT id FROM usuarios WHERE tipo = 'admin' ORDER BY id ASC LIMIT 1");
    if ($adminUser) {
        $existentes = $db->fetchAll(
            "SELECT c.id, c.diaria_id, d.titulo, u.nome as prestador_nome
             FROM candidaturas c
             JOIN diarias d ON c.diaria_id = d.id
             JOIN prestadores p ON c.prestador_id = p.id
             JOIN usuarios u ON p.usuario_id = u.id
             WHERE c.status = 'confirmada'
             ORDER BY c.id ASC"
        );
        foreach ($existentes as $c) {
            $link = 'diaria-detalhe.php?id=' . $c['diaria_id'];
            $existe = $db->fetch(
                "SELECT id FROM notificacoes WHERE usuario_id = ? AND tipo = 'candidatura_aceita' AND link = ? AND DATE(created_at) = CURDATE()",
                [$adminUser['id'], $link]
            );
            if (!$existe) {
                $db->insert('notificacoes', [
                    'usuario_id' => $adminUser['id'],
                    'tipo' => 'candidatura_aceita',
                    'titulo' => 'Novo candidato!',
                    'mensagem' => $c['prestador_nome'] . ' aceitou: ' . $c['titulo'],
                    'link' => $link
                ]);
            }
        }
    }
    $row = $db->fetch("SELECT MAX(id) as max_id FROM candidaturas");
    $novoId = $row ? (int)$row['max_id'] : 0;
    echo json_encode([
        'ativo' => true,
        'som' => false,
        'ultimo_id' => $novoId
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Buscar candidaturas novas (id maior que o último visto e status = 'confirmada' = aceitou a vaga)
$candidaturasNovas = $db->fetchAll(
    "SELECT c.id, c.diaria_id, d.titulo, u.nome as prestador_nome, u.id as admin_alvo
     FROM candidaturas c
     JOIN diarias d ON c.diaria_id = d.id
     JOIN prestadores p ON c.prestador_id = p.id
     JOIN usuarios u ON p.usuario_id = u.id
     WHERE c.id > ? AND c.status = 'confirmada'
     ORDER BY c.id ASC
     LIMIT 5",
    [$ultimoId]
);

$temNovo = !empty($candidaturasNovas);
$novoMaxId = $ultimoId;
if ($temNovo) {
    $novoMaxId = (int)$candidaturasNovas[count($candidaturasNovas) - 1]['id'];
    
    // Salvar notificação para o admin (usuario_id = 1, primeiro admin do sistema)
    $adminUser = $db->fetch("SELECT id FROM usuarios WHERE tipo = 'admin' ORDER BY id ASC LIMIT 1");
    if ($adminUser) {
        foreach ($candidaturasNovas as $c) {
            $msg = $c['prestador_nome'] . ' aceitou: ' . $c['titulo'];
            $link = 'diaria-detalhe.php?id=' . $c['diaria_id'];
            // Verificar se já existe para esta diária (usando link que contém diaria_id)
            $existe = $db->fetch(
                "SELECT id FROM notificacoes WHERE usuario_id = ? AND tipo = 'candidatura_aceita' AND link = ? AND DATE(created_at) = CURDATE()",
                [$adminUser['id'], $link]
            );
            if (!$existe) {
                $db->insert('notificacoes', [
                    'usuario_id' => $adminUser['id'],
                    'tipo' => 'candidatura_aceita',
                    'titulo' => 'Novo candidato!',
                    'mensagem' => $msg,
                    'link' => $link
                ]);
            }
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
