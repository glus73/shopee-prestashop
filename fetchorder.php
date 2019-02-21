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
include_once(_PS_MODULE_DIR_.'cedshopee/classes/CedShopeeOrder.php');
include_once(_PS_MODULE_DIR_.'cedshopee/classes/CedShopeeLibrary.php');


if (!Tools::getIsset('secure_key')
 || Tools::getValue('secure_key') != Configuration::get('CEDSHOPEE_CRON_SECURE_KEY')) {
    die('Secure key does not matched');
}

try {
    $CedShopeeOrder = new CedShopeeOrder();
    $CedShopeeLibrary = new CedShopeeLibrary();
    $res = $CedShopeeOrder->fetchOrder();
    $CedShopeeLibrary->log(
        'CronOrderFetch',
        'Info',
        'Cron For Order Fetch',
        ''
    );
    die(json_encode($res));
} catch (Exception $e) {
    $CedShopeeLibrary->log(
        'CronOrderFetch',
        'Exception',
        $e->getMessage(),
        json_encode(
            array(
                'Trace' => $e->getTraceAsString()
            )
        ),
        true
    );
    die(json_encode(
        array(
            'success' => false,
            'message' => $e->getMessage()
        )));
}
