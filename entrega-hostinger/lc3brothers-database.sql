-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: tienda
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
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (1,'Andrea Carolina Zambrano Mena','2201456783','andrea.zambrano@example.com','0991842637','Av. Alejandro Labaka y Rio Coca, barrio Central, El Coca','2026-06-22 06:33:36','2026-06-22 06:33:49'),(2,'Luis Fernando Grefa Alvarado','2202389413','luis.grefa@example.com','0985274163','Calle Napo y Quito, barrio 30 de Abril, El Coca','2026-06-22 06:33:36','2026-06-22 06:33:36'),(3,'Maria Jose Shiguango Cerda','2203527060','maria.shiguango@example.com','0963184752','Av. 9 de Octubre y Eugenio Espejo, barrio Union Imbaburena, El Coca','2026-06-22 06:33:36','2026-06-22 06:33:49'),(4,'Carlos Andres Villacis Paredes','2204673152','carlos.villacis@example.com','0976421835','Calle Amazonas y Vicente Rocafuerte, barrio Paraiso Amazonico, El Coca','2026-06-22 06:33:36','2026-06-22 06:33:49'),(5,'Daniela Estefania Tanguila Gualinga','2205718246','daniela.tanguila@example.com','0957362148','Av. Camilo de Torrano y Rio Payamino, barrio Flor de Oriente, El Coca','2026-06-22 06:33:36','2026-06-22 06:33:49'),(6,'Jorge Eduardo Vargas Moreira','2206842391','jorge.vargas@example.com','0996257314','Calle Cuenca y Guayaquil, barrio Los Rosales, El Coca','2026-06-22 06:33:36','2026-06-22 06:33:36'),(7,'Paola Fernanda Aguinda Mamallacta','2207931466','paola.aguinda@example.com','0981735624','Av. Padre Miguel Gamboa y Rio Tiputini, barrio Con Hogar, El Coca','2026-06-22 06:33:36','2026-06-22 06:33:49'),(9,'Erick Diaz','2250040850','erick@gmail.com','0988776655','Coca','2026-06-22 07:04:19','2026-06-22 07:04:19'),(10,'Mariela Zambrano','2204829135','mariela.zambrano@example.com','0994827316','Calle Amazonas y Eugenio Espejo, El Coca','2026-09-01 13:10:00','2026-10-06 01:04:35'),(11,'Luis Cardenas','1719284736','luis.cardenas@example.com','0986152047','Av. Alejandro Labaka y Rio Napo, El Coca','2026-09-02 13:10:00','2026-10-06 01:04:35'),(12,'Andrea Paredes','2200158479','andrea.paredes@example.com','0997364821','Av. 9 de Octubre y Quito, El Coca','2026-09-03 13:10:00','2026-10-06 01:04:35'),(13,'Jorge Villacis','1504983217','jorge.villacis@example.com','0962847315','Barrio Central, Puerto Francisco de Orellana','2026-09-04 13:10:00','2026-10-06 01:04:35'),(14,'Camila Rivas','2206135794','camila.rivas@example.com','0973162485','Cooperativa 30 de Abril, El Coca','2026-09-05 13:10:00','2026-10-06 01:04:35'),(15,'Patricio Grefa','2207451829','patricio.grefa@example.com','0984736152','Via Loreto km 2, El Coca','2026-09-06 13:10:00','2026-10-06 01:04:35'),(16,'Sofia Tanguila','2205718239','sofia.tanguila@example.com','0957362148','Barrio Flor de Oriente, El Coca','2026-09-07 13:10:00','2026-10-06 01:04:35'),(17,'Miguel Moreta','1603859274','miguel.moreta@example.com','0992846157','Av. Napo y Quito, El Coca','2026-09-08 13:10:00','2026-10-06 01:04:35'),(18,'Daniela Cevallos','1716048392','daniela.cevallos@example.com','0967341825','Ciudadela Paraiso Amazonico, El Coca','2026-09-09 13:10:00','2026-10-06 01:04:35'),(19,'Carlos Intriago','1309584726','carlos.intriago@example.com','0982476315','Calle Guayaquil y Loja, El Coca','2026-09-10 13:10:00','2026-10-06 01:04:35'),(20,'Fernanda Tapuy','2206841935','fernanda.tapuy@example.com','0954182736','Comuna San Jose, El Coca','2026-09-11 13:10:00','2026-10-06 01:04:35'),(21,'Roberto Macias','0918374625','roberto.macias@example.com','0996382714','Barrio 6 de Diciembre, El Coca','2026-09-12 13:10:00','2026-10-06 01:04:35');
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
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (5,5,'Pollo','7861000000055',13.91,0,10.20,'Carnes Rojas',19,'Carne rojita','1932d1225e35f0ad1708bb97.png','2026-01-15 22:29:03'),(6,4,'Arroz','7861000000062',23.20,0,18.00,'Granos',5,'granos','imagen_2026-01-16_004122429.png','2026-01-16 05:41:23'),(7,6,'chocolate','7861000000079',0.50,1,0.32,'dulces',8,'Dulces','c517e7aab06a17a0cbccb41c.png','2026-04-01 22:39:35'),(8,4,'Coca-Cola 2.5L','7861000000086',2.50,1,1.85,'Bebidas',59,'Refresco de cola familiar','620e767f925e901578b7f13e.png','2026-05-07 05:41:53'),(9,4,'Agua Mineral 500ml','7861000000093',0.50,1,0.28,'Bebidas',77,'Agua sin gas','847630c5a2709fa9c921a209.png','2026-05-07 05:41:53'),(10,4,'Jugo de Naranja 1L','7861000000109',1.20,1,0.78,'Bebidas',30,'Jugo natural pasteurizado','catalog-jugo-naranja-1l.jpg','2026-05-07 05:41:53'),(11,5,'Leche Entera 1L','7861000000116',0.90,0,0.64,'Lácteos',40,'Leche de vaca fortificada','catalog-leche-entera-1l.jpg','2026-05-07 05:41:53'),(12,5,'Yogurt de Fresa 1kg','7861000000123',1.80,0,1.25,'Lácteos',18,'Yogurt cremoso con trozos','catalog-yogurt-fresa-1kg.jpg','2026-05-07 05:41:53'),(13,5,'Queso Fresco 500g','7861000000130',3.50,0,2.55,'Lácteos',14,'Queso artesanal','catalog-queso-fresco-500g.jpg','2026-05-07 05:41:53'),(14,4,'Arroz 5kg','7861000000147',4.50,0,3.55,'Abarrotes',58,'Arroz grano largo','catalog-arroz-5kg.jpg','2026-05-07 05:41:53'),(15,4,'Aceite Girasol 1L','7861000000154',2.10,0,1.55,'Abarrotes',34,'Aceite vegetal refinado','catalog-aceite-girasol-1l.jpg','2026-05-07 05:41:53'),(16,6,'Pan de Molde','7861000000161',1.50,0,1.05,'Panadería',13,'Pan blanco tajado','catalog-pan-de-molde.jpg','2026-05-07 05:41:53'),(17,4,'Café Molido 250g','7861000000178',3.20,1,2.25,'Abarrotes',24,'Café de altura seleccionado','catalog-cafe-molido-250g.jpg','2026-05-07 05:41:53'),(18,6,'Gomitas','7861000000185',0.50,1,0.30,'Gomitas S.A',15,'Gomitas','2c44ea8647a8f84f699d5404.png','2026-06-23 05:32:18'),(19,4,'Mayonesa','7861000000192',1.00,1,0.68,'Condimentos',9,'Mayonesa','2b18b743c45112dd1cb8b386.png','2026-06-23 05:37:19');
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
) ENGINE=InnoDB AUTO_INCREMENT=249 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_items`
--

LOCK TABLES `purchase_items` WRITE;
/*!40000 ALTER TABLE `purchase_items` DISABLE KEYS */;
INSERT INTO `purchase_items` VALUES (1,1,4,'Helado',21.00,0.00,0.00,0.00,2,42.00),(2,1,5,'Pollo',14.00,10.20,0.00,0.00,2,28.00),(3,2,5,'Pollo',14.00,10.20,0.00,0.00,1,14.00),(4,3,5,'Pollo',14.00,10.20,0.00,0.00,1,14.00),(5,4,4,'Helado',21.00,0.00,0.00,0.00,2,42.00),(6,4,5,'Pollo',14.00,10.20,0.00,0.00,1,14.00),(7,5,5,'Pollo',14.00,10.20,0.00,0.00,1,14.00),(8,6,5,'Pollo',14.00,10.20,0.00,0.00,6,84.00),(9,6,4,'Helado',21.00,0.00,0.00,0.00,6,126.00),(10,7,5,'Pollo',14.00,10.20,0.00,0.00,10,140.00),(11,8,5,'Pollo',14.04,10.20,0.00,0.00,1,14.04),(12,8,6,'Arroz',23.20,18.00,0.00,0.00,1,23.20),(13,9,5,'Pollo',14.04,10.20,0.00,0.00,1,14.04),(14,9,4,'Helado',20.98,0.00,0.00,0.00,2,41.96),(15,10,5,'Pollo',13.91,10.20,0.00,0.00,3,41.73),(19,12,5,'Pollo',13.91,10.20,0.00,0.00,1,13.91),(20,12,4,'Helado',20.98,0.00,0.00,0.00,1,20.98),(21,12,6,'Arroz',23.20,18.00,0.00,0.00,1,23.20),(22,13,5,'Pollo',13.91,10.20,0.00,0.00,1,13.91),(23,14,6,'Arroz',23.20,18.00,0.00,0.00,1,23.20),(24,15,5,'Pollo',13.91,10.20,0.00,0.00,1,13.91),(25,15,6,'Arroz',23.20,18.00,0.00,0.00,1,23.20),(26,16,8,'Coca-Cola 2.5L',2.50,1.85,0.00,0.00,1,2.50),(27,17,8,'Coca-Cola 2.5L',2.50,1.85,0.00,0.00,1,2.50),(28,18,8,'Coca-Cola 2.5L',2.50,1.85,0.00,0.00,1,2.50),(29,19,5,'Pollo',13.91,10.20,0.00,0.00,1,13.91),(30,20,8,'Coca-Cola 2.5L',2.50,1.85,0.00,0.00,1,2.50),(31,21,8,'Coca-Cola 2.5L',2.50,1.85,0.00,0.00,1,2.50),(32,22,9,'Agua Mineral 500ml',0.50,0.28,0.00,0.00,1,0.50),(33,23,8,'Coca-Cola 2.5L',2.50,1.85,0.00,0.00,1,2.50),(34,24,8,'Coca-Cola 2.5L',2.50,1.85,0.00,0.00,3,7.50),(35,25,9,'Agua Mineral 500ml',0.50,0.28,0.00,0.00,1,0.50),(36,25,5,'Pollo',13.91,10.20,0.00,0.00,1,13.91),(37,26,8,'Coca-Cola 2.5L',2.50,1.85,0.00,0.00,1,2.50),(38,26,9,'Agua Mineral 500ml',0.50,0.28,0.00,0.00,1,0.50),(39,27,8,'Coca-Cola 2.5L',2.50,1.85,0.00,0.00,4,10.00),(40,27,9,'Agua Mineral 500ml',0.50,0.28,0.00,0.00,1,0.50),(41,28,9,'Agua Mineral 500ml',0.50,0.28,0.00,0.00,1,0.50),(42,28,12,'Yogurt de Fresa 1kg',1.80,1.25,0.00,0.00,5,9.00),(43,29,12,'Yogurt de Fresa 1kg',1.80,1.25,0.00,0.00,2,3.60),(44,29,5,'Pollo',13.91,10.20,0.00,0.00,1,13.91),(45,30,8,'Coca-Cola 2.5L',2.50,1.85,0.00,0.00,3,7.50),(46,30,9,'Agua Mineral 500ml',0.50,0.28,0.00,0.00,1,0.50),(48,32,5,'Pollo',13.91,10.20,0.00,0.00,1,13.91),(49,32,8,'Coca-Cola 2.5L',2.50,1.85,0.00,0.00,1,2.50),(50,32,9,'Agua Mineral 500ml',0.50,0.28,0.00,0.00,3,1.50),(51,33,5,'Pollo',13.91,10.20,0.00,0.00,1,13.91),(52,34,8,'Coca-Cola 2.5L',2.50,1.85,0.00,0.00,3,7.50),(53,36,19,'Mayonesa',1.00,0.68,0.00,0.00,15,15.00),(54,37,8,'Coca-Cola 2.5L',2.50,1.85,0.00,0.00,1,2.50),(55,37,9,'Agua Mineral 500ml',0.50,0.28,0.00,0.00,1,0.50),(56,37,13,'Queso Fresco 500g',3.50,2.55,0.00,0.00,1,3.50),(57,37,14,'Arroz 5kg',4.50,3.55,0.00,0.00,1,4.50),(58,38,17,'Café Molido 250g',3.20,2.25,0.00,0.00,1,3.20),(59,38,16,'Pan de Molde',1.50,1.05,0.00,0.00,7,10.50),(60,38,15,'Aceite Girasol 1L',2.10,1.55,0.00,0.00,1,2.10),(61,38,14,'Arroz 5kg',4.50,3.55,0.00,0.00,1,4.50),(62,39,8,'Coca-Cola 2.5L',2.50,1.85,0.00,0.00,1,2.50),(63,39,5,'Pollo',13.91,10.20,0.00,0.00,1,13.91),(64,39,9,'Agua Mineral 500ml',0.50,0.28,0.00,0.00,2,1.00),(65,39,23,'Paquete de Hojas',1.00,0.85,0.00,0.00,1,1.00),(66,40,8,'Coca-Cola 2.5L',2.50,1.85,15.00,1.13,3,8.63),(67,41,9,'Agua Mineral 500ml',0.50,0.28,15.00,0.38,5,2.88),(68,42,9,'Agua Mineral 500ml',0.50,0.28,15.00,0.83,11,6.33),(69,43,8,'Coca-Cola 2.5L',2.50,1.85,15.00,1.13,3,8.63),(70,43,10,'Jugo de Naranja 1L',1.20,0.78,15.00,0.36,2,2.76),(71,43,18,'Gomitas',0.50,0.30,15.00,0.38,5,2.88),(72,44,6,'Arroz',23.20,18.00,0.00,0.00,1,23.20),(73,44,9,'Agua Mineral 500ml',0.50,0.28,15.00,0.60,8,4.60),(74,45,11,'Leche Entera 1L',0.90,0.64,0.00,0.00,4,3.60),(75,45,12,'Yogurt de Fresa 1kg',1.80,1.25,0.00,0.00,2,3.60),(76,45,13,'Queso Fresco 500g',3.50,2.55,0.00,0.00,1,3.50),(77,46,17,'Café Molido 250g',3.20,2.25,15.00,0.96,2,7.36),(78,46,16,'Pan de Molde',1.50,1.05,0.00,0.00,3,4.50),(79,46,19,'Mayonesa',1.00,0.68,15.00,0.30,2,2.30),(80,47,5,'Pollo',13.91,10.20,0.00,0.00,1,13.91),(81,47,14,'Arroz 5kg',4.50,3.55,0.00,0.00,2,9.00),(82,47,15,'Aceite Girasol 1L',2.10,1.55,0.00,0.00,2,4.20),(83,48,8,'Coca-Cola 2.5L',2.50,1.85,15.00,1.50,4,11.50),(84,48,9,'Agua Mineral 500ml',0.50,0.28,15.00,0.75,10,5.75),(85,48,18,'Gomitas',0.50,0.30,15.00,0.45,6,3.45),(86,49,6,'Arroz',23.20,18.00,0.00,0.00,1,23.20),(87,49,9,'Agua Mineral 500ml',0.50,0.28,15.00,0.60,8,4.60),(88,50,12,'Yogurt de Fresa 1kg',1.80,1.25,0.00,0.00,3,5.40),(89,50,11,'Leche Entera 1L',0.90,0.64,0.00,0.00,5,4.50),(90,51,17,'Café Molido 250g',3.20,2.25,15.00,0.96,2,7.36),(91,51,16,'Pan de Molde',1.50,1.05,0.00,0.00,3,4.50),(92,51,19,'Mayonesa',1.00,0.68,15.00,0.30,2,2.30),(93,52,5,'Pollo',13.91,10.20,0.00,0.00,2,27.82),(94,52,13,'Queso Fresco 500g',3.50,2.55,0.00,0.00,2,7.00),(95,53,8,'Coca-Cola 2.5L',2.50,1.85,15.00,1.13,3,8.63),(96,53,10,'Jugo de Naranja 1L',1.20,0.78,15.00,0.36,2,2.76),(97,53,18,'Gomitas',0.50,0.30,15.00,0.38,5,2.88),(98,54,8,'Coca-Cola 2.5L',2.50,1.85,15.00,1.50,4,11.50),(99,54,9,'Agua Mineral 500ml',0.50,0.28,15.00,0.75,10,5.75),(100,54,18,'Gomitas',0.50,0.30,15.00,0.45,6,3.45),(101,55,14,'Arroz 5kg',4.50,3.55,0.00,0.00,3,13.50),(102,55,15,'Aceite Girasol 1L',2.10,1.55,0.00,0.00,2,4.20),(103,55,10,'Jugo de Naranja 1L',1.20,0.78,15.00,0.54,3,4.14),(104,56,12,'Yogurt de Fresa 1kg',1.80,1.25,0.00,0.00,3,5.40),(105,56,11,'Leche Entera 1L',0.90,0.64,0.00,0.00,5,4.50),(106,57,9,'Agua Mineral 500ml',0.50,0.28,15.00,0.45,6,3.45),(107,57,16,'Pan de Molde',1.50,1.05,0.00,0.00,2,3.00),(108,57,19,'Mayonesa',1.00,0.68,15.00,0.15,1,1.15),(109,58,5,'Pollo',13.91,10.20,0.00,0.00,2,27.82),(110,58,13,'Queso Fresco 500g',3.50,2.55,0.00,0.00,2,7.00),(111,59,8,'Coca-Cola 2.5L',2.50,1.85,15.00,1.13,3,8.63),(112,59,10,'Jugo de Naranja 1L',1.20,0.78,15.00,0.36,2,2.76),(113,59,18,'Gomitas',0.50,0.30,15.00,0.38,5,2.88),(114,60,14,'Arroz 5kg',4.50,3.55,0.00,0.00,3,13.50),(115,60,15,'Aceite Girasol 1L',2.10,1.55,0.00,0.00,2,4.20),(116,60,10,'Jugo de Naranja 1L',1.20,0.78,15.00,0.54,3,4.14),(117,61,11,'Leche Entera 1L',0.90,0.64,0.00,0.00,4,3.60),(118,61,12,'Yogurt de Fresa 1kg',1.80,1.25,0.00,0.00,2,3.60),(119,61,13,'Queso Fresco 500g',3.50,2.55,0.00,0.00,1,3.50),(120,62,9,'Agua Mineral 500ml',0.50,0.28,15.00,0.45,6,3.45),(121,62,16,'Pan de Molde',1.50,1.05,0.00,0.00,2,3.00),(122,62,19,'Mayonesa',1.00,0.68,15.00,0.15,1,1.15),(123,63,5,'Pollo',13.91,10.20,0.00,0.00,1,13.91),(124,63,14,'Arroz 5kg',4.50,3.55,0.00,0.00,2,9.00),(125,63,15,'Aceite Girasol 1L',2.10,1.55,0.00,0.00,2,4.20),(126,64,8,'Coca-Cola 2.5L',2.50,1.85,15.00,1.50,4,11.50),(127,64,9,'Agua Mineral 500ml',0.50,0.28,15.00,0.75,10,5.75),(128,64,18,'Gomitas',0.50,0.30,15.00,0.45,6,3.45),(129,65,8,'Coca-Cola 2.5L',2.50,1.85,15.00,1.13,3,8.63),(130,65,10,'Jugo de Naranja 1L',1.20,0.78,15.00,0.36,2,2.76),(131,65,18,'Gomitas',0.50,0.30,15.00,0.38,5,2.88),(132,66,6,'Arroz',23.20,18.00,0.00,0.00,1,23.20),(133,66,9,'Agua Mineral 500ml',0.50,0.28,15.00,0.60,8,4.60),(134,67,11,'Leche Entera 1L',0.90,0.64,0.00,0.00,4,3.60),(135,67,12,'Yogurt de Fresa 1kg',1.80,1.25,0.00,0.00,2,3.60),(136,67,13,'Queso Fresco 500g',3.50,2.55,0.00,0.00,1,3.50),(137,68,17,'Café Molido 250g',3.20,2.25,15.00,0.96,2,7.36),(138,68,16,'Pan de Molde',1.50,1.05,0.00,0.00,3,4.50),(139,68,19,'Mayonesa',1.00,0.68,15.00,0.30,2,2.30),(140,69,5,'Pollo',13.91,10.20,0.00,0.00,1,13.91),(141,69,14,'Arroz 5kg',4.50,3.55,0.00,0.00,2,9.00),(142,69,15,'Aceite Girasol 1L',2.10,1.55,0.00,0.00,2,4.20),(143,70,8,'Coca-Cola 2.5L',2.50,1.85,15.00,1.50,4,11.50),(144,70,9,'Agua Mineral 500ml',0.50,0.28,15.00,0.75,10,5.75),(145,70,18,'Gomitas',0.50,0.30,15.00,0.45,6,3.45),(146,71,6,'Arroz',23.20,18.00,0.00,0.00,1,23.20),(147,71,9,'Agua Mineral 500ml',0.50,0.28,15.00,0.60,8,4.60),(148,72,12,'Yogurt de Fresa 1kg',1.80,1.25,0.00,0.00,3,5.40),(149,72,11,'Leche Entera 1L',0.90,0.64,0.00,0.00,5,4.50),(150,73,17,'Café Molido 250g',3.20,2.25,15.00,0.96,2,7.36),(151,73,16,'Pan de Molde',1.50,1.05,0.00,0.00,3,4.50),(152,73,19,'Mayonesa',1.00,0.68,15.00,0.30,2,2.30),(153,74,5,'Pollo',13.91,10.20,0.00,0.00,2,27.82),(154,74,13,'Queso Fresco 500g',3.50,2.55,0.00,0.00,2,7.00),(155,75,8,'Coca-Cola 2.5L',2.50,1.85,15.00,1.13,3,8.63),(156,75,10,'Jugo de Naranja 1L',1.20,0.78,15.00,0.36,2,2.76),(157,75,18,'Gomitas',0.50,0.30,15.00,0.38,5,2.88),(158,76,8,'Coca-Cola 2.5L',2.50,1.85,15.00,1.50,4,11.50),(159,76,9,'Agua Mineral 500ml',0.50,0.28,15.00,0.75,10,5.75),(160,76,18,'Gomitas',0.50,0.30,15.00,0.45,6,3.45),(161,77,14,'Arroz 5kg',4.50,3.55,0.00,0.00,3,13.50),(162,77,15,'Aceite Girasol 1L',2.10,1.55,0.00,0.00,2,4.20),(163,77,10,'Jugo de Naranja 1L',1.20,0.78,15.00,0.54,3,4.14),(164,78,12,'Yogurt de Fresa 1kg',1.80,1.25,0.00,0.00,3,5.40),(165,78,11,'Leche Entera 1L',0.90,0.64,0.00,0.00,5,4.50),(166,79,9,'Agua Mineral 500ml',0.50,0.28,15.00,0.45,6,3.45),(167,79,16,'Pan de Molde',1.50,1.05,0.00,0.00,2,3.00),(168,79,19,'Mayonesa',1.00,0.68,15.00,0.15,1,1.15),(169,80,5,'Pollo',13.91,10.20,0.00,0.00,2,27.82),(170,80,13,'Queso Fresco 500g',3.50,2.55,0.00,0.00,2,7.00),(171,81,8,'Coca-Cola 2.5L',2.50,1.85,15.00,1.13,3,8.63),(172,81,10,'Jugo de Naranja 1L',1.20,0.78,15.00,0.36,2,2.76),(173,81,18,'Gomitas',0.50,0.30,15.00,0.38,5,2.88),(174,82,14,'Arroz 5kg',4.50,3.55,0.00,0.00,3,13.50),(175,82,15,'Aceite Girasol 1L',2.10,1.55,0.00,0.00,2,4.20),(176,82,10,'Jugo de Naranja 1L',1.20,0.78,15.00,0.54,3,4.14),(177,83,11,'Leche Entera 1L',0.90,0.64,0.00,0.00,4,3.60),(178,83,12,'Yogurt de Fresa 1kg',1.80,1.25,0.00,0.00,2,3.60),(179,83,13,'Queso Fresco 500g',3.50,2.55,0.00,0.00,1,3.50),(180,84,9,'Agua Mineral 500ml',0.50,0.28,15.00,0.45,6,3.45),(181,84,16,'Pan de Molde',1.50,1.05,0.00,0.00,2,3.00),(182,84,19,'Mayonesa',1.00,0.68,15.00,0.15,1,1.15),(183,85,5,'Pollo',13.91,10.20,0.00,0.00,1,13.91),(184,85,14,'Arroz 5kg',4.50,3.55,0.00,0.00,2,9.00),(185,85,15,'Aceite Girasol 1L',2.10,1.55,0.00,0.00,2,4.20),(186,86,8,'Coca-Cola 2.5L',2.50,1.85,15.00,1.50,4,11.50),(187,86,9,'Agua Mineral 500ml',0.50,0.28,15.00,0.75,10,5.75),(188,86,18,'Gomitas',0.50,0.30,15.00,0.45,6,3.45),(189,87,8,'Coca-Cola 2.5L',2.50,1.85,15.00,1.13,3,8.63),(190,87,10,'Jugo de Naranja 1L',1.20,0.78,15.00,0.36,2,2.76),(191,87,18,'Gomitas',0.50,0.30,15.00,0.38,5,2.88),(192,88,6,'Arroz',23.20,18.00,0.00,0.00,1,23.20),(193,88,9,'Agua Mineral 500ml',0.50,0.28,15.00,0.60,8,4.60),(194,89,11,'Leche Entera 1L',0.90,0.64,0.00,0.00,4,3.60),(195,89,12,'Yogurt de Fresa 1kg',1.80,1.25,0.00,0.00,2,3.60),(196,89,13,'Queso Fresco 500g',3.50,2.55,0.00,0.00,1,3.50),(197,90,17,'Café Molido 250g',3.20,2.25,15.00,0.96,2,7.36),(198,90,16,'Pan de Molde',1.50,1.05,0.00,0.00,3,4.50),(199,90,19,'Mayonesa',1.00,0.68,15.00,0.30,2,2.30),(200,91,5,'Pollo',13.91,10.20,0.00,0.00,1,13.91),(201,91,14,'Arroz 5kg',4.50,3.55,0.00,0.00,2,9.00),(202,91,15,'Aceite Girasol 1L',2.10,1.55,0.00,0.00,2,4.20),(203,92,8,'Coca-Cola 2.5L',2.50,1.85,15.00,1.50,4,11.50),(204,92,9,'Agua Mineral 500ml',0.50,0.28,15.00,0.75,10,5.75),(205,92,18,'Gomitas',0.50,0.30,15.00,0.45,6,3.45),(206,93,6,'Arroz',23.20,18.00,0.00,0.00,1,23.20),(207,93,9,'Agua Mineral 500ml',0.50,0.28,15.00,0.60,8,4.60),(208,94,12,'Yogurt de Fresa 1kg',1.80,1.25,0.00,0.00,3,5.40),(209,94,11,'Leche Entera 1L',0.90,0.64,0.00,0.00,5,4.50),(210,95,17,'Café Molido 250g',3.20,2.25,15.00,0.96,2,7.36),(211,95,16,'Pan de Molde',1.50,1.05,0.00,0.00,3,4.50),(212,95,19,'Mayonesa',1.00,0.68,15.00,0.30,2,2.30),(213,96,5,'Pollo',13.91,10.20,0.00,0.00,2,27.82),(214,96,13,'Queso Fresco 500g',3.50,2.55,0.00,0.00,2,7.00),(215,97,8,'Coca-Cola 2.5L',2.50,1.85,15.00,1.13,3,8.63),(216,97,10,'Jugo de Naranja 1L',1.20,0.78,15.00,0.36,2,2.76),(217,97,18,'Gomitas',0.50,0.30,15.00,0.38,5,2.88),(218,98,8,'Coca-Cola 2.5L',2.50,1.85,15.00,1.50,4,11.50),(219,98,9,'Agua Mineral 500ml',0.50,0.28,15.00,0.75,10,5.75),(220,98,18,'Gomitas',0.50,0.30,15.00,0.45,6,3.45),(221,99,14,'Arroz 5kg',4.50,3.55,0.00,0.00,3,13.50),(222,99,15,'Aceite Girasol 1L',2.10,1.55,0.00,0.00,2,4.20),(223,99,10,'Jugo de Naranja 1L',1.20,0.78,15.00,0.54,3,4.14),(224,100,12,'Yogurt de Fresa 1kg',1.80,1.25,0.00,0.00,3,5.40),(225,100,11,'Leche Entera 1L',0.90,0.64,0.00,0.00,5,4.50),(226,101,9,'Agua Mineral 500ml',0.50,0.28,15.00,0.45,6,3.45),(227,101,16,'Pan de Molde',1.50,1.05,0.00,0.00,2,3.00),(228,101,19,'Mayonesa',1.00,0.68,15.00,0.15,1,1.15),(229,102,5,'Pollo',13.91,10.20,0.00,0.00,2,27.82),(230,102,13,'Queso Fresco 500g',3.50,2.55,0.00,0.00,2,7.00),(231,103,8,'Coca-Cola 2.5L',2.50,1.85,15.00,1.13,3,8.63),(232,103,10,'Jugo de Naranja 1L',1.20,0.78,15.00,0.36,2,2.76),(233,103,18,'Gomitas',0.50,0.30,15.00,0.38,5,2.88),(234,104,14,'Arroz 5kg',4.50,3.55,0.00,0.00,3,13.50),(235,104,15,'Aceite Girasol 1L',2.10,1.55,0.00,0.00,2,4.20),(236,104,10,'Jugo de Naranja 1L',1.20,0.78,15.00,0.54,3,4.14),(237,105,11,'Leche Entera 1L',0.90,0.64,0.00,0.00,4,3.60),(238,105,12,'Yogurt de Fresa 1kg',1.80,1.25,0.00,0.00,2,3.60),(239,105,13,'Queso Fresco 500g',3.50,2.55,0.00,0.00,1,3.50),(240,106,9,'Agua Mineral 500ml',0.50,0.28,15.00,0.45,6,3.45),(241,106,16,'Pan de Molde',1.50,1.05,0.00,0.00,2,3.00),(242,106,19,'Mayonesa',1.00,0.68,15.00,0.15,1,1.15),(243,107,5,'Pollo',13.91,10.20,0.00,0.00,1,13.91),(244,107,14,'Arroz 5kg',4.50,3.55,0.00,0.00,2,9.00),(245,107,15,'Aceite Girasol 1L',2.10,1.55,0.00,0.00,2,4.20),(246,108,8,'Coca-Cola 2.5L',2.50,1.85,15.00,1.50,4,11.50),(247,108,9,'Agua Mineral 500ml',0.50,0.28,15.00,0.75,10,5.75),(248,108,18,'Gomitas',0.50,0.30,15.00,0.45,6,3.45);
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
) ENGINE=InnoDB AUTO_INCREMENT=109 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchases`
--

