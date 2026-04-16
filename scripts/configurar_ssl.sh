#!/bin/bash
# Script interativo para configuração SSL

set -e

echo "🔒 Configuração SSL - PSP Agendamento"
echo "======================================"
echo ""

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Verificar se é root
if [ "$EUID" -ne 0 ]; then
    exec sudo bash "$0" "$@"
fi

show_menu() {
    echo ""
    echo "Escolha o tipo de certificado:"
    echo ""
    echo "  1) ${GREEN}LetsEncrypt${NC} - Gratuito, produção (precisa de domínio)"
    echo "  2) ${YELLOW}Self-Signed${NC} - Desenvolvimento local (alerta no browser)"
    echo "  3) ${BLUE}Cloudflare Tunnel${NC} - Sem abrir portas, com domínio"
    echo "  4) Sair"
    echo ""
}

option_letsencrypt() {
    echo ""
    echo "🔐 Configurando LetsEncrypt..."
    echo ""

    # Verificar se domínio está configurado
    read -p "Digite o domínio (ex: psp.exemplo.com): " DOMAIN

    if [ -z "$DOMAIN" ]; then
        echo -e "${RED}❌ Domínio é obrigatório${NC}"
        return
    fi

    # Instalar certbot
    echo "📦 Instalando Certbot..."
    apt update
    apt install -y certbot python3-certbot-apache

    # Gerar certificado
    echo "📝 Gerando certificado..."
    certbot --apache -d "$DOMAIN"

    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ Certificado LetsEncrypt instalado!${NC}"
        echo ""
        echo "📍 Acesse: https://$DOMAIN"
        echo ""
        echo "🔄 Renovação automática configurada."
        echo "   Testar: certbot renew --dry-run"
    else
        echo -e "${RED}❌ Erro ao gerar certificado${NC}"
    fi
}

option_self_signed() {
    echo ""
    echo "🔐 Gerando certificado Self-Signed..."

    SSL_DIR="/etc/ssl/psp-agendamento"
    mkdir -p "$SSL_DIR"

    # Gerar certificado
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
        -keyout "$SSL_DIR/psp.key" \
        -out "$SSL_DIR/psp.crt" \
        -subj "/C=PT/ST=Portugal/L=Lisboa/O=PSP/CN=localhost" \
        -addext "subjectAltName=DNS:localhost,IP:127.0.0.1"

    echo -e "${GREEN}✅ Certificado gerado${NC}"

    # Criar VirtualHost
    APACHE_CONFIG="/etc/apache2/sites-available/psp-agendamento-ssl.conf"
    cat > "$APACHE_CONFIG" << EOF
<VirtualHost *:443>
    ServerName localhost
    DocumentRoot /home/tiagovsk/psp-agendamento/public

    SSLEngine on
    SSLCertificateFile /etc/ssl/psp-agendamento/psp.crt
    SSLCertificateKeyFile /etc/ssl/psp-agendamento/psp.key

    SSLProtocol all -SSLv3 -TLSv1 -TLSv1.1
    SSLCipherSuite HIGH:!aNULL:!MD5

    Header always set Strict-Transport-Security "max-age=31536000"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"

    <Directory /home/tiagovsk/psp-agendamento/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/psp-ssl-error.log
    CustomLog \${APACHE_LOG_DIR}/psp-ssl-access.log combined
</VirtualHost>

<VirtualHost *:80>
    ServerName localhost
    DocumentRoot /home/tiagovsk/psp-agendamento/public

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

    # Ativar
    a2enmod ssl rewrite headers
    a2ensite psp-agendamento-ssl
    apache2ctl configtest
    systemctl restart apache2

    echo -e "${GREEN}✅ SSL Self-Signed configurado!${NC}"
    echo ""
    echo "📍 Acesse: https://localhost/psp-agendamento/"
    echo ""
    echo "⚠️  Navegadores mostrarão alerta. Clique em 'Avançado' → 'Aceitar risco'"
}

option_cloudflare() {
    echo ""
    echo "🔐 Cloudflare Tunnel..."
    echo ""
    echo "Este método requer:"
    echo "  - Conta no Cloudflare"
    echo "  - Domínio gerenciado pelo Cloudflare"
    echo ""
    read -p "Deseja continuar? (s/n) " -n 1 -r
    echo

    if [[ ! $REPLY =~ ^[Ss]$ ]]; then
        return
    fi

    echo ""
    echo "📦 Instalando cloudflared..."

    # Instalar
    curl -fsSL https://pkg.cloudflare.com/cloudflare-main.gpg | \
        tee /usr/share/keyrings/cloudflare-main.gpg >/dev/null

    echo 'deb [signed-by=/usr/share/keyrings/cloudflare-main.gpg] \
        https://pkg.cloudflare.com/cloudflared jammy main' | \
        tee /etc/apt/sources.list.d/cloudflared.list

    apt-get update
    apt-get install -y cloudflared

    echo ""
    echo "🔑 Autenticando com Cloudflare..."
    echo "   Uma janela do navegador será aberta."
    cloudflared tunnel login

    echo ""
    echo "🚇 Criando tunnel..."
    read -p "Nome do tunnel (padrão: psp-agendamento): " TUNNEL_NAME
    TUNNEL_NAME=${TUNNEL_NAME:-psp-agendamento}

    cloudflared tunnel create "$TUNNEL_NAME"

    echo ""
    echo "📝 Configuração manual necessária:"
    echo "   1. Edite /etc/cloudflared/config.yml"
    echo "   2. Adicione o ingress para localhost:80"
    echo "   3. Execute: cloudflared tunnel run $TUNNEL_NAME"
    echo ""
    echo "📚 Veja instruções completas em: SSL_CONFIG.md"
}

# Loop principal
while true; do
    show_menu
    read -p "Opção: " opcao

    case $opcao in
        1) option_letsencrypt ;;
        2) option_self_signed ;;
        3) option_cloudflare ;;
        4) echo "Saindo..."; exit 0 ;;
        *) echo -e "${RED}Opção inválida${NC}" ;;
    esac
done
