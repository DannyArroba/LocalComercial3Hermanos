CREATE TABLE IF NOT EXISTS sales_returns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_id INT NOT NULL,
    user_id INT NOT NULL,
    return_code VARCHAR(40) NOT NULL UNIQUE,
    customer_name VARCHAR(150) NOT NULL,
    customer_idnumber VARCHAR(20) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    reason_category VARCHAR(60) NOT NULL DEFAULT 'MALA_ELECCION_PRODUCTO',
    reason_detail VARCHAR(255) NULL,
    total_refund DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sales_returns_purchase (purchase_id),
    INDEX idx_sales_returns_created (created_at),
    CONSTRAINT fk_sales_returns_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id),
    CONSTRAINT fk_sales_returns_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sales_return_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    return_id INT NOT NULL,
    purchase_item_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(150) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    unit_cost DECIMAL(10,2) NOT NULL DEFAULT 0,
    tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
    tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    line_total DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_sales_return_items_return FOREIGN KEY (return_id) REFERENCES sales_returns(id),
    CONSTRAINT fk_sales_return_items_purchase_item FOREIGN KEY (purchase_item_id) REFERENCES purchase_items(id),
    CONSTRAINT fk_sales_return_items_product FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
