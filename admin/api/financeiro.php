<?php
require_once __DIR__ . '/../../app.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn() || userTipo() !== 'admin') {
    jsonResponse(['error' => 'Acesso negado'], 401);
}

$db = Database::getInstance();
$action = $_GET['action'] ?? '';

if ($action === 'extrato') {
    $diariaId = (int)($_GET['diaria_id'] ?? 0);
    
    $diaria = $db->fetch(
        "SELECT d.*, e.razao_social FROM diarias d JOIN empresas e ON d.empresa_id = e.id WHERE d.id = :id",
        ['id' => $diariaId]
    );
    
    if (!$diaria) {
        jsonResponse(['error' => 'Diária não encontrada'], 404);
    }
    
    $presentes = $db->fetchAll(
        "SELECT c.id, c.status, u.nome, u.telefone, p.nota_media, d.valor,
                COALESCE(pp.status, 'pendente') as pago_status
         FROM candidaturas c
         JOIN prestadores p ON c.prestador_id = p.id
         JOIN usuarios u ON p.usuario_id = u.id
         JOIN diarias d ON c.diaria_id = d.id
         LEFT JOIN pagamentos_prestadores pp ON pp.diaria_id = c.diaria_id AND pp.prestador_id = c.prestador_id
         WHERE c.diaria_id = :did AND c.status = 'checkin_realizado'
         ORDER BY u.nome",
        ['did' => $diariaId]
    );
    
    $totalPrestadores = count($presentes);
    $valorTotalPrestadores = $diaria['valor'] * $totalPrestadores;
    $taxa = $diaria['taxa_agencia'] ?? 10;
    $valorEmpresa = $diaria['valor_empresa'] ?? ($valorTotalPrestadores * (1 + $taxa / 100));
    $lucroAgencia = $valorEmpresa - $valorTotalPrestadores;
    
    ob_start();
    ?>
    <div class="extrato-row">
        <span class="extrato-label">Empresa</span>
        <span class="extrato-value"><?php echo sanitize($diaria['razao_social']); ?></span>
    </div>
    <div class="extrato-row">
        <span class="extrato-label">Evento</span>
        <span class="extrato-value"><?php echo sanitize($diaria['titulo']); ?></span>
    </div>
    <div class="extrato-row">
        <span class="extrato-label">Data</span>
        <span class="extrato-value"><?php echo formatDate($diaria['data_evento']); ?></span>
    </div>
    
    <div style="margin: 16px 0; padding: 16px; background: #F9FAFB; border-radius: 8px;">
        <h4 style="margin: 0 0 12px; font-size: 0.875rem; color: #6B7280;">Resumo Financeiro</h4>
        <div class="extrato-row">
            <span class="extrato-label">Valor cobrado da empresa</span>
            <span class="extrato-value" style="color: #6366F1;"><?php echo formatMoney($valorEmpresa); ?></span>
        </div>
        <div class="extrato-row">
            <span class="extrato-label">Pago aos prestadores (<?php echo $totalPrestadores; ?>)</span>
            <span class="extrato-value money-negative">-<?php echo formatMoney($valorTotalPrestadores); ?></span>
        </div>
        <div class="extrato-row" style="border-bottom: none; padding-top: 12px; margin-top: 8px; border-top: 1px dashed #D1D5DB;">
            <span class="extrato-label"><strong>LUCRO DA AGÊNCIA</strong></span>
            <span class="extrato-value extrato-lucro"><?php echo formatMoney($lucroAgencia); ?></span>
        </div>
    </div>
    
    <h4 style="margin: 16px 0 8px; font-size: 0.875rem;">Prestadores</h4>
    <?php foreach ($presentes as $p): ?>
    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px; background: #F9FAFB; border-radius: 8px; margin-bottom: 8px;">
        <div>
            <strong><?php echo sanitize($p['nome']); ?></strong>
            <div style="font-size: 0.75rem; color: #6B7280;"><?php echo formatMoney($p['valor']); ?></div>
        </div>
        <div>
            <?php if ($p['pago_status'] === 'pago'): ?>
            <span class="badge badge-success">✓ Pago</span>
            <?php else: ?>
            <button class="btn btn-sm btn-primary" onclick="pagarPrestador(<?php echo $diariaId; ?>, <?php echo $p['id']; ?>)">Pagar</button>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php
    $html = ob_get_clean();
    
    jsonResponse(['html' => $html]);
}

jsonResponse(['error' => 'Ação inválida'], 400);
