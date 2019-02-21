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
 * @package   Cmxwalmart
 */

require_once _PS_MODULE_DIR_ . 'cedshopee/classes/CedShopeeLibrary.php';
require_once _PS_MODULE_DIR_ . 'cedshopee/classes/CedShopeeProduct.php';

class AdminCedShopeeUpdateStatusController extends ModuleAdminController
{
	public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function initContent()
    {
        parent::initContent();

        $db = Db::getInstance();
        $productIds = array();
        $sql = "SELECT `product_id` FROM `"._DB_PREFIX_."cedshopee_profile_products`";
        $result = $db->executeS($sql);
        if (isset($result) && is_array($result) && !empty($result)) {
            foreach ($result as $res) {
                $productIds[] = $res['product_id'];
            }
        }
        $this->context->smarty->assign(array(
            'upload_array' => addslashes(json_encode($productIds))
        ));
        $link = new LinkCore();
        $controllerUrl = $link->getAdminLink('AdminCedShopeeUpdateStatus');
        $token = $this->token;
        $this->context->smarty->assign(array('controllerUrl' => $controllerUrl));
        $this->context->smarty->assign(array('token' => $token));
        $content = $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'cedshopee/views/templates/admin/product/fetch_status.tpl'
        );
        $this->context->smarty->assign(array(
            'content' => $this->content . $content
        ));
    }

    public function ajaxProcessUpdateStatus()
    {
        die('ok');
        $CedShopeeLibrary = new CedShopeeLibrary;
        try {
            $pagination_offset = Tools::getValue('pagination_offset');
            $pagination_offset = isset($pagination_offset) ? $pagination_offset : '0';
            $pagination_entries_per_page = Tools::getValue('pagination_entries_per_page');
            $pagination_entries_per_page = isset($pagination_entries_per_page) ? $pagination_entries_per_page : '100';
            $response = $CedShopeeLibrary->postRequest('items/get', array('pagination_offset' => (int)$pagination_offset, 'pagination_entries_per_page' => (int)$pagination_entries_per_page));
            if (!isset($response['error']) && isset($response['items']) && !empty($response['items'])) {
                foreach ($response['items'] as $items) {
                    $db->update(
                        'cedshopee_uploaded_products',
                        array(
                            'shopee_status' => pSQL($items['status'])
                            ),
                        'shopee_item_id='. (int) $items["item_id"]
                        );
                    // $sql = "UPDATE`" . DB_PREFIX . "cedshopee_uploaded_products` SET shopee_status='" . $items['status'] . "' where `shopee_item_id`='" . $items['item_id'] . "'";
                    // $this->db->query($sql);
                }
                if(isset($response['more']) && $response['more']) {
                    die(json_encode(array('success' => true, 'pagination_offset' => (int)$pagination_entries_per_page, 'pagination_entries_per_page' =>(int) $pagination_entries_per_page )));
                } else {
                    die(json_encode(array('success' => true, 'message' => 'Status Updated Successfully.')));
                }
            } else {
                if (isset($response['msg'])) {
                    die(json_encode(array('success' => false, 'message' => $response['msg'])));
                } else if(isset($response['error'])){
                    die(json_encode(array('success' => false, 'message' => $response['error'])));
                } else {
                    die(json_encode(array('success' => false, 'message' => ' No Response Found in store.')));
                }
            }
        } catch(\Exception $e) {
            $CedShopeeLibrary->log(
                'AdminCedShopeeUpdateStatusController::fetchStatus',
                'Exception',
                $e->getMessage(),
                $e->getMessage(),
                true
            );
            die(json_encode(array(
                'status' => true,
                'message' => $e->getMessage()
            )));
        }
    }
}