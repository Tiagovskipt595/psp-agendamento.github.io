<?php
/**
 * Sistema de Envio de SMS
 *
 * Opções de provedores:
 * 1. Twilio (https://www.twilio.com/) - Global, mais caro
 * 2. Clockwork SMS (https://www.clockworksms.com/) - UK, bom para Europa
 * 3. TextLocal (https://www.textlocal.com/) - India/UK
 * 4. Vonage/Nexmo (https://vonage.com/) - Global
 * 5. Plivo (https://www.plivo.com/) - Alternativa ao Twilio
 *
 * Para Portugal, considerar também:
 * - SMS.pt (https://www.sms.pt/)
 * - MoxySMS (https://www.moxysms.com/)
 */

// Configurações SMS - EDITAR ESTES VALORES
define('SMS_PROVIDER', 'twilio');  // 'twilio', 'vonage', 'smspt', 'mock'
define('SMS_ENABLED', true);        // Ativar/desativar envio de SMS

// Twilio
define('TWILIO_SID', 'sua-account-sid');
define('TWILIO_TOKEN', 'seu-auth-token');
define('TWILIO_FROM', '+1234567890'); // Número Twilio

// Vonage/Nexmo
define('VONAGE_API_KEY', 'sua-api-key');
define('VONAGE_API_SECRET', 'seu-api-secret');
define('VONAGE_FROM', 'PSP'); // Nome remetente (se suportado)

// SMS.pt
define('SMSPT_API_KEY', 'sua-api-key');
define('SMSPT_FROM', 'PSP');

/**
 * Enviar SMS
 */
function enviarSMS($destinatario, $mensagem) {
    if (!SMS_ENABLED) {
        error_log('SMS desativado: ' . $mensagem);
        return false;
    }

    // Normalizar número de telefone (remover espaços, hífens, etc.)
    $destinatario = normalizePhoneNumber($destinatario);

    switch (SMS_PROVIDER) {
        case 'twilio':
            return enviarSMSTwilio($destinatario, $mensagem);
        case 'vonage':
            return enviarSMSVonage($destinatario, $mensagem);
        case 'smspt':
            return enviarSMSSMSPT($destinatario, $mensagem);
        case 'mock':
            return enviarSMSMock($destinatario, $mensagem);
        default:
            error_log('Provedor SMS desconhecido: ' . SMS_PROVIDER);
            return false;
    }
}

/**
 * Twilio SMS
 */
function enviarSMSTwilio($destinatario, $mensagem) {
    $url = 'https://api.twilio.com/2010-04-01/Accounts/' . TWILIO_SID . '/Messages.json';

    $dados = [
        'From' => TWILIO_FROM,
        'To' => $destinatario,
        'Body' => $mensagem
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($dados));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, TWILIO_SID . ':' . TWILIO_TOKEN);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($httpCode === 200) {
        error_log('SMS Twilio enviado para ' . $destinatario);
        return true;
    }

    error_log('Erro SMS Twilio (' . $httpCode . '): ' . $error . ' - Response: ' . $response);
    return false;
}

/**
 * Vonage/Nexmo SMS
 */
function enviarSMSVonage($destinatario, $mensagem) {
    $url = 'https://rest.nexmo.com/sms/json';

    $dados = [
        'api_key' => VONAGE_API_KEY,
        'api_secret' => VONAGE_API_SECRET,
        'from' => VONAGE_FROM,
        'to' => $destinatario,
        'text' => $mensagem
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($dados));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = json_decode($response, true);

    if ($httpCode === 200 && isset($result['messages'][0]['status']) && $result['messages'][0]['status'] === '0') {
        error_log('SMS Vonage enviado para ' . $destinatario);
        return true;
    }

    error_log('Erro SMS Vonage: ' . print_r($result, true));
    return false;
}

/**
 * SMS.pt (provedor português)
 */
