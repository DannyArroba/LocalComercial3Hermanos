-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: tienda
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `tienda`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `tienda` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `tienda`;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `id_number` varchar(10) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `unique_customer_id_number` (`id_number`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (1,'Andrea Carolina Zambrano Mena','2201456783','andrea.zambrano@example.com','0991842637','Av. Alejandro Labaka y Rio Coca, barrio Central, El Coca','2026-06-22 06:33:36','2026-06-22 06:33:49'),(2,'Luis Fernando Grefa Alvarado','2202389413','luis.grefa@example.com','0985274163','Calle Napo y Quito, barrio 30 de Abril, El Coca','2026-06-22 06:33:36','2026-06-22 06:33:36'),(3,'Maria Jose Shiguango Cerda','2203527060','maria.shiguango@example.com','0963184752','Av. 9 de Octubre y Eugenio Espejo, barrio Union Imbaburena, El Coca','2026-06-22 06:33:36','2026-06-22 06:33:49'),(4,'Carlos Andres Villacis Paredes','2204673152','carlos.villacis@example.com','0976421835','Calle Amazonas y Vicente Rocafuerte, barrio Paraiso Amazonico, El Coca','2026-06-22 06:33:36','2026-06-22 06:33:49'),(5,'Daniela Estefania Tanguila Gualinga','2205718246','daniela.tanguila@example.com','0957362148','Av. Camilo de Torrano y Rio Payamino, barrio Flor de Oriente, El Coca','2026-06-22 06:33:36','2026-06-22 06:33:49'),(6,'Jorge Eduardo Vargas Moreira','2206842391','jorge.vargas@example.com','0996257314','Calle Cuenca y Guayaquil, barrio Los Rosales, El Coca','2026-06-22 06:33:36','2026-06-22 06:33:36'),(7,'Paola Fernanda Aguinda Mamallacta','2207931466','paola.aguinda@example.com','0981735624','Av. Padre Miguel Gamboa y Rio Tiputini, barrio Con Hogar, El Coca','2026-06-22 06:33:36','2026-06-22 06:33:49'),(9,'Erick Diaz','2250040850','erick@gmail.com','0988776655','Coca','2026-06-22 07:04:19','2026-06-22 07:04:19');
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_logs`
--

DROP TABLE IF EXISTS `inventory_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `type` enum('sale','purchase') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `inventory_logs_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_logs`
--

LOCK TABLES `inventory_logs` WRITE;
/*!40000 ALTER TABLE `inventory_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` int(11) DEFAULT NULL,
  `invoice_number` varchar(255) NOT NULL,
  `invoice_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `file_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_number` (`invoice_number`),
  KEY `sale_id` (`sale_id`),
  CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `applies_iva` tinyint(1) NOT NULL DEFAULT 1,
  `cost_price` decimal(10,2) DEFAULT 0.00,
  `category` varchar(255) DEFAULT NULL,
  `stock` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_products_supplier_id` (`supplier_id`),
  CONSTRAINT `fk_products_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (5,2,'Pollo','7861000000055',13.91,0,10.20,'Carnes Rojas',22,'Carne rojita','1932d1225e35f0ad1708bb97.png','2026-01-15 22:29:03'),(6,1,'Arroz','7861000000062',23.20,0,18.00,'Granos',4,'granos','imagen_2026-01-16_004122429.png','2026-01-16 05:41:23'),(7,3,'chocolate','7861000000079',0.50,1,0.32,'dulces',0,'Dulces','c517e7aab06a17a0cbccb41c.png','2026-04-01 22:39:35'),(8,1,'Coca-Cola 2.5L','7861000000086',2.50,1,1.85,'Bebidas',67,'Refresco de cola familiar','620e767f925e901578b7f13e.png','2026-05-07 05:41:53'),(9,1,'Agua Mineral 500ml','7861000000093',0.50,1,0.28,'Bebidas',95,'Agua sin gas','847630c5a2709fa9c921a209.png','2026-05-07 05:41:53'),(10,1,'Jugo de Naranja 1L','7861000000109',1.20,1,0.78,'Bebidas',30,'Jugo natural pasteurizado','catalog-jugo-naranja-1l.jpg','2026-05-07 05:41:53'),(11,2,'Leche Entera 1L','7861000000116',0.90,0,0.64,'Lácteos',40,'Leche de vaca fortificada','catalog-leche-entera-1l.jpg','2026-05-07 05:41:53'),(12,2,'Yogurt de Fresa 1kg','7861000000123',1.80,0,1.25,'Lácteos',14,'Yogurt cremoso con trozos','catalog-yogurt-fresa-1kg.jpg','2026-05-07 05:41:53'),(13,2,'Queso Fresco 500g','7861000000130',3.50,0,2.55,'Lácteos',15,'Queso artesanal','catalog-queso-fresco-500g.jpg','2026-05-07 05:41:53'),(14,1,'Arroz 5kg','7861000000147',4.50,0,3.55,'Abarrotes',60,'Arroz grano largo','catalog-arroz-5kg.jpg','2026-05-07 05:41:53'),(15,1,'Aceite Girasol 1L','7861000000154',2.10,0,1.55,'Abarrotes',35,'Aceite vegetal refinado','catalog-aceite-girasol-1l.jpg','2026-05-07 05:41:53'),(16,3,'Pan de Molde','7861000000161',1.50,0,1.05,'Panadería',20,'Pan blanco tajado','catalog-pan-de-molde.jpg','2026-05-07 05:41:53'),(17,1,'Café Molido 250g','7861000000178',3.20,1,2.25,'Abarrotes',25,'Café de altura seleccionado','catalog-cafe-molido-250g.jpg','2026-05-07 05:41:53'),(18,3,'Gomitas','7861000000185',0.50,1,0.30,'Gomitas S.A',15,'Gomitas','2c44ea8647a8f84f699d5404.png','2026-06-23 05:32:18'),(19,1,'Mayonesa','7861000000192',1.00,1,0.68,'Condimentos',24,'Mayonesa','2b18b743c45112dd1cb8b386.png','2026-06-23 05:37:19');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_items`
--

DROP TABLE IF EXISTS `purchase_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `purchase_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(150) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unit_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `line_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `purchase_id` (`purchase_id`),
  CONSTRAINT `purchase_items_ibfk_1` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_items`
--

LOCK TABLES `purchase_items` WRITE;
/*!40000 ALTER TABLE `purchase_items` DISABLE KEYS */;
INSERT INTO `purchase_items` VALUES (1,1,4,'Helado',21.00,0.00,0.00,0.00,2,42.00),(2,1,5,'Pollo',14.00,10.20,0.00,0.00,2,28.00),(3,2,5,'Pollo',14.00,10.20,0.00,0.00,1,14.00),(4,3,5,'Pollo',14.00,10.20,0.00,0.00,1,14.00),(5,4,4,'Helado',21.00,0.00,0.00,0.00,2,42.00),(6,4,5,'Pollo',14.00,10.20,0.00,0.00,1,14.00),(7,5,5,'Pollo',14.00,10.20,0.00,0.00,1,14.00),(8,6,5,'Pollo',14.00,10.20,0.00,0.00,6,84.00),(9,6,4,'Helado',21.00,0.00,0.00,0.00,6,126.00),(10,7,5,'Pollo',14.00,10.20,0.00,0.00,10,140.00),(11,8,5,'Pollo',14.04,10.20,0.00,0.00,1,14.04),(12,8,6,'Arroz',23.20,18.00,0.00,0.00,1,23.20),(13,9,5,'Pollo',14.04,10.20,0.00,0.00,1,14.04),(14,9,4,'Helado',20.98,0.00,0.00,0.00,2,41.96),(15,10,5,'Pollo',13.91,10.20,0.00,0.00,3,41.73),(19,12,5,'Pollo',13.91,10.20,0.00,0.00,1,13.91),(20,12,4,'Helado',20.98,0.00,0.00,0.00,1,20.98),(21,12,6,'Arroz',23.20,18.00,0.00,0.00,1,23.20),(22,13,5,'Pollo',13.91,10.20,0.00,0.00,1,13.91),(23,14,6,'Arroz',23.20,18.00,0.00,0.00,1,23.20),(24,15,5,'Pollo',13.91,10.20,0.00,0.00,1,13.91),(25,15,6,'Arroz',23.20,18.00,0.00,0.00,1,23.20),(26,16,8,'Coca-Cola 2.5L',2.50,1.85,0.00,0.00,1,2.50),(27,17,8,'Coca-Cola 2.5L',2.50,1.85,0.00,0.00,1,2.50),(28,18,8,'Coca-Cola 2.5L',2.50,1.85,0.00,0.00,1,2.50),(29,19,5,'Pollo',13.91,10.20,0.00,0.00,1,13.91),(30,20,8,'Coca-Cola 2.5L',2.50,1.85,0.00,0.00,1,2.50),(31,21,8,'Coca-Cola 2.5L',2.50,1.85,0.00,0.00,1,2.50),(32,22,9,'Agua Mineral 500ml',0.50,0.28,0.00,0.00,1,0.50),(33,23,8,'Coca-Cola 2.5L',2.50,1.85,0.00,0.00,1,2.50),(34,24,8,'Coca-Cola 2.5L',2.50,1.85,0.00,0.00,3,7.50),(35,25,9,'Agua Mineral 500ml',0.50,0.28,0.00,0.00,1,0.50),(36,25,5,'Pollo',13.91,10.20,0.00,0.00,1,13.91),(37,26,8,'Coca-Cola 2.5L',2.50,1.85,0.00,0.00,1,2.50),(38,26,9,'Agua Mineral 500ml',0.50,0.28,0.00,0.00,1,0.50),(39,27,8,'Coca-Cola 2.5L',2.50,1.85,0.00,0.00,4,10.00),(40,27,9,'Agua Mineral 500ml',0.50,0.28,0.00,0.00,1,0.50),(41,28,9,'Agua Mineral 500ml',0.50,0.28,0.00,0.00,1,0.50),(42,28,12,'Yogurt de Fresa 1kg',1.80,1.25,0.00,0.00,5,9.00),(43,29,12,'Yogurt de Fresa 1kg',1.80,1.25,0.00,0.00,2,3.60),(44,29,5,'Pollo',13.91,10.20,0.00,0.00,1,13.91),(45,30,8,'Coca-Cola 2.5L',2.50,1.85,0.00,0.00,3,7.50),(46,30,9,'Agua Mineral 500ml',0.50,0.28,0.00,0.00,1,0.50),(48,32,5,'Pollo',13.91,10.20,0.00,0.00,1,13.91),(49,32,8,'Coca-Cola 2.5L',2.50,1.85,0.00,0.00,1,2.50),(50,32,9,'Agua Mineral 500ml',0.50,0.28,0.00,0.00,3,1.50),(51,33,8,'Coca-Cola 2.5L',2.50,1.85,0.00,0.00,3,7.50),(52,33,5,'Pollo',13.91,10.20,0.00,0.00,3,41.73),(53,33,12,'Yogurt de Fresa 1kg',1.80,1.25,0.00,0.00,4,7.20),(54,34,5,'Pollo',13.91,10.20,0.00,0.00,1,13.91),(55,34,8,'Coca-Cola 2.5L',2.50,1.85,0.00,0.00,2,5.00),(56,34,9,'Agua Mineral 500ml',0.50,0.28,0.00,0.00,1,0.50),(60,37,6,'Arroz',23.20,18.00,0.00,0.00,1,23.20),(61,37,8,'Coca-Cola 2.5L',2.50,1.85,15.00,0.38,1,2.88);
/*!40000 ALTER TABLE `purchase_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchases`
--

DROP TABLE IF EXISTS `purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `purchase_type` enum('CONSUMIDOR_FINAL','FACTURA') NOT NULL DEFAULT 'CONSUMIDOR_FINAL',
  `payment_method` enum('EFECTIVO','TRANSFERENCIA') DEFAULT NULL,
  `customer_name` varchar(120) DEFAULT NULL,
  `customer_email` varchar(120) DEFAULT NULL,
  `customer_phone` varchar(50) DEFAULT NULL,
  `customer_address` varchar(200) DEFAULT NULL,
  `customer_idnumber` varchar(30) DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `iva` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `amount_received` decimal(10,2) DEFAULT NULL,
  `change_amount` decimal(10,2) DEFAULT NULL,
  `transfer_reference` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `created_at` (`created_at`),
  KEY `idx_purchases_customer_id` (`customer_id`),
  CONSTRAINT `fk_purchases_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchases`
--

LOCK TABLES `purchases` WRITE;
/*!40000 ALTER TABLE `purchases` DISABLE KEYS */;
INSERT INTO `purchases` VALUES (1,1,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,70.00,8.40,78.40,NULL,NULL,NULL,'2026-01-15 23:35:12'),(2,1,NULL,'FACTURA',NULL,'a','a@2','a','a','a',14.00,1.68,15.68,NULL,NULL,NULL,'2026-01-15 23:53:14'),(3,1,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,14.00,1.68,15.68,NULL,NULL,NULL,'2026-01-15 23:56:33'),(4,1,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,56.00,6.72,62.72,NULL,NULL,NULL,'2026-01-15 23:57:11'),(5,1,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,14.00,1.68,15.68,NULL,NULL,NULL,'2026-01-16 00:11:23'),(6,1,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,210.00,25.20,235.20,NULL,NULL,NULL,'2026-01-16 00:21:19'),(7,1,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,140.00,16.80,156.80,NULL,NULL,NULL,'2026-01-16 00:33:47'),(8,2,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,37.24,4.47,41.71,NULL,NULL,NULL,'2026-01-16 00:41:34'),(9,1,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,56.00,6.72,62.72,NULL,NULL,NULL,'2026-01-16 00:42:42'),(10,1,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,41.73,5.01,46.74,NULL,NULL,NULL,'2026-01-16 00:43:23'),(12,3,NULL,'FACTURA',NULL,'Melisa','meli@gmail.com','098798674','centro','2200272215',58.09,6.97,65.06,NULL,NULL,NULL,'2026-01-17 20:41:00'),(13,4,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,13.91,1.67,15.58,NULL,NULL,NULL,'2026-04-01 17:52:44'),(14,4,NULL,'FACTURA',NULL,'mariana','mopositamariana@gmail.com','0968','centro','0202135463',23.20,2.78,25.98,NULL,NULL,NULL,'2026-04-01 17:57:27'),(15,5,NULL,'FACTURA',NULL,'fumando','acadamia1110@gmail.com','0968','centro','5555555555',37.11,4.45,41.56,NULL,NULL,NULL,'2026-05-07 00:08:17'),(16,5,NULL,'CONSUMIDOR_FINAL',NULL,'','','','','',2.50,0.38,2.88,NULL,NULL,NULL,'2026-05-07 01:21:02'),(17,5,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,2.50,0.38,2.88,NULL,NULL,NULL,'2026-05-07 01:24:29'),(18,5,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,2.50,0.38,2.88,NULL,NULL,NULL,'2026-05-07 01:28:29'),(19,5,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,13.91,2.09,16.00,NULL,NULL,NULL,'2026-05-07 01:32:29'),(20,5,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,2.50,0.38,2.88,NULL,NULL,NULL,'2026-05-07 01:32:36'),(21,5,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,2.50,0.38,2.88,NULL,NULL,NULL,'2026-05-07 01:35:03'),(22,5,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,0.50,0.08,0.58,NULL,NULL,NULL,'2026-05-07 01:36:15'),(23,5,NULL,'FACTURA',NULL,'Fernando Xavier Bravo Valladolid','fernandobravo6582@gmail.com','0987168084','Ecuador, El Coca Orellana','e',2.50,0.38,2.88,NULL,NULL,NULL,'2026-05-07 01:36:32'),(24,6,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,7.50,1.13,8.63,NULL,NULL,NULL,'2026-05-07 23:08:58'),(25,6,NULL,'FACTURA',NULL,'Fernando Xavier Bravo Valladolid','fernandobravo6582@gmail.com','0987168084','Ecuador, El Coca Orellana','343434',14.41,2.16,16.57,NULL,NULL,NULL,'2026-05-07 23:09:35'),(26,5,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,3.00,0.45,3.45,NULL,NULL,NULL,'2026-05-08 00:21:27'),(27,5,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,10.50,1.58,12.08,NULL,NULL,NULL,'2026-05-08 12:04:14'),(28,5,NULL,'FACTURA',NULL,'cristhian','ajbskvdkvs343@ls.cpm343','09934939434','34343','232323232323',9.50,1.43,10.93,NULL,NULL,NULL,'2026-05-08 12:05:22'),(29,5,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,17.51,2.63,20.14,NULL,NULL,NULL,'2026-05-08 13:04:55'),(30,5,NULL,'FACTURA',NULL,'mariana','cualquiera@hotmail.com','0365254155','las americas','2014569878',8.00,1.20,9.20,NULL,NULL,NULL,'2026-05-08 13:06:08'),(32,6,9,'FACTURA',NULL,'Erick Diaz','erick@gmail.com','0988776655','Coca','2250040850',17.91,2.69,20.60,NULL,NULL,NULL,'2026-06-22 02:04:19'),(33,5,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,56.43,8.46,64.89,NULL,NULL,NULL,'2026-09-22 00:40:03'),(34,5,9,'FACTURA',NULL,'Erick Diaz','erick@gmail.com','0988776655','Coca','2250040850',19.41,2.91,22.32,NULL,NULL,NULL,'2026-09-22 00:46:01'),(37,5,NULL,'CONSUMIDOR_FINAL','TRANSFERENCIA',NULL,NULL,NULL,NULL,NULL,25.70,0.38,26.08,NULL,NULL,'123132132132','2026-09-25 00:27:42');
/*!40000 ALTER TABLE `purchases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sale_items`
--

DROP TABLE IF EXISTS `sale_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_id` (`sale_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`),
  CONSTRAINT `sale_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sale_items`
--

LOCK TABLES `sale_items` WRITE;
/*!40000 ALTER TABLE `sale_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `sale_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL,
  `iva` decimal(10,2) NOT NULL,
  `total_with_iva` decimal(10,2) NOT NULL,
  `sale_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales`
--

LOCK TABLES `sales` WRITE;
/*!40000 ALTER TABLE `sales` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_return_items`
--

DROP TABLE IF EXISTS `sales_return_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales_return_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `return_id` int(11) NOT NULL,
  `purchase_item_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `unit_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_sales_return_items_return` (`return_id`),
  KEY `fk_sales_return_items_purchase_item` (`purchase_item_id`),
  KEY `fk_sales_return_items_product` (`product_id`),
  CONSTRAINT `fk_sales_return_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `fk_sales_return_items_purchase_item` FOREIGN KEY (`purchase_item_id`) REFERENCES `purchase_items` (`id`),
  CONSTRAINT `fk_sales_return_items_return` FOREIGN KEY (`return_id`) REFERENCES `sales_returns` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_return_items`
--

LOCK TABLES `sales_return_items` WRITE;
/*!40000 ALTER TABLE `sales_return_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales_return_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_returns`
--

DROP TABLE IF EXISTS `sales_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales_returns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `purchase_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `return_code` varchar(40) NOT NULL,
  `customer_name` varchar(150) NOT NULL,
  `customer_idnumber` varchar(20) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `reason_category` varchar(60) NOT NULL DEFAULT 'MALA_ELECCION_PRODUCTO',
  `reason_detail` varchar(255) DEFAULT NULL,
  `total_refund` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `return_code` (`return_code`),
  KEY `idx_sales_returns_purchase` (`purchase_id`),
  KEY `idx_sales_returns_created` (`created_at`),
  KEY `fk_sales_returns_user` (`user_id`),
  CONSTRAINT `fk_sales_returns_purchase` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`),
  CONSTRAINT `fk_sales_returns_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_returns`
--

LOCK TABLES `sales_returns` WRITE;
/*!40000 ALTER TABLE `sales_returns` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales_returns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_purchase_items`
--

DROP TABLE IF EXISTS `stock_purchase_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_purchase_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `purchase_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_id` (`purchase_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `stock_purchase_items_ibfk_1` FOREIGN KEY (`purchase_id`) REFERENCES `stock_purchases` (`id`),
  CONSTRAINT `stock_purchase_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_purchase_items`
--

LOCK TABLES `stock_purchase_items` WRITE;
/*!40000 ALTER TABLE `stock_purchase_items` DISABLE KEYS */;
INSERT INTO `stock_purchase_items` VALUES (1,1,6,3,18.00),(3,3,6,2,18.00),(9,9,8,6,1.85);
/*!40000 ALTER TABLE `stock_purchase_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_purchases`
--

DROP TABLE IF EXISTS `stock_purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_purchases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `status` enum('SOLICITADO','PAGADO','RECIBIDO','CANCELADO','REEMBOLSADO') NOT NULL DEFAULT 'SOLICITADO',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `paid_at` datetime DEFAULT NULL,
  `received_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `refunded_at` datetime DEFAULT NULL,
  `reversal_code` varchar(40) DEFAULT NULL,
  `reversal_reason` varchar(255) DEFAULT NULL,
  `reversed_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `stock_purchases_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_purchases`
--

LOCK TABLES `stock_purchases` WRITE;
/*!40000 ALTER TABLE `stock_purchases` DISABLE KEYS */;
INSERT INTO `stock_purchases` VALUES (1,5,NULL,54.00,'REEMBOLSADO','2026-09-25 04:48:11','2026-09-24 23:58:44',NULL,NULL,'2026-09-25 00:41:37','REV-20260925-F17570','nada',5),(3,5,1,36.00,'RECIBIDO','2026-09-25 04:58:07','2026-09-24 23:58:38','2026-09-24 23:59:17',NULL,NULL,NULL,NULL,NULL),(9,5,1,11.10,'RECIBIDO','2026-09-25 05:42:00','2026-09-25 00:42:07','2026-09-25 00:42:13',NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `stock_purchases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) NOT NULL,
  `contact_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `ruc` varchar(20) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (1,'Distribuidora Amazonica Paredes','Andrea Paredes','pedidos@distribuidoraparedes.ec','2200158479001','099 482 7316','Av. 9 de Octubre y Quito, Puerto Francisco de Orellana','2026-09-25 04:45:29','2026-09-25 04:45:29'),(2,'Comercializadora Andina del Oriente','Luis Cardenas','ventas@andinaoriente.ec','1793192847001','098 615 2047','Av. Alejandro Labaka y Rio Napo, Puerto Francisco de Orellana','2026-09-25 04:45:29','2026-09-25 04:45:29'),(3,'Alimentos Orellana Cia. Ltda.','Mariela Zambrano','distribucion@alimentosorellana.ec','2200215638001','096 288 4175','Calle Amazonas y Eugenio Espejo, Puerto Francisco de Orellana','2026-09-25 04:45:29','2026-09-25 04:47:33');
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','cashier') DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (5,'Cristhian@gmail.com','$2y$10$FODnFmAjmjeI6fR63Pvtf.nPQFqgs5.ErQhAI.5jkmLa./lAbc4mW','admin','2026-05-07 05:02:50'),(6,'Nexar@gmail.com','$2y$10$FODnFmAjmjeI6fR63Pvtf.nPQFqgs5.ErQhAI.5jkmLa./lAbc4mW','','2026-05-07 05:50:49');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'tienda'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-25  1:01:01
