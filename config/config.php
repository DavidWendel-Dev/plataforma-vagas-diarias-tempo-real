<?php
/**
 * Configurações do Sistema de Diárias
 */

// Configurações do Banco de Dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'diarias_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configurações do Sistema
define('APP_NAME', 'Diárias');
define('APP_URL', 'http://localhost/conect-eventos'); // URL do projeto
define('APP_DEBUG', true);

// Configurações de Sessão
define('SESSION_NAME', 'diarias_session');
define('SESSION_LIFETIME', 86400); // 24 horas

// Configurações de Upload
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Configurações do Mapbox
define('MAPBOX_TOKEN', 'SEU_MAPBOX_TOKEN_AQUI');

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Exibir erros em desenvolvimento
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}
