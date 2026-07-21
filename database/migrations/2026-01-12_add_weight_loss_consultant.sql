-- Add Weight Loss Consultant flag and per-pay-period sales count
ALTER TABLE users
  ADD COLUMN weight_loss_consultant TINYINT(1) NOT NULL DEFAULT 0 AFTER active;

ALTER TABLE timecards
  ADD COLUMN weight_loss_units INT NOT NULL DEFAULT 0 AFTER pto_minutes;
