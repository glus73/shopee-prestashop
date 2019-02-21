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
        $updated = 0;
        $fail = 0;

        foreach ($product_ids as $product_id) {
            $result = $CedShopeeProduct->updateInventory($product_id, array());
            if (isset($result['item'])) {
                $updated++;
            } elseif (isset($result['error'])) {
                $fail++;
            }
        }
            
        if ($updated) {
            if ($fail) {
                die(json_encode(array(
                    'status' => true,
                    'message' => $updated . ' Product(s) Updated and '.$fail . 'are failed to update '
                )));
            } else {
                die(json_encode(array(
                    'status' => true,
                    'message' => $updated . ' Product(s) Updated'
                )));
            }
        } else {
            die(json_encode(array(
                    'status' => false,
                    'message' => 'Unable to update data.'
                )));
        }
    } else {
        die(json_encode(array(
                'status' => false,
                'message' => 'Please Select Product to Update Inventory'
            )));
    }
} catch (\Exception $e) {
    $CedShopeeLibrary->log(
        'Cron updateInventory',
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
