# 🚀 Guia de Acesso Rápido

## URLs do Sistema

### Para Cidadãos
| Descrição | URL |
|-----------|-----|
| **Página Principal** | http://localhost/psp-agendamento/ |
| Listar Esquadras | http://localhost/psp-agendamento/ |
| Serviços de Esquadra | http://localhost/psp-agendamento/public/servicos.php?esquadra_id=1 |
| Agendar | http://localhost/psp-agendamento/public/agendar.php?esquadra_id=1 |
| Validar Código | http://localhost/psp-agendamento/public/validar.php |

### Para Agentes PSP
| Descrição | URL |
|-----------|-----|
| **Login** | http://localhost/psp-agendamento/public/login.php |
| Dashboard | http://localhost/psp-agendamento/public/dashboard.php |
| Pesquisar Agendamentos | http://localhost/psp-agendamento/public/pesquisar.php |

---

## 🔑 Credenciais de Acesso (Agentes)

```
┌─────────────────────────────────────┐
│ Email:   admin@psp.pt               │
│ Senha:   admin123                   │
└─────────────────────────────────────┘
```

---

## ⚡ Início Rápido

### Opção 1: Apache (Recomendado)
```bash
# Certifique-se que o Apache está rodando
sudo systemctl start apache2

# Acesse no navegador
http://localhost/psp-agendamento/
```

### Opção 2: Servidor PHP Embutido
```bash
# Navegue até a pasta public
cd /home/tiagovsk/psp-agendamento/public

# Inicie o servidor
php -S localhost:8000

# Acesse no navegador
http://localhost:8000
```

---

## 📋 Verificações Prévias

### 1. Banco de Dados
```bash
# Criar banco
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS psp_agendamento;"

# Importar schema
mysql -u root -p psp_agendamento < /home/tiagovsk/psp-agendamento/config/database.sql
```

### 2. Permissões
```bash
chmod -R 755 /home/tiagovsk/psp-agendamento
chmod -R 777 /home/tiagovsk/psp-agendamento/config
```

### 3. mod_rewrite (Apache)
```bash
# Verificar se está ativo
apache2ctl -M | grep rewrite

# Ativar se necessário
sudo a2enmod rewrite
sudo systemctl restart apache2
```

---

## 🎯 Fluxo do Cidadão (Passo a Passo)

1. **Acessa** http://localhost/psp-agendamento/
2. **Seleciona** a esquadra desejada
3. **Escolhe** o serviço
4. **Seleciona** data e hora
5. **Preenche** dados pessoais
6. **Confirma** agendamento
7. **Recebe** email com QR Code

---

## 🎯 Fluxo do Agente (Passo a Passo)

1. **Acessa** http://localhost/psp-agendamento/public/login.php
2. **Faz login** com credenciais
3. **Visualiza** dashboard com estatísticas
4. **Gerencia** agendamentos do dia
5. **Valida** códigos de agendamento
6. **Exporta** relatórios (CSV/PDF)

---

## ❓ Problemas Comuns

| Erro | Solução |
|------|---------|
| Página em branco | Verifique logs do Apache: `tail -f /var/log/apache2/error.log` |
| Erro 404 | Verifique se mod_rewrite está ativo |
| Erro de DB | Verifique credenciais em `config/config.php` |
| CSS não carrega | Verifique se SITE_URL está correto |

---

## 📞 Suporte

Em caso de dúvidas, consulte o `README.md` ou a equipe de desenvolvimento.
