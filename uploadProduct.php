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
 * @package   CedShopee
 */

include(dirname(__FILE__).'/../../config/config.inc.php');
include_once _PS_MODULE_DIR_ . 'cedshopee/classes/CedShopeeLibrary.php';
include_once(_PS_MODULE_DIR_.'cedshopee/classes/CedShopeeProduct.php');
/*include_once(_PS_MODULE_DIR_.'cedwish/classes/CedTraderaFeeds.php');*/


if (!Tools::isSubmit('secure_key')
 || Tools::getValue('secure_key') != Configuration::get('CEDSHOPEE_CRON_SECURE_KEY')) {
    die('Secure key not matched');
}

try {
        $CedShopeeLibrary = new CedShopeeLibrary;
        $CedShopeeProduct = new CedShopeeProduct;

        $db = Db::getInstance();

        $product_ids = array();

        $res = $db->executeS("SELECT `product_id` FROM `"._DB_PREFIX_."cedshopee_profile_products`");
    if (isset($res) && !empty($res) && is_array($res)) {
        foreach ($res as $id) {
            $product_ids[] = $id['product_id'];
        }
    }
    if (!empty($product_ids)) {
        $product_ids = array_unique($product_ids);
    }

    if (is_array($product_ids) && count($product_ids)) {
        $errors = array();
        $successes = array();
        $response = $CedShopeeProduct->uploadProducts($product_ids);
                    
        if (isset($response) && is_array($response)) {
            if (isset($response['success']) && $response['success'] == true) {
                $successes[] = $response['message'].'<br>';
            } else {
                $errors[] = $response['message'];
            }
        }
        die(json_encode(
            array(
               'status' => true,
               'response' => array(
                   'success' => $successes,
                   'errors' => $errors,
               )
            )
        ));
    } else {
        die(json_encode(array(
                'status' => false,
                'message' => 'Please Select Product to Upload Product'
            )));
    }
} catch (\Exception $e) {
    $CedShopeeLibrary->log(
        'Cron uploadProduct',
        'Exception',
        $e->getMessage(),
        $e->getMessage(),
        true
    );
    die(json_encode(array(
        'status' => false,
        'message' => $e->getMessage()
    )));
}
