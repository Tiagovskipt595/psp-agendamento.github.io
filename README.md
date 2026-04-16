# 🚀 PSP Agendamento - Sistema de Agendamento Online

Sistema de agendamento online para esquadras da PSP (Polícia de Segurança Pública).

## 🌐 Acesso Rápido

### URL de Acesso
```
http://localhost/psp-agendamento/
```
ou
```
http://localhost/psp-agendamento/public/
```

### Credenciais de Teste (Agente)
```
Email: admin@psp.pt
Senha: admin123
```

## 📋 Pré-requisitos

- PHP 7.4 ou superior
- MySQL 5.7+ ou MariaDB 10.3+
- Apache com mod_rewrite ativado
- Composer (opcional, para dependências futuras)

## ⚡ Instalação Rápida

### 1. Clonar/Copiar o projeto
```bash
# Coloque na sua pasta web (ex: /var/www/html ou htdocs)
```

### 2. Configurar Banco de Dados
```bash
# Importar o schema
mysql -u root -p psp_agendamento < config/database.sql
```

### 3. Configurar Credenciais (Opcional)
Edite `config/config.php` se necessário:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'psp_agendamento');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 4. Acessar
```
http://localhost/psp-agendamento/
```

## 🔒 SSL/HTTPS

### Desenvolvimento (Self-Signed)
```bash
# Método rápido
sudo ./scripts/ativar_ssl.sh

# Ou menu interativo
sudo ./scripts/configurar_ssl.sh
```

Acesso: `https://localhost/psp-agendamento/`

⚠️ Navegadores mostrarão alerta - clique em "Avançado" → "Aceitar risco"

### Produção (LetsEncrypt)
```bash
sudo ./scripts/configurar_ssl.sh
# Opção 1: LetsEncrypt
```

Requer domínio configurado.

## 📁 Estrutura de URLs

| URL | Descrição |
|-----|-----------|
| `/` | Página inicial (lista esquadras) |
| `/esquadra/{id}/servicos` | Serviços de uma esquadra |
| `/agendar/{esquadra}/{servico}` | Agendar atendimento |
| `/validar/{codigo}` | Validar código de agendamento |
| `/confirmacao/{codigo}` | Confirmar agendamento |
| `/login` | Login de agentes PSP |
| `/dashboard` | Dashboard do agente |
| `/pesquisar` | Pesquisar agendamentos |

## 🔧 Comandos Úteis

### Iniciar servidor PHP embutido (desenvolvimento)
```bash
cd public
php -S localhost:8000
```
Acesso: `http://localhost:8000`

### Verificar se Apache está rodando
```bash
sudo systemctl status apache2  # Linux
sudo apachectl status          # macOS
```

### Ativar mod_rewrite (Apache)
```bash
sudo a2enmod rewrite  # Debian/Ubuntu
sudo systemctl restart apache2
```

## 🛠️ Troubleshooting

### Erro 404 nas páginas
Verifique se o mod_rewrite está ativo:
```bash
apache2ctl -M | grep rewrite
```

### Erro de conexão com banco
```bash
# Testar conexão
mysql -u root -p -e "SHOW DATABASES;"
```

### Permissões de arquivos
```bash
chmod -R 755 /caminho/para/psp-agendamento
chmod -R 777 /caminho/para/psp-agendamento/config
```

## 📞 Suporte

Para questões técnicas, contacte a equipe de desenvolvimento.

---

**Desenvolvido para a PSP** 🇵🇹