LOCK TABLES `purchases` WRITE;
/*!40000 ALTER TABLE `purchases` DISABLE KEYS */;
INSERT INTO `purchases` VALUES (1,1,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,70.00,8.40,78.40,NULL,NULL,NULL,'2026-01-15 23:35:12'),(2,1,NULL,'FACTURA',NULL,'a','a@2','a','a','a',14.00,1.68,15.68,NULL,NULL,NULL,'2026-01-15 23:53:14'),(3,1,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,14.00,1.68,15.68,NULL,NULL,NULL,'2026-01-15 23:56:33'),(4,1,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,56.00,6.72,62.72,NULL,NULL,NULL,'2026-01-15 23:57:11'),(5,1,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,14.00,1.68,15.68,NULL,NULL,NULL,'2026-01-16 00:11:23'),(6,1,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,210.00,25.20,235.20,NULL,NULL,NULL,'2026-01-16 00:21:19'),(7,1,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,140.00,16.80,156.80,NULL,NULL,NULL,'2026-01-16 00:33:47'),(8,2,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,37.24,4.47,41.71,NULL,NULL,NULL,'2026-01-16 00:41:34'),(9,1,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,56.00,6.72,62.72,NULL,NULL,NULL,'2026-01-16 00:42:42'),(10,1,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,41.73,5.01,46.74,NULL,NULL,NULL,'2026-01-16 00:43:23'),(12,3,NULL,'FACTURA',NULL,'Melisa','meli@gmail.com','098798674','centro','2200272215',58.09,6.97,65.06,NULL,NULL,NULL,'2026-01-17 20:41:00'),(13,4,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,13.91,1.67,15.58,NULL,NULL,NULL,'2026-04-01 17:52:44'),(14,4,NULL,'FACTURA',NULL,'mariana','mopositamariana@gmail.com','0968','centro','0202135463',23.20,2.78,25.98,NULL,NULL,NULL,'2026-04-01 17:57:27'),(15,5,NULL,'FACTURA',NULL,'fumando','acadamia1110@gmail.com','0968','centro','5555555555',37.11,4.45,41.56,NULL,NULL,NULL,'2026-05-07 00:08:17'),(16,5,NULL,'CONSUMIDOR_FINAL',NULL,'','','','','',2.50,0.38,2.88,NULL,NULL,NULL,'2026-05-07 01:21:02'),(17,5,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,2.50,0.38,2.88,NULL,NULL,NULL,'2026-05-07 01:24:29'),(18,5,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,2.50,0.38,2.88,NULL,NULL,NULL,'2026-05-07 01:28:29'),(19,5,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,13.91,2.09,16.00,NULL,NULL,NULL,'2026-05-07 01:32:29'),(20,5,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,2.50,0.38,2.88,NULL,NULL,NULL,'2026-05-07 01:32:36'),(21,5,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,2.50,0.38,2.88,NULL,NULL,NULL,'2026-05-07 01:35:03'),(22,5,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,0.50,0.08,0.58,NULL,NULL,NULL,'2026-05-07 01:36:15'),(23,5,NULL,'FACTURA',NULL,'Fernando Xavier Bravo Valladolid','fernandobravo6582@gmail.com','0987168084','Ecuador, El Coca Orellana','e',2.50,0.38,2.88,NULL,NULL,NULL,'2026-05-07 01:36:32'),(24,6,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,7.50,1.13,8.63,NULL,NULL,NULL,'2026-05-07 23:08:58'),(25,6,NULL,'FACTURA',NULL,'Fernando Xavier Bravo Valladolid','fernandobravo6582@gmail.com','0987168084','Ecuador, El Coca Orellana','343434',14.41,2.16,16.57,NULL,NULL,NULL,'2026-05-07 23:09:35'),(26,5,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,3.00,0.45,3.45,NULL,NULL,NULL,'2026-05-08 00:21:27'),(27,5,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,10.50,1.58,12.08,NULL,NULL,NULL,'2026-05-08 12:04:14'),(28,5,NULL,'FACTURA',NULL,'cristhian','ajbskvdkvs343@ls.cpm343','09934939434','34343','232323232323',9.50,1.43,10.93,NULL,NULL,NULL,'2026-05-08 12:05:22'),(29,5,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,17.51,2.63,20.14,NULL,NULL,NULL,'2026-05-08 13:04:55'),(30,5,NULL,'FACTURA',NULL,'mariana','cualquiera@hotmail.com','0365254155','las americas','2014569878',8.00,1.20,9.20,NULL,NULL,NULL,'2026-05-08 13:06:08'),(32,6,9,'FACTURA',NULL,'Erick Diaz','erick@gmail.com','0988776655','Coca','2250040850',17.91,2.69,20.60,NULL,NULL,NULL,'2026-06-22 02:04:19'),(33,5,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,13.91,2.09,16.00,NULL,NULL,NULL,'2026-06-24 01:11:49'),(34,6,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,7.50,1.13,8.63,NULL,NULL,NULL,'2026-06-24 17:01:42'),(36,6,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,15.00,2.25,17.25,NULL,NULL,NULL,'2026-06-24 17:08:40'),(37,6,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,11.00,1.65,12.65,NULL,NULL,NULL,'2026-06-24 17:17:32'),(38,6,NULL,'CONSUMIDOR_FINAL',NULL,NULL,NULL,NULL,NULL,NULL,20.30,3.04,23.34,NULL,NULL,NULL,'2026-06-24 17:20:15'),(39,5,5,'FACTURA',NULL,'Daniela Estefania Tanguila Gualinga','daniela.tanguila@example.com','0957362148','Av. Camilo de Torrano y Rio Payamino, barrio Flor de Oriente, El Coca','2205718246',18.41,2.76,21.17,NULL,NULL,NULL,'2026-07-02 20:01:31'),(40,5,NULL,'CONSUMIDOR_FINAL','EFECTIVO',NULL,NULL,NULL,NULL,NULL,7.50,1.13,8.63,20.00,11.37,NULL,'2026-10-05 19:21:28'),(41,5,NULL,'CONSUMIDOR_FINAL','EFECTIVO',NULL,NULL,NULL,NULL,NULL,2.50,0.38,2.88,20.00,17.12,NULL,'2026-10-05 19:21:43'),(42,5,NULL,'CONSUMIDOR_FINAL','EFECTIVO',NULL,NULL,NULL,NULL,NULL,5.50,0.83,6.33,10.00,3.67,NULL,'2026-10-05 19:22:00'),(43,6,NULL,'CONSUMIDOR_FINAL','EFECTIVO',NULL,NULL,NULL,NULL,NULL,12.40,1.87,14.27,20.00,5.73,NULL,'2026-09-01 10:17:00'),(44,5,NULL,'CONSUMIDOR_FINAL','EFECTIVO',NULL,NULL,NULL,NULL,NULL,27.20,0.60,27.80,30.00,2.20,NULL,'2026-09-01 11:30:00'),(45,5,NULL,'CONSUMIDOR_FINAL','EFECTIVO',NULL,NULL,NULL,NULL,NULL,10.70,0.00,10.70,15.00,4.30,NULL,'2026-09-02 11:24:00'),(46,6,4,'FACTURA','EFECTIVO','Carlos Andres Villacis Paredes','carlos.villacis@example.com','0976421835','Calle Amazonas y Vicente Rocafuerte, barrio Paraiso Amazonico, El Coca','2204673152',12.90,1.26,14.16,20.00,5.84,NULL,'2026-09-02 12:37:00'),(47,6,4,'FACTURA','EFECTIVO','Carlos Andres Villacis Paredes','carlos.villacis@example.com','0976421835','Calle Amazonas y Vicente Rocafuerte, barrio Paraiso Amazonico, El Coca','2204673152',27.11,0.00,27.11,30.00,2.89,NULL,'2026-09-03 12:31:00'),(48,5,NULL,'CONSUMIDOR_FINAL','TRANSFERENCIA',NULL,NULL,NULL,NULL,NULL,18.00,2.70,20.70,NULL,NULL,'TRX-20260903-002','2026-09-03 13:44:00'),(49,5,NULL,'CONSUMIDOR_FINAL','TRANSFERENCIA',NULL,NULL,NULL,NULL,NULL,27.20,0.60,27.80,NULL,NULL,'TRX-20260904-001','2026-09-04 13:38:00'),(50,6,NULL,'CONSUMIDOR_FINAL','EFECTIVO',NULL,NULL,NULL,NULL,NULL,9.90,0.00,9.90,15.00,5.10,NULL,'2026-09-04 14:51:00'),(51,6,NULL,'CONSUMIDOR_FINAL','EFECTIVO',NULL,NULL,NULL,NULL,NULL,12.90,1.26,14.16,20.00,5.84,NULL,'2026-09-05 14:45:00'),(52,5,7,'FACTURA','EFECTIVO','Paola Fernanda Aguinda Mamallacta','paola.aguinda@example.com','0981735624','Av. Padre Miguel Gamboa y Rio Tiputini, barrio Con Hogar, El Coca','2207931466',34.82,0.00,34.82,40.00,5.18,NULL,'2026-09-05 15:13:00'),(53,6,NULL,'CONSUMIDOR_FINAL','EFECTIVO',NULL,NULL,NULL,NULL,NULL,12.40,1.87,14.27,20.00,5.73,NULL,'2026-09-05 16:26:00'),(54,5,7,'FACTURA','EFECTIVO','Paola Fernanda Aguinda Mamallacta','paola.aguinda@example.com','0981735624','Av. Padre Miguel Gamboa y Rio Tiputini, barrio Con Hogar, El Coca','2207931466',18.00,2.70,20.70,25.00,4.30,NULL,'2026-09-06 15:52:00'),(55,6,NULL,'CONSUMIDOR_FINAL','EFECTIVO',NULL,NULL,NULL,NULL,NULL,21.30,0.54,21.84,25.00,3.16,NULL,'2026-09-06 16:20:00'),(56,6,NULL,'CONSUMIDOR_FINAL','EFECTIVO',NULL,NULL,NULL,NULL,NULL,9.90,0.00,9.90,15.00,5.10,NULL,'2026-09-07 16:14:00'),(57,5,NULL,'CONSUMIDOR_FINAL','TRANSFERENCIA',NULL,NULL,NULL,NULL,NULL,7.00,0.60,7.60,NULL,NULL,'TRX-20260907-002','2026-09-07 17:27:00'),(58,5,NULL,'CONSUMIDOR_FINAL','TRANSFERENCIA',NULL,NULL,NULL,NULL,NULL,34.82,0.00,34.82,NULL,NULL,'TRX-20260908-001','2026-09-08 17:21:00'),(59,6,11,'FACTURA','EFECTIVO','Luis Cardenas','luis.cardenas@example.com','0986152047','Av. Alejandro Labaka y Rio Napo, El Coca','1719284736',12.40,1.87,14.27,20.00,5.73,NULL,'2026-09-08 09:34:00'),(60,6,11,'FACTURA','EFECTIVO','Luis Cardenas','luis.cardenas@example.com','0986152047','Av. Alejandro Labaka y Rio Napo, El Coca','1719284736',21.30,0.54,21.84,25.00,3.16,NULL,'2026-09-09 09:28:00'),(61,5,NULL,'CONSUMIDOR_FINAL','EFECTIVO',NULL,NULL,NULL,NULL,NULL,10.70,0.00,10.70,15.00,4.30,NULL,'2026-09-09 10:41:00'),(62,5,NULL,'CONSUMIDOR_FINAL','EFECTIVO',NULL,NULL,NULL,NULL,NULL,7.00,0.60,7.60,10.00,2.40,NULL,'2026-09-10 10:35:00'),(63,6,NULL,'CONSUMIDOR_FINAL','EFECTIVO',NULL,NULL,NULL,NULL,NULL,27.11,0.00,27.11,30.00,2.89,NULL,'2026-09-10 11:48:00'),(64,5,14,'FACTURA','TRANSFERENCIA','Camila Rivas','camila.rivas@example.com','0973162485','Cooperativa 30 de Abril, El Coca','2206135794',18.00,2.70,20.70,NULL,NULL,'TRX-20260910-003','2026-09-10 12:16:00'),(65,6,NULL,'CONSUMIDOR_FINAL','EFECTIVO',NULL,NULL,NULL,NULL,NULL,12.40,1.87,14.27,20.00,5.73,NULL,'2026-09-11 11:42:00'),(66,5,14,'FACTURA','TRANSFERENCIA','Camila Rivas','camila.rivas@example.com','0973162485','Cooperativa 30 de Abril, El Coca','2206135794',27.20,0.60,27.80,NULL,NULL,'TRX-20260911-002','2026-09-11 12:10:00'),(67,5,14,'FACTURA','TRANSFERENCIA','Camila Rivas','camila.rivas@example.com','0973162485','Cooperativa 30 de Abril, El Coca','2206135794',10.70,0.00,10.70,NULL,NULL,'TRX-20260912-001','2026-09-12 12:49:00'),(68,6,NULL,'CONSUMIDOR_FINAL','EFECTIVO',NULL,NULL,NULL,NULL,NULL,12.90,1.26,14.16,20.00,5.84,NULL,'2026-09-12 13:17:00'),(69,6,NULL,'CONSUMIDOR_FINAL','EFECTIVO',NULL,NULL,NULL,NULL,NULL,27.11,0.00,27.11,30.00,2.89,NULL,'2026-09-13 13:11:00'),(70,5,NULL,'CONSUMIDOR_FINAL','EFECTIVO',NULL,NULL,NULL,NULL,NULL,18.00,2.70,20.70,25.00,4.30,NULL,'2026-09-13 14:24:00'),(71,5,NULL,'CONSUMIDOR_FINAL','EFECTIVO',NULL,NULL,NULL,NULL,NULL,27.20,0.60,27.80,30.00,2.20,NULL,'2026-09-14 14:18:00'),(72,6,17,'FACTURA','EFECTIVO','Miguel Moreta','miguel.moreta@example.com','0992846157','Av. Napo y Quito, El Coca','1603859274',9.90,0.00,9.90,15.00,5.10,NULL,'2026-09-14 15:31:00'),(73,6,17,'FACTURA','EFECTIVO','Miguel Moreta','miguel.moreta@example.com','0992846157','Av. Napo y Quito, El Coca','1603859274',12.90,1.26,14.16,20.00,5.84,NULL,'2026-09-15 15:25:00'),(74,5,NULL,'CONSUMIDOR_FINAL','TRANSFERENCIA',NULL,NULL,NULL,NULL,NULL,34.82,0.00,34.82,NULL,NULL,'TRX-20260915-002','2026-09-15 16:38:00'),(75,6,NULL,'CONSUMIDOR_FINAL','EFECTIVO',NULL,NULL,NULL,NULL,NULL,12.40,1.87,14.27,20.00,5.73,NULL,'2026-09-15 17:51:00'),(76,5,NULL,'CONSUMIDOR_FINAL','TRANSFERENCIA',NULL,NULL,NULL,NULL,NULL,18.00,2.70,20.70,NULL,NULL,'TRX-20260916-001','2026-09-16 16:32:00'),(77,6,NULL,'CONSUMIDOR_FINAL','EFECTIVO',NULL,NULL,NULL,NULL,NULL,21.30,0.54,21.84,25.00,3.16,NULL,'2026-09-16 17:45:00'),(78,6,NULL,'CONSUMIDOR_FINAL','EFECTIVO',NULL,NULL,NULL,NULL,NULL,9.90,0.00,9.90,15.00,5.10,NULL,'2026-09-17 17:39:00'),(79,5,20,'FACTURA','EFECTIVO','Fernanda Tapuy','fernanda.tapuy@example.com','0954182736','Comuna San Jose, El Coca','2206841935',7.00,0.60,7.60,10.00,2.40,NULL,'2026-09-17 09:52:00'),(80,5,20,'FACTURA','EFECTIVO','Fernanda Tapuy','fernanda.tapuy@example.com','0954182736','Comuna San Jose, El Coca','2206841935',34.82,0.00,34.82,40.00,5.18,NULL,'2026-09-18 09:46:00'),(81,6,NULL,'CONSUMIDOR_FINAL','EFECTIVO',NULL,NULL,NULL,NULL,NULL,12.40,1.87,14.27,20.00,5.73,NULL,'2026-09-18 10:14:00'),(82,6,NULL,'CONSUMIDOR_FINAL','EFECTIVO',NULL,NULL,NULL,NULL,NULL,21.30,0.54,21.84,25.00,3.16,NULL,'2026-09-19 10:53:00'),(83,5,NULL,'CONSUMIDOR_FINAL','TRANSFERENCIA',NULL,NULL,NULL,NULL,NULL,10.70,0.00,10.70,NULL,NULL,'TRX-20260919-002','2026-09-19 11:21:00'),(84,5,NULL,'CONSUMIDOR_FINAL','TRANSFERENCIA',NULL,NULL,NULL,NULL,NULL,7.00,0.60,7.60,NULL,NULL,'TRX-20260920-001','2026-09-20 11:15:00'),(85,6,2,'FACTURA','EFECTIVO','Luis Fernando Grefa Alvarado','luis.grefa@example.com','0985274163','Calle Napo y Quito, barrio 30 de Abril, El Coca','2202389413',27.11,0.00,27.11,30.00,2.89,NULL,'2026-09-20 12:28:00'),(86,5,NULL,'CONSUMIDOR_FINAL','EFECTIVO',NULL,NULL,NULL,NULL,NULL,18.00,2.70,20.70,25.00,4.30,NULL,'2026-09-20 13:41:00'),(87,6,2,'FACTURA','EFECTIVO','Luis Fernando Grefa Alvarado','luis.grefa@example.com','0985274163','Calle Napo y Quito, barrio 30 de Abril, El Coca','2202389413',12.40,1.87,14.27,20.00,5.73,NULL,'2026-09-21 12:22:00'),(88,5,NULL,'CONSUMIDOR_FINAL','EFECTIVO',NULL,NULL,NULL,NULL,NULL,27.20,0.60,27.80,30.00,2.20,NULL,'2026-09-21 13:35:00'),(89,5,NULL,'CONSUMIDOR_FINAL','EFECTIVO',NULL,NULL,NULL,NULL,NULL,10.70,0.00,10.70,15.00,4.30,NULL,'2026-09-22 13:29:00'),(90,6,NULL,'CONSUMIDOR_FINAL','EFECTIVO',NULL,NULL,NULL,NULL,NULL,12.90,1.26,14.16,20.00,5.84,NULL,'2026-09-22 14:42:00'),(91,6,NULL,'CONSUMIDOR_FINAL','EFECTIVO',NULL,NULL,NULL,NULL,NULL,27.11,0.00,27.11,30.00,2.89,NULL,'2026-09-23 14:36:00'),(92,5,5,'FACTURA','TRANSFERENCIA','Daniela Estefania Tanguila Gualinga','daniela.tanguila@example.com','0957362148','Av. Camilo de Torrano y Rio Payamino, barrio Flor de Oriente, El Coca','2205718246',18.00,2.70,20.70,NULL,NULL,'TRX-20260923-002','2026-09-23 15:49:00'),(93,5,5,'FACTURA','TRANSFERENCIA','Daniela Estefania Tanguila Gualinga','daniela.tanguila@example.com','0957362148','Av. Camilo de Torrano y Rio Payamino, barrio Flor de Oriente, El Coca','2205718246',27.20,0.60,27.80,NULL,NULL,'TRX-20260924-001','2026-09-24 15:43:00'),(94,6,NULL,'CONSUMIDOR_FINAL','EFECTIVO',NULL,NULL,NULL,NULL,NULL,9.90,0.00,9.90,15.00,5.10,NULL,'2026-09-24 16:11:00'),(95,6,NULL,'CONSUMIDOR_FINAL','EFECTIVO',NULL,NULL,NULL,NULL,NULL,12.90,1.26,14.16,20.00,5.84,NULL,'2026-09-25 16:50:00'),(96,5,NULL,'CONSUMIDOR_FINAL','EFECTIVO',NULL,NULL,NULL,NULL,NULL,34.82,0.00,34.82,40.00,5.18,NULL,'2026-09-25 17:18:00'),(97,6,9,'FACTURA','EFECTIVO','Erick Diaz','erick@gmail.com','0988776655','Coca','2250040850',12.40,1.87,14.27,20.00,5.73,NULL,'2026-09-25 09:31:00'),(98,5,NULL,'CONSUMIDOR_FINAL','EFECTIVO',NULL,NULL,NULL,NULL,NULL,18.00,2.70,20.70,25.00,4.30,NULL,'2026-09-26 17:12:00'),(99,6,9,'FACTURA','EFECTIVO','Erick Diaz','erick@gmail.com','0988776655','Coca','2250040850',21.30,0.54,21.84,25.00,3.16,NULL,'2026-09-26 09:25:00'),(100,6,9,'FACTURA','EFECTIVO','Erick Diaz','erick@gmail.com','0988776655','Coca','2250040850',9.90,0.00,9.90,15.00,5.10,NULL,'2026-09-27 09:19:00'),(101,5,NULL,'CONSUMIDOR_FINAL','TRANSFERENCIA',NULL,NULL,NULL,NULL,NULL,7.00,0.60,7.60,NULL,NULL,'TRX-20260927-002','2026-09-27 10:32:00'),(102,5,NULL,'CONSUMIDOR_FINAL','TRANSFERENCIA',NULL,NULL,NULL,NULL,NULL,34.82,0.00,34.82,NULL,NULL,'TRX-20260928-001','2026-09-28 10:26:00'),(103,6,NULL,'CONSUMIDOR_FINAL','EFECTIVO',NULL,NULL,NULL,NULL,NULL,12.40,1.87,14.27,20.00,5.73,NULL,'2026-09-28 11:39:00'),(104,6,NULL,'CONSUMIDOR_FINAL','EFECTIVO',NULL,NULL,NULL,NULL,NULL,21.30,0.54,21.84,25.00,3.16,NULL,'2026-09-29 11:33:00'),(105,5,12,'FACTURA','EFECTIVO','Andrea Paredes','andrea.paredes@example.com','0997364821','Av. 9 de Octubre y Quito, El Coca','2200158479',10.70,0.00,10.70,15.00,4.30,NULL,'2026-09-29 12:46:00'),(106,5,12,'FACTURA','EFECTIVO','Andrea Paredes','andrea.paredes@example.com','0997364821','Av. 9 de Octubre y Quito, El Coca','2200158479',7.00,0.60,7.60,10.00,2.40,NULL,'2026-09-30 12:40:00'),(107,6,NULL,'CONSUMIDOR_FINAL','EFECTIVO',NULL,NULL,NULL,NULL,NULL,27.11,0.00,27.11,30.00,2.89,NULL,'2026-09-30 13:53:00'),(108,5,NULL,'CONSUMIDOR_FINAL','TRANSFERENCIA',NULL,NULL,NULL,NULL,NULL,18.00,2.70,20.70,NULL,NULL,'TRX-20260930-003','2026-09-30 14:21:00');
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_purchase_items`
--

LOCK TABLES `stock_purchase_items` WRITE;
/*!40000 ALTER TABLE `stock_purchase_items` DISABLE KEYS */;
INSERT INTO `stock_purchase_items` VALUES (15,1,9,35,0.28),(16,1,8,14,1.85),(17,1,10,14,0.78),(18,2,5,7,10.20),(19,2,11,18,0.64),(20,2,12,10,1.25),(21,2,13,5,2.55),(22,3,14,12,3.55),(23,3,15,10,1.55),(24,3,16,15,1.05),(25,3,19,14,0.68),(26,4,8,18,1.85),(27,4,9,45,0.28),(28,4,18,24,0.30);
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_purchases`
--

