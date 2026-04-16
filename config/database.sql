-- Base de Dados: Agendamento PSP
-- Criar base de dados
CREATE DATABASE IF NOT EXISTS psp_agendamento
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE psp_agendamento;

-- Tabela: Esquadras
CREATE TABLE IF NOT EXISTS esquadras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    morada VARCHAR(255) NOT NULL,
    telefone VARCHAR(20),
    email VARCHAR(100),
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela: Serviços
CREATE TABLE IF NOT EXISTS servicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    esquadra_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    duracao_minutos INT DEFAULT 30,
    ativo TINYINT(1) DEFAULT 1,
    FOREIGN KEY (esquadra_id) REFERENCES esquadras(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela: Agendamentos
CREATE TABLE IF NOT EXISTS agendamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    esquadra_id INT NOT NULL,
    servico_id INT NOT NULL,
    codigo_agendamento VARCHAR(20) UNIQUE NOT NULL,
    nome_cidadao VARCHAR(100) NOT NULL,
    cc_numero VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    telemovel VARCHAR(20) NOT NULL,
    data_agendamento DATE NOT NULL,
    hora_agendamento TIME NOT NULL,
    estado ENUM('confirmado', 'presente', 'em_atendimento', 'concluido', 'cancelado', 'faltou') DEFAULT 'confirmado',
    observacoes TEXT,
    lembrete_24h_enviado TINYINT(1) DEFAULT 0,
    lembrete_1h_enviado TINYINT(1) DEFAULT 0,
    data_lembrete_24h TIMESTAMP NULL,
    data_lembrete_1h TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (esquadra_id) REFERENCES esquadras(id) ON DELETE CASCADE,
    FOREIGN KEY (servico_id) REFERENCES servicos(id) ON DELETE CASCADE,
    INDEX idx_data (data_agendamento),
    INDEX idx_estado (estado),
    INDEX idx_codigo (codigo_agendamento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela: Usuários (Agentes PSP)
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    esquadra_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    perfil ENUM('agente', 'coordenador', 'admin') DEFAULT 'agente',
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (esquadra_id) REFERENCES esquadras(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela: Horários de Atendimento
CREATE TABLE IF NOT EXISTS horarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    esquadra_id INT NOT NULL,
    dia_semana INT NOT NULL COMMENT '0=Domingo, 6=Sábado',
    hora_inicio TIME NOT NULL,
    hora_fim TIME NOT NULL,
    FOREIGN KEY (esquadra_id) REFERENCES esquadras(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dados de exemplo
INSERT INTO esquadras (nome, morada, telefone, email) VALUES
('Esquadra de São Sebastião', 'Rua Alexandre Herculano, Lisboa', '213 555 100', 'sao.sebastiao@psp.pt'),
('Esquadra de Alvalade', 'Av. de Roma, Lisboa', '213 555 200', 'alvalade@psp.pt'),
('Esquadra de Belém', 'Praça do Império, Lisboa', '213 555 300', 'belem@psp.pt');

INSERT INTO servicos (esquadra_id, nome, descricao, duracao_minutos) VALUES
(1, 'Renovação de Licença de Arma', 'Renovação de licença de arma existente', 30),
(1, 'Primeira Emissão de Licença de Arma', 'Emissão de nova licença de arma', 45),
(1, 'Apresentar Queixa', 'Registo de queixa criminal', 20),
(1, 'Certificado de Registo Criminal', 'Emissão de certificado', 15),
(2, 'Renovação de Licença de Arma', 'Renovação de licença de arma existente', 30),
(2, 'Apresentar Queixa', 'Registo de queixa criminal', 20),
(3, 'Renovação de Licença de Arma', 'Renovação de licença de arma existente', 30),
(3, 'Apresentar Queixa', 'Registo de queixa criminal', 20);

INSERT INTO horarios (esquadra_id, dia_semana, hora_inicio, hora_fim) VALUES
(1, 1, '09:00:00', '12:00:00'), (1, 1, '14:00:00', '17:00:00'),
(1, 2, '09:00:00', '12:00:00'), (1, 2, '14:00:00', '17:00:00'),
(1, 3, '09:00:00', '12:00:00'), (1, 3, '14:00:00', '17:00:00'),
(1, 4, '09:00:00', '12:00:00'), (1, 4, '14:00:00', '17:00:00'),
(1, 5, '09:00:00', '12:00:00'), (1, 5, '14:00:00', '16:00:00'),
(2, 1, '09:00:00', '12:00:00'), (2, 1, '14:00:00', '17:00:00'),
(2, 2, '09:00:00', '12:00:00'), (2, 2, '14:00:00', '17:00:00'),
(2, 3, '09:00:00', '12:00:00'), (2, 3, '14:00:00', '17:00:00'),
(2, 4, '09:00:00', '12:00:00'), (2, 4, '14:00:00', '17:00:00'),
(2, 5, '09:00:00', '12:00:00'), (2, 5, '14:00:00', '16:00:00'),
(3, 1, '09:00:00', '12:00:00'), (3, 1, '14:00:00', '17:00:00'),
(3, 2, '09:00:00', '12:00:00'), (3, 2, '14:00:00', '17:00:00'),
(3, 3, '09:00:00', '12:00:00'), (3, 3, '14:00:00', '17:00:00'),
(3, 4, '09:00:00', '12:00:00'), (3, 4, '14:00:00', '17:00:00'),
(3, 5, '09:00:00', '12:00:00'), (3, 5, '14:00:00', '16:00:00');

-- Utilizador admin padrão (password: admin123)
INSERT INTO usuarios (esquadra_id, nome, email, password_hash, perfil) VALUES
(1, 'Administrador', 'admin@psp.pt', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
