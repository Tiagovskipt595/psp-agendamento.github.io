<?php
/**
 * Configuração do PHPMailer
 *
 * Para instalar o PHPMailer via Composer:
 *   composer require phpmailer/phpmailer
 *
 * Ou descarregar manualmente de:
 *   https://github.com/PHPMailer/PHPMailer
 */

// Configurações SMTP - EDITAR ESTES VALORES
define('SMTP_HOST', 'smtp.gmail.com');        // Servidor SMTP
define('SMTP_PORT', 587);                      // Porta (587 TLS, 465 SSL)
define('SMTP_USER', 'seu-email@gmail.com');   // Email de envio
define('SMTP_PASS', 'sua-senha-app');         // Password ou App Password
define('SMTP_SECURE', 'tls');                  // 'tls' ou 'ssl'
define('SMTP_FROM_EMAIL', 'noreply@psp-agendamento.pt');
define('SMTP_FROM_NAME', 'Agendamento PSP');

/**
 * Enviar email usando PHPMailer
 */
function enviarEmailPHPMailer($destinatario, $assunto, $corpoHTML, $corpoTexto = '') {
    // Verificar se PHPMailer está disponível
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        // Fallback para mail() nativo se PHPMailer não estiver instalado
        error_log('PHPMailer não encontrado. A usar mail() nativo.');
        return enviarEmailNativo($destinatario, $assunto, $corpoHTML, $corpoTexto);
    }

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // Configurações do servidor
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;

        // Charset e encoding
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        // Remetente e destinatário
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($destinatario);

        // Conteúdo
        $mail->isHTML(true);
        $mail->Subject = $assunto;
        $mail->Body = $corpoHTML;

        if (!empty($corpoTexto)) {
            $mail->AltBody = $corpoTexto;
        }

        // Enviar
        $mail->send();
        error_log('Email enviado com sucesso para: ' . $destinatario);
        return true;
    } catch (Exception $e) {
        error_log('Erro ao enviar email para ' . $destinatario . ': ' . $e->getMessage());
        return false;
    }
}

/**
 * Fallback para mail() nativo
 */
function enviarEmailNativo($destinatario, $assunto, $corpoHTML, $corpoTexto = '') {
    $headers = [
        "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">",
        "Reply-To: " . SMTP_FROM_EMAIL,
        "X-Mailer: PHP/" . phpversion(),
        "MIME-Version: 1.0",
        "Content-Type: text/html; charset=UTF-8"
    ];

    return mail($destinatario, $assunto, $corpoHTML, implode("\r\n", $headers));
}

/**
 * Enviar email com anexo (ex: PDF)
 */
function enviarEmailComAnexo($destinatario, $assunto, $corpoHTML, $caminhoAnexo) {
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        error_log('PHPMailer não disponível para envio com anexo.');
        return false;
    }

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;

        $mail->CharSet = 'UTF-8';
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($destinatario);

        $mail->isHTML(true);
        $mail->Subject = $assunto;
        $mail->Body = $corpoHTML;

        // Adicionar anexo
        $mail->addAttachment($caminhoAnexo);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Erro ao enviar email com anexo: ' . $e->getMessage());
        return false;
    }
}

/**
 * Testar conexão SMTP
 */
function testarSMTP() {
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        return ['sucesso' => false, 'erro' => 'PHPMailer não instalado'];
    }

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        $mail->SMTPDebug = 0;

        // Testar conexão
        $mail->smtpConnect();

        if ($mail->smtp->getError()) {
            return ['sucesso' => false, 'erro' => $mail->smtp->getError()['error']];
        }

        return ['sucesso' => true, 'erro' => null];
    } catch (Exception $e) {
        return ['sucesso' => false, 'erro' => $e->getMessage()];
    }
}
