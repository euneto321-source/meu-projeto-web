-- =============================================
-- NTO LOGISTICS - BANCO DE DADOS MySQL
-- Execute este SQL no phpMyAdmin
-- =============================================

CREATE TABLE IF NOT EXISTS users (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'driver', 'emergency', 'sector', 'financial', 'approval') NOT NULL DEFAULT 'driver',
    location_id VARCHAR(36) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS locations (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type ENUM('emergency_unit', 'internal_sector', 'delivery_point') NOT NULL,
    address VARCHAR(500) NULL,
    contact VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS calls (
    id VARCHAR(36) PRIMARY KEY,
    origin_id VARCHAR(36) NULL,
    origin_name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    priority ENUM('normal', 'urgent', 'emergency') NOT NULL DEFAULT 'normal',
    type ENUM('sample_collection', 'delivery', 'pickup') NOT NULL DEFAULT 'sample_collection',
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    created_by_id VARCHAR(36) NULL,
    created_by_name VARCHAR(255) NULL,
    assigned_driver_id VARCHAR(36) NULL,
    assigned_driver_name VARCHAR(255) NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    arrival_at_nto DATETIME NULL,
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_created_at (created_at),
    INDEX idx_assigned_driver (assigned_driver_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS shipments (
    id VARCHAR(36) PRIMARY KEY,
    origin_sector VARCHAR(255) NOT NULL,
    destination_id VARCHAR(36) NULL,
    destination_name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    priority ENUM('normal', 'urgent') NOT NULL DEFAULT 'normal',
    type ENUM('send', 'pickup') NOT NULL DEFAULT 'send',
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    created_by_id VARCHAR(36) NULL,
    created_by_name VARCHAR(255) NULL,
    assigned_driver_id VARCHAR(36) NULL,
    assigned_driver_name VARCHAR(255) NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    INDEX idx_status (status),
    INDEX idx_type (type),
    INDEX idx_created_at (created_at),
    INDEX idx_assigned_driver (assigned_driver_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- NOVA TABELA: Solicitações de Despesas
CREATE TABLE IF NOT EXISTS expenses (
    id VARCHAR(36) PRIMARY KEY,
    description TEXT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    category VARCHAR(100) NOT NULL,
    justification TEXT NULL,
    status ENUM('pending', 'approved', 'rejected', 'released') NOT NULL DEFAULT 'pending',
    created_by_id VARCHAR(36) NOT NULL,
    created_by_name VARCHAR(255) NOT NULL,
    created_by_sector VARCHAR(255) NOT NULL,
    approved_by_id VARCHAR(36) NULL,
    approved_by_name VARCHAR(255) NULL,
    approved_at DATETIME NULL,
    released_by_id VARCHAR(36) NULL,
    released_by_name VARCHAR(255) NULL,
    released_at DATETIME NULL,
    rejection_reason TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_created_by (created_by_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- PERFIS DE USUÁRIO:
-- admin      = Acesso total, dashboard de indicadores
-- driver     = Motorista, atende chamados/envios/retiradas
-- emergency  = Unidade de emergência, cria chamados
-- sector     = Setor interno, solicita envios/retiradas e despesas
-- approval   = Aprovador de despesas
-- financial  = Financeiro, libera despesas aprovadas
-- =============================================
