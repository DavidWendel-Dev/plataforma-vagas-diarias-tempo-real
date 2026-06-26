<?php
/**
 * Carregador de variáveis de ambiente (.env)
 */
class Env {
    private static $loaded = false;
    private static $vars = [];

    public static function load($path = null) {
        if (self::$loaded) return;

        $path = $path ?? dirname(__DIR__) . '/.env';
        
        if (!file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Ignorar comentários
            if (strpos(trim($line), '#') === 0) continue;
            
            // Parsear linha
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                
                // Remover aspas
                $value = trim($value, '"\'');
                
                self::$vars[$name] = $value;
                $_ENV[$name] = $value;
                putenv("$name=$value");
            }
        }

        self::$loaded = true;
    }

    public static function get($key, $default = null) {
        self::load();
        
        if (isset(self::$vars[$key])) {
            return self::$vars[$key];
        }
        
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }
        
        return getenv($key) ?: $default;
    }
}

// Carregar .env
Env::load();

// Definir constantes a partir do .env
define('APP_NAME', Env::get('APP_NAME', 'Diárias'));
define('APP_ENV', Env::get('APP_ENV', 'local'));
define('APP_DEBUG', Env::get('APP_DEBUG', 'true') === 'true');
define('APP_URL', Env::get('APP_URL', 'http://localhost/conect-eventos'));

define('DB_HOST', Env::get('DB_HOST', 'localhost'));
define('DB_NAME', Env::get('DB_NAME', 'diarias_db'));
define('DB_USER', Env::get('DB_USER', 'root'));
define('DB_PASS', Env::get('DB_PASS', ''));
define('DB_CHARSET', Env::get('DB_CHARSET', 'utf8mb4'));

define('MAPBOX_TOKEN', Env::get('MAPBOX_TOKEN', ''));
define('MAX_FILE_SIZE', (int)Env::get('MAX_FILE_SIZE', 5242880));

// Configurações de Sessão
define('SESSION_NAME', Env::get('SESSION_NAME', 'diarias_session'));
define('SESSION_LIFETIME', (int)Env::get('SESSION_LIFETIME', 86400));

// Upload Path
define('UPLOAD_PATH', dirname(__DIR__) . '/uploads/');
