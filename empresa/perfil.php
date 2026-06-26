<?php
require_once __DIR__ . '/../app.php';
$auth = new Auth();
$auth->requireType('empresa');
$db = Database::getInstance();

$empresa = $db->fetch("SELECT e.*, u.nome, u.email, u.telefone FROM empresas e JOIN usuarios u ON e.usuario_id = u.id WHERE u.id = :id", ['id' => userId()]);

// Salvar perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'salvar') {
    $db->update('usuarios', [
        'nome' => sanitize($_POST['nome']),
        'telefone' => sanitize($_POST['telefone']),
        'updated_at' => date('Y-m-d H:i:s')
    ], 'id = :id', ['id' => userId()]);
    
    $db->update('empresas', [
        'razao_social' => sanitize($_POST['razao_social']),
        'cnpj' => sanitize($_POST['cnpj']),
        'contato_nome' => sanitize($_POST['contato_nome']),
        'updated_at' => date('Y-m-d H:i:s')
    ], 'id = :id', ['id' => $empresa['id']]);
    
    redirect('perfil.php?success=1');
}

// Alterar senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'senha') {
    $senhaAtual = $_POST['senha_atual'] ?? '';
    $novaSenha = $_POST['nova_senha'] ?? '';
    
    $user = $db->fetch("SELECT senha FROM usuarios WHERE id = :id", ['id' => userId()]);
    
    if (!password_verify($senhaAtual, $user['senha'])) {
        $erro = 'Senha atual incorreta';
    } elseif (strlen($novaSenha) < 6) {
        $erro = 'A nova senha deve ter pelo menos 6 caracteres';
    } else {
        $db->update('usuarios', [
            'senha' => password_hash($novaSenha, PASSWORD_DEFAULT),
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => userId()]);
        $sucesso = true;
    }
}

$user = $auth->getUser();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Perfil - <?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>.admin-sidebar{background:linear-gradient(180deg,#10B981,#059669)}.nav-link{color:#fff}.nav-link:hover{background:rgba(255,255,255,0.15)}.nav-link.active{background:rgba(255,255,255,0.2)}</style>
</head>
<body class="admin-body">
    <?php include 'includes/sidebar.php'; ?>
    <main class="admin-main">
        <?php include 'includes/header.php'; ?>
        <div class="admin-content">
            <div class="page-header" style="margin-bottom:24px"><h1 style="margin:0">Meu Perfil</h1></div>
            
            <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success" style="background:#D1FAE5;color:#065F46;padding:12px 16px;border-radius:8px;margin-bottom:16px">✓ Perfil atualizado!</div>
            <?php endif; ?>
            
            <?php if (isset($sucesso)): ?>
            <div class="alert alert-success" style="background:#D1FAE5;color:#065F46;padding:12px 16px;border-radius:8px;margin-bottom:16px">✓ Senha alterada!</div>
            <?php endif; ?>
            
            <?php if (isset($erro)): ?>
            <div class="alert alert-danger" style="background:#FEE2E2;color:#991B1B;padding:12px 16px;border-radius:8px;margin-bottom:16px"><?php echo $erro; ?></div>
            <?php endif; ?>
            
            <div class="card" style="max-width:600px">
                <div class="card-header"><h3>Informações da Empresa</h3></div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="salvar">
                        <div class="form-group"><label>Nome</label><input type="text" name="nome" class="form-control" value="<?php echo sanitize($empresa['nome']); ?>" required></div>
                        <div class="form-group"><label>Email</label><input type="email" class="form-control" value="<?php echo sanitize($empresa['email']); ?>" disabled></div>
                        <div class="form-group"><label>Telefone</label><input type="text" name="telefone" class="form-control" value="<?php echo sanitize($empresa['telefone']); ?>"></div>
                        <div class="form-group"><label>Razão Social</label><input type="text" name="razao_social" class="form-control" value="<?php echo sanitize($empresa['razao_social']); ?>"></div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                            <div class="form-group"><label>CNPJ</label><input type="text" name="cnpj" class="form-control" value="<?php echo sanitize($empresa['cnpj']); ?>"></div>
                            <div class="form-group"><label>Contato</label><input type="text" name="contato_nome" class="form-control" value="<?php echo sanitize($empresa['contato_nome']); ?>"></div>
                        </div>
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    </form>
                </div>
            </div>
            
            <div class="card" style="max-width:600px;margin-top:24px">
                <div class="card-header"><h3>Alterar Senha</h3></div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="senha">
                        <div class="form-group"><label>Senha Atual</label><input type="password" name="senha_atual" class="form-control" required></div>
                        <div class="form-group"><label>Nova Senha</label><input type="password" name="nova_senha" class="form-control" required minlength="6"></div>
                        <button type="submit" class="btn btn-secondary">Alterar Senha</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
    <script src="../assets/js/admin.js"></script>
</body>
</html>
