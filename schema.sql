CREATE DATABASE IF NOT EXISTS inspections_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE inspections_db;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  reset_token VARCHAR(64) DEFAULT NULL,
  reset_token_expires DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS inspections (
  id VARCHAR(40) NOT NULL PRIMARY KEY,

  -- Page 1: Inspection report
  client VARCHAR(255) NOT NULL,
  inspection_date DATE NOT NULL,
  report_number VARCHAR(100) DEFAULT '',
  address VARCHAR(500) NOT NULL,
  iecc_year VARCHAR(10) DEFAULT '',
  iecc_year2 VARCHAR(10) DEFAULT '',
  areas JSON NOT NULL,

  -- Page 2: Invoice
  fee DECIMAL(10,2) DEFAULT NULL,
  payment_method VARCHAR(50) DEFAULT NULL,

  -- Page 3: Payment method / agreement
  payment_option TINYINT DEFAULT NULL,
  re_field VARCHAR(500) DEFAULT '',
  pm_name VARCHAR(255) DEFAULT '',
  pm_cell VARCHAR(50) DEFAULT '',
  company_name VARCHAR(255) DEFAULT '',
  company_contact VARCHAR(255) DEFAULT '',
  signature_name VARCHAR(255) DEFAULT '',
  signature_date DATE DEFAULT NULL,

  -- Delivery
  recipient_email VARCHAR(255) DEFAULT '',

  saved_at DATETIME NOT NULL,
  INDEX idx_saved_at (saved_at)
) ENGINE=InnoDB;

-- If you already ran an earlier version of this schema.sql on your
-- server (the one with a `building` column and no `report_title`
-- column), run this migration once instead of re-running the CREATE
-- TABLE below (CREATE TABLE IF NOT EXISTS will not alter an existing
-- table):
--   ALTER TABLE plumbing_inspections CHANGE building inspector_license VARCHAR(100) DEFAULT '';
--   ALTER TABLE plumbing_inspections ADD COLUMN report_title VARCHAR(255) DEFAULT '' AFTER id;
--
-- ---------------------------------------------------------------------
-- Property Inspection Report (MEP / Plumbing) -- "Cypress" style form.
-- Modeled on the uploaded Word template: header block, inspection
-- scope / parties present / weather checkboxes, time + outside temp,
-- and a repeatable list of inspected items (category, subcategory,
-- I/NI/NP/D status, findings) -- same repeatable-list pattern as the
-- `areas` JSON column above.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS plumbing_inspections (
  id VARCHAR(40) NOT NULL PRIMARY KEY,

  -- Header
  report_title VARCHAR(255) DEFAULT '',
  client VARCHAR(255) NOT NULL,
  inspection_address VARCHAR(500) NOT NULL,
  inspector_license VARCHAR(100) DEFAULT '',
  inspector_name VARCHAR(255) DEFAULT '',
  inspection_date DATE NOT NULL,

  -- Inspection scope (checkboxes; multiple allowed)
  scope_plumbing TINYINT(1) NOT NULL DEFAULT 0,
  scope_electrical TINYINT(1) NOT NULL DEFAULT 0,
  scope_hvac TINYINT(1) NOT NULL DEFAULT 0,
  scope_other TINYINT(1) NOT NULL DEFAULT 0,
  scope_other_text VARCHAR(255) DEFAULT '',

  -- Parties present (checkboxes; multiple allowed)
  parties_superintendent TINYINT(1) NOT NULL DEFAULT 0,
  parties_subcontractor TINYINT(1) NOT NULL DEFAULT 0,
  parties_other TINYINT(1) NOT NULL DEFAULT 0,
  parties_other_text VARCHAR(255) DEFAULT '',

  -- Weather (single choice)
  weather VARCHAR(20) DEFAULT '',

  time_of_inspection VARCHAR(20) DEFAULT '',
  outside_temp VARCHAR(20) DEFAULT '',

  -- Additional written information provided with this report (Yes/No)
  additional_info VARCHAR(10) DEFAULT '',

  -- Repeatable checklist items: [{category, subcategory, status, findings}, ...]
  items JSON NOT NULL,

  -- Delivery
  recipient_email VARCHAR(255) DEFAULT '',

  saved_at DATETIME NOT NULL,
  INDEX idx_plumbing_saved_at (saved_at)
) ENGINE=InnoDB;