LOCK TABLES `stock_purchases` WRITE;
/*!40000 ALTER TABLE `stock_purchases` DISABLE KEYS */;
INSERT INTO `stock_purchases` VALUES (1,5,4,46.62,'RECIBIDO','2026-09-02 13:30:00','2026-09-02 10:00:00','2026-09-03 15:20:00',NULL,NULL,NULL,NULL,NULL),(2,6,5,108.17,'RECIBIDO','2026-09-09 13:30:00','2026-09-09 10:00:00','2026-09-10 15:20:00',NULL,NULL,NULL,NULL,NULL),(3,5,6,83.37,'RECIBIDO','2026-09-16 13:30:00','2026-09-16 10:00:00','2026-09-17 15:20:00',NULL,NULL,NULL,NULL,NULL),(4,6,2,53.10,'RECIBIDO','2026-09-23 13:30:00','2026-09-23 10:00:00','2026-09-24 15:20:00',NULL,NULL,NULL,NULL,NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (2,'Coca-Cola Company','Departamento de Ventas','contacto@coca-cola.com','1790016919001','(02) 397-2600','Quito, Pichincha, Ecuador','2026-06-24 05:21:31','2026-06-24 05:21:31'),(3,'Pronaca','Departamento de venta','pronaca@gmail.com','4566123756','0988776655','Av. 9 de octubre y Miguel Gamboa','2026-06-24 22:00:52','2026-06-24 22:01:17'),(4,'Distribuidora Amazonica Paredes','Andrea Paredes','pedidos@distribuidoraparedes.ec','2200158479001','099 482 7316','Av. 9 de Octubre y Quito, Puerto Francisco de Orellana','2026-10-06 00:17:33','2026-10-06 00:17:33'),(5,'Comercializadora Andina del Oriente','Luis Cardenas','ventas@andinaoriente.ec','1793192847001','098 615 2047','Av. Alejandro Labaka y Rio Napo, Puerto Francisco de Orellana','2026-10-06 00:17:33','2026-10-06 00:17:33'),(6,'Alimentos Orellana Cia. Ltda.','Mariela Zambrano','distribucion@alimentosorellana.ec','2200215638001','06 288 4175','Calle Amazonas y Eugenio Espejo, Puerto Francisco de Orellana','2026-10-06 00:17:33','2026-10-06 00:17:33');
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
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-27  0:11:28
