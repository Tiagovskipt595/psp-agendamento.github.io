#!/bin/bash
# Script para gerar certificado SSL Self-Signed para desenvolvimento

set -e

echo "🔒 Gerando certificado SSL Self-Signed..."
echo ""

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Verificar se é root
if [ "$EUID" -ne 0 ]; then
    echo -e "${YELLOW}⚠️  Este script precisa de root. Executando com sudo...${NC}"
    exec sudo bash "$0" "$@"
fi

# Criar diretório
SSL_DIR="/etc/ssl/psp-agendamento"
mkdir -p "$SSL_DIR"

# Gerar certificado
echo "📝 Gerando chave e certificado..."
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout "$SSL_DIR/psp.key" \
    -out "$SSL_DIR/psp.crt" \
    -subj "/C=PT/ST=Portugal/L=Lisboa/O=PSP/CN=localhost" \
    -addext "subjectAltName=DNS:localhost,IP:127.0.0.1"

echo -e "${GREEN}✅ Certificado gerado em $SSL_DIR${NC}"
echo ""

# Criar configuração do Apache
APACHE_CONFIG="/etc/apache2/sites-available/psp-agendamento-ssl.conf"
cat > "$APACHE_CONFIG" << 'EOF'
<VirtualHost *:443>
    ServerName localhost
    DocumentRoot /home/tiagovsk/psp-agendamento/public

    SSLEngine on
    SSLCertificateFile /etc/ssl/psp-agendamento/psp.crt
    SSLCertificateKeyFile /etc/ssl/psp-agendamento/psp.key

    # SSL Modern Config
    SSLProtocol all -SSLv3 -TLSv1 -TLSv1.1
    SSLCipherSuite HIGH:!aNULL:!MD5
    SSLHonorCipherOrder on

    # Headers de segurança
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-XSS-Protection "1; mode=block"

    <Directory /home/tiagovsk/psp-agendamento/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Logs
    ErrorLog ${APACHE_LOG_DIR}/psp-ssl-error.log
    CustomLog ${APACHE_LOG_DIR}/psp-ssl-access.log combined
</VirtualHost>

<VirtualHost *:80>
    ServerName localhost
    DocumentRoot /home/tiagovsk/psp-agendamento/public

    # Redirecionar HTTP para HTTPS
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

    <Directory /home/tiagovsk/psp-agendamento/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
EOF

echo -e "${GREEN}✅ Configuração Apache criada${NC}"

# Ativar módulos e site
echo "🔧 Ativando módulos e site..."
a2enmod ssl 2>/dev/null || true
a2enmod rewrite 2>/dev/null || true
a2enmod headers 2>/dev/null || true
a2ensite psp-agendamento-ssl 2>/dev/null || true

# Desativar site default se existir
a2dissite 000-default.conf 2>/dev/null || true

# Testar configuração
echo "🧪 Testando configuração do Apache..."
if apache2ctl configtest; then
    echo -e "${GREEN}✅ Configuração válida${NC}"
else
    echo -e "${RED}❌ Erro na configuração${NC}"
    exit 1
fi

# Reiniciar Apache
echo "🔄 Reiniciando Apache..."
systemctl restart apache2

echo ""
echo -e "${GREEN}============================================${NC}"
echo -e "${GREEN}✅ SSL configurado com sucesso!${NC}"
echo -e "${GREEN}============================================${NC}"
echo ""
echo "📍 Acesse:"
echo "   https://localhost/psp-agendamento/"
echo "   https://127.0.0.1/psp-agendamento/"
echo ""
echo "⚠️  IMPORTANTE:"
echo "   Navegadores mostrarão alerta de segurança."
echo "   Clique em 'Avançado' → 'Aceitar risco e continuar'"
echo ""
echo "📋 Detalhes do certificado:"
echo "   - Validade: 365 dias"
echo "   - Domínio: localhost"
echo "   - Tipo: Self-Signed (apenas desenvolvimento)"
echo ""
echo "🔍 Verificar certificado:"
echo "   openssl s_client -connect localhost:443"
echo ""
