<?php
/**
 * Segurança - CSRF, Rate Limiting, Sanitização Avançada
 */

/**
 * Gerar token CSRF
 */
function gerarTokenCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validar token CSRF
 */
function validarTokenCSRF($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Renovar token CSRF (após ação sensível)
 */
function renovarTokenCSRF() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Input CSRF hidden field
 */
function csrfInput() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(gerarTokenCSRF()) . '">';
}

/**
 * Rate Limiting simples baseado em sessão/IP
 */
function rateLimit($identificador, $limite = 10, $janelaSegundos = 60) {
    $chave = "rate_limit_{$identificador}";

    if (!isset($_SESSION[$chave])) {
        $_SESSION[$chave] = ['tentativas' => 0, 'inicio' => time()];
    }

    $dados = $_SESSION[$chave];
    $agora = time();

    // Resetar janela expirada
    if ($agora - $dados['inicio'] > $janelaSegundos) {
        $_SESSION[$chave] = ['tentativas' => 0, 'inicio' => $agora];
        return ['permitido' => true, 'restante' => $limite];
    }

    // Verificar limite
    if ($dados['tentativas'] >= $limite) {
        return [
            'permitido' => false,
            'restante' => 0,
            'retry_after' => $janelaSegundos - ($agora - $dados['inicio'])
        ];
    }

    // Incrementar tentativa
    $_SESSION[$chave]['tentativas']++;

    return [
        'permitido' => true,
        'restante' => $limite - $_SESSION[$chave]['tentativas']
    ];
}

/**
 * Sanitização avançada
 */
function sanitizeAvancado($data, $tipo = 'string') {
    if (is_array($data)) {
        return array_map(fn($v) => sanitizeAvancado($v, $tipo), $data);
    }

    $data = trim($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');

    switch ($tipo) {
        case 'email':
            return filter_var($data, FILTER_SANITIZE_EMAIL);
        case 'int':
            return filter_var($data, FILTER_SANITIZE_NUMBER_INT);
        case 'float':
            return filter_var($data, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        case 'tel':
            return preg_replace('/[^0-9+\s-]/', '', $data);
        case 'cc':
            return strtoupper(preg_replace('/[^0-9A-Z]/', '', $data));
        default:
            return $data;
    }
}

/**
 * XSS Prevention - Output encoding
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect seguro com validação de URL
 */
function redirectSeguro($url) {
    // Prevenir open redirect
    if (strpos($url, 'http') === 0 || strpos($url, '/') === 0) {
        header("Location: " . $url);
        exit;
    }
    header("Location: " . SITE_URL . ltrim($url, '/'));
    exit;
}

/**
 * Verificar se é request AJAX
 */
function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Headers de segurança HTTP
 */
function enviarHeadersSeguranca() {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

/**
 * Hash seguro para passwords (bcrypt com custo elevado)
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verificar password
 */
function verificarPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Logging de eventos de segurança
 */
function logSeguranca($evento, $detalhes = []) {
    $logFile = dirname(__DIR__) . '/logs/seguranca.log';
    $dir = dirname($logFile);

    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $entrada = [
        'data' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'evento' => $evento,
        'detalhes' => $detalhes,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ];

    file_put_contents(
        $logFile,
        json_encode($entrada, JSON_UNESCAPED_UNICODE) . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}
