<?php
/**
 * Script de Envio de Lembretes Automáticos
 *
 * Este script deve ser executado via cron a cada hora:
 * 0 * * * * /usr/bin/php /caminho/para/psp-agendamento/scripts/EnviarLembretes.php
 *
 * Envia lembretes de agendamento:
 * - 24 horas antes (email + SMS)
 * - 1 hora antes (email + SMS)
 */

// Configurar caminho correto
$rootDir = dirname(__DIR__);

// Incluir configuração
require_once $rootDir . '/config/config.php';
require_once $rootDir . '/config/email.php';
require_once $rootDir . '/config/sms.php';

// Opcional: PHPMailer
if (file_exists($rootDir . '/config/phpmailer.php')) {
    require_once $rootDir . '/config/phpmailer.php';
}

echo "=== Envio de Lembretes PSP ===\n";
echo "Data: " . date('Y-m-d H:i:s') . "\n\n";

// Verificar se SMS está ativo
$smsAtivo = SMS_ENABLED && SMS_PROVIDER !== 'mock';
echo "SMS Ativo: " . ($smsAtivo ? 'Sim (' . SMS_PROVIDER . ')' : 'Não') . "\n";

try {
    $db = getDbConnection();
    echo "Conexão DB: OK\n\n";
} catch (Exception $e) {
    die("Erro na conexão com a base de dados: " . $e->getMessage() . "\n");
}

// =========================================================================
// LEMBRETES DE 24 HORAS
// =========================================================================
echo "--- Lembretes 24h ---\n";

$dataAmanha = date('Y-m-d', strtotime('+1 day'));
$horaAtual = date('H:i:s');

