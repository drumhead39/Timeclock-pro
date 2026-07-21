-- Add reusable Nursing Encounters employee flag + per-pay-period count
ALTER TABLE users
  ADD COLUMN nursing_encounters_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER weight_loss_consultant;

ALTER TABLE timecards
  ADD COLUMN nursing_encounters INT NOT NULL DEFAULT 0 AFTER weight_loss_units;

-- Enable the feature for the appropriate employee through the admin area,
-- or update that employee's record directly after confirming the user ID.
