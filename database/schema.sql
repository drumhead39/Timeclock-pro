-- TimeClock Pro schema (MySQL/MariaDB)
-- Intended for a fresh database. Existing installations should use migrations.

SET sql_mode = 'STRICT_ALL_TABLES';

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(190) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('employee','super') NOT NULL DEFAULT 'employee',
  active TINYINT(1) NOT NULL DEFAULT 1,
  weight_loss_consultant TINYINT(1) NOT NULL DEFAULT 0,
  nursing_encounters_enabled TINYINT(1) NOT NULL DEFAULT 0,
  pto_bank_minutes INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings_versions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  created_by INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  config_json JSON NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS app_state (
  id INT PRIMARY KEY,
  current_settings_version_id INT NOT NULL,
  company_name VARCHAR(190) NOT NULL DEFAULT 'TimeClock Pro',
  company_email VARCHAR(190) NOT NULL DEFAULT '',
  pay_period_anchor_date DATE NOT NULL,
  pay_period_length_days INT NOT NULL DEFAULT 14,
  CONSTRAINT fk_app_state_settings FOREIGN KEY (current_settings_version_id) REFERENCES settings_versions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pay_periods (
  id INT AUTO_INCREMENT PRIMARY KEY,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  settings_version_id INT NOT NULL,
  locked_at DATETIME NULL,
  locked_by INT NULL,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uniq_period (start_date, end_date),
  CONSTRAINT fk_payperiod_settings FOREIGN KEY (settings_version_id) REFERENCES settings_versions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS timecards (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  pay_period_id INT NOT NULL,
  pto_minutes INT NOT NULL DEFAULT 0,
  weight_loss_units INT NOT NULL DEFAULT 0,
  nursing_encounters INT NOT NULL DEFAULT 0,
  submitted_at DATETIME NULL,
  locked_at DATETIME NULL,
  locked_by INT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uniq_timecard (user_id, pay_period_id),
  CONSTRAINT fk_timecards_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_timecards_period FOREIGN KEY (pay_period_id) REFERENCES pay_periods(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS time_entries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  pay_period_id INT NOT NULL,
  work_date DATE NOT NULL,
  time_in TIME NULL,
  time_out TIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uniq_entry (user_id, pay_period_id, work_date),
  CONSTRAINT fk_entries_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_entries_period FOREIGN KEY (pay_period_id) REFERENCES pay_periods(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS providers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  provider_type ENUM('chiro','massage') NOT NULL DEFAULT 'chiro',
  full_name VARCHAR(190) NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  pto_bank_minutes INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_providers_active_type (active, provider_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS appointment_types (
  id INT AUTO_INCREMENT PRIMARY KEY,
  provider_type ENUM('chiro','massage') NOT NULL DEFAULT 'chiro',
  name VARCHAR(190) NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_appointment_type (provider_type, active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS provider_rates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  provider_id INT NOT NULL,
  appointment_type_id INT NOT NULL,
  rate DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  effective_from DATE NOT NULL,
  effective_to DATE NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_provider_rate_lookup (provider_id, appointment_type_id, effective_from, effective_to),
  CONSTRAINT fk_provider_rates_provider FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE CASCADE,
  CONSTRAINT fk_provider_rates_type FOREIGN KEY (appointment_type_id) REFERENCES appointment_types(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS provider_production (
  id INT AUTO_INCREMENT PRIMARY KEY,
  provider_id INT NOT NULL,
  pay_period_id INT NOT NULL,
  appointment_type_id INT NOT NULL,
  `count` INT NOT NULL DEFAULT 0,
  rate_used DECIMAL(10,2) NULL,
  submitted_at DATETIME NULL,
  locked_at DATETIME NULL,
  locked_by INT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uniq_provider_production (provider_id, pay_period_id, appointment_type_id),
  INDEX idx_provider_production_period (pay_period_id),
  CONSTRAINT fk_production_provider FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE CASCADE,
  CONSTRAINT fk_production_period FOREIGN KEY (pay_period_id) REFERENCES pay_periods(id) ON DELETE CASCADE,
  CONSTRAINT fk_production_type FOREIGN KEY (appointment_type_id) REFERENCES appointment_types(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS provider_pto (
  provider_id INT NOT NULL,
  pay_period_id INT NOT NULL,
  pto_minutes INT NOT NULL DEFAULT 0,
  submitted_at DATETIME NULL,
  locked_at DATETIME NULL,
  locked_by INT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (provider_id, pay_period_id),
  INDEX idx_provider_pto_period (pay_period_id),
  CONSTRAINT fk_provider_pto_provider FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE CASCADE,
  CONSTRAINT fk_provider_pto_period FOREIGN KEY (pay_period_id) REFERENCES pay_periods(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  actor_user_id INT NOT NULL,
  action VARCHAR(120) NOT NULL,
  entity VARCHAR(120) NOT NULL,
  entity_id INT NULL,
  payload_json JSON NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_actor_created (actor_user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reports (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pay_period_id INT NOT NULL,
  filename VARCHAR(255) NOT NULL,
  filepath VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL,
  emailed_to VARCHAR(190) NOT NULL,
  email_status VARCHAR(32) NOT NULL DEFAULT 'unknown',
  CONSTRAINT fk_reports_period FOREIGN KEY (pay_period_id) REFERENCES pay_periods(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO settings_versions (id, created_by, created_at, config_json)
VALUES (1, 0, NOW(), '{"time_increment_minutes":15,"allowed_minutes":[0,15,30,45],"rounding_mode":"nearest","days_to_show":[1,2,3,4,5,6],"time_start_hour":5,"time_end_hour":20}');

INSERT IGNORE INTO app_state
  (id, current_settings_version_id, company_name, company_email, pay_period_anchor_date, pay_period_length_days)
VALUES
  (1, 1, 'TimeClock Pro', '', DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), 14);