// Buscar agendamentos de amanhã que ainda não receberam lembrete de 24h
$stmt = $db->prepare("
    SELECT a.codigo_agendamento, a.nome_cidadao, a.email, a.telemovel,
           a.lembrete_24h_enviado, a.estado,
           e.nome as esquadra_nome, s.nome as servico_nome,
           a.data_agendamento, a.hora_agendamento
    FROM agendamentos a
    JOIN esquadras e ON a.esquadra_id = e.id
    JOIN servicos s ON a.servico_id = s.id
    WHERE a.data_agendamento = ?
    AND a.hora_agendamento >= ?
    AND a.estado IN ('confirmado')
    AND (a.lembrete_24h_enviado IS NULL OR a.lembrete_24h_enviado = 0)
");

$stmt->execute([$dataAmanha, $horaAtual]);
$agendamentos24h = $stmt->fetchAll();

echo "Agendamentos encontrados: " . count($agendamentos24h) . "\n";

foreach ($agendamentos24h as $agendamento) {
    echo "\nProcessando: {$agendamento['codigo_agendamento']} - {$agendamento['nome_cidadao']}\n";

    $enviadoEmail = false;
    $enviadoSMS = false;

    // Enviar email de lembrete
    try {
        if (function_exists('enviarEmailPHPMailer')) {
            $assunto = "Lembrete: Agendamento PSP Amanhã";
            $corpo = gerarEmailLembreteHTML($agendamento);
            $enviadoEmail = enviarEmailPHPMailer($agendamento['email'], $assunto, $corpo);
        } else {
            $enviadoEmail = enviarLembreteAgendamento($agendamento['codigo_agendamento'], $db);
        }
        echo "  Email: " . ($enviadoEmail ? 'Enviado ✓' : 'Falha ✗') . "\n";
    } catch (Exception $e) {
        echo "  Email: Erro - " . $e->getMessage() . "\n";
    }

    // Enviar SMS de lembrete
    if ($smsAtivo) {
        try {
            $enviadoSMS = enviarSMSLembrete($agendamento['codigo_agendamento'], $db);
            echo "  SMS: " . ($enviadoSMS ? 'Enviado ✓' : 'Falha ✗') . "\n";
        } catch (Exception $e) {
            echo "  SMS: Erro - " . $e->getMessage() . "\n";
        }
    } else {
        echo "  SMS: Skip (desativado)\n";
    }

    // Marcar lembrete como enviado
    if ($enviadoEmail || $enviadoSMS) {
        $updateStmt = $db->prepare("UPDATE agendamentos SET lembrete_24h_enviado = 1, data_lembrete_24h = NOW() WHERE codigo_agendamento = ?");
        $updateStmt->execute([$agendamento['codigo_agendamento']]);
        echo "  Status: Marcado como enviado\n";
    }
}

// =========================================================================
// LEMBRETES DE 1 HORA
// =========================================================================
echo "\n--- Lembretes 1h ---\n";

$dataHoje = date('Y-m-d');
$horaMaisUma = date('H:i:s', strtotime('+1 hour'));

// Buscar agendamentos da próxima hora que ainda não receberam lembrete de 1h
$stmt = $db->prepare("
    SELECT a.codigo_agendamento, a.nome_cidadao, a.email, a.telemovel,
           a.lembrete_1h_enviado, a.estado,
           e.nome as esquadra_nome, s.nome as servico_nome,
           a.data_agendamento, a.hora_agendamento
    FROM agendamentos a
    JOIN esquadras e ON a.esquadra_id = e.id
    JOIN servicos s ON a.servico_id = s.id
    WHERE a.data_agendamento = ?
    AND a.hora_agendamento BETWEEN ? AND ?
    AND a.estado IN ('confirmado')
    AND (a.lembrete_1h_enviado IS NULL OR a.lembrete_1h_enviado = 0)
");

$horaMenosUma = date('H:i:s', strtotime('-1 hour'));
$stmt->execute([$dataHoje, $horaMenosUma, $horaMaisUma]);
$agendamentos1h = $stmt->fetchAll();

echo "Agendamentos encontrados: " . count($agendamentos1h) . "\n";

foreach ($agendamentos1h as $agendamento) {
    echo "\nProcessando: {$agendamento['codigo_agendamento']} - {$agendamento['nome_cidadao']}\n";

    $enviadoEmail = false;
    $enviadoSMS = false;

    // Email de lembrete 1h
    try {
        $assunto = "Último Lembrete: Agendamento PSP em 1 hora";
        $corpo = gerarEmailLembrete1hHTML($agendamento);

        if (function_exists('enviarEmailPHPMailer')) {
            $enviadoEmail = enviarEmailPHPMailer($agendamento['email'], $assunto, $corpo);
        } else {
            $enviadoEmail = enviarEmail($agendamento['email'], $assunto, $corpo);
        }
        echo "  Email: " . ($enviadoEmail ? 'Enviado ✓' : 'Falha ✗') . "\n";
    } catch (Exception $e) {
        echo "  Email: Erro - " . $e->getMessage() . "\n";
    }

    // SMS de lembrete 1h
    if ($smsAtivo) {
        try {
            $mensagem = "PSP: O seu agendamento é daqui a 1 hora ({$agendamento['hora_agendamento']}). Código: {$agendamento['codigo_agendamento']}";
            $enviadoSMS = enviarSMS($agendamento['telemovel'], $mensagem);
            echo "  SMS: " . ($enviadoSMS ? 'Enviado ✓' : 'Falha ✗') . "\n";
        } catch (Exception $e) {
            echo "  SMS: Erro - " . $e->getMessage() . "\n";
        }
    } else {
        echo "  SMS: Skip (desativado)\n";
    }

    // Marcar lembrete como enviado
    if ($enviadoEmail || $enviadoSMS) {
        $updateStmt = $db->prepare("UPDATE agendamentos SET lembrete_1h_enviado = 1, data_lembrete_1h = NOW() WHERE codigo_agendamento = ?");
        $updateStmt->execute([$agendamento['codigo_agendamento']]);
        echo "  Status: Marcado como enviado\n";
    }
}

// =========================================================================
// RESUMO
// =========================================================================
echo "\n=== Resumo ===\n";
echo "Lembretes 24h processados: " . count($agendamentos24h) . "\n";
echo "Lembretes 1h processados: " . count($agendamentos1h) . "\n";
echo "\nConcluído: " . date('Y-m-d H:i:s') . "\n";

/**
 * Gerar email de lembrete 1h antes
 */
function gerarEmailLembrete1hHTML($agendamento) {
    $horaFormatada = formatarHora($agendamento['hora_agendamento']);

    return <<<HTML
<!DOCTYPE html>
<html>
<head><style>
    body { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; }
    .header { background: #003366; color: white; padding: 20px; text-align: center; }
    .content { padding: 20px; }
    .urgente { background: #ff6b6b; color: white; padding: 15px; border-radius: 8px; margin: 15px 0; text-align: center; font-weight: bold; }
    .codigo { font-size: 24px; font-weight: bold; color: #003366; text-align: center; padding: 15px; background: #f0f0f0; border-radius: 8px; margin: 15px 0; }
</style></head>
<body>
    <div class="header"><h1>⏰ Último Lembrete PSP</h1></div>
    <div class="content">
        <div class="urgente">O seu agendamento é daqui a 1 hora!</div>
        <div class="codigo">{$agendamento['codigo_agendamento']}</div>
        <p><strong>Hora:</strong> {$horaFormatada}</p>
        <p><strong>Local:</strong> {$agendamento['esquadra_nome']}</p>
        <p style="color: #666; font-size: 14px;">Por favor, chegue 10 minutos antes do horário marcado.</p>
    </div>
</body>
</html>
HTML;
}
