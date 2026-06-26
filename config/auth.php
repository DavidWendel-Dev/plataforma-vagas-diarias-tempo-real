<?php
/**
 * Sistema de Autenticação
 */

class Auth {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->startSession();
    }

    /**
     * Iniciar sessão segura
     */
    private function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            $lifetime = defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 2592000;

            // Configurar cookie de sessão para persistir após fechar o navegador
            session_set_cookie_params([
                'lifetime' => $lifetime,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);

            // Garantir que o garbage collector respeite o lifetime
            ini_set('session.gc_maxlifetime', (string)$lifetime);
            ini_set('session.cookie_lifetime', (string)$lifetime);

            session_name(SESSION_NAME);
            session_start();

            // Renovar o cookie a cada acesso para estender a validade
            if (isset($_COOKIE[SESSION_NAME])) {
                setcookie(
                    SESSION_NAME,
                    session_id(),
                    [
                        'expires'  => time() + $lifetime,
                        'path'     => '/',
                        'httponly' => true,
                        'samesite' => 'Lax',
                    ]
                );
            }
        }
    }

    /**
     * Login de usuário
     */
    public function login($email, $password) {
        $user = $this->db->fetch(
            "SELECT * FROM usuarios WHERE email = :email AND ativo = 1",
            ['email' => $email]
        );

        if ($user && password_verify($password, $user['senha'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_tipo'] = $user['tipo'];
            $_SESSION['user_nome'] = $user['nome'];
            $_SESSION['logged_in'] = true;

            // Atualizar último acesso
            $this->db->update(
                'usuarios',
                ['ultimo_acesso' => date('Y-m-d H:i:s')],
                'id = :id',
                ['id' => $user['id']]
            );

            return true;
        }

        return false;
    }

    /**
     * Logout
     */
    public function logout() {
        $_SESSION = [];
        session_destroy();
    }

    /**
     * Verificar se está logado
     */
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /**
     * Verificar tipo de usuário
     */
    public function isType($tipo) {
        return isset($_SESSION['user_tipo']) && $_SESSION['user_tipo'] === $tipo;
    }

    /**
     * Obter ID do usuário logado
     */
    public function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Obter dados do usuário logado
     */
    public function getUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return $this->db->fetch(
            "SELECT * FROM usuarios WHERE id = :id",
            ['id' => $this->getUserId()]
        );
    }

    /**
     * Registrar novo usuário
     */
    public function register($data, $extraData = []) {
        // Verificar se email já existe
        $exists = $this->db->fetch(
            "SELECT id FROM usuarios WHERE email = :email",
            ['email' => $data['email']]
        );

        if ($exists) {
            return ['error' => 'Este email já está cadastrado.'];
        }

        // Hash da senha
        $data['senha'] = password_hash($data['senha'], PASSWORD_DEFAULT);
        $data['created_at'] = date('Y-m-d H:i:s');

        // Inserir usuário
        $userId = $this->db->insert('usuarios', $data);

        // Se for prestador, criar registro na tabela de prestadores
        if ($data['tipo'] === 'prestador') {
            $prestadorData = [
                'usuario_id' => $userId,
                'status' => 'pendente',
                'created_at' => date('Y-m-d H:i:s')
            ];
            // Mesclar dados extras do prestador
            if (!empty($extraData)) {
                $prestadorData = array_merge($prestadorData, $extraData);
            }
            $this->db->insert('prestadores', $prestadorData);
        }

        return ['success' => true, 'user_id' => $userId];
    }

    /**
     * Requer que usuário esteja logado
     */
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: ' . APP_URL . '/login.php');
            exit;
        }
    }

    /**
     * Requer tipo específico de usuário
     */
    public function requireType($tipo) {
        $this->requireLogin();

        if (!$this->isType($tipo)) {
            http_response_code(403);
            die('Acesso negado.');
        }
    }
}

// Funções helper globais
function auth() {
    return new Auth();
}

function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function userId() {
    return $_SESSION['user_id'] ?? null;
}

function userTipo() {
    return $_SESSION['user_tipo'] ?? null;
}

function userName() {
    return $_SESSION['user_nome'] ?? null;
}
