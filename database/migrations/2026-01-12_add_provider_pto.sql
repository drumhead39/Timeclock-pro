-- Provider PTO bank + per-pay-period PTO usage
-- Providers who do not accrue PTO can leave pto_bank_minutes = 0.

ALTER TABLE providers
  ADD COLUMN pto_bank_minutes INT NOT NULL DEFAULT 0 AFTER active;

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
  KEY idx_provider_pto_period (pay_period_id),
  KEY idx_provider_pto_provider (provider_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
