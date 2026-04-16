# 🔒 Guia de Configuração SSL

## Opções Disponíveis

| Opção | Uso | Validade | Custo |
|-------|-----|----------|-------|
| **LetsEncrypt** | Produção | 90 dias (renovável) | Gratuito |
| **Self-Signed** | Desenvolvimento | 365 dias | Gratuito |
| **Cloudflare Tunnel** | Produção sem porta 80 | 15 anos | Gratuito |

---

## 🟢 Opção 1: LetsEncrypt (Recomendado para Produção)

### Pré-requisitos
- Domínio apontando para o servidor (DNS configurado)
- Porta 80 liberada
- Apache rodando

### Passos

#### 1. Instalar Certbot
```bash
sudo apt update
sudo apt install certbot python3-certbot-apache -y
```

#### 2. Gerar Certificado
```bash
sudo certbot --apache -d seudominio.com -d www.seudominio.com
```

#### 3. Renovar Automaticamente
```bash
# Testar renovação
sudo certbot renew --dry-run

# Agendar renovação (já vem configurado por padrão)
sudo systemctl status certbot.timer
```

#### 4. Verificar
```bash
sudo certbot certificates
```

---

## 🟡 Opção 2: Certificado Self-Signed (Desenvolvimento)

### Método Automático (Script)

```bash
# Executar script de geração
cd /home/tiagovsk/psp-agendamento
sudo ./scripts/gerar_ssl_self_signed.sh
```

### Método Manual

#### 1. Gerar Certificado
```bash
sudo mkdir -p /etc/ssl/psp-agendamento
sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout /etc/ssl/psp-agendamento/psp.key \
  -out /etc/ssl/psp-agendamento/psp.crt \
  -subj "/C=PT/ST=Portugal/L=Lisboa/O=PSP/CN=localhost"
```

#### 2. Configurar Apache
```bash
sudo nano /etc/apache2/sites-available/psp-agendamento-ssl.conf
```

Conteúdo:
```apache
<VirtualHost *:443>
    ServerName localhost
    DocumentRoot /home/tiagovsk/psp-agendamento/public

    SSLEngine on
    SSLCertificateFile /etc/ssl/psp-agendamento/psp.crt
    SSLCertificateKeyFile /etc/ssl/psp-agendamento/psp.key

    <Directory /home/tiagovsk/psp-agendamento/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Headers de segurança
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
</VirtualHost>
```

#### 3. Ativar SSL
```bash
sudo a2enmod ssl
sudo a2ensite psp-agendamento-ssl
sudo apache2ctl configtest
sudo systemctl restart apache2
```

#### 4. Acessar
```
https://localhost/psp-agendamento/
```

⚠️ **Nota:** Navegadores mostrarão alerta de segurança. Clique em "Avançado" → "Aceitar risco".

---

## 🟢 Opção 3: Cloudflare Tunnel (Sem abrir portas)

### Vantagens
- Não precisa abrir porta 80/443 no firewall
- SSL automático e ilimitado
- Proteção DDoS incluída
- Funciona atrás de NAT

### Passos

#### 1. Instalar cloudflared
```bash
# Debian/Ubuntu
curl -fsSL https://pkg.cloudflare.com/cloudflare-main.gpg | \
  sudo tee /usr/share/keyrings/cloudflare-main.gpg >/dev/null

echo 'deb [signed-by=/usr/share/keyrings/cloudflare-main.gpg] \
  https://pkg.cloudflare.com/cloudflared jammy main' | \
  sudo tee /etc/apt/sources.list.d/cloudflared.list

sudo apt-get update && sudo apt-get install cloudflared
```

#### 2. Criar Tunnel
```bash
# Autenticar (abre navegador)
sudo cloudflared tunnel login

# Criar tunnel
sudo cloudflared tunnel create psp-agendamento
```

#### 3. Configurar
```bash
# Editar config
sudo nano /etc/cloudflared/config.yml
```

Conteúdo:
```yaml
tunnel: psp-agendamento
credentials-file: /root/.cloudflared/psp-agendamento.json

ingress:
  - hostname: psp-agendamento.seudominio.com
    service: http://localhost:80
  - service: http_status:404
```

