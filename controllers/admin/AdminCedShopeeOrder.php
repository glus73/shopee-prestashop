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

class AdminCedShopeeOrderController extends ModuleAdminController
{
    public function __construct()
    {
             $this->db = Db::getInstance();
             $this->bootstrap = true;
             $this->table = 'order';
             $this->className = 'Order';
             $this->lang = false;
             $this->addRowAction('view');
             // $this->addRowAction('cancel');
             // $this->addRowAction('edit');
             $this->bulk_actions = array(
                // 'accept' => array(
                //     'text' => 'Accept Order',
                //     'icon' => 'icon-refresh'
                //     ),
                'cancel' => array(
                    'text' => 'Cancel Order',
                    'icon' => 'icon-refresh'
                    ),
            );

             $this->explicitSelect = true;
             $this->allow_export = true;
             $this->deleted = false;
             $this->context = Context::getContext();

             parent::__construct();

             $this->_select = '
                a.id_currency,
                a.id_order AS id_pdf,
                CONCAT(LEFT(c.`firstname`, 1), \'. \', c.`lastname`) AS `customer`,
                osl.`name` AS `osname`,
                os.`color`,
                IF((SELECT so.id_order FROM `'._DB_PREFIX_.'orders` so WHERE so.id_customer = a.id_customer 
                AND so.id_order < a.id_order LIMIT 1) > 0, 0, 1) as new,
                country_lang.name as cname,
                IF(a.valid, 1, 0) badge_success';

                $this->_join = '
                LEFT JOIN `'._DB_PREFIX_.'customer` c ON (c.`id_customer` = a.`id_customer`)
                JOIN `'._DB_PREFIX_.'cedshopee_order` cwo ON (cwo.`prestashop_order_id` = a.`id_order`)
                LEFT JOIN `'._DB_PREFIX_.'address` address ON address.id_address = a.id_address_delivery
                LEFT JOIN `'._DB_PREFIX_.'country` country ON address.id_country = country.id_country
                LEFT JOIN `'._DB_PREFIX_.'country_lang` country_lang ON (country.`id_country` = country_lang.`id_country` 
                AND country_lang.`id_lang` = '.(int)$this->context->language->id.')
                LEFT JOIN `'._DB_PREFIX_.'order_state` os ON (os.`id_order_state` = a.`current_state`)
                LEFT JOIN `'._DB_PREFIX_.'order_state_lang` osl ON (os.`id_order_state` = osl.`id_order_state` 
                AND osl.`id_lang` = '.(int)$this->context->language->id.')';
                $this->_orderBy = 'id_order';
                $this->_orderWay = 'DESC';
                $this->_use_found_rows = true;

                $statuses = OrderState::getOrderStates((int)$this->context->language->id);
        foreach ($statuses as $status) {
            $this->statuses_array[$status['id_order_state']] = $status['name'];
        }

                $this->fields_list = array(
                    'id_order' => array(
                        'title' => 'ID',
                        'align' => 'text-center',
                        'class' => 'fixed-width-xs'
                    ),
                    'shopee_order_id' => array(
                        'title' => 'Purchase Order ID',
                        'align' => 'text-center',
                        'class' => 'fixed-width-xs'
                    ),
                    'reference' => array(
                        'title' => 'Reference'
                    ),
                    'customer' => array(
                        'title' => 'Customer',
                        'havingFilter' => true,
                    ),
                );

                $this->fields_list = array_merge($this->fields_list, array(
                    'total_paid_tax_incl' => array(
                        'title' => $this->l('Total'),
                        'align' => 'text-right',
                        'type' => 'price',
                        'currency' => true,
                        'callback' => 'setOrderCurrency',
                        'badge_success' => true
                    ),
                    'payment' => array(
                        'title' => $this->l('Payment')
                    ),
                    'osname' => array(
                        'title' => $this->l('Status'),
                        'type' => 'select',
                        'color' => 'color',
                        'list' => $this->statuses_array,
                        'filter_key' => 'os!id_order_state',
                        'filter_type' => 'int',
                        'order_key' => 'osname'
                    ),
                    'date_add' => array(
                        'title' => $this->l('Date'),
                        'align' => 'text-right',
                        'type' => 'datetime',
                        'filter_key' => 'a!date_add'
                    )
                ));
                $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
                SELECT DISTINCT c.id_country, cl.`name`
                FROM `'._DB_PREFIX_.'orders` o
                '.Shop::addSqlAssociation('orders', 'o').'
                INNER JOIN `'._DB_PREFIX_.'address` a ON a.id_address = o.id_address_delivery
                INNER JOIN `'._DB_PREFIX_.'country` c ON a.id_country = c.id_country
                INNER JOIN `'._DB_PREFIX_.'country_lang` cl ON (c.`id_country` = cl.`id_country` 
                AND cl.`id_lang` = '.(int)$this->context->language->id.')
                ORDER BY cl.name ASC');

                $country_array = array();
        foreach ($result as $row) {
            $country_array[$row['id_country']] = $row['name'];
        }

                $part1 = array_slice($this->fields_list, 0, 3);
                $part2 = array_slice($this->fields_list, 3);
                $part1['cname'] = array(
                    'title' => $this->l('Delivery'),
                    'type' => 'select',
                    'list' => $country_array,
                    'filter_key' => 'country!id_country',
                    'filter_type' => 'int',
                    'order_key' => 'cname'
                );
                $this->fields_list = array_merge($part1, $part2);
                $this->shopLinkType = 'shop';
                $this->shopShareDatas = Shop::SHARE_ORDER;

        if (Tools::isSubmit('id_order')) {
            $order = new Order((int)Tools::getValue('id_order'));
            $this->context->cart = new Cart($order->id_cart);
            $this->context->customer = new Customer($order->id_customer);
        }
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_order'] = array(
                'href' => self::$currentIndex.'&fetchorder&token='.$this->token,
                'desc' => $this->l('Fetch Orders', null, null, false),
                'icon' => 'process-icon-new'
            );
        } elseif ($this->display == 'view') {
            $this->page_header_toolbar_btn['backtolist'] = array(
                'href' => self::$currentIndex . '&token=' . $this->token,
                'desc' => $this->l('Back To List', null, null, false),
                'icon' => 'process-icon-back'
            );
        }
        parent::initPageHeaderToolbar();
    }

    public function renderList()
    {
        if (Tools::getIsset('fetchorder')) {
            $CedShopeeOrder = new CedShopeeOrder();
            $response = $CedShopeeOrder->fetchOrder();
            if (isset($response['success']) && $response['success'] == true) {
                $this->confirmations[] = $response['message'];
            } else {
                $this->errors[] = isset($response['message']) ?
                    $response['message']: 'Failed to fetch Shopee orders';
            }
        }
        return parent::renderList();
    }

    public function renderView()
    {
        $order = $this->loadObject();
        $order_data = (array)$order;
        $id_order = 0;
        if (isset($order_data['id']) && $order_data['id']) {
            $id_order =$order_data['id'];
        }
        if ($id_order) {
            $sql = "SELECT `order_data` FROM `"._DB_PREFIX_."cedshopee_order` 
            where `prestashop_order_id` = '".(int)$id_order."'";
            $result = $this->db->ExecuteS($sql);

            if (is_array($result) && count($result) && isset($result['0']['order_data'])) {
                if (Tools::stripslashes($result['0']['order_data'])) {
                    $order_data = json_decode(Tools::stripslashes(trim($result['0']['order_data'], '"')), true);

                    if ($order_data) {
                        $recipient_address = $order_data['orders']['0']['recipient_address'];
                        if ($recipient_address) {
                            $this->context->smarty->assign(array('recipient_address'  => $recipient_address));
                        }
                        $items = $order_data['orders']['0']['items'];
                        if ($items) {
                                $this->context->smarty->assign(array('items'  => $items));
                        }
                        $shippingInfo = $recipient_address;
                        
                        $this->context->smarty->assign(array('shippingInfo'  => $shippingInfo));
                        $order_info = array();
                        foreach ($order_data as $key => $value) {
                            foreach ($value as $key1 => $val) {
                                $order_info[] = array(
                                        'order_id' => $val['ordersn'],
                                        'order_status' => $val['order_status'],
                                        'tracking_no' => $val['tracking_no'],
                                        'payment_method' => $val['payment_method'],
                                        'country' => $val['country'],
                                        'currency' => $val['currency'],
                                        'days_to_ship' => $val['days_to_ship'],
                                        'order_total' => $val['escrow_amount']
                                        );
                            }
                        }
                        if ($order_info) {
                                $this->context->smarty->assign(array('order_info'  => $order_info));
                        }
                        $tracking_no = $order_data['orders']['0']['tracking_no'];
                        $ordersn = $order_data['orders']['0']['ordersn'];
                        $this->context->smarty->assign(array(
                                'tracking_no'  => $tracking_no,
                                'ordersn' => $ordersn
                                ));
                    }
                   
                    // $this->context->smarty->assign(
                    //     'ship',
                    //     $this->context->link->getAdminLink('AdminCedShopeeOrder').
                    //     '&submitShippingNumber=true'
                    // );
                    $this->context->smarty->assign('id_order', $id_order);
                    $this->context->smarty->assign('token', $this->token);
                    $parent = $this->context->smarty->fetch(
                        _PS_MODULE_DIR_ .'cedshopee/views/templates/admin/order/form.tpl'
                    );
                    parent::renderView();
                    return $parent;
                }
            }
        }
    }

    public static function setOrderCurrency($echo, $tr)
    {
        $order = new Order($tr['id_order']);
        return Tools::displayPrice($echo, (int)$order->id_currency);
    }

   // public function processBulkAccept()
   //  {
   //      $CedShopeeLibrary = new CedShopeeLibrary;
   //      $CedShopeeOrder = new CedShopeeOrder;
   //      $order_ids = Tools::getValue('orderBox');
   //      try {
   //          if (!empty($order_ids)) {
   //              foreach ($order_ids as $order_id) {
   //                  if ($order_id) {
   //                      $shopeeOrderId = $this->db->getValue(
   //                          'SELECT `shopee_order_id` FROM `'._DB_PREFIX_.'cedshopee_order_error`
   //                          WHERE `id`="'.(int)($order_id).'"'
   //                      );
   //                      $params = array(
   //                          'id' => $shopeeOrderId
   //                          );
   //                      $result = $CedShopeeOrder->acceptOrder($params);
   //                      $CedShopeeLibrary->log(
   //                          __METHOD__,
   //                          'Info',
   //                          'Response for Bulk accept order',
   //                          json_encode($result)
   //                      );

   //                      if (isset($result['success']) && $result['success']) {
   //                          if (isset($result['response']) && $result['response']) {
   //                              if (is_array($result)) {
   //                                  return array('success' =>true, 'response' => 'Accepted Successfully!');
   //                              } else {
   //                                  return array('success' =>false, 'message' => $result['message']);
   //                              }
   //                          } else {
   //                              return array('success' =>false, 'message' => $result['message']);
   //                          }
   //                      } else {
   //                          return array('success' =>false, 'message' => $result['message']);
   //                      }
   //                  }
   //              }
   //          }
   //      } catch (\Exception $e) {
   //          $CedShopeeLibrary->log(
   //              'AdminCedShopeeOrdersController::processBulkAcknowledge',
   //              'Exception',
   //              $e->getMessage(),
   //              $e->getMessage(),
   //              true
   //          );
   //      }
   //  }

    public function processBulkCancel()
    {
        $CedShopeeLibrary = new CedShopeeLibrary;
        $CedShopeeOrder = new CedShopeeOrder;
        $ids = Tools::getValue('orderBox');
        if (!empty($ids)) {
            foreach ($ids as $id) {
                if ($id) {
                    $shopeeOrderId = $this->db->getValue(
                        'SELECT `shopee_order_id` FROM `'._DB_PREFIX_.'cedshopee_order_error` 
                        WHERE `id`="'.(int)($id).'"'
                    );
                    $params = array(
                            'id' => $shopeeOrderId,
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
        } else {
            $this->errors[] = Tools::displayError('Please Select Order');
        }
    }

    public function ajaxProcessShipOrder()
    {
        $CedShopeeOrder = new CedShopeeOrder;
        $order_id = Tools::getValue('ordersn');
        $tracking_no = Tools::getValue('tracking_number');
        $ship_data = array(
            'ordersn' => $order_id,
            'tracking_number' => $tracking_no
            );
        $response = $CedShopeeOrder->shipOrder($ship_data);
        if (isset($response) && !empty($response)) {
            die(json_encode($response));
        } else {
            $response = array('success'=> false, 'message'=> 'No Response from Shopee');
            die(json_encode($response));
        }
    }

    public function ajaxProcessCancelOrder()
    {
        $CedShopeeOrder = new CedShopeeOrder;
        $request = Tools::getAllValues();
        $order_id = Tools::getValue('ordersn');
        $cancel_reason = Tools::getValue('cancel_reason');
        $cancel_data = array(
            'ordersn' => $order_id,
            'cancel_reason' => $cancel_reason
            );
        $response = $CedShopeeOrder->cancelOrder($cancel_data);
        if (isset($response) && !empty($response)) {
            die(json_encode($response));
        } else {
            $response = array('success'=> false, 'message'=> 'No Response from Shopee');
            die(json_encode($response));
        }
    }
}
