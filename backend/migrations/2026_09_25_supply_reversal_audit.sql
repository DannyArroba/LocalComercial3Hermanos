ALTER TABLE stock_purchases
ADD COLUMN IF NOT EXISTS reversal_code VARCHAR(40) NULL AFTER refunded_at,
ADD COLUMN IF NOT EXISTS reversal_reason VARCHAR(255) NULL AFTER reversal_code,
ADD COLUMN IF NOT EXISTS reversed_by INT NULL AFTER reversal_reason;

