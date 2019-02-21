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

require_once _PS_MODULE_DIR_ . 'cedshopee/classes/CedShopeeLibrary.php';

class CedShopeeOrder
{
	public function fetchOrder()
    {
        $CedShopeeLibrary = new CedShopeeLibrary;
        $status = $CedShopeeLibrary->isEnabled();
        $url = 'orders/get';
        $CedShopeeLibrary->log($url);
        $createdStartDate = date('Y-m-d', strtotime("-10 days"));
        $params = array('order_status' => 'READY_TO_SHIP', 'create_time_from' => (int)$createdStartDate);
        $db = Db::getInstance();
        try {
        	if($status)
        	{
        		$response = $CedShopeeLibrary->postRequest($url, $params);
        		
	            $CedShopeeLibrary->log(
	                __METHOD__,
	                'Info',
	                'Fetched Order',
	                json_encode($response)
	            );
	            if (isset($response['success']) && $response['success'] == true) {
	            // if (isset($response['error']) && $response['error']) {
	            	$order_ids = array();
	                // $response = $response['message'];
	                if (is_array($response) 
	                	&& isset($response['orders'])
	                    && count($response['orders'])
	                    ) {
	                    $count = 0;
	                    $response['orders'] = array_chunk($response['orders'], '5');

	                    foreach ($response['orders'] as $key => $orders) {
	                        if (isset($orders) && $orders) {
	                        	$order_to_fetch = array();
	                            // $orderData = $order['Order'];
	                            foreach ($orders as $order) {
	                            	$shopeeOrderId = $order['ordersn'];
                                    $already_exist = $this->isShopeeOrderAlreadyExist($shopeeOrderId);
                                    if ($already_exist) {
                                        continue;
                                    } else {
                                    	$count++;
                                        $order_to_fetch[] = $shopeeOrderId;
                                    }
                                }
                                $orders_data = $this->fetchOrderDetails($order_to_fetch);

                                foreach ($orders_data['orders'] as $order_data) {

                                    if (isset($order_data['ordersn']) && $order_data['ordersn']) 
                                    {
							            $CedShopeeLibrary->log(json_encode($order_data), '6', true);
							            $prestashopOrderId = $this->createPrestashopOrder($order_data);
							            if($prestashopOrderId) 
							            {
							            	$shopee_order_id = $order_data['ordersn'];
								            $shipment = $order_data['recipient_address'];
								            $orderDate = $order_data['create_time'];
								            $status = 'Created';
								            if ($orderDate) 
								            {
								            	$orderDate = date("Y-m-d", $orderDate);
								            }
							            	$db->insert(
	                                    		'cedshopee_order',
	                                    		array(
	                                    			'id' => NULL,
	                                    			'order_place_date' => pSQL($orderDate),
	                                    			'prestashop_order_id' => pSQL($prestashopOrderId),
	                                    			'status' => pSQL($status),
	                                    			'order_data' => pSQL($order_data),
	                                    			'shipment_data' => pSQL(json_encode($shipment)),
	                                    			'shopee_order_id' => pSQL($shopee_order_id),
	                                    			'shipment_request_data' => pSQL($shipment),
	                                    			'shipment_response_data' => pSQL($shipment)
	                                    		)
	                                    	);
							            }
                                    }
                                }
	                        }
	                    }
	                    if ($count == 0) {
	                        return array(
	                            'success' => false,
	                            'message' => 'No new Shopee order(s) found'
	                        );
	                    } else {
	                        return array(
	                            'success' => true,
	                            'message' => $count . ' new order(s) fetched successfully'
	                        );
	                    }
	                } else {
	                    return array(
	                        'success' => false,
	                        'message' => $response['message']
	                    );
	                }
	            } else {
	                return array(
	                    'success' => false,
	                    'message' => isset($response['message']) ? $response['message'] : 'No new Shopee order(s) found'
	                );
	            }
        	} else {
        		return array('success' => false, 'message' => 'Module is not enabled.');
        	}
        } catch (\Exception $e) {
            $CedShopeeLibrary->log(
                __METHOD__,
                'Exception',
                $e->getMessage(),
                json_encode(array(
                    'Trace' => $e->getTraceAsString()
                )),
                true
            );
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }

    public function isShopeeOrderAlreadyExist($shopeeOrderId = 0)
    {
        $db = Db::getInstance();
        $isExist = false;
        if ($shopeeOrderId) {
            $sql = "SELECT `id` FROM `" . _DB_PREFIX_ . "cedshopee_order`
             WHERE `shopee_order_id` = '" . pSQL($shopeeOrderId) . "'";
            $result = $db->ExecuteS($sql);
            if (is_array($result) && count($result)) {
                $isExist = true;
            }
        }
        return $isExist;
    }

    public function fetchOrderDetails($order_ids)
    {
    	$CedShopeeLibrary = new CedShopeeLibrary;
        $order_data = array();
        if (isset($order_ids) && count($order_ids)) {
            $url = 'orders/detail';
            $CedShopeeLibrary->log($url);
            $params = array('ordersn_list' => $order_ids);
            $CedShopeeLibrary->log($params);
            $order_data = $CedShopeeLibrary->postRequest($url, $params);
        }
        $order_data = json_decode($order_data, true);
        return $order_data;
    }

    public function createPrestashopOrder($orderData)
    {
        $contexts = Context::getContext();

        $shopeeOrderId = $orderData['ordersn'];
        $idLang = Context::getContext()->language->id;
        $name = $orderData['recipient_address']['name'];

        $nameArr = explode(" ", trim($name));
        $firstName = isset($nameArr[0]) && !empty($nameArr[0]) ? $nameArr[0] : 'Shopee';
        $lastName = isset($nameArr[1]) && !empty($nameArr[1]) ? $nameArr[1] : 'Customer';
        $email = isset($orderData['email']) ? $orderData['email'] : '';
        // Adding Customer in prestashop

        if (empty($email)) {
            $email = Configuration::get('CEDSHOPEE_ORDER_EMAIL') ?
                Configuration::get('CEDSHOPEE_ORDER_EMAIL') :
                $shopeeOrderId . '@shopee.com';
        }
        $idCustomer = 0;
        // if ((int)Configuration::get('CEDWISH_CUSTOMER_ID')) {
        //     $config_id_customer = (int)Configuration::get('CEDWISH_CUSTOMER_ID');
        //     $customer = new Customer($config_id_customer);
        //     if (isset($customer->id) && $customer->id) {
        //         $idCustomer = (int)$customer->id;
        //     }
        // } else
        if (Customer::customerExists($email)) {
            $customer = Customer::getCustomersByEmail($email);
            if (isset($customer[0]) && isset($customer[0]['id_customer']) && $customer[0]['id_customer']) {
                $idCustomer = (int)$customer[0]['id_customer'];
            }
        }
        if (!$idCustomer) {
            $new_customer = new Customer();
            $new_customer->email = $email;
            $new_customer->lastname = $lastName;
            $new_customer->firstname = $firstName;
            $new_customer->passwd = 'shopee';
            $new_customer->add();
            $idCustomer = (int)$new_customer->id;
        }

        $contexts->customer = new Customer($idCustomer);
        //Adding Shipping Address detail in prestashop

        $state = isset($orderData['recipient_address']['state']) ? $orderData['recipient_address']['state'] : '';
        $country = isset($orderData['recipient_address']['country']) ? $orderData['recipient_address']['country'] : '';
        $getLocalizationDetails = $this->getLocalizationDetails($state, $country);
        $idCountry = $getLocalizationDetails['country_id'];
        $idState = $getLocalizationDetails['zone_id'];

        $addressShipping = new Address();
        $addressShipping->id_customer = $idCustomer;
        $addressShipping->id_country = $idCountry;
        $addressShipping->alias = $firstName . ' ' . time();
        $addressShipping->firstname = $firstName;
        $addressShipping->lastname = $lastName;
        $addressShipping->id_state = $idState;
        $addressShipping->address1 = isset($orderData['recipient_address']['full_address']) ?
            $orderData['recipient_address']['full_address'] : '';
        $addressShipping->address2 = '';
        $addressShipping->postcode = isset($orderData['recipient_address']['zipcode']) ?
            $orderData['recipient_address']['zipcode'] : '';
        $addressShipping->city = isset($orderData['recipient_address']['city']) ?
            $orderData['recipient_address']['city'] : '';
        $addressShipping->phone = isset($orderData['recipient_address']['phone']) ?
            $orderData['recipient_address']['phone'] : '';
        $addressShipping->add();
        $idAddressShipping = $addressShipping->id;
        //Adding Delivery Address detail in prestashop

        $addressInvoice = new Address();
        $addressInvoice->id_customer = $idCustomer;
        $addressInvoice->id_country = $idCountry;
        $addressInvoice->alias = $firstName . ' ' . time();
        $addressInvoice->firstname = $firstName;
        $addressInvoice->lastname = $lastName;
        $addressInvoice->id_state = $idState;
        $addressInvoice->address1 = isset($orderData['recipient_address']['full_address']) ?
            $orderData['recipient_address']['full_address'] : '';
        $addressShipping->address2 = '';
        ;
        $addressInvoice->postcode = isset($orderData['recipient_address']['zipcode']) ?
            $orderData['recipient_address']['zipcode'] : '';
        $addressInvoice->city = isset($orderData['recipient_address']['city']) ?
            $orderData['ShippingDetail']['city'] : '';
        $addressInvoice->phone = isset($orderData['recipient_address']['phone']) ?
            $orderData['recipient_address']['phone'] : '';
        $addressInvoice->add();
        $idAddressInvoice = $addressInvoice->id;

        $paymentModule = isset($orderData['recipient_address']['payment_method']) ?
            $orderData['recipient_address']['payment_method'] : 'cedshopeePayment';
        $moduleId = 0;
        $modulesList = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT DISTINCT m.`id_module`, h.`id_hook`, m.`name`, hm.`position`
                  FROM `' . _DB_PREFIX_ . 'module` m 
                  LEFT JOIN `' . _DB_PREFIX_ . 'hook_module` hm ON hm.`id_module` = m.`id_module` 
                  LEFT JOIN `' . _DB_PREFIX_ . 'hook` h ON hm.`id_hook` = h.`id_hook` 
                  GROUP BY hm.id_hook, hm.id_module ORDER BY hm.`position`, m.`name` DESC'
        );
        foreach ($modulesList as $module) {
            $moduleObj = Module::getInstanceById($module['id_module']);
            if (isset($moduleObj->name) && $moduleObj->name == $paymentModule) {
                $moduleId = $module['id_module'];
                break;
            }
        }
        $context = (array)$contexts;
        $currency = isset($context['currency']) ? (array)$context['currency'] : $orderData['currency'];

        if (Configuration::get('PS_CURRENCY_DEFAULT')) {
            $idCurrency = Configuration::get('PS_CURRENCY_DEFAULT');
        } else {
            $idCurrency = isset($currency['id']) ? $currency['id'] : '0';
        }
        if (!$idCurrency) {
            $currencyId = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                'SELECT `id_currency` FROM `' . _DB_PREFIX_ . 'module_currency` 
                WHERE `id_module` = ' . (int)$moduleId
            );
            $idCurrency = isset($currencyId['0']['id_currency']) ? $currencyId['0']['id_currency'] : 0;
        }
        $cart = new Cart();
        $cart->id_customer = $idCustomer;
        $cart->id_address_delivery = $idAddressShipping;
        $cart->id_address_invoice = $idAddressInvoice;
        $cart->id_currency = (int)$idCurrency;
        $cart->id_carrier = $orderData['recipient_address']['shipping_carrier'];
        $cart->recyclable = 0;
        $cart->gift = 0;
        $cart->add();
        $cartId = (int)($cart->id);
        $contexts->cart = new Cart($cartId);
        $orderTotal = 0;
        $final_item_cost = 0;
        $total_vat = 0;
        $shippingCost = isset($orderData['estimated_shipping_fee']) ? (float)$orderData['estimated_shipping_fee'] : 0;
        $orderTotal += $shippingCost;

