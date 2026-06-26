<?php
require_once __DIR__ . '/../app.php';

$auth = new Auth();
$auth->requireType('admin');

$db = Database::getInstance();

// Ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;
    
    if (isset($input['action'])) {
        header('Content-Type: application/json');
        
        switch ($input['action']) {
            case 'criar':
                $nome = sanitize($input['nome'] ?? '');
                $razao = sanitize($input['razao_social'] ?? '');
                $cnpj = sanitize($input['cnpj'] ?? '');
                $email = sanitize($input['email'] ?? '');
                $telefone = sanitize($input['telefone'] ?? '');
                $contato = sanitize($input['contato_nome'] ?? '');
                $senha = $input['password'] ?? 'password';
                
                if ($nome && $email) {
                    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
                    $db->insert('usuarios', [
                        'nome' => $nome,
                        'email' => $email,
                        'senha' => $senhaHash,
                        'telefone' => $telefone,
                        'tipo' => 'empresa',
                        'ativo' => 1,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    $usuarioId = $db->lastInsertId();
                    
                    $db->insert('empresas', [
                        'usuario_id' => $usuarioId,
                        'razao_social' => $razao ?: $nome,
                        'cnpj' => $cnpj,
                        'contato_nome' => $contato,
                        'status' => 'ativo',
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    jsonResponse(['success' => true, 'message' => 'Empresa criada!']);
                }
                jsonResponse(['error' => 'Preencha os campos obrigatórios'], 400);
                break;
                
            case 'editar':
                $id = (int)($input['id'] ?? 0);
                if ($id <= 0) jsonResponse(['error' => 'ID inválido'], 400);
                
                $empresa = $db->fetch("SELECT usuario_id FROM empresas WHERE id = :id", ['id' => $id]);
                if (!$empresa) jsonResponse(['error' => 'Empresa não encontrada'], 404);
                
                $db->update('empresas', [
                    'razao_social' => sanitize($input['razao_social'] ?? ''),
                    'cnpj' => sanitize($input['cnpj'] ?? ''),
                    'contato_nome' => sanitize($input['contato_nome'] ?? ''),
                    'telefone_empresa' => sanitize($input['telefone'] ?? '')
                ], 'id = :id', ['id' => $id]);
                
                $db->update('usuarios', [
                    'nome' => sanitize($input['nome'] ?? ''),
                    'email' => sanitize($input['email'] ?? ''),
                    'telefone' => sanitize($input['telefone'] ?? '')
                ], 'id = :id', ['id' => $empresa['usuario_id']]);
                
                jsonResponse(['success' => true, 'message' => 'Empresa atualizada!']);
                break;
                
            case 'suspender':
                $id = (int)($input['id'] ?? 0);
                if ($id <= 0) jsonResponse(['error' => 'ID inválido'], 400);
                
                $empresa = $db->fetch("SELECT usuario_id FROM empresas WHERE id = :id", ['id' => $id]);
                if (!$empresa) jsonResponse(['error' => 'Empresa não encontrada'], 404);
                
                $db->update('usuarios', ['ativo' => 0], 'id = :id', ['id' => $empresa['usuario_id']]);
                jsonResponse(['success' => true, 'message' => 'Empresa suspensa!']);
                break;
                
            case 'reativar':
                $id = (int)($input['id'] ?? 0);
                if ($id <= 0) jsonResponse(['error' => 'ID inválido'], 400);
                
                $empresa = $db->fetch("SELECT usuario_id FROM empresas WHERE id = :id", ['id' => $id]);
                if (!$empresa) jsonResponse(['error' => 'Empresa não encontrada'], 404);
                
                $db->update('usuarios', ['ativo' => 1], 'id = :id', ['id' => $empresa['usuario_id']]);
                jsonResponse(['success' => true, 'message' => 'Empresa reativada!']);
                break;
                
            case 'deletar':
                $id = (int)($input['id'] ?? 0);
                if ($id <= 0) jsonResponse(['error' => 'ID inválido'], 400);
                
                $empresa = $db->fetch("SELECT usuario_id FROM empresas WHERE id = :id", ['id' => $id]);
                if (!$empresa) jsonResponse(['error' => 'Empresa não encontrada'], 404);
                
                // Verificar diárias
                $diarias = $db->fetch(
                    "SELECT COUNT(*) as total FROM diarias WHERE empresa_id = :id",
                    ['id' => $id]
                );
                
                if ($diarias['total'] > 0) {
                    jsonResponse(['error' => 'Esta empresa possui ' . $diarias['total'] . ' diária(s). Exclua as diárias primeiro ou suspenda a empresa.'], 400);
                }
                
                $db->delete('empresas', 'id = :id', ['id' => $id]);
                $db->delete('usuarios', 'id = :id', ['id' => $empresa['usuario_id']]);
                
                jsonResponse(['success' => true, 'message' => 'Empresa excluída!']);
                break;
        }
        
        jsonResponse(['error' => 'Ação inválida'], 400);
    }
}

$empresas = $db->fetchAll(
    "SELECT e.*, u.nome, u.email, u.telefone, u.ativo,
            (SELECT COUNT(*) FROM diarias d WHERE d.empresa_id = e.id) as total_diarias
     FROM empresas e
     JOIN usuarios u ON e.usuario_id = u.id
     ORDER BY e.created_at DESC"
);

$user = $auth->getUser();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empresas - <?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=3">
    <link rel="stylesheet" href="../assets/css/admin.css?v=3">
</head>
<body class="admin-body">
    <?php include 'includes/sidebar.php'; ?>
    <main class="admin-main">
        <?php include 'includes/header.php'; ?>
        <div class="admin-content">
            <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <h1 style="margin: 0;">Empresas</h1>
                <button class="btn btn-primary" onclick="abrirModalCriar()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 8px;">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>
                    </svg>
                    Nova Empresa
                </button>
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Empresa</th>
                                <th>Contato</th>
                                <th>Email</th>
                                <th>Telefone</th>
                                <th>Diárias</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($empresas)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-8 text-muted">Nenhuma empresa cadastrada</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($empresas as $e): ?>
                            <tr>
                                <td data-label="Empresa">
                                    <a href="empresa-detalhe.php?id=<?php echo $e['id']; ?>" style="color: inherit; text-decoration: none;">
                                        <strong><?php echo sanitize($e['razao_social'] ?: $e['nome']); ?></strong>
                                    </a>
                                    <?php if ($e['nome'] && $e['razao_social']): ?>
                                    <br><small class="text-muted"><?php echo sanitize($e['nome']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Contato"><?php echo sanitize($e['contato_nome'] ?: '-'); ?></td>
                                <td data-label="Email"><?php echo sanitize($e['email']); ?></td>
                                <td data-label="Telefone">
                                    <span style="display: inline-flex; align-items: center; gap: 6px;">
                                        <?php echo sanitize($e['telefone'] ?: '-'); ?>
                                        <?php if (!empty($e['telefone'])):
                                            $whats = preg_replace('/\D+/', '', $e['telefone']);
                                            if (strlen($whats) === 11) $whats = '55' . $whats;
                                        ?>
                                        <a href="https://wa.me/<?php echo $whats; ?>" target="_blank" title="Falar no WhatsApp" style="color:#16A34A;text-decoration:none;display:inline-flex;vertical-align:middle;">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.980.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                        </a>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td data-label="Diárias"><?php echo $e['total_diarias']; ?></td>
                                <td data-label="Status">
                                    <span class="badge badge-<?php echo $e['ativo'] ? 'success' : 'danger'; ?>">
                                        <?php echo $e['ativo'] ? 'Ativo' : 'Suspenso'; ?>
                                    </span>
                                </td>
                                <td data-label="Ações">
                                    <div style="display: flex; gap: 8px; align-items: center;">
                                        <a href="empresa-detalhe.php?id=<?php echo $e['id']; ?>" class="btn-icon-sm" title="Ver Detalhes" style="background:#EEF2FF;color:#4F46E5;padding:6px;border-radius:8px;display:inline-flex;text-decoration:none;">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                <circle cx="12" cy="12" r="3"/>
                                            </svg>
                                        </a>
                                        <button class="btn-icon-sm" title="Editar" onclick='editarEmpresa(<?php echo json_encode($e); ?>)' style="background:#FEF3C7;color:#92400E;padding:6px;border-radius:8px;border:none;cursor:pointer;display:inline-flex;">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                            </svg>
                                        </button>
                                        <?php if ($e['ativo']): ?>
                                        <button class="btn-icon-sm" title="Suspender" onclick="suspenderEmpresa(<?php echo $e['id']; ?>)" style="background:#FEF3C7;color:#92400E;padding:6px;border-radius:8px;border:none;cursor:pointer;display:inline-flex;">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                <rect x="6" y="4" width="4" height="16"/>
                                                <rect x="14" y="4" width="4" height="16"/>
                                            </svg>
                                        </button>
                                        <?php else: ?>
                                        <button class="btn-icon-sm" title="Reativar" onclick="reativarEmpresa(<?php echo $e['id']; ?>)" style="background:#D1FAE5;color:#065F46;padding:6px;border-radius:8px;border:none;cursor:pointer;display:inline-flex;">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                <polygon points="5 3 19 12 5 21 5 3"/>
                                            </svg>
                                        </button>
                                        <?php endif; ?>
                                        <button class="btn-icon-sm" title="Excluir" onclick="deletarEmpresa(<?php echo $e['id']; ?>, <?php echo $e['total_diarias']; ?>)" style="background:#FEE2E2;color:#991B1B;padding:6px;border-radius:8px;border:none;cursor:pointer;display:inline-flex;">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                <polyline points="3 6 5 6 21 6"/>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Nova/Editar Empresa -->
    <div id="modalEmpresa" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 200; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 16px; width: 100%; max-width: 500px; max-height: 90vh; overflow-y: auto;">
            <div style="padding: 24px; border-bottom: 1px solid #E5E7EB; display: flex; justify-content: space-between; align-items: center;">
                <h2 id="modalTitulo" style="margin: 0; font-size: 1.25rem;">Nova Empresa</h2>
                <button onclick="document.getElementById('modalEmpresa').style.display='none'" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
            </div>
            <form id="formEmpresa" style="padding: 24px;">
                <input type="hidden" name="action" id="formAction" value="criar">
                <input type="hidden" name="id" id="formId" value="">
                
                <div class="form-group">
                    <label>Nome Fantasia *</label>
                    <input type="text" name="nome" id="formNome" class="form-control" required placeholder="Nome da empresa">
                </div>
                
                <div class="form-group">
                    <label>Razão Social</label>
                    <input type="text" name="razao_social" id="formRazao" class="form-control" placeholder="Razão social">
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label>CNPJ</label>
                        <input type="text" name="cnpj" id="formCnpj" class="form-control" placeholder="00.000.000/0000-00">
                    </div>
                    <div class="form-group">
                        <label>Telefone</label>
                        <input type="tel" name="telefone" id="formTelefone" class="form-control" placeholder="(00) 00000-0000">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" id="formEmail" class="form-control" required placeholder="empresa@email.com">
                </div>
                
                <div class="form-group" id="campoSenha">
                    <label>Senha</label>
                    <input type="password" name="password" id="formSenha" class="form-control" value="password">
                    <small class="text-muted">Padrão: password</small>
                </div>
                
                <div class="form-group">
                    <label>Pessoa de Contato</label>
                    <input type="text" name="contato_nome" id="formContato" class="form-control" placeholder="Nome do responsável">
                </div>
                
                <div style="display: flex; gap: 12px; margin-top: 24px;">
                    <button type="button" onclick="document.getElementById('modalEmpresa').style.display='none'" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="../assets/js/admin.js"></script>
    <script>
    function abrirModalCriar() {
        document.getElementById('modalTitulo').textContent = 'Nova Empresa';
        document.getElementById('formAction').value = 'criar';
        document.getElementById('formId').value = '';
        document.getElementById('formEmpresa').reset();
        document.getElementById('campoSenha').style.display = 'block';
        document.getElementById('modalEmpresa').style.display = 'flex';
    }
    
    function editarEmpresa(empresa) {
        document.getElementById('modalTitulo').textContent = 'Editar Empresa';
        document.getElementById('formAction').value = 'editar';
        document.getElementById('formId').value = empresa.id;
        document.getElementById('formNome').value = empresa.nome || '';
        document.getElementById('formRazao').value = empresa.razao_social || '';
        document.getElementById('formCnpj').value = empresa.cnpj || '';
        document.getElementById('formTelefone').value = empresa.telefone || '';
        document.getElementById('formEmail').value = empresa.email || '';
        document.getElementById('formContato').value = empresa.contato_nome || '';
        document.getElementById('campoSenha').style.display = 'none';
        document.getElementById('modalEmpresa').style.display = 'flex';
    }
    
    document.getElementById('formEmpresa').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        try {
            const res = await fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(Object.fromEntries(formData))
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert(data.error || 'Erro');
            }
        } catch (err) {
            alert('Erro de conexão');
        }
    });
    
    async function suspenderEmpresa(id) {
        if (!confirm('Suspender esta empresa? Ela não poderá acessar o sistema.')) return;
        try {
            const res = await fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'suspender', id: id})
            });
            const data = await res.json();
            if (data.success) { alert(data.message); location.reload(); }
            else alert(data.error);
        } catch (err) { alert('Erro'); }
    }
    
    async function reativarEmpresa(id) {
        if (!confirm('Reativar esta empresa?')) return;
        try {
            const res = await fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'reativar', id: id})
            });
            const data = await res.json();
            if (data.success) { alert(data.message); location.reload(); }
            else alert(data.error);
        } catch (err) { alert('Erro'); }
    }
    
    async function deletarEmpresa(id, diarias) {
        if (diarias > 0) {
            alert('Esta empresa possui ' + diarias + ' diária(s). Exclua as diárias primeiro ou suspenda a empresa.');
            return;
        }
        if (!confirm('Excluir esta empresa permanentemente?')) return;
        try {
            const res = await fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'deletar', id: id})
            });
            const data = await res.json();
            if (data.success) { alert(data.message); location.reload(); }
            else alert(data.error);
        } catch (err) { alert('Erro'); }
    }
    </script>
</body>
</html>
