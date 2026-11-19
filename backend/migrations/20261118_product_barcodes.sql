-- Códigos comerciales alternativos para variedades con precio y stock común.
-- Ejecutar una sola vez o utilizar CREATE TABLE IF NOT EXISTS.
CREATE TABLE IF NOT EXISTS product_barcodes (
    barcode VARCHAR(100) NOT NULL,
    product_id INT(11) NOT NULL,
    PRIMARY KEY (barcode),
    KEY idx_product_barcodes_product (product_id),
    CONSTRAINT fk_product_barcodes_product FOREIGN KEY (product_id)
        REFERENCES products (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
