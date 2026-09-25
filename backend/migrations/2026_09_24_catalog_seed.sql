START TRANSACTION;

INSERT INTO suppliers (company_name, contact_name, email, ruc, phone, address)
SELECT 'Distribuidora Amazonica Paredes', 'Andrea Paredes', 'pedidos@distribuidoraparedes.ec', '2200158479001', '099 482 7316', 'Av. 9 de Octubre y Quito, Puerto Francisco de Orellana'
WHERE NOT EXISTS (SELECT 1 FROM suppliers WHERE ruc = '2200158479001');

INSERT INTO suppliers (company_name, contact_name, email, ruc, phone, address)
SELECT 'Comercializadora Andina del Oriente', 'Luis Cardenas', 'ventas@andinaoriente.ec', '1793192847001', '098 615 2047', 'Av. Alejandro Labaka y Rio Napo, Puerto Francisco de Orellana'
WHERE NOT EXISTS (SELECT 1 FROM suppliers WHERE ruc = '1793192847001');

INSERT INTO suppliers (company_name, contact_name, email, ruc, phone, address)
SELECT 'Alimentos Orellana Cia. Ltda.', 'Mariela Zambrano', 'distribucion@alimentosorellana.ec', '2200215638001', '06 288 4175', 'Calle Amazonas y Eugenio Espejo, Puerto Francisco de Orellana'
WHERE NOT EXISTS (SELECT 1 FROM suppliers WHERE ruc = '2200215638001');

SET @supplier_general = (SELECT id FROM suppliers WHERE ruc = '2200158479001' LIMIT 1);
SET @supplier_fresh = (SELECT id FROM suppliers WHERE ruc = '1793192847001' LIMIT 1);
SET @supplier_food = (SELECT id FROM suppliers WHERE ruc = '2200215638001' LIMIT 1);

UPDATE products SET barcode='7861000000055', cost_price=10.20, supplier_id=@supplier_fresh WHERE id=5;
UPDATE products SET barcode='7861000000062', cost_price=18.00, supplier_id=@supplier_general WHERE id=6;
UPDATE products SET barcode='7861000000079', cost_price=0.32, supplier_id=@supplier_food WHERE id=7;
UPDATE products SET barcode='7861000000086', cost_price=1.85, supplier_id=@supplier_general WHERE id=8;
UPDATE products SET barcode='7861000000093', cost_price=0.28, supplier_id=@supplier_general WHERE id=9;
UPDATE products SET barcode='7861000000109', cost_price=0.78, supplier_id=@supplier_general WHERE id=10;
UPDATE products SET barcode='7861000000116', cost_price=0.64, supplier_id=@supplier_fresh WHERE id=11;
UPDATE products SET barcode='7861000000123', cost_price=1.25, supplier_id=@supplier_fresh WHERE id=12;
UPDATE products SET barcode='7861000000130', cost_price=2.55, supplier_id=@supplier_fresh WHERE id=13;
UPDATE products SET barcode='7861000000147', cost_price=3.55, supplier_id=@supplier_general WHERE id=14;
UPDATE products SET barcode='7861000000154', cost_price=1.55, supplier_id=@supplier_general WHERE id=15;
UPDATE products SET barcode='7861000000161', cost_price=1.05, supplier_id=@supplier_food WHERE id=16;
UPDATE products SET barcode='7861000000178', cost_price=2.25, supplier_id=@supplier_general WHERE id=17;
UPDATE products SET barcode='7861000000185', cost_price=0.30, supplier_id=@supplier_food WHERE id=18;
UPDATE products SET barcode='7861000000192', cost_price=0.68, supplier_id=@supplier_general WHERE id=19;

UPDATE purchase_items pi
JOIN products p ON p.id = pi.product_id
SET pi.unit_cost = p.cost_price
WHERE pi.unit_cost = 0;

COMMIT;