#### 4. Iniciar
```bash
# Testar
sudo cloudflared tunnel run psp-agendamento

# Ou como serviço
sudo cloudflared service install
sudo systemctl start cloudflared
sudo systemctl enable cloudflared
```

---

## 🔧 Configuração Apache Completa (Produção)

### VirtualHost HTTP (Redireciona para HTTPS)
```apache
<VirtualHost *:80>
    ServerName seudominio.com
    ServerName www.seudominio.com
    ServerAlias psp-agendamento
    DocumentRoot /home/tiagovsk/psp-agendamento/public

    # Redirecionar para HTTPS
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

    <Directory /home/tiagovsk/psp-agendamento/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### VirtualHost HTTPS
```apache
<VirtualHost *:443>
    ServerName seudominio.com
    ServerName www.seudominio.com
    DocumentRoot /home/tiagovsk/psp-agendamento/public

    # SSL
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/seudominio.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/seudominio.com/privkey.pem

    # SSL Modern Config
    SSLProtocol all -SSLv3 -TLSv1 -TLSv1.1
    SSLCipherSuite ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256
    SSLHonorCipherOrder off
    SSLSessionTickets on

    # HSTS
    Header always set Strict-Transport-Security "max-age=63072000; includeSubDomains; preload"

    # Segurança
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"

    <Directory /home/tiagovsk/psp-agendamento/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Logs
    ErrorLog ${APACHE_LOG_DIR}/psp-error.log
    CustomLog ${APACHE_LOG_DIR}/psp-access.log combined
</VirtualHost>
```

---

## ✅ Verificação

### Testar SSL
```bash
# Verificar certificado
openssl s_client -connect localhost:443 -servername localhost

# Testar com curl
curl -k https://localhost/psp-agendamento/

# Testar online (produção)
# https://www.ssllabs.com/ssltest/
```

### Verificar Redirecionamento
```bash
curl -I http://localhost/psp-agendamento/
# Deve retornar 301 para HTTPS
```

---

## 🐛 Troubleshooting

### Erro: Certificado expirado
```bash
# LetsEncrypt
sudo certbot renew --force-renewal
```

### Erro: Permissões
```bash
sudo chown -R www-data:www-data /home/tiagovsk/psp-agendamento/public
sudo chmod -R 755 /home/tiagovsk/psp-agendamento
```

### Erro: Apache não inicia
```bash
# Verificar config
sudo apache2ctl configtest

# Ver logs
tail -f /var/log/apache2/error.log
```

### Erro: Porta 443 ocupada
```bash
# Verificar o que usa a porta
sudo lsof -i :443
sudo netstat -tlnp | grep 443
```

---

## 📊 Comparação de Métodos

| Critério | LetsEncrypt | Self-Signed | Cloudflare |
|----------|-------------|-------------|------------|
| Custo | Grátis | Grátis | Grátis |
| Validade | 90 dias | 1 ano | 15 anos |
| Confiança | ✅ Alta | ❌ Baixa | ✅ Alta |
| Setup | Médio | Fácil | Fácil |
| Renovação | Auto | Manual | Auto |
| DNS Público | Necessário | Não | Opcional |
| Porta 80 | Necessária | Não | Não |

---

## 🎯 Recomendação

| Cenário | Recomendação |
|---------|--------------|
| **Produção com domínio** | LetsEncrypt |
| **Desenvolvimento local** | Self-Signed |
| **Sem domínio/NAT** | Cloudflare Tunnel |
| **Ambiente corporativo** | Certificado comercial |

---

## 📞 Comandos Úteis

```bash
# Ver sites ativos
apache2ctl -S

# Recarregar Apache
sudo systemctl reload apache2

# Ver status SSL
sudo openssl s_client -showcerts -connect localhost:443

# Gerar CSR (para CA comercial)
openssl req -new -newkey rsa:2048 -nodes \
  -keyout server.key -out server.csr
```

---

**Próximos passos:** Execute o script `scripts/configurar_ssl.sh` para configuração automática.
