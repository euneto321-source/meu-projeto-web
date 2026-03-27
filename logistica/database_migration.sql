-- =============================================
-- NTO LOGISTICS - MIGRAÇÃO: Novos campos de Despesas
-- Execute este SQL no phpMyAdmin ANTES de subir os novos arquivos
-- =============================================

ALTER TABLE expenses
  ADD COLUMN IF NOT EXISTS solicitante        VARCHAR(255)  NULL AFTER created_by_sector,
  ADD COLUMN IF NOT EXISTS data_solicitacao   DATE          NULL AFTER solicitante,
  ADD COLUMN IF NOT EXISTS centro_custo       VARCHAR(255)  NULL AFTER data_solicitacao,
  ADD COLUMN IF NOT EXISTS processo           VARCHAR(255)  NULL AFTER centro_custo,
  ADD COLUMN IF NOT EXISTS classificacao_fin  VARCHAR(255)  NULL AFTER processo,
  ADD COLUMN IF NOT EXISTS competencia        VARCHAR(20)   NULL AFTER classificacao_fin,
  ADD COLUMN IF NOT EXISTS beneficiario       VARCHAR(255)  NULL AFTER competencia,
  ADD COLUMN IF NOT EXISTS cpf_cnpj           VARCHAR(20)   NULL AFTER beneficiario,
  ADD COLUMN IF NOT EXISTS forma_pagamento    ENUM('dinheiro','pix','cartao','transferencia') NULL AFTER cpf_cnpj,
  ADD COLUMN IF NOT EXISTS emite_nota_fiscal  ENUM('sim','nao') NULL AFTER forma_pagamento,
  ADD COLUMN IF NOT EXISTS titular            VARCHAR(255)  NULL AFTER emite_nota_fiscal,
  ADD COLUMN IF NOT EXISTS banco              VARCHAR(255)  NULL AFTER titular,
  ADD COLUMN IF NOT EXISTS agencia            VARCHAR(20)   NULL AFTER banco,
  ADD COLUMN IF NOT EXISTS conta              VARCHAR(50)   NULL AFTER agencia,
  ADD COLUMN IF NOT EXISTS pix_chave          VARCHAR(255)  NULL AFTER conta,
  ADD COLUMN IF NOT EXISTS data_limite_pag    DATE          NULL AFTER pix_chave,
  ADD COLUMN IF NOT EXISTS obs                TEXT          NULL AFTER data_limite_pag;

-- Ajuste no status: adicionar 'pending_release' não é necessário pois o novo fluxo
-- usa o mesmo status 'pending' visível para ambos (approval e financial)

-- Verificar estrutura final
-- DESCRIBE expenses;

-- =============================================
-- MIGRAÇÃO: Campos de reset de senha para users
-- Execute este SQL no phpMyAdmin
-- =============================================
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS reset_code         VARCHAR(10)  NULL AFTER password_hash,
  ADD COLUMN IF NOT EXISTS reset_code_expires DATETIME     NULL AFTER reset_code;
