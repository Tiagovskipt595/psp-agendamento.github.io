#!/bin/bash
# Script de inicialização rápida do PSP Agendamento

echo "🚀 Iniciando PSP Agendamento..."
echo ""

# Verificar se PHP está instalado
if ! command -v php &> /dev/null; then
    echo "❌ PHP não encontrado. Instale PHP 7.4+ primeiro."
    exit 1
fi

# Verificar versão do PHP
PHP_VERSION=$(php -v | head -1 | cut -d ' ' -f 2 | cut -d '.' -f 1,2)
echo "✅ PHP $PHP_VERSION detectado"

# Verificar se MySQL está rodando
if ! pgrep -x "mysqld" > /dev/null && ! pgrep -x "mariadbd" > /dev/null; then
    echo "⚠️  MySQL/MariaDB não está rodando"
    read -p "Deseja iniciar? (s/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Ss]$ ]]; then
        sudo systemctl start mysql || sudo systemctl start mariadb
    fi
fi

# Verificar banco de dados
echo ""
echo "📊 Verificando banco de dados..."
if ! mysql -u root -e "USE psp_agendamento;" 2>/dev/null; then
    echo "⚠️  Banco de dados não existe"
    read -p "Deseja criar e importar o schema? (s/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Ss]$ ]]; then
        mysql -u root -e "CREATE DATABASE IF NOT EXISTS psp_agendamento;"
        mysql -u root psp_agendamento < config/database.sql
        echo "✅ Banco de dados criado!"
    fi
fi

# Verificar permissões
echo ""
echo "📁 Verificando permissões..."
chmod -R 755 /home/tiagovsk/psp-agendamento 2>/dev/null
chmod -R 777 /home/tiagovsk/psp-agendamento/config 2>/dev/null
echo "✅ Permissões configuradas"

# Escolher método de execução
echo ""
echo "Como deseja executar?"
echo "1) Apache (http://localhost/psp-agendamento/)"
echo "2) PHP Server (http://localhost:8000)"
echo "3) Apenas verificar configuração"
echo ""
read -p "Opção (1-3): " opcao

case $opcao in
    1)
        echo ""
        echo "✅ Acessando via Apache..."
        echo "📍 URL: http://localhost/psp-agendamento/"
        echo ""
        ;;
    2)
        echo ""
        echo "🚀 Iniciando servidor PHP..."
        cd /home/tiagovsk/psp-agendamento/public
        php -S localhost:8000
        ;;
    3)
        echo ""
        echo "✅ Verificação concluída!"
        echo ""
        echo "Resumo:"
        echo "  - PHP: $PHP_VERSION ✅"
        echo "  - Pasta: /home/tiagovsk/psp-agendamento"
        echo "  - Config: config/config.php"
        echo ""
        echo "URLs disponíveis:"
        echo "  - Cidadão: http://localhost/psp-agendamento/"
        echo "  - Agente:  http://localhost/psp-agendamento/public/login.php"
        echo ""
        ;;
    *)
        echo "Opção inválida"
        ;;
esac

echo ""
echo "📋 Credenciais de Agente:"
echo "   Email: admin@psp.pt"
echo "   Senha: admin123"
echo ""
