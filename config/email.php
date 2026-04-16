<?php
/**
 * Sistema de Envio de Emails
 */

/**
 * Enviar email de confirmação de agendamento
 */
function enviarEmailConfirmacao($codigo, $db) {
    // Buscar dados do agendamento
    $stmt = $db->prepare("SELECT a.*, e.nome as esquadra_nome, e.morada, s.nome as servico_nome
                          FROM agendamentos a
                          JOIN esquadras e ON a.esquadra_id = e.id
                          JOIN servicos s ON a.servico_id = s.id
                          WHERE a.codigo_agendamento = ?");
    $stmt->execute([$codigo]);
    $agendamento = $stmt->fetch();

    if (!$agendamento) {
        throw new Exception("Agendamento não encontrado");
    }

    $assunto = "Confirmação de Agendamento - " . $codigo;
    $corpo = gerarEmailHTML($agendamento);
    $texto = gerarEmailTexto($agendamento);

    return enviarEmail($agendamento['email'], $assunto, $corpo, $texto);
}

/**
 * Enviar email genérico
 */
function enviarEmail($destinatario, $assunto, $corpoHTML, $corpoTexto = '') {
    // Em produção, usar PHPMailer ou serviço como SendGrid/Mailgun
    // Por enquanto, usa mail() nativo com headers adequados

    $headers = [
        "From: " . SITE_NAME . " <noreply@psp-agendamento.pt>",
        "Reply-To: noreply@psp-agendamento.pt",
        "X-Mailer: PHP/" . phpversion(),
        "MIME-Version: 1.0",
        "Content-Type: text/html; charset=UTF-8"
    ];

    return mail($destinatario, $assunto, $corpoHTML, implode("\r\n", $headers));
}

/**
 * Gerar corpo do email em HTML
 */
function gerarEmailHTML($agendamento) {
    $dataFormatada = formatarData($agendamento['data_agendamento'], 'd \d\e F \d\e Y');
    $horaFormatada = formatarHora($agendamento['hora_agendamento']);

    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; }
        .header { background: #003366; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .codigo { background: #fff; border: 2px solid #003366; padding: 15px; font-size: 24px; font-weight: bold; text-align: center; letter-spacing: 3px; margin: 20px 0; }
        .detalhes { background: #fff; padding: 15px; border-radius: 8px; }
        .detalhes p { margin: 10px 0; }
        .footer { background: #333; color: #999; padding: 15px; text-align: center; font-size: 12px; }
        .qr-code { text-align: center; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>👮 Agendamento PSP</h1>
        <p>Polícia de Segurança Pública</p>
    </div>
    <div class="content">
        <h2>O Seu Agendamento</h2>
        <p>Olá <strong>{$agendamento['nome_cidadao']}</strong>,</p>
        <p>O seu agendamento foi confirmado com sucesso!</p>

        <div class="codigo">{$agendamento['codigo_agendamento']}</div>

        <div class="qr-code">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={$agendamento['codigo_agendamento']}" alt="QR Code">
        </div>

        <div class="detalhes">
            <h3>Detalhes do Agendamento</h3>
            <p><strong>Serviço:</strong> {$agendamento['servico_nome']}</p>
            <p><strong>Esquadra:</strong> {$agendamento['esquadra_nome']}</p>
            <p><strong>Morada:</strong> {$agendamento['morada']}</p>
            <p><strong>Data:</strong> {$dataFormatada}</p>
            <p><strong>Hora:</strong> {$horaFormatada}</p>
        </div>

        <p style="margin-top: 20px; color: #666;">
            📍 Por favor, chegue 10 minutos antes da hora marcada e apresente o código na receção.
        </p>
    </div>
    <div class="footer">
        <p>Este email foi enviado automaticamente. Não responda a esta mensagem.</p>
        <p>&copy; " . date('Y') . " PSP - Polícia de Segurança Pública</p>
    </div>
</body>
</html>
HTML;
}

/**
 * Gerar corpo do email em texto simples
 */
function gerarEmailTexto($agendamento) {
    $dataFormatada = formatarData($agendamento['data_agendamento'], 'd/m/Y');
    $horaFormatada = formatarHora($agendamento['hora_agendamento']);

    return <<<TEXTO
AGENDAMENTO PSP - Confirmação

Código: {$agendamento['codigo_agendamento']}

Detalhes:
Serviço: {$agendamento['servico_nome']}
Esquadra: {$agendamento['esquadra_nome']}
Morada: {$agendamento['morada']}
Data: {$dataFormatada}
Hora: {$horaFormatada}

Por favor, chegue 10 minutos antes da hora marcada.

---
PSP - Polícia de Segurança Pública
TEXTO;
}

/**
 * Enviar lembrete de agendamento (24h antes)
 */
function enviarLembreteAgendamento($codigo, $db) {
    $stmt = $db->prepare("SELECT a.*, e.nome as esquadra_nome, s.nome as servico_nome
                          FROM agendamentos a
                          JOIN esquadras e ON a.esquadra_id = e.id
                          JOIN servicos s ON a.servico_id = s.id
                          WHERE a.codigo_agendamento = ?");
    $stmt->execute([$codigo]);
    $agendamento = $stmt->fetch();

    if (!$agendamento) {
        return false;
    }

    // Verificar se já foi enviado lembrete
    if (!empty($agendamento['lembrete_enviado'])) {
        return false;
    }

    $assunto = "Lembrete: Agendamento PSP Amanhã";
    $corpo = gerarEmailLembreteHTML($agendamento);

    return enviarEmail($agendamento['email'], $assunto, $corpo);
}

/**
 * Gerar email de lembrete
 */
function gerarEmailLembreteHTML($agendamento) {
    $dataFormatada = formatarData($agendamento['data_agendamento'], 'd/m/Y');
    $horaFormatada = formatarHora($agendamento['hora_agendamento']);

    return <<<HTML
<!DOCTYPE html>
<html>
<head><style>
    body { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; }
    .header { background: #003366; color: white; padding: 20px; text-align: center; }
    .content { padding: 20px; }
    .destaque { background: #ffcc00; padding: 15px; border-radius: 8px; margin: 15px 0; }
</style></head>
<body>
    <div class="header"><h1>🔔 Lembrete PSP</h1></div>
    <div class="content">
        <div class="destaque">
            <strong>O seu agendamento é amanhã!</strong>
        </div>
        <p><strong>Código:</strong> {$agendamento['codigo_agendamento']}</p>
        <p><strong>Hora:</strong> {$horaFormatada}</p>
        <p><strong>Local:</strong> {$agendamento['esquadra_nome']}</p>
    </div>
</body>
</html>
HTML;
}
