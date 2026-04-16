#!/bin/bash
# Atalho rápido para ativar SSL Self-Signed

echo "🔒 Ativando SSL Self-Signed para desenvolvimento..."
echo ""

# Executar como root
if [ "$EUID" -ne 0 ]; then
    exec sudo bash "$0" "$@"
fi

# Gerar certificado
SSL_DIR="/etc/ssl/psp-agendamento"
mkdir -p "$SSL_DIR"

openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout "$SSL_DIR/psp.key" \
    -out "$SSL_DIR/psp.crt" \
    -subj "/C=PT/ST=Portugal/L=Lisboa/O=PSP/CN=localhost" \
    -addext "subjectAltName=DNS:localhost,IP:127.0.0.1" 2>/dev/null

# Criar config Apache
cat > /etc/apache2/sites-available/psp-agendamento-ssl.conf << 'EOF'
<VirtualHost *:443>
    ServerName localhost
    DocumentRoot /home/tiagovsk/psp-agendamento/public

    SSLEngine on
    SSLCertificateFile /etc/ssl/psp-agendamento/psp.crt
    SSLCertificateKeyFile /etc/ssl/psp-agendamento/psp.key

    SSLProtocol all -SSLv3 -TLSv1 -TLSv1.1
    SSLCipherSuite HIGH:!aNULL:!MD5

    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"

    <Directory /home/tiagovsk/psp-agendamento/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
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
a2enmod ssl rewrite headers >/dev/null 2>&1
a2ensite psp-agendamento-ssl >/dev/null 2>&1
apache2ctl configtest >/dev/null 2>&1 && systemctl restart apache2

echo ""
echo "✅ SSL ativado!"
echo ""
echo "📍 Acesse: https://localhost/psp-agendamento/"
echo ""
echo "⚠️  Navegador mostrará alerta - clique em 'Aceitar risco'"
