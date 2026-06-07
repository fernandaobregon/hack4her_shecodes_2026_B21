-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 07, 2026 at 05:50 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `order_rescue`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `audit_id` bigint(20) NOT NULL,
  `user_id` bigint(20) DEFAULT NULL,
  `entity_name` varchar(100) DEFAULT NULL,
  `entity_id` varchar(80) DEFAULT NULL,
  `action` varchar(80) DEFAULT NULL,
  `old_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_value`)),
  `new_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_value`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `business_units`
--

CREATE TABLE `business_units` (
  `business_unit_id` int(11) NOT NULL,
  `business_unit_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cedis`
--

CREATE TABLE `cedis` (
  `cedis_code` varchar(30) NOT NULL,
  `country` varchar(80) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` varchar(40) NOT NULL,
  `country` varchar(80) DEFAULT NULL,
  `customer_segment` varchar(80) DEFAULT 'SIN_SEGMENTO',
  `customer_type` varchar(80) DEFAULT 'B2B',
  `zone` varchar(100) DEFAULT NULL,
  `vip_score` decimal(5,2) DEFAULT 0.00,
  `average_ticket` decimal(12,2) DEFAULT NULL,
  `purchase_frequency` varchar(50) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_preferences`
--

CREATE TABLE `customer_preferences` (
  `customer_id` varchar(40) NOT NULL,
  `default_action` enum('SUSTITUIR_SIMILAR','NO_SUSTITUIR','CONTACTARME','ELEGIR_MANUALMENTE') NOT NULL DEFAULT 'ELEGIR_MANUALMENTE',
  `allow_automatic_substitution` tinyint(1) DEFAULT 0,
  `max_price_difference` decimal(6,2) DEFAULT 0.00,
  `preferred_contact_channel` enum('APP','SMS','WHATSAPP','EMAIL') DEFAULT 'APP',
  `response_deadline_time` time DEFAULT '09:00:00',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_responses`
--

CREATE TABLE `customer_responses` (
  `response_id` bigint(20) NOT NULL,
  `alert_id` bigint(20) NOT NULL,
  `customer_id` varchar(40) NOT NULL,
  `action` enum('ACEPTA_SUGERENCIA','ELIGE_OTRO','ELIMINA_PRODUCTO','SOLICITA_CREDITO','SIN_RESPUESTA') NOT NULL,
  `selected_product_id` bigint(20) DEFAULT NULL,
  `response_channel` enum('APP','SMS','WHATSAPP','EMAIL') DEFAULT 'APP',
  `responded_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `data_quality_issues`
--

CREATE TABLE `data_quality_issues` (
  `issue_id` bigint(20) NOT NULL,
  `issue_type` varchar(100) DEFAULT NULL,
  `entity_name` varchar(100) DEFAULT NULL,
  `entity_id` varchar(80) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `severity` enum('BAJA','MEDIA','ALTA','CRITICA') DEFAULT 'MEDIA',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `delivery_routes`
--

CREATE TABLE `delivery_routes` (
  `route_id` varchar(30) NOT NULL,
  `route_name` varchar(100) NOT NULL,
  `driver_name` varchar(100) DEFAULT NULL,
  `vehicle_info` varchar(150) DEFAULT NULL,
  `cedis_code` varchar(30) NOT NULL,
  `progress_percent` decimal(5,2) DEFAULT 0.00,
  `route_status` enum('PENDIENTE','EN_PREPARACION','RETENIDA','LISTA_DESPACHO','EN_RUTA','CERRADA') DEFAULT 'PENDIENTE',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_cuts`
--

CREATE TABLE `inventory_cuts` (
  `inventory_cut_id` bigint(20) NOT NULL,
  `cedis_code` varchar(30) NOT NULL,
  `cut_datetime` datetime NOT NULL,
  `source_system` varchar(100) DEFAULT 'SAP / CEDIS',
  `uploaded_by` bigint(20) DEFAULT NULL,
  `status` enum('PENDIENTE','VALIDADO','ERROR') DEFAULT 'PENDIENTE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_cut_lines`
--

CREATE TABLE `inventory_cut_lines` (
  `inventory_cut_id` bigint(20) NOT NULL,
  `product_id` bigint(20) NOT NULL,
  `stock_available` int(11) NOT NULL DEFAULT 0,
  `stock_committed` int(11) NOT NULL DEFAULT 0,
  `stock_reserved` int(11) NOT NULL DEFAULT 0,
  `deficit_estimated` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_validations`
--

CREATE TABLE `inventory_validations` (
  `validation_id` bigint(20) NOT NULL,
  `product_id` bigint(20) NOT NULL,
  `cedis_code` varchar(30) NOT NULL,
  `inventory_cut_id` bigint(20) DEFAULT NULL,
  `sap_stock` int(11) NOT NULL DEFAULT 0,
  `physical_stock` int(11) NOT NULL DEFAULT 0,
  `difference_stock` int(11) NOT NULL DEFAULT 0,
  `validation_status` enum('CUADRA','DIFERENCIA','ERROR_CAPTURA') DEFAULT 'DIFERENCIA',
  `validated_by` bigint(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `validated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ml_model_versions`
--

CREATE TABLE `ml_model_versions` (
  `model_id` bigint(20) NOT NULL,
  `model_name` varchar(100) DEFAULT NULL,
  `version` varchar(50) DEFAULT NULL,
  `trained_at` datetime DEFAULT NULL,
  `accuracy` decimal(5,2) DEFAULT NULL,
  `precision_score` decimal(5,2) DEFAULT NULL,
  `recall_score` decimal(5,2) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ml_predictions`
--

CREATE TABLE `ml_predictions` (
  `prediction_id` bigint(20) NOT NULL,
  `model_id` bigint(20) NOT NULL,
  `order_line_id` bigint(20) NOT NULL,
  `predicted_risk` decimal(5,2) NOT NULL,
  `predicted_cause_id` int(11) DEFAULT 8,
  `predicted_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_log`
--

CREATE TABLE `notification_log` (
  `notification_id` bigint(20) NOT NULL,
  `alert_id` bigint(20) NOT NULL,
  `customer_id` varchar(40) DEFAULT NULL,
  `channel` enum('APP','SMS','WHATSAPP','EMAIL','KAM') DEFAULT 'APP',
  `message` text NOT NULL,
  `status` enum('PENDIENTE','ENVIADA','FALLIDA') DEFAULT 'PENDIENTE',
  `sent_by` bigint(20) DEFAULT NULL,
  `sent_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_pk` bigint(20) NOT NULL,
  `order_key` char(64) NOT NULL,
  `order_external_id` varchar(40) NOT NULL,
  `customer_id` varchar(40) DEFAULT NULL,
  `business_unit_id` int(11) DEFAULT NULL,
  `cedis_code` varchar(30) DEFAULT NULL,
  `country` varchar(80) DEFAULT NULL,
  `order_datetime` datetime DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `order_datetime_raw` varchar(50) DEFAULT NULL,
  `delivery_datetime_raw` varchar(50) DEFAULT NULL,
  `final_status` varchar(80) DEFAULT NULL,
  `order_value` int(11) DEFAULT NULL,
  `subtotal` decimal(12,2) DEFAULT NULL,
  `total` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_lines`
--

CREATE TABLE `order_lines` (
  `order_line_id` bigint(20) NOT NULL,
  `order_external_id` varchar(40) NOT NULL,
  `order_pk` bigint(20) DEFAULT NULL,
  `current_product_id` bigint(20) DEFAULT NULL,
  `current_product_hash` varchar(40) DEFAULT NULL,
  `current_product_name` varchar(255) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `line_status` varchar(80) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `permission_key` varchar(100) NOT NULL,
  `permission_label` varchar(150) NOT NULL,
  `module` varchar(60) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` bigint(20) NOT NULL,
  `product_key` char(64) NOT NULL,
  `product_hash` varchar(40) DEFAULT NULL,
  `sku_code` varchar(40) DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `normalized_name` varchar(255) NOT NULL,
  `business_unit_id` int(11) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `presentation` varchar(100) DEFAULT NULL,
  `unit_size` varchar(100) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `risk_alerts`
--

CREATE TABLE `risk_alerts` (
  `alert_id` bigint(20) NOT NULL,
  `order_line_id` bigint(20) NOT NULL,
  `inventory_cut_id` bigint(20) DEFAULT NULL,
  `risk_score` decimal(5,2) NOT NULL,
  `risk_level` enum('BAJO','MEDIO','CRITICO') NOT NULL,
  `probable_cause_id` int(11) DEFAULT 8,
  `alert_status` enum('PENDIENTE','NOTIFICADO_CLIENTE','RESPONDIDO','RESUELTO','VENCIDO') DEFAULT 'PENDIENTE',
  `deadline_response` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_key` varchar(100) NOT NULL,
  `is_allowed` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `route_orders`
--

CREATE TABLE `route_orders` (
  `route_id` varchar(30) NOT NULL,
  `order_pk` bigint(20) NOT NULL,
  `assigned_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stg_orders`
--

CREATE TABLE `stg_orders` (
  `id_pedido` varchar(40) DEFAULT NULL,
  `customer_id` varchar(40) DEFAULT NULL,
  `pais` varchar(80) DEFAULT NULL,
  `id_businessunit` varchar(20) DEFAULT NULL,
  `business_unit` varchar(100) DEFAULT NULL,
  `cedis` varchar(30) DEFAULT NULL,
  `fecha_pedido` varchar(50) DEFAULT NULL,
  `fecha_entrega` varchar(50) DEFAULT NULL,
  `status_final` varchar(80) DEFAULT NULL,
  `valor_pedido` varchar(30) DEFAULT NULL,
  `SubTotal` varchar(40) DEFAULT NULL,
  `Total` varchar(40) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stg_order_details`
--

CREATE TABLE `stg_order_details` (
  `id_linea` varchar(40) DEFAULT NULL,
  `id_pedido` varchar(40) DEFAULT NULL,
  `sku_solicitado` varchar(40) DEFAULT NULL,
  `nombre_sku_solicitado` varchar(255) DEFAULT NULL,
  `Quantity` varchar(30) DEFAULT NULL,
  `Status` varchar(80) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stg_resultados`
--

CREATE TABLE `stg_resultados` (
  `id_businessunit` varchar(20) DEFAULT NULL,
  `id_linea` varchar(40) DEFAULT NULL,
  `id_pedido` varchar(40) DEFAULT NULL,
  `sku_solicitado` varchar(40) DEFAULT NULL,
  `sku_solicitado_hash` varchar(40) DEFAULT NULL,
  `nombre_sku_solicitado` varchar(255) DEFAULT NULL,
  `sku_solicitado_cambio` varchar(40) DEFAULT NULL,
  `sku_solicitado_cambio_hash` varchar(40) DEFAULT NULL,
  `nombre_sku_solicitado_cambio` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `substitution_causes`
--

CREATE TABLE `substitution_causes` (
  `cause_id` int(11) NOT NULL,
  `cause_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `substitution_events`
--

CREATE TABLE `substitution_events` (
  `substitution_id` bigint(20) NOT NULL,
  `order_line_id` bigint(20) NOT NULL,
  `order_external_id` varchar(40) NOT NULL,
  `business_unit_id` int(11) DEFAULT NULL,
  `original_sku_code` varchar(40) DEFAULT NULL,
  `original_product_id` bigint(20) DEFAULT NULL,
  `original_product_hash` varchar(40) DEFAULT NULL,
  `original_product_name` varchar(255) DEFAULT NULL,
  `replacement_sku_code` varchar(40) DEFAULT NULL,
  `replacement_product_id` bigint(20) DEFAULT NULL,
  `replacement_product_hash` varchar(40) DEFAULT NULL,
  `replacement_product_name` varchar(255) DEFAULT NULL,
  `cause_id` int(11) DEFAULT 8,
  `accepted_by_customer` tinyint(1) DEFAULT NULL,
  `substitution_datetime` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `substitution_recommendations`
--

CREATE TABLE `substitution_recommendations` (
  `recommendation_id` bigint(20) NOT NULL,
  `alert_id` bigint(20) NOT NULL,
  `replacement_product_id` bigint(20) NOT NULL,
  `ranking` int(11) NOT NULL,
  `predicted_acceptance` decimal(5,2) DEFAULT NULL,
  `available_stock` int(11) DEFAULT NULL,
  `recommendation_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `setting_label` varchar(150) DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` bigint(20) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_cedis_access`
--

CREATE TABLE `user_cedis_access` (
  `user_id` bigint(20) NOT NULL,
  `cedis_code` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `user_id` bigint(20) NOT NULL,
  `role_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_admin_permission_matrix`
-- (See below for the actual view)
--
CREATE TABLE `vw_admin_permission_matrix` (
`role_id` int(11)
,`role_name` varchar(80)
,`permission_key` varchar(100)
,`permission_label` varchar(150)
,`module` varchar(60)
,`is_allowed` int(4)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_alertas_dashboard_cedis`
-- (See below for the actual view)
--
CREATE TABLE `vw_alertas_dashboard_cedis` (
`alert_id` bigint(20)
,`cedis_code` varchar(30)
,`pedido` varchar(40)
,`customer_id` varchar(40)
,`order_line_id` bigint(20)
,`producto_en_riesgo` varchar(255)
,`quantity` int(11)
,`risk_score` decimal(5,2)
,`risk_level` enum('BAJO','MEDIO','CRITICO')
,`alert_status` enum('PENDIENTE','NOTIFICADO_CLIENTE','RESPONDIDO','RESUELTO','VENCIDO')
,`causa_probable` varchar(100)
,`deadline_response` datetime
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_cedis_dashboard_alerts`
-- (See below for the actual view)
--
CREATE TABLE `vw_cedis_dashboard_alerts` (
`alert_id` bigint(20)
,`order_line_id` bigint(20)
,`pedido` varchar(40)
,`order_pk` bigint(20)
,`cedis_code` varchar(30)
,`customer_id` varchar(40)
,`customer_segment` varchar(80)
,`customer_type` varchar(80)
,`producto_en_riesgo` varchar(255)
,`quantity` int(11)
,`risk_score` decimal(5,2)
,`risk_level` enum('BAJO','MEDIO','CRITICO')
,`alert_status` enum('PENDIENTE','NOTIFICADO_CLIENTE','RESPONDIDO','RESUELTO','VENCIDO')
,`causa_probable` varchar(100)
,`deadline_response` datetime
,`created_at` timestamp
,`resolved_at` datetime
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_cedis_mas_sustituciones`
-- (See below for the actual view)
--
CREATE TABLE `vw_cedis_mas_sustituciones` (
`cedis_code` varchar(30)
,`total_sustituciones` bigint(21)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_cedis_stock_critical`
-- (See below for the actual view)
--
CREATE TABLE `vw_cedis_stock_critical` (
`cedis_code` varchar(30)
,`product_name` varchar(255)
,`pedidos_en_riesgo` bigint(21)
,`alertas_criticas` decimal(22,0)
,`cantidad_comprometida` decimal(32,0)
,`deficit_estimado` decimal(33,0)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_data_quality_summary`
-- (See below for the actual view)
--
CREATE TABLE `vw_data_quality_summary` (
`issue_type` varchar(100)
,`severity` enum('BAJA','MEDIA','ALTA','CRITICA')
,`total_issues` bigint(21)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_data_validation_dashboard`
-- (See below for the actual view)
--
CREATE TABLE `vw_data_validation_dashboard` (
`validation_id` bigint(20)
,`product_name` varchar(255)
,`cedis_code` varchar(30)
,`sap_stock` int(11)
,`physical_stock` int(11)
,`difference_stock` int(11)
,`validation_status` enum('CUADRA','DIFERENCIA','ERROR_CAPTURA')
,`validated_by_name` varchar(150)
,`notes` text
,`validated_at` datetime
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_logistics_routes_summary`
-- (See below for the actual view)
--
CREATE TABLE `vw_logistics_routes_summary` (
`route_id` varchar(30)
,`route_name` varchar(100)
,`driver_name` varchar(100)
,`vehicle_info` varchar(150)
,`cedis_code` varchar(30)
,`progress_percent` decimal(5,2)
,`route_status` enum('PENDIENTE','EN_PREPARACION','RETENIDA','LISTA_DESPACHO','EN_RUTA','CERRADA')
,`total_pedidos` bigint(21)
,`pedidos_incompletos_o_riesgo` bigint(21)
,`max_risk_score` bigint(2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_manager_kpis`
-- (See below for the actual view)
--
CREATE TABLE `vw_manager_kpis` (
`tasa_sustitucion_general` decimal(26,2)
,`alertas_criticas_activas` bigint(21)
,`clientes_en_riesgo` bigint(21)
,`sustituciones_historicas` bigint(21)
,`lineas_analizadas` bigint(21)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_ml_training_dataset`
-- (See below for the actual view)
--
CREATE TABLE `vw_ml_training_dataset` (
`order_line_id` bigint(20)
,`order_external_id` varchar(40)
,`order_pk` bigint(20)
,`customer_id` varchar(40)
,`cedis_code` varchar(30)
,`business_unit_id` int(11)
,`current_product_id` bigint(20)
,`current_product_hash` varchar(40)
,`current_product_name` varchar(255)
,`quantity` int(11)
,`line_status` varchar(80)
,`order_datetime` datetime
,`delivery_date` date
,`order_datetime_raw` varchar(50)
,`delivery_datetime_raw` varchar(50)
,`customer_segment` varchar(80)
,`customer_type` varchar(80)
,`zone` varchar(100)
,`vip_score` decimal(5,2)
,`category` varchar(100)
,`brand` varchar(100)
,`presentation` varchar(100)
,`was_substituted` int(1)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_productos_mas_sustituidos`
-- (See below for the actual view)
--
CREATE TABLE `vw_productos_mas_sustituidos` (
`producto_original` varchar(255)
,`total_sustituciones` bigint(21)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_reemplazos_mas_usados`
-- (See below for the actual view)
--
CREATE TABLE `vw_reemplazos_mas_usados` (
`producto_reemplazo` varchar(255)
,`total_usos_como_reemplazo` bigint(21)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_segmentos_afectados`
-- (See below for the actual view)
--
CREATE TABLE `vw_segmentos_afectados` (
`segmento` varchar(80)
,`total_sustituciones` bigint(21)
,`clientes_afectados` bigint(21)
);

-- --------------------------------------------------------

--
-- Structure for view `vw_admin_permission_matrix`
--
DROP TABLE IF EXISTS `vw_admin_permission_matrix`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_admin_permission_matrix`  AS SELECT `r`.`role_id` AS `role_id`, `r`.`role_name` AS `role_name`, `p`.`permission_key` AS `permission_key`, `p`.`permission_label` AS `permission_label`, `p`.`module` AS `module`, coalesce(`rp`.`is_allowed`,0) AS `is_allowed` FROM ((`roles` `r` join `permissions` `p`) left join `role_permissions` `rp` on(`rp`.`role_id` = `r`.`role_id` and `rp`.`permission_key` = `p`.`permission_key`)) WHERE `r`.`role_name` <> 'CLIENTE' ORDER BY `r`.`role_id` ASC, `p`.`module` ASC, `p`.`permission_key` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `vw_alertas_dashboard_cedis`
--
DROP TABLE IF EXISTS `vw_alertas_dashboard_cedis`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_alertas_dashboard_cedis`  AS SELECT `ra`.`alert_id` AS `alert_id`, `o`.`cedis_code` AS `cedis_code`, `o`.`order_external_id` AS `pedido`, `o`.`customer_id` AS `customer_id`, `ol`.`order_line_id` AS `order_line_id`, `ol`.`current_product_name` AS `producto_en_riesgo`, `ol`.`quantity` AS `quantity`, `ra`.`risk_score` AS `risk_score`, `ra`.`risk_level` AS `risk_level`, `ra`.`alert_status` AS `alert_status`, `sc`.`cause_name` AS `causa_probable`, `ra`.`deadline_response` AS `deadline_response`, `ra`.`created_at` AS `created_at` FROM (((`risk_alerts` `ra` join `order_lines` `ol` on(`ol`.`order_line_id` = `ra`.`order_line_id`)) left join `orders` `o` on(`o`.`order_pk` = `ol`.`order_pk`)) left join `substitution_causes` `sc` on(`sc`.`cause_id` = `ra`.`probable_cause_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `vw_cedis_dashboard_alerts`
--
DROP TABLE IF EXISTS `vw_cedis_dashboard_alerts`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_cedis_dashboard_alerts`  AS SELECT `ra`.`alert_id` AS `alert_id`, `ra`.`order_line_id` AS `order_line_id`, `ol`.`order_external_id` AS `pedido`, `o`.`order_pk` AS `order_pk`, `o`.`cedis_code` AS `cedis_code`, `o`.`customer_id` AS `customer_id`, coalesce(`c`.`customer_segment`,'B2B') AS `customer_segment`, coalesce(`c`.`customer_type`,'Cliente B2B') AS `customer_type`, coalesce(`ol`.`current_product_name`,`se`.`original_product_name`,'SIN_NOMBRE') AS `producto_en_riesgo`, `ol`.`quantity` AS `quantity`, `ra`.`risk_score` AS `risk_score`, `ra`.`risk_level` AS `risk_level`, `ra`.`alert_status` AS `alert_status`, `sc`.`cause_name` AS `causa_probable`, `ra`.`deadline_response` AS `deadline_response`, `ra`.`created_at` AS `created_at`, `ra`.`resolved_at` AS `resolved_at` FROM (((((`risk_alerts` `ra` join `order_lines` `ol` on(`ol`.`order_line_id` = `ra`.`order_line_id`)) left join `orders` `o` on(`o`.`order_pk` = `ol`.`order_pk`)) left join `customers` `c` on(`c`.`customer_id` = `o`.`customer_id`)) left join `substitution_events` `se` on(`se`.`order_line_id` = `ol`.`order_line_id`)) left join `substitution_causes` `sc` on(`sc`.`cause_id` = `ra`.`probable_cause_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `vw_cedis_mas_sustituciones`
--
DROP TABLE IF EXISTS `vw_cedis_mas_sustituciones`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_cedis_mas_sustituciones`  AS SELECT coalesce(`o`.`cedis_code`,'SIN_CEDIS') AS `cedis_code`, count(`se`.`substitution_id`) AS `total_sustituciones` FROM ((`substitution_events` `se` join `order_lines` `ol` on(`ol`.`order_line_id` = `se`.`order_line_id`)) left join `orders` `o` on(`o`.`order_pk` = `ol`.`order_pk`)) GROUP BY coalesce(`o`.`cedis_code`,'SIN_CEDIS') ORDER BY count(`se`.`substitution_id`) DESC ;

-- --------------------------------------------------------

--
-- Structure for view `vw_cedis_stock_critical`
--
DROP TABLE IF EXISTS `vw_cedis_stock_critical`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_cedis_stock_critical`  AS SELECT coalesce(`v`.`cedis_code`,'SIN_CEDIS') AS `cedis_code`, `v`.`producto_en_riesgo` AS `product_name`, count(0) AS `pedidos_en_riesgo`, sum(case when `v`.`risk_level` = 'CRITICO' then 1 else 0 end) AS `alertas_criticas`, sum(coalesce(`v`.`quantity`,0)) AS `cantidad_comprometida`, -sum(case when `v`.`risk_level` = 'CRITICO' then coalesce(`v`.`quantity`,0) else 0 end) AS `deficit_estimado` FROM `vw_cedis_dashboard_alerts` AS `v` GROUP BY coalesce(`v`.`cedis_code`,'SIN_CEDIS'), `v`.`producto_en_riesgo` ORDER BY sum(case when `v`.`risk_level` = 'CRITICO' then 1 else 0 end) DESC, count(0) DESC ;

-- --------------------------------------------------------

--
-- Structure for view `vw_data_quality_summary`
--
DROP TABLE IF EXISTS `vw_data_quality_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_data_quality_summary`  AS SELECT `data_quality_issues`.`issue_type` AS `issue_type`, `data_quality_issues`.`severity` AS `severity`, count(0) AS `total_issues` FROM `data_quality_issues` GROUP BY `data_quality_issues`.`issue_type`, `data_quality_issues`.`severity` ORDER BY count(0) DESC ;

-- --------------------------------------------------------

--
-- Structure for view `vw_data_validation_dashboard`
--
DROP TABLE IF EXISTS `vw_data_validation_dashboard`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_data_validation_dashboard`  AS SELECT `iv`.`validation_id` AS `validation_id`, `p`.`product_name` AS `product_name`, `iv`.`cedis_code` AS `cedis_code`, `iv`.`sap_stock` AS `sap_stock`, `iv`.`physical_stock` AS `physical_stock`, `iv`.`difference_stock` AS `difference_stock`, `iv`.`validation_status` AS `validation_status`, `u`.`full_name` AS `validated_by_name`, `iv`.`notes` AS `notes`, `iv`.`validated_at` AS `validated_at` FROM ((`inventory_validations` `iv` join `products` `p` on(`p`.`product_id` = `iv`.`product_id`)) left join `users` `u` on(`u`.`user_id` = `iv`.`validated_by`)) ORDER BY `iv`.`validated_at` DESC ;

-- --------------------------------------------------------

--
-- Structure for view `vw_logistics_routes_summary`
--
DROP TABLE IF EXISTS `vw_logistics_routes_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_logistics_routes_summary`  AS SELECT `dr`.`route_id` AS `route_id`, `dr`.`route_name` AS `route_name`, `dr`.`driver_name` AS `driver_name`, `dr`.`vehicle_info` AS `vehicle_info`, `dr`.`cedis_code` AS `cedis_code`, `dr`.`progress_percent` AS `progress_percent`, `dr`.`route_status` AS `route_status`, count(distinct `ro`.`order_pk`) AS `total_pedidos`, count(distinct case when `ra`.`risk_level` in ('CRITICO','MEDIO') then `ra`.`alert_id` end) AS `pedidos_incompletos_o_riesgo`, max(case when `ra`.`risk_level` = 'CRITICO' then 95 when `ra`.`risk_level` = 'MEDIO' then 45 when `ra`.`risk_level` = 'BAJO' then 5 else 0 end) AS `max_risk_score` FROM (((`delivery_routes` `dr` left join `route_orders` `ro` on(`ro`.`route_id` = `dr`.`route_id`)) left join `order_lines` `ol` on(`ol`.`order_pk` = `ro`.`order_pk`)) left join `risk_alerts` `ra` on(`ra`.`order_line_id` = `ol`.`order_line_id`)) GROUP BY `dr`.`route_id`, `dr`.`route_name`, `dr`.`driver_name`, `dr`.`vehicle_info`, `dr`.`cedis_code`, `dr`.`progress_percent`, `dr`.`route_status` ;

-- --------------------------------------------------------

--
-- Structure for view `vw_manager_kpis`
--
DROP TABLE IF EXISTS `vw_manager_kpis`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_manager_kpis`  AS SELECT round((select count(0) from `substitution_events`) / nullif((select count(0) from `order_lines`),0) * 100,2) AS `tasa_sustitucion_general`, (select count(0) from `risk_alerts` where `risk_alerts`.`risk_level` = 'CRITICO' and `risk_alerts`.`alert_status` in ('PENDIENTE','NOTIFICADO_CLIENTE')) AS `alertas_criticas_activas`, (select count(distinct `vw_cedis_dashboard_alerts`.`customer_id`) from `vw_cedis_dashboard_alerts` where `vw_cedis_dashboard_alerts`.`risk_level` in ('CRITICO','MEDIO')) AS `clientes_en_riesgo`, (select count(0) from `substitution_events`) AS `sustituciones_historicas`, (select count(0) from `order_lines`) AS `lineas_analizadas` ;

-- --------------------------------------------------------

--
-- Structure for view `vw_ml_training_dataset`
--
DROP TABLE IF EXISTS `vw_ml_training_dataset`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_ml_training_dataset`  AS SELECT `ol`.`order_line_id` AS `order_line_id`, `ol`.`order_external_id` AS `order_external_id`, `o`.`order_pk` AS `order_pk`, `o`.`customer_id` AS `customer_id`, `o`.`cedis_code` AS `cedis_code`, `o`.`business_unit_id` AS `business_unit_id`, `ol`.`current_product_id` AS `current_product_id`, `ol`.`current_product_hash` AS `current_product_hash`, `ol`.`current_product_name` AS `current_product_name`, `ol`.`quantity` AS `quantity`, `ol`.`line_status` AS `line_status`, `o`.`order_datetime` AS `order_datetime`, `o`.`delivery_date` AS `delivery_date`, `o`.`order_datetime_raw` AS `order_datetime_raw`, `o`.`delivery_datetime_raw` AS `delivery_datetime_raw`, `c`.`customer_segment` AS `customer_segment`, `c`.`customer_type` AS `customer_type`, `c`.`zone` AS `zone`, `c`.`vip_score` AS `vip_score`, `p`.`category` AS `category`, `p`.`brand` AS `brand`, `p`.`presentation` AS `presentation`, CASE WHEN `se`.`substitution_id` is not null THEN 1 ELSE 0 END AS `was_substituted` FROM ((((`order_lines` `ol` left join `orders` `o` on(`o`.`order_pk` = `ol`.`order_pk`)) left join `customers` `c` on(`c`.`customer_id` = `o`.`customer_id`)) left join `products` `p` on(`p`.`product_id` = `ol`.`current_product_id`)) left join `substitution_events` `se` on(`se`.`order_line_id` = `ol`.`order_line_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `vw_productos_mas_sustituidos`
--
DROP TABLE IF EXISTS `vw_productos_mas_sustituidos`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_productos_mas_sustituidos`  AS SELECT coalesce(`se`.`original_product_name`,`p`.`product_name`,'SIN_NOMBRE') AS `producto_original`, count(0) AS `total_sustituciones` FROM (`substitution_events` `se` left join `products` `p` on(`p`.`product_id` = `se`.`original_product_id`)) GROUP BY coalesce(`se`.`original_product_name`,`p`.`product_name`,'SIN_NOMBRE') ORDER BY count(0) DESC ;

-- --------------------------------------------------------

--
-- Structure for view `vw_reemplazos_mas_usados`
--
DROP TABLE IF EXISTS `vw_reemplazos_mas_usados`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_reemplazos_mas_usados`  AS SELECT coalesce(`se`.`replacement_product_name`,`p`.`product_name`,'SIN_NOMBRE') AS `producto_reemplazo`, count(0) AS `total_usos_como_reemplazo` FROM (`substitution_events` `se` left join `products` `p` on(`p`.`product_id` = `se`.`replacement_product_id`)) GROUP BY coalesce(`se`.`replacement_product_name`,`p`.`product_name`,'SIN_NOMBRE') ORDER BY count(0) DESC ;

-- --------------------------------------------------------

--
-- Structure for view `vw_segmentos_afectados`
--
DROP TABLE IF EXISTS `vw_segmentos_afectados`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_segmentos_afectados`  AS SELECT coalesce(`c`.`customer_segment`,'SIN_SEGMENTO') AS `segmento`, count(`se`.`substitution_id`) AS `total_sustituciones`, count(distinct `o`.`customer_id`) AS `clientes_afectados` FROM (((`substitution_events` `se` join `order_lines` `ol` on(`ol`.`order_line_id` = `se`.`order_line_id`)) left join `orders` `o` on(`o`.`order_pk` = `ol`.`order_pk`)) left join `customers` `c` on(`c`.`customer_id` = `o`.`customer_id`)) GROUP BY coalesce(`c`.`customer_segment`,'SIN_SEGMENTO') ORDER BY count(`se`.`substitution_id`) DESC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`audit_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `business_units`
--
ALTER TABLE `business_units`
  ADD PRIMARY KEY (`business_unit_id`);

--
-- Indexes for table `cedis`
--
ALTER TABLE `cedis`
  ADD PRIMARY KEY (`cedis_code`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`);

--
-- Indexes for table `customer_preferences`
--
ALTER TABLE `customer_preferences`
  ADD PRIMARY KEY (`customer_id`);

--
-- Indexes for table `customer_responses`
--
ALTER TABLE `customer_responses`
  ADD PRIMARY KEY (`response_id`),
  ADD KEY `alert_id` (`alert_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `selected_product_id` (`selected_product_id`);

--
-- Indexes for table `data_quality_issues`
--
ALTER TABLE `data_quality_issues`
  ADD PRIMARY KEY (`issue_id`);

--
-- Indexes for table `delivery_routes`
--
ALTER TABLE `delivery_routes`
  ADD PRIMARY KEY (`route_id`),
  ADD KEY `cedis_code` (`cedis_code`);

--
-- Indexes for table `inventory_cuts`
--
ALTER TABLE `inventory_cuts`
  ADD PRIMARY KEY (`inventory_cut_id`),
  ADD KEY `idx_cut_cedis_date` (`cedis_code`,`cut_datetime`);

--
-- Indexes for table `inventory_cut_lines`
--
ALTER TABLE `inventory_cut_lines`
  ADD PRIMARY KEY (`inventory_cut_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `inventory_validations`
--
ALTER TABLE `inventory_validations`
  ADD PRIMARY KEY (`validation_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `cedis_code` (`cedis_code`),
  ADD KEY `inventory_cut_id` (`inventory_cut_id`),
  ADD KEY `validated_by` (`validated_by`);

--
-- Indexes for table `ml_model_versions`
--
ALTER TABLE `ml_model_versions`
  ADD PRIMARY KEY (`model_id`);

--
-- Indexes for table `ml_predictions`
--
ALTER TABLE `ml_predictions`
  ADD PRIMARY KEY (`prediction_id`),
  ADD KEY `idx_pred_order_line` (`order_line_id`),
  ADD KEY `model_id` (`model_id`),
  ADD KEY `predicted_cause_id` (`predicted_cause_id`);

--
-- Indexes for table `notification_log`
--
ALTER TABLE `notification_log`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `alert_id` (`alert_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `sent_by` (`sent_by`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_pk`),
  ADD UNIQUE KEY `order_key` (`order_key`),
  ADD KEY `idx_orders_external_id` (`order_external_id`),
  ADD KEY `idx_orders_customer` (`customer_id`),
  ADD KEY `idx_orders_cedis` (`cedis_code`),
  ADD KEY `idx_orders_delivery` (`delivery_date`),
  ADD KEY `business_unit_id` (`business_unit_id`);

--
-- Indexes for table `order_lines`
--
ALTER TABLE `order_lines`
  ADD PRIMARY KEY (`order_line_id`),
  ADD KEY `idx_ol_order_external` (`order_external_id`),
  ADD KEY `idx_ol_order_pk` (`order_pk`),
  ADD KEY `idx_ol_product` (`current_product_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`permission_key`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD UNIQUE KEY `product_key` (`product_key`),
  ADD KEY `idx_products_hash` (`product_hash`),
  ADD KEY `idx_products_name` (`normalized_name`),
  ADD KEY `business_unit_id` (`business_unit_id`);

--
-- Indexes for table `risk_alerts`
--
ALTER TABLE `risk_alerts`
  ADD PRIMARY KEY (`alert_id`),
  ADD KEY `idx_alert_line` (`order_line_id`),
  ADD KEY `idx_alert_status` (`alert_status`),
  ADD KEY `idx_alert_level` (`risk_level`),
  ADD KEY `inventory_cut_id` (`inventory_cut_id`),
  ADD KEY `probable_cause_id` (`probable_cause_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_key`),
  ADD KEY `permission_key` (`permission_key`);

--
-- Indexes for table `route_orders`
--
ALTER TABLE `route_orders`
  ADD PRIMARY KEY (`route_id`,`order_pk`),
  ADD KEY `order_pk` (`order_pk`);

--
-- Indexes for table `substitution_causes`
--
ALTER TABLE `substitution_causes`
  ADD PRIMARY KEY (`cause_id`),
  ADD UNIQUE KEY `cause_name` (`cause_name`);

--
-- Indexes for table `substitution_events`
--
ALTER TABLE `substitution_events`
  ADD PRIMARY KEY (`substitution_id`),
  ADD UNIQUE KEY `uk_substitution_line` (`order_line_id`),
  ADD KEY `idx_se_original` (`original_product_id`),
  ADD KEY `idx_se_replacement` (`replacement_product_id`),
  ADD KEY `idx_se_order_external` (`order_external_id`),
  ADD KEY `business_unit_id` (`business_unit_id`),
  ADD KEY `cause_id` (`cause_id`);

--
-- Indexes for table `substitution_recommendations`
--
ALTER TABLE `substitution_recommendations`
  ADD PRIMARY KEY (`recommendation_id`),
  ADD UNIQUE KEY `uk_alert_ranking` (`alert_id`,`ranking`),
  ADD KEY `replacement_product_id` (`replacement_product_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_cedis_access`
--
ALTER TABLE `user_cedis_access`
  ADD PRIMARY KEY (`user_id`,`cedis_code`),
  ADD KEY `cedis_code` (`cedis_code`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`user_id`,`role_id`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `audit_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_responses`
--
ALTER TABLE `customer_responses`
  MODIFY `response_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `data_quality_issues`
--
ALTER TABLE `data_quality_issues`
  MODIFY `issue_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_cuts`
--
ALTER TABLE `inventory_cuts`
  MODIFY `inventory_cut_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_validations`
--
ALTER TABLE `inventory_validations`
  MODIFY `validation_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ml_model_versions`
--
ALTER TABLE `ml_model_versions`
  MODIFY `model_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ml_predictions`
--
ALTER TABLE `ml_predictions`
  MODIFY `prediction_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_log`
--
ALTER TABLE `notification_log`
  MODIFY `notification_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_pk` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `risk_alerts`
--
ALTER TABLE `risk_alerts`
  MODIFY `alert_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `substitution_causes`
--
ALTER TABLE `substitution_causes`
  MODIFY `cause_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `substitution_events`
--
ALTER TABLE `substitution_events`
  MODIFY `substitution_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `substitution_recommendations`
--
ALTER TABLE `substitution_recommendations`
  MODIFY `recommendation_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `customer_preferences`
--
ALTER TABLE `customer_preferences`
  ADD CONSTRAINT `customer_preferences_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`);

--
-- Constraints for table `customer_responses`
--
ALTER TABLE `customer_responses`
  ADD CONSTRAINT `customer_responses_ibfk_1` FOREIGN KEY (`alert_id`) REFERENCES `risk_alerts` (`alert_id`),
  ADD CONSTRAINT `customer_responses_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`),
  ADD CONSTRAINT `customer_responses_ibfk_3` FOREIGN KEY (`selected_product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `delivery_routes`
--
ALTER TABLE `delivery_routes`
  ADD CONSTRAINT `delivery_routes_ibfk_1` FOREIGN KEY (`cedis_code`) REFERENCES `cedis` (`cedis_code`);

--
-- Constraints for table `inventory_cuts`
--
ALTER TABLE `inventory_cuts`
  ADD CONSTRAINT `inventory_cuts_ibfk_1` FOREIGN KEY (`cedis_code`) REFERENCES `cedis` (`cedis_code`);

--
-- Constraints for table `inventory_cut_lines`
--
ALTER TABLE `inventory_cut_lines`
  ADD CONSTRAINT `inventory_cut_lines_ibfk_1` FOREIGN KEY (`inventory_cut_id`) REFERENCES `inventory_cuts` (`inventory_cut_id`),
  ADD CONSTRAINT `inventory_cut_lines_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `inventory_validations`
--
ALTER TABLE `inventory_validations`
  ADD CONSTRAINT `inventory_validations_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `inventory_validations_ibfk_2` FOREIGN KEY (`cedis_code`) REFERENCES `cedis` (`cedis_code`),
  ADD CONSTRAINT `inventory_validations_ibfk_3` FOREIGN KEY (`inventory_cut_id`) REFERENCES `inventory_cuts` (`inventory_cut_id`),
  ADD CONSTRAINT `inventory_validations_ibfk_4` FOREIGN KEY (`validated_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `ml_predictions`
--
ALTER TABLE `ml_predictions`
  ADD CONSTRAINT `ml_predictions_ibfk_1` FOREIGN KEY (`model_id`) REFERENCES `ml_model_versions` (`model_id`),
  ADD CONSTRAINT `ml_predictions_ibfk_2` FOREIGN KEY (`order_line_id`) REFERENCES `order_lines` (`order_line_id`),
  ADD CONSTRAINT `ml_predictions_ibfk_3` FOREIGN KEY (`predicted_cause_id`) REFERENCES `substitution_causes` (`cause_id`);

--
-- Constraints for table `notification_log`
--
ALTER TABLE `notification_log`
  ADD CONSTRAINT `notification_log_ibfk_1` FOREIGN KEY (`alert_id`) REFERENCES `risk_alerts` (`alert_id`),
  ADD CONSTRAINT `notification_log_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`),
  ADD CONSTRAINT `notification_log_ibfk_3` FOREIGN KEY (`sent_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`business_unit_id`) REFERENCES `business_units` (`business_unit_id`),
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`cedis_code`) REFERENCES `cedis` (`cedis_code`);

--
-- Constraints for table `order_lines`
--
ALTER TABLE `order_lines`
  ADD CONSTRAINT `order_lines_ibfk_1` FOREIGN KEY (`order_pk`) REFERENCES `orders` (`order_pk`),
  ADD CONSTRAINT `order_lines_ibfk_2` FOREIGN KEY (`current_product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`business_unit_id`) REFERENCES `business_units` (`business_unit_id`);

--
-- Constraints for table `risk_alerts`
--
ALTER TABLE `risk_alerts`
  ADD CONSTRAINT `risk_alerts_ibfk_1` FOREIGN KEY (`order_line_id`) REFERENCES `order_lines` (`order_line_id`),
  ADD CONSTRAINT `risk_alerts_ibfk_2` FOREIGN KEY (`inventory_cut_id`) REFERENCES `inventory_cuts` (`inventory_cut_id`),
  ADD CONSTRAINT `risk_alerts_ibfk_3` FOREIGN KEY (`probable_cause_id`) REFERENCES `substitution_causes` (`cause_id`);

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`),
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_key`) REFERENCES `permissions` (`permission_key`);

--
-- Constraints for table `route_orders`
--
ALTER TABLE `route_orders`
  ADD CONSTRAINT `route_orders_ibfk_1` FOREIGN KEY (`route_id`) REFERENCES `delivery_routes` (`route_id`),
  ADD CONSTRAINT `route_orders_ibfk_2` FOREIGN KEY (`order_pk`) REFERENCES `orders` (`order_pk`);

--
-- Constraints for table `substitution_events`
--
ALTER TABLE `substitution_events`
  ADD CONSTRAINT `substitution_events_ibfk_1` FOREIGN KEY (`order_line_id`) REFERENCES `order_lines` (`order_line_id`),
  ADD CONSTRAINT `substitution_events_ibfk_2` FOREIGN KEY (`business_unit_id`) REFERENCES `business_units` (`business_unit_id`),
  ADD CONSTRAINT `substitution_events_ibfk_3` FOREIGN KEY (`original_product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `substitution_events_ibfk_4` FOREIGN KEY (`replacement_product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `substitution_events_ibfk_5` FOREIGN KEY (`cause_id`) REFERENCES `substitution_causes` (`cause_id`);

--
-- Constraints for table `substitution_recommendations`
--
ALTER TABLE `substitution_recommendations`
  ADD CONSTRAINT `substitution_recommendations_ibfk_1` FOREIGN KEY (`alert_id`) REFERENCES `risk_alerts` (`alert_id`),
  ADD CONSTRAINT `substitution_recommendations_ibfk_2` FOREIGN KEY (`replacement_product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `user_cedis_access`
--
ALTER TABLE `user_cedis_access`
  ADD CONSTRAINT `user_cedis_access_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `user_cedis_access_ibfk_2` FOREIGN KEY (`cedis_code`) REFERENCES `cedis` (`cedis_code`);

--
-- Constraints for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `user_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
