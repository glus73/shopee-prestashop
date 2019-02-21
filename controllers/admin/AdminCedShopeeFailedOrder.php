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

include_once  _PS_MODULE_DIR_.'cedshopee/classes/CedShopeeOrder.php';
include_once  _PS_MODULE_DIR_.'cedshopee/classes/CedShopeeLibrary.php';

class AdminCedShopeeFailedOrderController extends ModuleAdminController
{
    public function __construct()
    {
        $this->db = Db::getInstance();
        $this->bootstrap  = true;
        $this->table = 'cedshopee_order_error';
        $this->identifier = 'id';
        $this->list_no_link = true;
        $this->addRowAction('cancel');

        $this->fields_list = array(
            'id'       => array(
                'title' => 'ID',
                'type'  => 'text',
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ),
            'merchant_sku'     => array(
                'title' => 'SKU',
                'type'  => 'text',
            ),
            'shopee_order_id' => array(
                'title' => 'Shopee Order Id',
                'type'  => 'text',
            ),
            'reason' => array(
                'title' => 'Reason',
                'type'  => 'text',
            ),
        );
        $this->bulk_actions = array(
            'cancel' => array('text' => 'Cancel Order', 'icon' => 'icon-power-off'),
        );
        $CedShopeeLibrary = new CedShopeeLibrary;
        $CedShopeeOrder = new CedShopeeOrder;
        if (Tools::getIsset('cancelorder') && Tools::getValue('cancelorder')) {
            $id = Tools::getValue('cancelorder');
            if ($id) {
                $shopeeOrderId = $this->db->getValue(
                    'SELECT `shopee_order_id` FROM `'._DB_PREFIX_.'cedshopee_order_error` 
                    WHERE `id`="'.(int)($id).'"'
                );
                $status = $CedShopeeLibrary->isEnabled();
                if ($status) {
                    $params = array(
                        'id' => $id,
                        'order_line' => 1,
                        'url' => 'v3/orders'
                    );
                    $response = $CedShopeeOrder->cancelOrder($params);
                    if (isset($response['success'])&& $response['success'] == true) {
                        $this->confirmations[] = isset($response['message']) ?
                            $response['message']: "Order ".$shopeeOrderId." cancelled successfully";
                    } else {
                        $this->errors[] = isset($response['message']) ?
                            $response['message']: "Order ".$shopeeOrderId." can not be cancelled";
                    }
                }
            } else {
                $this->errors[] = Tools::displayError('Please Select Order');
            }
        }
        parent::__construct();
    }

    public function postProcess()
    {
        if (Tools::getIsset('delete_failed_orders') && Tools::getValue('delete_failed_orders')) {
            $db = Db::getInstance();
            $sql = "TRUNCATE TABLE `"._DB_PREFIX_."cedshopee_order_error`";
            $res = $db->execute($sql);
            if ($res) {
                $this->confirmations[] = "Failed Orders Deleted Successfully";
            } else {
                $this->errors[] = "Failed To Delete";
            }
        }
        return parent::postProcess();
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['delete_failed_orders'] = array(
                'href' => self::$currentIndex . '&delete_failed_orders=1&token=' . $this->token,
                'desc' => 'Delete All',
                'icon' => 'process-icon-eraser'
            );
        }
        parent::initPageHeaderToolbar();
    }

    public function processBulkCancel()
    {
        $CedShopeeLibrary = new CedShopeeLibrary;
        $CedShopeeOrder = new CedShopeeOrder;
        $ids = $this->boxes;
        if (Tools::getIsset($ids) && count($ids)) {
            foreach ($ids as $id) {
                if ($id) {
                    $shopeeOrderId = $this->db->getValue(
                        'SELECT `shopee_order_id` FROM `'._DB_PREFIX_.'cedshopee_order_error` 
                        WHERE `id`="'.(int)($id).'"'
                    );
                    $status = $CedShopeeLibrary->isEnabled();
                    if ($status) {
                        $params = array(
                            'id' => $id,
                            'order_line' => '1',
                            'url' => 'v3/orders'
                        );
                        $response = $CedShopeeOrder->cancelOrder($params);
                        if (isset($response['success'])&& $response['success'] == true) {
                            $this->confirmations[] = isset($response['message']) ?
                                $response['message']: "Order ".$shopeeOrderId." cancelled successfully";
                        } else {
                            $this->errors[] = isset($response['message']) ?
                                $response['message']: "Order ".$shopeeOrderId." can not be cancelled";
                        }
                    }
                }
            }
        } else {
            $this->errors[] = Tools::displayError('Please Select Order');
        }
    }

    public function displayCancelLink($token = null, $id = null, $name = null)
    {
        if ($token && $name) {
            $tpl = $this->createTemplate('helpers/list/list_action_view.tpl');
        } else {
            $tpl = $this->createTemplate('helpers/list/list_action_view.tpl');
        }
        if (!array_key_exists('Cancel', self::$cache_lang)) {
            self::$cache_lang['Cancel'] = $this->l('Cancel', 'Helper');
        }

        $tpl->assign(array(
            'href' => Context::getContext()->link->getAdminLink(
                'AdminCedShopeeFailedOrder'
            ).'&cancelorder='.$id.'&id='.$id,
            'action' => self::$cache_lang['Cancel'],
            'id' => $id
        ));

        return $tpl->fetch();
    }
}
