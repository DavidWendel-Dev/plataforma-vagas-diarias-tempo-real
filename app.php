<?php
/**
 * Arquivo de inicialização do sistema
 */

// Carregar configurações
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

// Iniciar sessão
$auth = new Auth();

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Erros em desenvolvimento
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// ===========================================
// FUNÇÕES HELPER
// ===========================================

function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function asset($path) {
    return APP_URL . '/assets/' . ltrim($path, '/');
}

function url($path = '') {
    return APP_URL . '/' . ltrim($path, '/');
}

function formatMoney($value) {
    return 'R$ ' . number_format((float)$value, 2, ',', '.');
}

function formatDate($date, $format = 'd/m/Y') {
    if (!$date) return '';
    return date($format, strtotime($date));
}

function formatTime($time) {
    return substr($time, 0, 5);
}

function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) return 'agora mesmo';
    if ($diff < 3600) return floor($diff / 60) . ' min atrás';
    if ($diff < 86400) return floor($diff / 3600) . 'h atrás';
    if ($diff < 604800) return floor($diff / 86400) . ' dias atrás';
    
    return formatDate($datetime);
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function uploadFile($file, $directory, $allowedTypes = ['image/jpeg', 'image/png', 'image/webp']) {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['error' => 'Nenhum arquivo enviado.'];
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return ['error' => 'Arquivo muito grande. Máximo: 5MB'];
    }

    if (!in_array($file['type'], $allowedTypes)) {
        return ['error' => 'Tipo de arquivo não permitido.'];
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = generateToken() . '.' . $ext;
    $path = $directory . $filename;

    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $path)) {
        return ['success' => true, 'filename' => $filename, 'path' => $path];
    }

    return ['error' => 'Erro ao fazer upload do arquivo.'];
}

function geocodeAddress($address) {
    $address = urlencode($address);
    $token = MAPBOX_TOKEN;
    
    $url = "https://api.mapbox.com/geocoding/v5/mapbox.places/{$address}.json?access_token={$token}&country=BR&limit=1";
    
    $response = @file_get_contents($url);
    
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['features'][0])) {
            return [
                'lat' => $data['features'][0]['center'][1],
                'lng' => $data['features'][0]['center'][0],
                'place_name' => $data['features'][0]['place_name']
            ];
        }
    }
    
    return null;
}

function getCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateToken();
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function logAction($acao, $tabela = null, $registroId = null, $dadosAntigos = null, $dadosNovos = null) {
    $db = Database::getInstance();
    
    $db->insert('logs_auditoria', [
        'usuario_id' => userId(),
        'acao' => $acao,
        'tabela' => $tabela,
        'registro_id' => $registroId,
        'dados_antigos' => $dadosAntigos ? json_encode($dadosAntigos) : null,
        'dados_novos' => $dadosNovos ? json_encode($dadosNovos) : null,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
}

function getStats() {
    $db = Database::getInstance();
    
    return [
        'diarias_ativas' => $db->fetch(
            "SELECT COUNT(*) as total FROM diarias WHERE status = 'ativa' AND data_evento >= CURDATE()"
        )['total'] ?? 0,
        
        'prestadores_ativos' => $db->fetch(
            "SELECT COUNT(*) as total FROM prestadores WHERE status = 'aprovado'"
        )['total'] ?? 0,
        
        'empresas_ativas' => $db->fetch(
            "SELECT COUNT(*) as total FROM empresas WHERE status = 'ativo'"
        )['total'] ?? 0,
        
        'trabalhos_realizados' => $db->fetch(
            "SELECT COUNT(*) as total FROM candidaturas WHERE status = 'checkin_realizado'"
        )['total'] ?? 0,
    ];
}

function getConfig($chave, $default = null) {
    static $configs = null;
    
    if ($configs === null) {
        $db = Database::getInstance();
        $result = $db->fetchAll("SELECT chave, valor FROM configuracoes");
        $configs = [];
        foreach ($result as $row) {
            $configs[$row['chave']] = $row['valor'];
        }
    }
    
    return $configs[$chave] ?? $default;
}

function isEmpresaCadastroPermitido() {
    return getConfig('permitir_cadastro_empresas', '1') === '1';
}

function isPublicarVagasPermitido() {
    return getConfig('permitir_publicar_vagas', '1') === '1';
}

/**
 * Calcular valores de cobrança
 * Retorna array com valor_empresa, taxa_agencia e valor_prestador
 */
function calcularCobranca($valorPrestador, $vagas = 1, $dataEvento = null) {
    $cobrancaAtiva = getConfig('cobranca_ativa', '0') === '1';
    
    // Se cobrança desativada, valores são iguais
    if (!$cobrancaAtiva) {
        return [
            'valor_prestador' => $valorPrestador,
            'valor_empresa' => $valorPrestador,
            'taxa_agencia' => 0,
            'modelo' => 'sem_taxa'
        ];
    }
    
    $modelo = getConfig('modelo_cobranca', 'spread');
    $margem = (float)getConfig('margem_padrao', '20');
    $taxaFixa = (float)getConfig('taxa_fixa_profissional', '30');
    
    $valorEmpresa = $valorPrestador;
    $taxaAgencia = 0;
    
    switch ($modelo) {
        case 'spread':
            // Margem percentual sobre o valor
            $taxaAgencia = $valorPrestador * ($margem / 100);
            $valorEmpresa = $valorPrestador + $taxaAgencia;
            break;
            
        case 'taxa_fixa':
            // Taxa fixa por profissional
            $taxaAgencia = $taxaFixa * $vagas;
            $valorEmpresa = ($valorPrestador * $vagas) + $taxaAgencia;
            break;
            
        case 'ambos':
            // Spread + Taxa fixa
            $taxaSpread = $valorPrestador * ($margem / 100);
            $taxaAgencia = $taxaSpread + ($taxaFixa * $vagas);
            $valorEmpresa = ($valorPrestador * $vagas) + $taxaAgencia;
            break;
            
        case 'personalizado':
            // Definido manualmente - retorna sem cálculo
            return [
                'valor_prestador' => $valorPrestador,
                'valor_empresa' => null, // Será definido manualmente
                'taxa_agencia' => null,
                'modelo' => 'personalizado'
            ];
    }
    
    // Adicionar taxa de urgência se ativa e evento é em menos de 48h
    if (getConfig('taxa_urgencia_ativa', '0') === '1' && $dataEvento) {
        $dataEvt = strtotime($dataEvento);
        $agora = time();
        $horasAteEvento = ($dataEvt - $agora) / 3600;
        
        if ($horasAteEvento > 0 && $horasAteEvento < 48) {
            $taxaUrgencia = (float)getConfig('taxa_urgencia_valor', '50') * $vagas;
            $taxaAgencia += $taxaUrgencia;
            $valorEmpresa += $taxaUrgencia;
        }
    }
    
    return [
        'valor_prestador' => $valorPrestador,
        'valor_empresa' => round($valorEmpresa, 2),
        'taxa_agencia' => round($taxaAgencia, 2),
        'modelo' => $modelo
    ];
}
