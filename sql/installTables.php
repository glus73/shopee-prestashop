<?php
/**
 * CedCommerce
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End User License Agreement(EULA)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://cedcommerce.com/license-agreement.txt
 *
 * @author    CedCommerce Core Team <connect@cedcommerce.com>
 * @copyright Copyright CEDCOMMERCE(http://cedcommerce.com/)
 * @license   http://cedcommerce.com/license-agreement.txt
 * @category  Ced
 * @package   Cedshopee
 */

$db =  Db::getInstance();
$sql_queries = array();

$sql_queries[] = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "cedshopee_discount` (
          `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
          `discount_name` text COLLATE utf8_unicode_ci NOT NULL,
          `discount_id` int(11) NOT NULL,
          `start_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          `end_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
          `items` longtext NOT NULL,
          PRIMARY KEY (`id`)
        ) ;";

$sql_queries[] = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "cedshopee_uploaded_products` (
          `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
          `product_id` int(11) NOT NULL,
          `shopee_profile_id` int(11) NOT NULL,
          `shopee_status` text COLLATE utf8_unicode_ci NOT NULL,
          `error_message` longtext COLLATE utf8_unicode_ci NOT NULL,
          `shopee_item_id` bigint(20) NOT NULL,
          `logistics` text COLLATE utf8_unicode_ci NOT NULL,
          `wholesale` text COLLATE utf8_unicode_ci NOT NULL,
          PRIMARY KEY (`id`)
        ) ;";

$sql_queries[] = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "cedshopee_attribute` (
          `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
          `attribute_id` bigint(20) NOT NULL,
          `attribute_name` text COLLATE utf8_unicode_ci NOT NULL,
          `is_mandatory` tinyint(1) NOT NULL,
          `attribute_type` text COLLATE utf8_unicode_ci NOT NULL,
          `input_type` text COLLATE utf8_unicode_ci NOT NULL,
          `options` longtext COLLATE utf8_unicode_ci NOT NULL,
          `category_id` bigint(20) NOT NULL,
          PRIMARY KEY (`id`)
        ) ;";

$sql_queries[] = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "cedshopee_category` (
          `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
          `category_id` bigint(11) NOT NULL,
          `category_name` text COLLATE utf8_unicode_ci NOT NULL,
          `parent_id` bigint(20) NOT NULL,
          `has_children` bigint(20) NOT NULL,
          PRIMARY KEY (`id`)
        ) ;";

$sql_queries[] = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "cedshopee_logistics` (
          `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
          `logistic_id` text COLLATE utf8_unicode_ci NOT NULL,
          `logistic_name` text COLLATE utf8_unicode_ci NOT NULL,
          `has_cod` tinyint(1) NOT NULL,
          `enabled` tinyint(1) NOT NULL,
          `fee_type` text COLLATE utf8_unicode_ci NOT NULL,
          `sizes` longtext COLLATE utf8_unicode_ci NOT NULL,
          `weight_limits` longtext COLLATE utf8_unicode_ci NOT NULL,
          `item_max_dimension` longtext COLLATE utf8_unicode_ci NOT NULL,
          PRIMARY KEY (`id`)
        ) ;";

$sql_queries[] = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "cedshopee_order` (
          `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
          `order_place_date` datetime DEFAULT NULL COMMENT 'Order Place Date',
          `prestashop_order_id` int(11) DEFAULT NULL COMMENT 'Prestashop Order Id',
          `status` text COLLATE utf8_unicode_ci COMMENT 'status',
          `order_data` text COLLATE utf8_unicode_ci COMMENT 'Order Data',
          `shipment_data` text COLLATE utf8_unicode_ci COMMENT 'Shipping Data',
          `shopee_order_id` text COLLATE utf8_unicode_ci COMMENT 'Reference Order Id',
          `shipment_request_data` text COLLATE utf8_unicode_ci COMMENT 'Shipment Data send on shopee',
          `shipment_response_data` text COLLATE utf8_unicode_ci COMMENT 'Shipment Data get from shopee',
          PRIMARY KEY (`id`)
        ) ;";

$sql_queries[] = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "cedshopee_order_error` (
          `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
          `shopee_order_id` text COLLATE utf8_unicode_ci NOT NULL COMMENT 'Purchase Order Id',
          `merchant_sku` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0' COMMENT 'Reference_Number',
          `reason` text COLLATE utf8_unicode_ci NOT NULL COMMENT 'Reason',
          `order_data` text COLLATE utf8_unicode_ci NOT NULL COMMENT 'Order Data',
          PRIMARY KEY (`id`)
        ) ;";

$sql_queries[] = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "cedshopee_products` (
          `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
          `product_id` int(11) NOT NULL,
          `shopee_status` text COLLATE utf8_unicode_ci NOT NULL,
          `error_message` longtext COLLATE utf8_unicode_ci NOT NULL,
          `shopee_item_id` bigint(20) NOT NULL,
          PRIMARY KEY (`id`)
        ) ;";

$sql_queries[] = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "cedshopee_product_variations` (
          `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
          `product_id` int(11) NOT NULL,
          `name` text COLLATE utf8_unicode_ci NOT NULL,
          `variation_sku` text COLLATE utf8_unicode_ci NOT NULL,
          `status` text COLLATE utf8_unicode_ci NOT NULL,
          `is_removed` text COLLATE utf8_unicode_ci NOT NULL,
          `variation_id` int(11) NOT NULL,
          `stock` int(11) NOT NULL,
          `price` float NOT NULL,
          PRIMARY KEY (`id`)
        ) ;";

$sql_queries[] = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "cedshopee_profile` (
          `id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'ID',
          `title` text COLLATE utf8_unicode_ci NOT NULL,
          `store_category` longtext COLLATE utf8_unicode_ci NOT NULL,
          `shopee_categories` longtext COLLATE utf8_unicode_ci NOT NULL,
          `shopee_category` longtext COLLATE utf8_unicode_ci NOT NULL,
          `profile_attribute_mapping` longtext COLLATE utf8_unicode_ci NOT NULL,
          `status` int(11) NOT NULL,
          `logistics` longtext COLLATE utf8_unicode_ci NOT NULL,
          `wholesale` longtext COLLATE utf8_unicode_ci NOT NULL,
          `default_mapping` text COLLATE utf8_unicode_ci NOT NULL,
          `profile_store` text COLLATE utf8_unicode_ci NOT NULL,
          `product_manufacturer` text COLLATE utf8_unicode_ci NOT NULL,
          `profile_language` int(11) NOT NULL,
          `shopee_category_name` text COLLATE utf8_unicode_ci NOT NULL,
          PRIMARY KEY (`id`)
        ) ;";

$sql_queries[] = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "cedshopee_profile_products` (
          `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
          `product_id` int(11) NOT NULL,
          `shopee_profile_id` int(11) NOT NULL,
          `shopee_status` text COLLATE utf8_unicode_ci NOT NULL,
          `error_message` longtext COLLATE utf8_unicode_ci NOT NULL,
          `shopee_item_id` bigint(20) NOT NULL,
          PRIMARY KEY (`id`)
        ) ;";

$sql_queries[] = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "cedshopee_return` (
          `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
          `reason` text COLLATE utf8_unicode_ci NOT NULL,
          `text_reason` longtext COLLATE utf8_unicode_ci NOT NULL,
          `returnsn` text COLLATE utf8_unicode_ci NOT NULL,
          `ordersn` text COLLATE utf8_unicode_ci NOT NULL,
          `return_data` longtext COLLATE utf8_unicode_ci NOT NULL,
          `status` text COLLATE utf8_unicode_ci NOT NULL,
          `dispute_request` longtext COLLATE utf8_unicode_ci NOT NULL,
          `dispute_response` longtext COLLATE utf8_unicode_ci NOT NULL,
          PRIMARY KEY (`id`)
        ) ;";

$sql_queries[] = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "cedshopee_logs` (   
        `id` int(11) NOT NULL AUTO_INCREMENT,   
        `method` text NOT NULL,   
        `type` varchar(150) NOT NULL,
        `message` text NOT NULL,   
        `data` longtext NOT NULL,   
        `created_at` datetime default current_timestamp,   
         PRIMARY KEY (`id`) 
        );";

foreach ($sql_queries as $query) {
    if ($db->execute($query) == false) {
        return false;
    }
}
