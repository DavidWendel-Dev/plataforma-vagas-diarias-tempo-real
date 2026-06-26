<?php
require_once __DIR__ . '/../../app.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn() || userTipo() !== 'empresa') {
    jsonResponse(['error' => 'Acesso negado'], 401);
}

$db = Database::getInstance();
$diariaId = (int)($_GET['diaria_id'] ?? 0);

$diaria = $db->fetch(
    "SELECT d.* FROM diarias d WHERE d.id = :id",
    ['id' => $diariaId]
);

if (!$diaria) {
    jsonResponse(['error' => 'Evento não encontrado'], 404);
}

$prestadores = $db->fetchAll(
    "SELECT u.nome, u.cpf, c.status, d.valor, p.funcao_pretendida
     FROM candidaturas c
     JOIN prestadores p ON c.prestador_id = p.id
     JOIN usuarios u ON p.usuario_id = u.id
     JOIN diarias d ON c.diaria_id = d.id
     WHERE c.diaria_id = :did AND c.status = 'checkin_realizado'
     ORDER BY u.nome",
    ['did' => $diariaId]
);

$totalBase = $diaria['valor'] * count($prestadores);
$taxa = $diaria['taxa_agencia'] ?? 10;
$taxaValor = ($diaria['valor_empresa'] ?? $totalBase * (1 + $taxa/100)) - $totalBase;
$totalGeral = $totalBase + $taxaValor;

ob_start();
?>
<div style="margin-bottom:16px">
    <div style="font-size:0.875rem;color:#6B7280">Evento</div>
    <div style="font-size:1.125rem;font-weight:700"><?php echo sanitize($diaria['titulo']); ?></div>
    <div style="font-size:0.875rem;color:#6B7280"><?php echo formatDate($diaria['data_evento']); ?> • <?php echo $diaria['horario_inicio']; ?> - <?php echo $diaria['horario_fim']; ?></div>
</div>

<div style="margin-bottom:16px">
    <div style="font-size:0.875rem;color:#6B7280">Local</div>
    <div><?php echo sanitize($diaria['endereco']); ?></div>
</div>

<hr style="border:none;border-top:1px solid #E5E7EB;margin:16px 0">

<h4 style="margin:0 0 12px;font-size:0.875rem">Profissionais (<?php echo count($prestadores); ?>)</h4>

<div style="background:#F9FAFB;border-radius:8px;padding:12px">
<?php foreach ($prestadores as $p): ?>
<div class="extrato-row">
    <div>
        <strong><?php echo sanitize($p['nome']); ?></strong>
        <div style="font-size:0.75rem;color:#6B7280"><?php echo sanitize($p['funcao_pretendida']); ?></div>
    </div>
    <div style="text-align:right">
        <div style="font-weight:600"><?php echo formatMoney($p['valor']); ?></div>
        <div style="font-size:0.75rem;color:#6B7280">1 diária</div>
    </div>
</div>
<?php endforeach; ?>
</div>

<div class="taxa-info">
    <div class="extrato-row">
        <span>Subtotal (Prestadores)</span>
        <strong><?php echo formatMoney($totalBase); ?></strong>
    </div>
    <div class="extrato-row" style="border-bottom:none">
        <span>Taxa de Agenciamento (<?php echo $taxa; ?>%)</span>
        <span style="color:#6B7280"><?php echo formatMoney($taxaValor); ?></span>
    </div>
</div>

<div style="background:#10B981;color:white;padding:16px;border-radius:12px;margin-top:16px">
    <div style="display:flex;justify-content:space-between;align-items:center">
        <span style="font-size:0.875rem">VALOR TOTAL DO EVENTO</span>
        <span style="font-size:1.5rem;font-weight:800"><?php echo formatMoney($totalGeral); ?></span>
    </div>
</div>

<?php if (!$diaria['pago_empresa']): ?>
<div style="margin-top:16px;padding:12px;background:#FEF3C7;border-radius:8px;font-size:0.875rem;color:#92400E">
    ⏳ Aguardando pagamento
</div>
<?php else: ?>
<div style="margin-top:16px;padding:12px;background:#D1FAE5;border-radius:8px;font-size:0.875rem;color:#065F46">
    ✓ Pagamento realizado em <?php echo formatDate($diaria['pago_empresa_at']); ?>
</div>
<?php endif; ?>
<?php
$html = ob_get_clean();
jsonResponse(['html' => $html]);