function enviarSMSSMSPT($destinatario, $mensagem) {
    $url = 'https://api.sms.pt/v1/sms/send';

    $dados = [
        'from' => SMSPT_FROM,
        'to' => $destinatario,
        'message' => $mensagem,
        'apiKey' => SMSPT_API_KEY
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = json_decode($response, true);

    if ($httpCode === 200 && isset($result['success']) && $result['success'] === true) {
        error_log('SMS SMS.pt enviado para ' . $destinatario);
        return true;
    }

    error_log('Erro SMS SMS.pt: ' . print_r($result, true));
    return false;
}

/**
 * Mock SMS (para desenvolvimento/testing)
 * Apenas regista no log
 */
function enviarSMSMock($destinatario, $mensagem) {
    error_log('[MOCK SMS] Para: ' . $destinatario . ' | Mensagem: ' . $mensagem);
    return true;
}

/**
 * Normalizar número de telefone
 */
function normalizePhoneNumber($phone) {
    // Remover todos os caracteres não numéricos
    $phone = preg_replace('/[^0-9]/', '', $phone);

    // Se começar com 0 e tiver 9 dígitos (nacional português), adicionar +351
    if (strlen($phone) === 9 && $phone[0] === '9') {
        $phone = '351' . $phone;
    }

    // Se não tiver prefixo internacional, adicionar +351
    if (strlen($phone) === 9) {
        $phone = '351' . $phone;
    }

    // Adicionar + se não tiver
    if ($phone[0] !== '+') {
        $phone = '+' . $phone;
    }

    return $phone;
}

/**
 * Enviar SMS de confirmação de agendamento
 */
function enviarSMSConfirmacao($codigo, $db) {
    $stmt = $db->prepare("SELECT a.*, e.nome as esquadra_nome
                          FROM agendamentos a
                          JOIN esquadras e ON a.esquadra_id = e.id
                          WHERE a.codigo_agendamento = ?");
    $stmt->execute([$codigo]);
    $agendamento = $stmt->fetch();

    if (!$agendamento) {
        return false;
    }

    $dataFormatada = formatarData($agendamento['data_agendamento'], 'd/m/Y');
    $horaFormatada = formatarHora($agendamento['hora_agendamento']);

    $mensagem = "PSP: Agendamento confirmado! {$agendamento['esquadra_nome']}, {$dataFormatada} às {$horaFormatada}. Código: {$codigo}. Chegue 10min antes.";

    return enviarSMS($agendamento['telemovel'], $mensagem);
}

/**
 * Enviar SMS de lembrete (24h antes)
 */
function enviarSMSLembrete($codigo, $db) {
    $stmt = $db->prepare("SELECT a.*, e.nome as esquadra_nome
                          FROM agendamentos a
                          JOIN esquadras e ON a.esquadra_id = e.id
                          WHERE a.codigo_agendamento = ?");
    $stmt->execute([$codigo]);
    $agendamento = $stmt->fetch();

    if (!$agendamento) {
        return false;
    }

    $horaFormatada = formatarHora($agendamento['hora_agendamento']);

    $mensagem = "PSP: Lembrete: Agendamento amanhã às {$horaFormatada} em {$agendamento['esquadra_nome']}. Código: {$codigo}";

    return enviarSMS($agendamento['telemovel'], $mensagem);
}

/**
 * Enviar SMS de cancelamento
 */
function enviarSMSCancelamento($codigo, $db, $motivo = '') {
    $stmt = $db->prepare("SELECT a.*, e.nome as esquadra_nome
                          FROM agendamentos a
                          JOIN esquadras e ON a.esquadra_id = e.id
                          WHERE a.codigo_agendamento = ?");
    $stmt->execute([$codigo]);
    $agendamento = $stmt->fetch();

    if (!$agendamento) {
        return false;
    }

    $mensagem = "PSP: Agendamento {$codigo} cancelado.";
    if ($motivo) {
        $mensagem .= " Motivo: " . substr($motivo, 0, 50);
    }
    $mensagem .= " Contacte-nos para remarcar.";

    return enviarSMS($agendamento['telemovel'], $mensagem);
}

/**
 * Testar envio de SMS
 */
function testarSMS($numeroTeste = '+351912345678') {
    $mensagem = "Teste de SMS - PSP Agendamento - " . date('Y-m-d H:i:s');
    return enviarSMS($numeroTeste, $mensagem);
}
