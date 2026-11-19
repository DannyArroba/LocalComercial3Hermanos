-- Different identified customers may share a family email address.
-- CI remains the unique customer identifier; keep email searchable.
ALTER TABLE customers DROP INDEX email, ADD INDEX idx_customers_email (email);
