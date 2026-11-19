-- Apply with scripts/migrate_user_access.php for idempotent checks and backup validation.
ALTER TABLE users ADD COLUMN name VARCHAR(120) NULL;
ALTER TABLE users ADD COLUMN permissions_json TEXT NULL;
ALTER TABLE purchases ADD COLUMN served_by_name VARCHAR(120) NULL;
ALTER TABLE sales_returns ADD COLUMN handled_by_name VARCHAR(120) NULL;
ALTER TABLE stock_purchases ADD COLUMN requested_by_name VARCHAR(120) NULL;
-- NULL permissions use worker defaults (sales,inventory); [] explicitly grants no section.
UPDATE users SET role = 'cashier' WHERE role IS NULL OR role = '';
ALTER TABLE users MODIFY COLUMN role ENUM('admin','cashier') NOT NULL DEFAULT 'cashier';
