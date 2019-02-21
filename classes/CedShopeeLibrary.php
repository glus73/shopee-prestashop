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

class CedShopeeLibrary
{
    public function _init()
    {
        $this->_api_url = Configuration::get('CEDSHOPEE_API_URL');
        $this->partner_id = Configuration::get('CEDSHOPEE_PARTNER_ID');
        $this->shop_id = Configuration::get('CEDSHOPEE_SHOP_ID');
        $this->signature = Configuration::get('CEDSHOPEE_SIGNATURE');
    }

	/**
     * @return bool
     */
    public function isEnabled()
    {
        $flag = false;
        if (Configuration::get('CEDSHOPEE_ENABLE')) {
            $flag = true;
            $this->_init();
        }
        return $flag;
    }
	/**
     * Post Request
     * $params = ['file' => "", 'data' => "" ]
     * @param string $url
     * @param array $params
     * @return string|array
     */
    public function postRequest($url, $params = array())
    {
        $request = null;
        $response = null;
        $enable = $this->isEnabled();
        if ($enable) {
            try {
                $host = str_replace('/api/v1/', '', Configuration::get('CEDSHOPEE_API_URL'));
                $host = str_replace('https://', '', $host);
                $url = Configuration::get('CEDSHOPEE_API_URL') . $url;
                $jsonBody = $this->createJsonBody($params);
                $headers = array(
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'Authorization: '.$this->signature($url, $jsonBody),
                    'Host: '.$host,
                    'Content-Length: '.strlen($jsonBody)
                );
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST,       true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_HEADER, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);
                $servererror = curl_error($ch);
                $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                $body = substr($response, $header_size);
                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $this->log('Headers');
                $this->log($headers);
                $this->log('Parameters');
                $this->log($params);
                $this->log('body');
                $this->log($body);
                $this->log('Responses');
                $this->log($response);
                if ($body) {
                    $body = json_decode($body, true);
                }
                if ($httpcode != 200) {
                    return $body;
                }
                if (!empty($servererror)) {
                    $request = curl_getinfo($ch);
                    curl_close($ch);
                    return array('error' => 'server_error', 'msg' => $servererror);
                }
                curl_close($ch);
                if ($body && ($httpcode == 200)) {
                    return $body;
                } else {
                    return '{}';
                }
            } catch (Exception $e) {
                $this->log(
                    "Shopee\\Shopee\\Request\\postRequest() : \n URL: " . $url .
                    "\n Request : \n" . var_export($request, true) .
                    "\n Response : \n " . var_export($body, true) .
                    "\n Errors : \n " . var_export($e->getMessage(), true)
                );
                return array('error' => $httpcode, 'msg' => $body);
            }
        }
    }

    /**
     * Generate an HMAC-SHA256 signature for a HTTP request
     *
     * @param UriInterface $uri
     * @param string $body
     * @return string
     */
    protected function signature($url, $body)
    {
        $data = $url . '|' . $body;
        return hash_hmac('sha256', $data, trim($this->signature));
    }

    protected function createJsonBody(array $data)
    {
        $data = array_merge(array(
            'shopid' => (int)$this->shop_id,
            'partner_id' => (int)$this->partner_id,
            'timestamp' => time(),
        ), $data);
        return json_encode($data);
    }

    public function log($method = '', $type = '', $message = '', $response = '', $force_log = false)
    {
        if (Configuration::get('CEDSHOPEE_DEBUG_MODE') || $force_log == true) {
            $db = Db::getInstance();
            $db->insert(
                'cedshopee_logs',
                array(
                    'method' => pSQL($method),
                    'type' => pSQL($type),
                    'message' => pSQL($message),
                    'data' => pSQL($response, true),
                )
            );
        }
    }

    public static function getShopeeAttributes()
    {
        $db = Db::getInstance();
        $sql = "SELECT `attribute_id`,`attribute_name` FROM `" . _DB_PREFIX_ . "cedshopee_attribute` ";
        $result = $db->ExecuteS($sql);
        if (is_array($result) && count($result)) {
            return $result;
        } else {
            return array();
        }
    }

    public static function getDefaultShopeeAttributes()
    {
        return array(
           'name' => array(
               'code' => 'name',
               'title' => 'Name',
               'description' => 'Name of the Product',
               'required' => true
            ),
            'description' => array(
                'code' => 'description',
                'title' => 'Description',
                'description' => 'Description of the Product',
                'required' => true
            ),
            'price' => array(
                'code' => 'price',
                'title' => 'Price',
                'description' => 'Price of the product',
                'required' => true
            ),
            'quantity' => array(
               'code' => 'quantity',
               'title' => 'Stock',
               'description' => 'Stock of the product.',
               'required' => true
            ),
            'reference' => array(
                'code' => 'reference',
                'title' => 'Item Sku',
                'description' => '',
                'required' => true
            ),
            'weight' => array(
                'code' => 'weight',
                'title' => 'Weight',
                'description' => '',
                'required' => true
            ),
            'length' => array(
                'code' => 'length',
                'title' => 'Package Length',
                'description' => '',
                'required' => true
            ),
            'width' => array(
                'code' => 'width',
                'title' => 'Package Width',
                'description' => '',
                'required' => true
            ),
            'height' => array(
                'code' => 'height',
                'title' => 'Package Height',
                'description' => '',
                'required' => true
            ),
            'days_to_ship' => array(
                'code' => 'days_to_ship',
                'title' => 'Days to Ship',
                'description' => '',
                'required' => false
            )
        );
    }

    public static function getSystemAttributes()
    {
        return array(
            'name' => 'Name',
            'description' => 'Description',
            'price' => 'Price',
            'quantity' => 'Quantity',
            'reference' => 'Reference',
            'weight' => 'Weight',
            'length' => 'Length',
            'width' => 'Width',
            'height' => 'Height',
            'days_to_ship' => 'Days to Ship'
        );
    }

    public static function getLogistics()
    {
        $db = Db::getInstance();
        $sql = "SELECT * FROM `" . _DB_PREFIX_ . "cedshopee_logistics` WHERE logistic_id >= '0'";
        $result = $db->ExecuteS($sql);
        if (is_array($result) && count($result)) {
            return $result;
        } else {
            return array();
        }
    }

    public static function getShopeeLogistics()
    {
        return array(
            array(
                'col' => 6,
                'row' => 5,
                'type' => 'text',
                'id' => 'logistics',
                'description' => '',
                'required' => false,
                'name' => 'logistics',
                'label' => ('Logistics'),
            ),
            array(
                'type' => 'switch',
                'label' => ('Is Free'),
                'name' => 'is_free',
                'description' => '',
                'is_bool' => true,
                'values' => array(
                    array(
                        'id' => 'active_on',
                        'value' => 1,
                        'label' => ('Yes')
                    ),
                    array(
                        'id' => 'active_off',
                        'value' => 0,
                        'label' => ('No')
                    )
                ),
            ),
            array(
                'col' => 6,
                'type' => 'text',
                'id' => 'shipping_fee',
                'required' => false,
                'name' => 'shipping_fee',
                'description' => 'Needed, if selected logistics have fee_type = CUSTOM_PRICE',
                'label' => ('Shipping Fee'),
            )
        );
    }

    public static function getShopeeWholesale()
    {
        return array(
            array(
                'col' => 6,
                'type' => 'text',
                'id' => 'wholesale_min',
                'required' => false,
                'name' => 'wholesale_min',
                'label' => ('Wholesale Min'),
            ),
            array(
                'col' => 6,
                'type' => 'text',
                'id' => 'wholesale_max',
                'required' => false,
                'name' => 'wholesale_max',
                'label' => ('Wholesale Max'),
            ),
            array(
                'col' => 6,
                'type' => 'text',
                'id' => 'wholesale_price',
                'required' => false,
                'name' => 'wholesale_price',
                'label' => ('Wholesale Price'),
            )
        );
    }

    public static function getShopeeCategories($data = array())
    {
        $db = Db::getInstance();
        if(isset($data) && !empty($data['filter_name']))
        {
            $sql = "SELECT `category_id`,`category_name` FROM `" . _DB_PREFIX_ . "cedshopee_category` WHERE `category_name` LIKE '%" . pSQL($data['filter_name']) . "%' ORDER BY `category_name`";
            $result = $db->ExecuteS($sql);
        } else {
            $sql = "SELECT `category_id` FROM `" . _DB_PREFIX_ . "cedshopee_category` ORDER BY `category_name`";
            $result = $db->ExecuteS($sql);
        } 
        if (is_array($result) && count($result)) {
            return $result;
        } else {
            return array();
        }
    }

    public function productSecondaryImageURL($product_id = 0, $attribute_id = 0)
    {
        $db = Db::getInstance();
        if ($product_id) {
            //$productImages = array();
            $additionalAssets = array();
            $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

            $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'image` i LEFT JOIN `' . _DB_PREFIX_ . 'image_lang` il 
            ON (i.`id_image` = il.`id_image`)';

            if ($attribute_id) {
                $sql .= ' LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_image` ai ON (i.`id_image` = ai.`id_image`)';
                $attribute_filter = ' AND ai.`id_product_attribute` = ' . (int)$attribute_id;
                $sql .= ' WHERE i.`id_product` = ' . (int)$product_id . ' AND 
                il.`id_lang` = ' . (int)$default_lang . $attribute_filter . ' ORDER BY i.`position` ASC';
            } else {
                $sql .= ' WHERE i.`id_product` = ' . (int)$product_id . ' AND 
                il.`id_lang` = ' . (int)$default_lang . ' ORDER BY i.`position` ASC';
            }


            $Execute = $db->ExecuteS($sql);
            $type = ImageType::getFormatedName('large');
            $product = new Product($product_id);
            $link = new Link;
            if (count($Execute) > 0) {
                //$count = 0;
//                foreach ($Execute as $key => $image) {
                foreach ($Execute as $image) {
                    $image_url = $link->getImageLink($product->link_rewrite[$default_lang], $image['id_image'], $type);
                    if (isset($image['cover']) && $image['cover']) {
                        $additionalAssets['mainImageUrl'] =
                            (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://') . $image_url;
                    } else {
                        $additionalAssets['productSecondaryImageURL'][] =
                            (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://') . $image_url;
                    }
                }
            }
            return $additionalAssets;
        }
    }
}

/*

public function postRequest($url, $params = array())
    {
        $request = null;
        $response = null;
        $redirect_url = Tools::getHttpHost(true).__PS_BASE_URI__ . 'index.php?controller=AdminCedShopeeCategory&method=fetchCategory&token=' . $params['token'];
        $enable = $this->isEnabled();
        if ($enable) {
            try {
                $host = Configuration::get('CEDSHOPEE_API_URL');
                // $host = str_replace('api/v1/', '', Configuration::get('CEDSHOPEE_API_URL'));
                // $host = str_replace('https://', '', $host);
                $url = Configuration::get('CEDSHOPEE_API_URL') . $url;
                $jsonBody = $this->createJsonBody($params);
                // $signature = $this->signature($url, $jsonBody);
                $token = $this->signature($redirect_url);
                // $qwe = $this->key.$redirect_url;
                
                $jsonBody = $host . '?id=' . $this->partner_id . '&token=' . $token . '&redirect=' . $redirect_url;
                // echo $headers; die;
                $headers = array(
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'Authorization: '. $token,
                    'Host: '.$host,
                    'Content-Length: '.strlen($jsonBody)
                );
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST,       true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_HEADER, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);
                $servererror = curl_error($ch);
                $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                $body = substr($response, $header_size);
                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                echo $response; die;
                $this->log('Headers');
                $this->log($headers);
                $this->log('Parameters');
                $this->log($params);
                $this->log('body');
                $this->log($body);
                $this->log('Responses');
                $this->log($response);
                if ($body) {
                    $body = json_decode($body, true);
                }
                if ($httpcode != 200) {
                    return $body;
                }
                if (!empty($servererror)) {
                    $request = curl_getinfo($ch);
                    curl_close($ch);
                    return array('error' => 'server_error', 'msg' => $servererror);
                }
                curl_close($ch);
                if ($body && ($httpcode == 200)) {
                    return $body;
                } else {
                    return '{}';
                }
            } catch (Exception $e) {
                $this->log(
                    "Shopee\\Shopee\\Request\\postRequest() : \n URL: " . $url .
                    "\n Request : \n" . var_export($request, true) .
                    "\n Response : \n " . var_export($body, true) .
                    "\n Errors : \n " . var_export($e->getMessage(), true)
                );
                return array('error' => $httpcode, 'msg' => $body);
            }
        }
    }
   
    protected function signature($url)
    {
        $data = $url . $this->key;
        return hash_hmac('sha256', $data, trim($this->key));
    }

*/