        if (isset($orderData) && isset($orderData['items'])) {
        	$productArray = array();
            // $sku = $orderData['sku'];
            $order_items = $orderData['items'];
            foreach ($order_items as $orderLine => $item) {
                $cancelQty = 0;
                $item_sku = isset($item['item_sku']) ? $item['item_sku'] : '';
                $variation_sku = isset($item['variation_sku']) ? $item['variation_sku'] : '';

                $id_product = $this->getProductIdByReference($item_sku);
                if (!$id_product) {
                    $id_product = $this->getVariantProductIdByReference($variation_sku);
                }
                $id_product_attribute = $this->getProductAttributeIdByReference($variation_sku);
                $qty = isset($item['variation_quantity_purchased']) ? $item['variation_quantity_purchased'] : '0';
                $producToAdd = new Product((int)($id_product), true, (int)($id_lang));
                $sku = isset($item_sku) ? $item_sku : $variation_sku;
                if ((!$producToAdd->id)) {
                    $this->orderErrorInformation(
                        $sku,
                        $shopeeOrderId,
                        "PRODUCT ID" . $id_product . " DOES NOT EXIST",
                        $orderData
                    );
                    return null;
                }
                if (!$producToAdd->active) {
                    $this->orderErrorInformation(
                        $sku,
                        $shopeeOrderId,
                        "PRODUCT STATUS IS DISABLED WITH ID " . $id_product . "",
                        $orderData
                    );
                    return null;
                }
                if (!$producToAdd->checkQty((int)$qty)) {
                    $quantity = $producToAdd->getQuantity($id_product);
                    $cancelQty = $qty - $quantity;
                    $qty = $quantity;
                    $this->orderErrorInformation(
                    	$sku,
                        $shopeeOrderId,
                        "REQUESTED QUANTITY FOR PRODUCT ID " . $id_product . " IS NOT AVAILABLE",
                        $orderData
                    );
                    return null;
                }
                $cart->updateQty((int)($qty), (int)($id_product), (int)$id_product_attribute);
                $cart->update();

                $item_cost = isset($item['variation_original_price']) ? (float)$item['variation_original_price'] : 0;
                
                 $productArray[$id_product] = array(
		            'price_tax_included' => $item_cost,
		            'quantity' => $qty,
		            'price_tax_excluded' => $item_cost
		        );
                $total_cost = $item_cost * (int)$qty;
                $total_vat += $item_cost * (int)$qty;
                $final_item_cost += (float)$total_cost;
            }
            $orderTotal += $final_item_cost;
            if (count($productArray)) {
                $extraVars = array();
                $extraVars['item_shipping_cost'] = $shippingCost;
                $extraVars['total_paid'] = $orderTotal;
                $extraVars['total_item_cost'] = $final_item_cost;
                $extraVars['total_item_tax'] = $total_vat;
                $extraVars['item_shipping_tax'] = $shippingCost;
                $extraVars['merchant_order_id'] = $shopeeOrderId;
                $extraVars['customer_reference_order_id'] = $shopeeOrderId;
                $secureKey = false;
                $id_shop = (int)$contexts->shop->id;
        		$shop = new Shop($id_shop);
            }
            if (!empty($productArray)) {
                $prestashop_order_id = $this->addOrderInPrestashop(
                    $cartId,
                    $idCustomer,
                    $idAddressShipping,
                	$idAddressInvoice,
                    Configuration::get('CEDSHOPEE_ORDER_CARRIER'),
                    $idCurrency,
                    $extraVars,
                    $productArray,
                    $secureKey,
                    $contexts,
                    $shop,
                    $paymentModule,
                    Configuration::get('CEDSHOPEE_ORDER_STATE_IMPORT')
                );
                if (!empty($prestashop_order_id)) {
                    return $prestashop_order_id;
                }
            } else {
                return false;
            }
            return false;
        }
    }
    public function addOrderInPrestashop(
        $id_cart,
        $id_customer,
        $id_address_delivery,
        $id_address_invoice,
        $id_carrier,
        $id_currency,
        $extra_vars,
        $products,
        $secure_key,
        $context,
        $shop,
        $payment_module,
        $orderState
    ) {
        $context->cart = new Cart($id_cart);
        $newOrder = new Order();
        $carrier = new Carrier($id_carrier, $context->cart->id_lang);
        $newOrder->id_address_delivery = $id_address_delivery;
        $newOrder->id_address_invoice = $id_address_invoice;
        $newOrder->id_shop_group = $shop->id_shop_group;
        $newOrder->id_shop = $shop->id;
        $newOrder->id_cart = $id_cart;
        $newOrder->id_currency = $id_currency;
        $newOrder->id_lang = $context->language->id;
        $newOrder->id_customer = $id_customer;
        $newOrder->id_carrier = $id_carrier;
        $newOrder->current_state = $orderState;
        $newOrder->secure_key = (
        $secure_key ? pSQL($secure_key) : pSQL($context->customer->secure_key)
        );
        $newOrder->payment = $payment_module ? $payment_module : 'Cedshopee Payment';
        $newOrder->module = 'cedshopee';
        $newOrder->conversion_rate = $context->currency->conversion_rate;
        $newOrder->recyclable = $context->cart->recyclable;
        $newOrder->gift = (int)$context->cart->gift;
        $newOrder->gift_message = $context->cart->gift_message;
        $newOrder->mobile_theme = $context->cart->mobile_theme;
        $newOrder->total_discounts = 0;
        $newOrder->total_discounts_tax_incl = 0;
        $newOrder->total_discounts_tax_excl = 0;
        $newOrder->total_paid = $extra_vars['total_paid'];
        $newOrder->total_paid_tax_incl = $extra_vars['total_paid'];
        $newOrder->total_paid_tax_excl = $extra_vars['total_paid'];
        $newOrder->total_paid_real = $extra_vars['total_paid'];
        $newOrder->total_products = $extra_vars['total_item_cost'];
        $newOrder->total_products_wt = $extra_vars['total_item_cost'];
        $newOrder->total_shipping = $extra_vars['item_shipping_cost'];
        $newOrder->total_shipping_tax_incl = $extra_vars['item_shipping_cost'];
        $newOrder->total_shipping_tax_excl = $extra_vars['item_shipping_cost'] - $extra_vars['item_shipping_tax'];
        if (!is_null($carrier) && Validate::isLoadedObject($carrier)) {
            $newOrder->carrier_tax_rate = $carrier->getTaxesRate(
                new Address($context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')})
            );
        }
        $newOrder->total_wrapping = 0;
        $newOrder->total_wrapping_tax_incl = 0;
        $newOrder->total_wrapping_tax_excl = 0;
        $newOrder->invoice_date = '0000-00-00 00:00:00';
        $newOrder->delivery_date = '0000-00-00 00:00:00';
        $newOrder->valid = true;
        do {
            $reference = Order::generateReference();
        } while (Order::getByReference($reference)->count());
        $newOrder->reference = $reference;
        $newOrder->round_mode = Configuration::get('PS_PRICE_ROUND_MODE');
        $packageList = $context->cart->getPackageList();
        $orderItems = array();
        foreach ($packageList as $id_address => $packageByAddress) {
            foreach ($packageByAddress as $id_package => $package) {
                foreach ($package['product_list'] as &$product) {
                    if (array_key_exists($product['id_product'], $products)) {
                        $product['price'] = $products[$product['id_product']]['price_tax_excluded'];
                        $product['price_wt'] = $products[$product['id_product']]['price_tax_included'];
                        $product['total'] = $products[$product['id_product']]['price_tax_excluded'] *
                            $products[$product['id_product']]['quantity'];
                        $product['total_wt'] = $products[$product['id_product']]['price_tax_included'] *
                            $products[$product['id_product']]['quantity'];
                    }
                }
                $orderItems = $package['product_list'];
            }
        }
        $newOrder->product_list = $orderItems;
        if(isset($orderItems) && $orderItems) {
        	$res = $newOrder->add(true, false);
	        if (!$res) {
	            PrestaShopLogger::addLog(
	                'Order cannot be created',
	                3,
	                null,
	                'Cart',
	                (int)$id_cart,
	                true
	            );
	            throw new PrestaShopException('Can\'t add Order');
	        }
        }
        if ($newOrder->id_carrier) {
            $newOrderCarrier = new OrderCarrier();
            $newOrderCarrier->id_order = (int)$newOrder->id;
            $newOrderCarrier->id_carrier = (int)$newOrder->id_carrier;
            $newOrderCarrier->weight = (float)$newOrder->getTotalWeight();
            $newOrderCarrier->shipping_cost_tax_excl = $newOrder->total_shipping_tax_excl;
            $newOrderCarrier->shipping_cost_tax_incl = $newOrder->total_shipping_tax_incl;
            $newOrderCarrier->add();
        }
        if (isset($newOrder->product_list) && count($newOrder->product_list)) {
            foreach ($newOrder->product_list as $product_d) {
                $order_detail = new OrderDetail();
                $order_detail->id_order = (int)$newOrder->id;
                $order_detail->id_order_invoice = $product_d['id_address_delivery'];
                $order_detail->product_id = $product_d['id_product'];
                $order_detail->id_shop = $product_d['id_shop'];
                $order_detail->id_warehouse = $packageList[$id_address][$id_package]['id_warehouse'];
                $order_detail->product_attribute_id = $product_d['id_product_attribute'];
                $order_detail->product_name = $product_d['name'];
                $order_detail->product_quantity = $product_d['cart_quantity'];
                $order_detail->product_quantity_in_stock = $product_d['quantity_available'];
                $order_detail->product_price = $product_d['price'];
                $order_detail->unit_price_tax_incl = $product_d['price_wt'];
                $order_detail->unit_price_tax_excl = $product_d['price'];
                $order_detail->total_price_tax_incl = $product_d['total_wt'];
                $order_detail->total_price_tax_excl = $product_d['total'];
                $order_detail->product_ean13 = $product_d['ean13'];
                $order_detail->product_upc = $product_d['upc'];
                $order_detail->product_reference = $product_d['reference'];
                $order_detail->product_supplier_reference = $product_d['supplier_reference'];
                $order_detail->product_weight = $product_d['weight'];
                $order_detail->ecotax = $product_d['ecotax'];
                $order_detail->discount_quantity_applied = $product_d['quantity_discount_applies'];
                $o_res = $order_detail->add();
                if (!$o_res) {
                    $newOrder->delete();
                    PrestaShopLogger::addLog(
                        'Order details cannot be created',
                        3,
                        null,
                        'Cart',
                        (int)$id_cart,
                        true
                    );
                    throw new PrestaShopException('Can\'t add Order details');
                }
            }
            Hook::exec(
                'actionValidateOrder',
                array(
                    'cart' => $context->cart,
                    'order' => $newOrder,
                    'customer' => $context->customer,
                    'currency' => $context->currency,
                    'orderStatus' => $orderState
                )
            );

            $order_status = new OrderState(
                $orderState,
                (int)$context->language->id
            );
            foreach ($context->cart->getProducts() as $product) {
                if ($order_status->logable) {
                    ProductSale::addProductSale(
                        (int)$product['id_product'],
                        (int)$product['cart_quantity']
                    );
                }
            }

            // Set the order status
            $new_history = new OrderHistory();
            $new_history->id_order = (int)$newOrder->id;
            $new_history->changeIdOrderState($orderState, $newOrder, true);
            $new_history->add(true, $extra_vars);

            // Switch to back order if needed
            if (Configuration::get('PS_STOCK_MANAGEMENT') && $order_detail->getStockState()) {
                $history = new OrderHistory();
                $history->id_order = (int)$newOrder->id;
                $history->changeIdOrderState(
                    Configuration::get(
                        $newOrder->valid ? 'PS_OS_OUTOFSTOCK_PAID' : 'PS_OS_OUTOFSTOCK_UNPAID'
                    ),
                    $newOrder,
                    true
                );
                $history->add();
            }


            // Order is reloaded because the status just changed

            // Send an e-mail to customer (one order = one email)

            //  updates stock in shops
            $product_list = $newOrder->getProducts();
            foreach ($product_list as $product) {
                $idProd = $product['product_id'];
                $idProdAttr = $product['product_attribute_id'];
                $qtyToReduce = (int)$product['product_quantity']*-1;
                StockAvailable::updateQuantity($idProd, $idProdAttr, $qtyToReduce, $newOrder->id_shop);
            }
            if (isset($newOrder->id) && $newOrder->id) {
                return $newOrder->id;
            }
        }
        return false;
    }

    public function getLocalizationDetails($Statecode, $countryCode)
    {

        $db = Db::getInstance();
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        $sql = "SELECT c.id_country, cl.name FROM `" . _DB_PREFIX_ . "country` c
         LEFT JOIN `" . _DB_PREFIX_ . "country_lang` cl on (c.id_country =cl.id_country)
          WHERE `iso_code` LIKE '" . pSQL($countryCode) . "' AND cl.id_lang ='" . (int)$default_lang . "'";
        $Execute = $db->ExecuteS($sql);
        if (is_array($Execute) && count($Execute) && isset($Execute['0'])) {
            $country_id = 0;
            $country_name = '';
            if (isset($Execute['0']['id_country']) && $Execute['0']['id_country']) {
                $country_id = $Execute['0']['id_country'];
                $country_name = $Execute['0']['name'];
            }
            if ($country_id) {
                $Execute = $db->ExecuteS("SELECT `id_state`,`name` FROM 
                 `" . _DB_PREFIX_ . "state` WHERE `id_country`='" . (int)$country_id . "'
                  AND `name` LIKE '%" . pSQL($Statecode) . "%'");
                if (is_array($Execute) && count($Execute)) {
                    if (isset($Execute['0']['id_state']) && isset($Execute['0']['name'])) {
                        return array(
                            'country_id' => $country_id,
                            'zone_id' => $Execute['0']['id_state'],
                            'name' => $Execute['0']['name'],
                            'country_name' => $country_name
                        );
                    };
                } else {
                    return array(
                        'country_id' => $country_id,
                        'zone_id' => '',
                        'name' => '',
                        'country_name' => $country_name
                    );
                }
            } else {
                return array(
                    'country_id' => '',
                    'zone_id' => '',
                    'name' => '',
                    'country_name' => ''
                );
            }
        } else {
            return array(
                'country_id' => '',
                'zone_id' => '',
                'name' => '',
                'country_name' => ''
            );
        }
    }

    public function getProductIdByReference($merchant_sku)
    {
        $db = Db::getInstance();
        return $db->getValue(
            'Select `id_product` FROM `' . _DB_PREFIX_ . 'product` 
            WHERE `reference`="' . pSQL($merchant_sku) . '"'
        );
    }

    public function getVariantProductIdByReference($merchant_sku)
    {
        $db = Db::getInstance();
        return $db->getValue(
            'Select `id_product` FROM `' . _DB_PREFIX_ . 'product_attribute` 
            WHERE `reference`="' . pSQL($merchant_sku) . '"'
        );
    }

    public static function getProductAttributeIdByReference($merchant_sku)
    {
        $db = Db::getInstance();
        return $db->getValue(
            'Select `id_product_attribute` FROM `'._DB_PREFIX_.'product_attribute` WHERE `reference`="'.
            pSQL($merchant_sku).'"'
        );
    }

    public function orderErrorInformation($sku, $shopeeOrderId, $reason, $orderData)
    {
        $db = Db::getInstance();
        $sql_check_already_exists = "SELECT * FROM `" . _DB_PREFIX_ . "cedshopee_order_error` 
        WHERE `merchant_sku`='" . pSQL($sku) . "' AND `shopee_order_id`='" . pSQL($shopeeOrderId) . "'";
        $Execute_check_already_exists = $db->ExecuteS($sql_check_already_exists);
        if (count($Execute_check_already_exists) == 0) {
            $sql_insert = "INSERT INTO `" . _DB_PREFIX_ . "cedshopee_order_error` (
            `merchant_sku`,`shopee_order_id`,`reason`,`order_data`)
            VALUES('" . pSQL($sku) . "','" . pSQL($shopeeOrderId) . "','" . pSQL($reason) . "',
            '" . pSQL(json_encode($orderData)) . "')";
            $result = $db->Execute($sql_insert);
            // if (count($result)) {
            //     if (Configuration::get('CEDSHOPEE_REJECTED_ORDER')) {
            //         $this->rejectOrder($shopeeOrderId, 1);
            //     }
            // }
        }
    }

    public function cancelOrder($params = array())
    {
    	$CedShopeeLibrary = new CedShopeeLibrary;
        $response = $CedShopeeLibrary->postRequest('orders/cancel', $params);
        try {
            if (!isset($response['error']) && empty($response['error'])) {
                if (isset($response['response']) && $response['response']) {
                    return array('success' => true, 'response' => $response['msg']);
                } else if(isset($response['msg'])) {
                    return array('success' => false, 'message' => $response['msg']);
                }
            } elseif(isset($response['error']) && !empty($response['error'])) {
                return array('success' => false, 'message' => $response['error']);
            }
        } catch (Exception $e) {
            $CedShopeeLibrary->log('cancelOrder: ' . var_export($response, true));
            return false;
        }
    }

    public function shipOrder($ship_data = null)
    {
    	$CedShopeeLibrary = new CedShopeeLibrary;
    	$db = Db::getInstance();
        $trackingNumber = '';
        if (isset($ship_data['tracking_number'])) {
            $trackingNumber = $ship_data['tracking_number'];
        }

        $ordersn = '';
        if (isset($ship_data['ordersn'])) {
            $ordersn = $ship_data['ordersn'];
        }
        if ($trackingNumber && $ordersn) {
            // ordersn is shopee order id
            try {
                $params = array('info_list' => array(array('tracking_number' => $trackingNumber, 'ordersn' =>$ordersn)));
                $response = $CedShopeeLibrary->postRequest('logistics/tracking_number/set_mass',
                    $params);
                if (isset($response['result']) && $response['result']) {
                    if (isset($response['result']['success_count']) && $response['result']['success_count']) {
                    	$db = Db::getInstance();
	                    $prestashopOrderId = $db->getValue(
	                        'SELECT `prestashop_order_id` FROM `' . _DB_PREFIX_ . 'cedshopee_order` 
	                         WHERE `shopee_order_id`="' . pSQL($ordersn) . '"'
	                    );
                    	$db->update(
                            'cedshopee_order',
                            array(
                                'status' => pSQL('SHIPPED')
                            ),
                            'shopee_order_id="'.pSQL($ordersn).'"'
                        );
                        $this->updateOrderStatus($ordersn, 'shipped');
                        return array('success' => true, 'response' => json_encode($response));
                    } else {
                        return array('success' => false, 'message' => $response['result']['error_codes']);
                    }
                }
            } catch (Exception $e) {
                $CedShopeeLibrary->log('Response: ' . var_export($response, true));
                return array('success' => false, 'message' => 'Response: ' . var_export($response, true));
            }
        } else {
            return array('success' => false, 'message' => 'Missing Order ID or Tracking no.');
        }
    }

    public function acceptOrder($shopee_order_id, $url = 'v3/orders')
    {
    	$CedShopeeLibrary = new CedShopeeLibrary;
        $response = $CedShopeeLibrary->postRequest($url . '/' . $shopee_order_id . '/acknowledge'
        );
        try {
            if (isset($response['success']) && $response['success']) {
                $response = $response['response'];
            } else {
                return $response;
            }
            $response = json_decode($response, true);
            if (isset($response['error'])) {
                return array('success' => false, 'message' => $response['error']);
            }
            $this->updateOrderStatus($shopee_order_id, 'acknowledged');
            return $response;
        } catch (Exception $e) {
            $CedShopeeLibrary->log('acceptOrder' . var_export($response, true));
            return false;
        }
    }

    public function updateOrderStatus($purchaseOrderId, $status = 'CEDSHOPEE_ORDER_STATE')
    {
        $db = Db::getInstance();
        $result = $db->ExecuteS("SELECT `prestashop_order_id` FROM `" . _DB_PREFIX_ . "shopee_order` 
        where `shopee_order_id` = '" . pSQL($purchaseOrderId) . "'");
        $order_id = 0;
        if (is_array($result) && count($result)) {
            $order_id = $result['0']['prestashop_order_id'];
        }
        $id_order_state = (int)Configuration::get($status);
        $order_state = new OrderState($id_order_state);
        $order = new Order((int)$order_id);
        $history = new OrderHistory();
        $history->id_order = $order->id;
        //$history->id_employee = (int)Context::getContext()->employee->id;
        $use_existings_payment = !$order->hasInvoice();
        $history->changeIdOrderState((int)$order_state->id, $order, $use_existings_payment);
        $product_list = $order->getProducts();
        foreach ($product_list as $product) {
            $idProd = $product['product_id'];
            $idProdAttr = $product['product_attribute_id'];
            $qtyToReduce = (int)$product['product_quantity']*-1;
            StockAvailable::updateQuantity($idProd, $idProdAttr, $qtyToReduce, $newOrder->id_shop);
        }
    }
}