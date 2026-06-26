<?php
/**
 * API: Autenticação
 */
// Debug temporário
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../app.php';

header('Content-Type: application/json');

// NÃO usar json_decode com FormData - FormData já é processado automaticamente pelo PHP
// O problema era que file_get_contents consome o stream

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Debug temporário - remover após corrigir
if (empty($action)) {
    jsonResponse([
        'error' => 'Ação não definida', 
        'debug' => [
            'POST_keys' => array_keys($_POST),
            'FILES_keys' => array_keys($_FILES),
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'não definido'
        ]
    ], 400);
}

switch ($action) {
    case 'login':
        handleLogin();
        break;

    case 'logout':
        handleLogout();
        break;

    case 'register_prestador':
        handleRegisterPrestador();
        break;

    case 'register_empresa':
        handleRegisterEmpresa();
        break;

    case 'check_session':
        checkSession();
        break;

    case 'forgot_password':
        handleForgotPassword();
        break;

    case 'reset_password':
        handleResetPassword();
        break;

    default:
        jsonResponse(['error' => 'Ação inválida: ' . $action], 400);
}

function handleLogin() {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        jsonResponse(['error' => 'Preencha todos os campos.'], 400);
    }

    $auth = new Auth();

    if ($auth->login($email, $password)) {
        $user = $auth->getUser();
        
        $redirect = match($user['tipo']) {
            'admin' => APP_URL . '/admin/dashboard.php',
            'empresa' => APP_URL . '/empresa/dashboard.php',
            'prestador' => APP_URL . '/app/index.php',
            default => APP_URL
        };

        jsonResponse([
            'success' => true,
            'redirect' => $redirect,
            'user' => [
                'id' => $user['id'],
                'nome' => $user['nome'],
                'tipo' => $user['tipo']
            ]
        ]);
    }

    jsonResponse(['error' => 'Email ou senha incorretos.'], 401);
}

function handleLogout() {
    $auth = new Auth();
    $auth->logout();
    header('Location: ' . APP_URL . '/login.php');
    exit;
}

