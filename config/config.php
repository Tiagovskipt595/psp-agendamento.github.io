<?php
/**
 * Configuração da Base de Dados
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'psp_agendamento');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Configurações Gerais
 */
define('SITE_NAME', 'Agendamento PSP');
// URL base - ajusta automaticamente se estiver em subdiretório
$protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
define('SITE_URL', $protocolo . '://localhost/psp-agendamento/public/');
define('TIMEZONE', 'Europe/Lisbon');

/**
 * Configurações de Sessão
 */
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0); // 1 apenas em HTTPS
ini_set('session.use_strict_mode', 1);
session_start();

/**
 * Segurança (CSRF, Rate Limiting, etc.)
 */
require_once __DIR__ . '/security.php';

/**
 * Sistema de Emails
 */
require_once __DIR__ . '/email.php';

/**
 * Fuso horário
 */
date_default_timezone_set(TIMEZONE);

/**
 * Função de conexão com a base de dados
 */
function getDbConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        die("Erro de conexão: " . $e->getMessage());
    }
}

/**
 * Função para gerar código de agendamento único
 */
function gerarCodigoAgendamento() {
    return 'PSP-' . strtoupper(substr(uniqid(), -6));
}

/**
 * Função para validar email
 */
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Função para formatar data em português
 */
function formatarData($data, $formato = 'd/m/Y') {
    if (!$data) return '';
    $datetime = new DateTime($data);
    return $datetime->format($formato);
}

/**
 * Função para formatar hora
 */
function formatarHora($hora, $formato = 'H:i') {
    if (!$hora) return '';
    $datetime = new DateTime($hora);
    return $datetime->format($formato);
}

/**
 * Redirect seguro
 */
function redirect($url) {
    header("Location: " . $url);
    exit;
}

/**
 * Verificar se usuário está logado
 */
function estaLogado() {
    return isset($_SESSION['usuario_id']);
}

/**
 * Exigir login
 */
function exigirLogin() {
    if (!estaLogado()) {
        redirect(SITE_URL . 'login.php');
    }
}

/**
 * Sanitizar input
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Flash messages
 */
function setFlash($tipo, $mensagem) {
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensagem' => $mensagem];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
