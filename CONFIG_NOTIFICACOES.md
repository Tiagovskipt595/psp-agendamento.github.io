# Guia de Configuração de Notificações (Email e SMS)

Este guia explica como configurar o envio de emails e SMS para o sistema de agendamento PSP.

---

## 📧 CONFIGURAÇÃO DE EMAIL

### Opção 1: PHPMailer com Gmail (Recomendado para produção)

#### Passo 1: Instalar PHPMailer

**Via Composer (recomendado):**
```bash
cd /caminho/para/psp-agendamento
composer require phpmailer/phpmailer
```

**Via download manual:**
1. Descarregar de: https://github.com/PHPMailer/PHPMailer/releases
2. Extrair para `vendor/phpmailer/`

#### Passo 2: Configurar Gmail

1. Aceder a https://myaccount.google.com/security
2. Ativar "Verificação em 2 etapas"
3. Criar "Password de App":
   - Ir a: https://myaccount.google.com/apppasswords
   - Selecionar "Mail" e o dispositivo
   - Copiar a password gerada (16 caracteres)

#### Passo 3: Editar `config/phpmailer.php`

```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'seu-email@gmail.com');
define('SMTP_PASS', 'sua-app-password-aqui');
define('SMTP_SECURE', 'tls');
define('SMTP_FROM_EMAIL', 'seu-email@gmail.com');
define('SMTP_FROM_NAME', 'Agendamento PSP');
```

---

### Opção 2: SMTP Corporativo/Outlook

```php
define('SMTP_HOST', 'smtp.office365.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'seu-email@empresa.pt');
define('SMTP_PASS', 'sua-password');
define('SMTP_SECURE', 'tls');
define('SMTP_FROM_EMAIL', 'noreply@psp-agendamento.pt');
define('SMTP_FROM_NAME', 'Agendamento PSP');
```

---

### Opção 3: SendGrid (Grátis até 100 emails/dia)

1. Criar conta em https://sendgrid.com/
2. Criar API Key em Settings → API Keys
3. Configurar:

```php
define('SMTP_HOST', 'smtp.sendgrid.net');
define('SMTP_PORT', 587);
define('SMTP_USER', 'apikey');  // Literalmente "apikey"
define('SMTP_PASS', 'sua-sendgrid-api-key');
define('SMTP_SECURE', 'tls');
```

---

## 📱 CONFIGURAÇÃO DE SMS

### Opção 1: Twilio (Global, mais popular)

#### Passo 1: Criar conta
1. Aceder a https://www.twilio.com/try-twilio
2. Criar conta (trial grátis com $15 de crédito)
3. Comprar número de telefone

#### Passo 2: Obter credenciais
- Account SID: Dashboard → Account Info
- Auth Token: Dashboard → Account Info
- Twilio Number: Phone Numbers → Manage → Active

#### Passo 3: Configurar `config/sms.php`

```php
define('SMS_PROVIDER', 'twilio');
define('SMS_ENABLED', true);
define('TWILIO_SID', 'ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
define('TWILIO_TOKEN', 'seu-auth-token');
define('TWILIO_FROM', '+1234567890'); // Seu número Twilio
```

**Preços:** ~$0.0075 por SMS para Portugal

---

### Opção 2: SMS.pt (Provedor Português)

#### Passo 1: Criar conta
1. Aceder a https://www.sms.pt/
2. Criar conta
3. Comprar créditos

#### Passo 2: Obter API Key
- Painel de controlo → API → Gerar API Key

#### Passo 3: Configurar `config/sms.php`

```php
define('SMS_PROVIDER', 'smspt');
define('SMS_ENABLED', true);
define('SMSPT_API_KEY', 'sua-api-key-aqui');
define('SMSPT_FROM', 'PSP'); // Nome que aparece no destinatário
```

**Preços:** ~€0.035 por SMS para Portugal

---

### Opção 3: Mock (Para desenvolvimento)

Para testes sem enviar SMS reais:

```php
define('SMS_PROVIDER', 'mock');
define('SMS_ENABLED', true);
```

As SMS serão apenas registadas no log do servidor.

---

## ⏰ CONFIGURAR LEMBRETES AUTOMÁTICOS (CRON)

### Passo 1: Verificar se o cron está ativo

```bash
sudo systemctl status cron
```

### Passo 2: Editar crontab

```bash
crontab -e
```

### Passo 3: Adicionar entradas

```bash
# Enviar lembretes de agendamento a cada hora
0 * * * * /usr/bin/php /caminho/completo/psp-agendamento/scripts/EnviarLembretes.php >> /var/log/psp-lembretes.log 2>&1
```

### Passo 4: Testar manualmente

```bash
php /caminho/completo/psp-agendamento/scripts/EnviarLembretes.php
```

---

## 🧪 TESTAR CONFIGURAÇÃO

### Testar Email

Criar ficheiro `test_email.php`:

```php
<?php
require_once 'config/config.php';
require_once 'config/phpmailer.php';

$resultado = enviarEmailPHPMailer(
    'seu-email@teste.com',
    'Teste de Email PSP',
    '<h1>Teste</h1><p>Email enviado com sucesso!</p>'
);

echo $resultado ? 'SUCESSO' : 'FALHA';
```

### Testar SMS

```bash
php -r "
require 'config/config.php';
require 'config/sms.php';
testarSMS('+351912345678');
echo 'Teste enviado!';
"
```

---

## 📊 MONITORIZAÇÃO

### Verificar logs de envio

```bash
# Logs de email
tail -f /var/log/apache2/error.log | grep -i "email"

# Logs de SMS
tail -f /var/log/apache2/error.log | grep -i "sms"

# Logs do script de lembretes
tail -f /var/log/psp-lembretes.log
```

### Verificar na base de dados

```sql
-- Verificar lembretes enviados
SELECT codigo_agendamento, nome_cidadao, 
       lembrete_24h_enviado, data_lembrete_24h,
       lembrete_1h_enviado, data_lembrete_1h
FROM agendamentos
ORDER BY created_at DESC
LIMIT 20;
```

---

## 🔒 BOAS PRÁTICAS

1. **Nunca commitar passwords** para o repositório Git
2. Usar variáveis de ambiente para credenciais sensíveis
3. Manter `SMS_ENABLED = false` em desenvolvimento
4. Testar sempre com `SMS_PROVIDER = mock` antes de produção
5. Monitorizar falhas de envio nos logs

---

## 📋 RESUMO DOS FICHEIROS

| Ficheiro | Descrição |
|----------|-----------|
| `config/phpmailer.php` | Configuração de envio de emails |
| `config/sms.php` | Configuração de envio de SMS |
| `config/email.php` | Templates de email (já existente) |
| `scripts/EnviarLembretes.php` | Script de lembretes automáticos |

---

## ❓ RESOLUÇÃO DE PROBLEMAS

### Email não envia

1. Verificar logs: `tail -f /var/log/apache2/error.log`
2. Testar conexão SMTP: `telnet smtp.gmail.com 587`
3. Verificar se PHPMailer está instalado: `php -m | grep -i openssl`

### SMS não envia

1. Verificar se `SMS_ENABLED = true`
2. Verificar credenciais no `config/sms.php`
3. Testar com `SMS_PROVIDER = mock` primeiro
4. Verificar logs: `grep -i "sms" /var/log/apache2/error.log`

### Cron não executa

1. Verificar se cron está ativo: `sudo systemctl status cron`
2. Verificar logs do cron: `grep CRON /var/log/syslog`
3. Testar script manualmente primeiro