function handleRegisterPrestador() {
    global $auth;

    try {
    $nome = sanitize($_POST['nome'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $telefone = sanitize($_POST['telefone'] ?? '');
    $password = $_POST['password'] ?? '';
    $dataNascimentoInput = $_POST['data_nascimento'] ?? '';
    $funcoes = $_POST['funcoes'] ?? [];

    // Converter data de dd/mm/aaaa para aaaa-mm-dd (formato MySQL)
    $dataNascimento = '';
    if (!empty($dataNascimentoInput)) {
        $d = DateTime::createFromFormat('d/m/Y', $dataNascimentoInput);
        if ($d) {
            $dataNascimento = $d->format('Y-m-d');
        } else {
            $d = DateTime::createFromFormat('Y-m-d', $dataNascimentoInput);
            $dataNascimento = $d ? $d->format('Y-m-d') : $dataNascimentoInput;
        }
    }

    // Validações
    if (empty($nome) || empty($email) || empty($password) || empty($dataNascimento)) {
        jsonResponse(['error' => 'Preencha todos os campos obrigatórios.'], 400);
    }

    // Verificar maioridade
    $idade = date_diff(date_create($dataNascimento), date_create('today'))->y;
    $config = db()->fetch("SELECT valor FROM configuracoes WHERE chave = 'idade_minima'");
    $idadeMinima = $config ? (int)$config['valor'] : 18;

    if ($idade < $idadeMinima) {
        jsonResponse(['error' => "Você deve ter pelo menos {$idadeMinima} anos."], 400);
    }

    // Upload da foto
    $foto = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadFile($_FILES['foto'], UPLOAD_PATH . 'prestadores/');
        if (isset($upload['error'])) {
            jsonResponse(['error' => $upload['error']], 400);
        }
        $foto = $upload['filename'];
    } else {
        jsonResponse(['error' => 'Foto de perfil é obrigatória.'], 400);
    }

    // Cadastrar usuário
    $result = $auth->register([
        'nome' => $nome,
        'email' => $email,
        'senha' => $password,
        'telefone' => $telefone,
        'foto' => $foto,
        'tipo' => 'prestador'
    ], [
        'data_nascimento' => $dataNascimento
    ]);

    if (isset($result['error'])) {
        jsonResponse(['error' => $result['error']], 400);
    }

    // Atualizar dados do prestador
    db()->update(
        'prestadores',
        [
            'data_nascimento' => $dataNascimento,
            'funcoes' => json_encode($funcoes)
        ],
        'usuario_id = :id',
        ['id' => $result['user_id']]
    );

    jsonResponse([
        'success' => true,
        'message' => 'Cadastro realizado com sucesso! Aguarde a aprovação.'
    ]);
    } catch (Exception $e) {
        error_log('Erro register_prestador: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
        jsonResponse(['error' => 'Erro interno: ' . $e->getMessage()], 500);
    }
}

function handleRegisterEmpresa() {
    global $auth;

    $nome = sanitize($_POST['nome'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $telefone = sanitize($_POST['telefone'] ?? '');
    $password = $_POST['password'] ?? '';
    $razaoSocial = sanitize($_POST['razao_social'] ?? '');
    $cnpj = sanitize($_POST['cnpj'] ?? '');
    $contatoNome = sanitize($_POST['contato_nome'] ?? '');

    if (empty($nome) || empty($email) || empty($password)) {
        jsonResponse(['error' => 'Preencha todos os campos obrigatórios.'], 400);
    }

    $result = $auth->register([
        'nome' => $nome,
        'email' => $email,
        'senha' => $password,
        'telefone' => $telefone,
        'tipo' => 'empresa'
    ]);

    if (isset($result['error'])) {
        jsonResponse(['error' => $result['error']], 400);
    }

    db()->update(
        'empresas',
        [
            'razao_social' => $razaoSocial,
            'cnpj' => $cnpj,
            'contato_nome' => $contatoNome,
            'telefone_empresa' => $telefone
        ],
        'usuario_id = :id',
        ['id' => $result['user_id']]
    );

    jsonResponse([
        'success' => true,
        'message' => 'Cadastro realizado com sucesso!'
    ]);
}

function checkSession() {
    $auth = new Auth();
    
    if ($auth->isLoggedIn()) {
        $user = $auth->getUser();
        jsonResponse([
            'logged_in' => true,
            'user' => [
                'id' => $user['id'],
                'nome' => $user['nome'],
                'tipo' => $user['tipo'],
                'foto' => $user['foto']
            ]
        ]);
    }
    
    jsonResponse(['logged_in' => false]);
}

function handleForgotPassword() {
    $email = sanitize($_POST['email'] ?? '');

    if (empty($email)) {
        jsonResponse(['error' => 'Informe seu email.'], 400);
    }

    $user = db()->fetch(
        "SELECT id, nome FROM usuarios WHERE email = :email",
        ['email' => $email]
    );

    if (!$user) {
        jsonResponse(['error' => 'Email não encontrado.'], 404);
    }

    $token = generateToken();

    db()->update(
        'usuarios',
        ['token_recuperacao' => $token],
        'id = :id',
        ['id' => $user['id']]
    );

    jsonResponse([
        'success' => true,
        'message' => 'Email de recuperação enviado!',
        'debug_token' => $token
    ]);
}

function handleResetPassword() {
    $token = sanitize($_POST['token'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($token) || empty($password)) {
        jsonResponse(['error' => 'Dados incompletos.'], 400);
    }

    if ($password !== $confirmPassword) {
        jsonResponse(['error' => 'As senhas não conferem.'], 400);
    }

    $user = db()->fetch(
        "SELECT id FROM usuarios WHERE token_recuperacao = :token",
        ['token' => $token]
    );

    if (!$user) {
        jsonResponse(['error' => 'Token inválido ou expirado.'], 400);
    }

    db()->update(
        'usuarios',
        [
            'senha' => password_hash($password, PASSWORD_DEFAULT),
            'token_recuperacao' => null
        ],
        'id = :id',
        ['id' => $user['id']]
    );

    jsonResponse(['success' => true, 'message' => 'Senha alterada com sucesso!']);
}
