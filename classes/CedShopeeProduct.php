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

class CedShopeeProduct
{
    public function getAllMappedCategories()
    {
        $db = Db::getInstance();
        $row = $db->ExecuteS("SELECT `store_category` 
        FROM `" . _DB_PREFIX_ . "cedshopee_profile` 
        WHERE `store_category` != ''");

        if (isset($row['0']) && $row['0']) {
            $mapped_categories = array();
            foreach ($row as $value) {
                $mapped_categories = array_merge($mapped_categories, json_decode($value['store_category'], true));
            }
            return $mapped_categories;
        } else {
            return array();
        }
    }

    public function uploadProducts($product_ids)
    {
        $CedShopeeLibrary = new CedShopeeLibrary;
        $db = Db::getInstance();
        $product_ids = array_filter($product_ids);
        $product_ids = array_unique($product_ids);
        $validation_error = array();
        if (!empty($product_ids)) {
            $productToUpload = array();
            $itemCount = 0;
            foreach ($product_ids as $product_id) {
                if (is_numeric($product_id)) {
                    $profile_info = $this->getProfileByProductId($product_id);
                    $default_lang = isset($profile_info['0']['profile_language']) ? $profile_info['0']['profile_language'] : Configuration::get('PS_LANG_DEFAULT');
                    // $product = $this->getProduct($product_id);
                    $product = (array)new Product($product_id, true, $default_lang);
                    // echo '<pre>'; print_r($product); die;
                    if ($profile_info && !empty($product)) {
                        $product_info = $this->getCedShopeeMappedProductData($product_id, $profile_info, $product);

                        $category = $this->getCedShopeeCategory($product_id, $profile_info);

                        $price = $this->getCedShopeePrice($product_id, $product);

                        $stock = $this->getCedShopeeQuantity($product_id, $product);

                        $attributes = $this->getCedShopeeAttribute($product_id, $profile_info, $product);

                        if (!empty($attributes)) {
                            $productToUpload['attributes'] =  $attributes;
                        } else {
                            $productToUpload['attributes'] =  array(array('attributes_id' => 19428, 'value' => (string)'No Brand'));
                        }

                        // $images = $this->productSecondaryImageURL($product_id, $product);
                        $images = $CedShopeeLibrary->productSecondaryImageURL($product_id);

                        $productToUpload['category_id'] = (int) $category;

                        if (isset($product_info['name']) &&  $product_info['name']) {
                            $productToUpload['name'] = (string) $product_info['name'];
                        } else {
                            $validation_error[$itemCount] = 'Product ID '.$product_id.'Name is required Field';
                        }

                        if (isset($product_info['description']) &&  $product_info['description']) {
                            $productToUpload['description'] = (string) (strip_tags(Tools::substr(html_entity_decode($product_info['description']), 0, 2994).'...'));
                            $productToUpload['description'] = htmlspecialchars_decode(str_replace('&nbsp;', '', $productToUpload['description']));
                        } else {
                            $validation_error[$itemCount] = 'Product ID '.$product_id.'Description is required Field';
                        }

                        $productToUpload['price'] = (float) $price;

                        $productToUpload['stock'] =  (int)$stock;

                        if (isset($product['reference']) &&  $product['reference']) {
                            $productToUpload['item_sku'] = (string) $product['reference'];
                        }

                        if (isset($product_info['weight']) && $product_info['weight']) {
                            $productToUpload['weight'] = (int) $product_info['weight'];
                            // if (isset($product['weight_class_id']) && !empty($product['weight_class_id'])) {
                            //     $muliplyBy = $this->getMultliplyNumber($product['weight_class_id']);
                            //     if (!empty($muliplyBy)) {
                            //         $productToUpload['weight'] = ((float)$productToUpload['weight'] * $muliplyBy) > 0.1 ? (float)$productToUpload['weight'] * $muliplyBy : 0.2;
                            //     }
                            // }
                        }

                        if (isset($product_info['length']) && $product_info['length']) {
                            $productToUpload['length'] = (int) $product_info['length'];
                        }

                        if (isset($product_info['width']) && $product_info['width']) {
                            $productToUpload['width'] = (int) $product_info['width'];
                        }

                        if (isset($product_info['height']) && $product_info['height']) {
                            $productToUpload['height'] =  (int)$product_info['height'];
                        }

                        if (isset($product_info['days_to_ship']) && $product_info['days_to_ship']) {
                            $productToUpload['days_to_ship'] = (int) $product_info['days_to_ship'];
                        }

                        if (!empty($images)) {
                            $productToUpload['images'] = (array) $images;
                        } else {
                            $validation_error[$itemCount] = 'Product ID '.$product_id.'Image is required Field';
                        }

                        $logistics = $this->getLogistics($profile_info, $product_id);
           
                        if (!empty($logistics)) {
                            $productToUpload['logistics'] =  $logistics;
                        } else {
                            $validation_error[$itemCount] = 'Product ID '.$product_id.'Logistics is required Field';
                        }

                        $wholesales  = $this->getWholesales($profile_info, $product_id);
                        
                        if (!empty($wholesales)) {
                            $productToUpload['wholesales'] = (array) array($wholesales);
                        }

                        $result = $db->executeS("SELECT shopee_item_id FROM `"._DB_PREFIX_."cedshopee_uploaded_products` WHERE product_id='".$product_id."'");

                        $productToUpload['item_id'] = isset($result['shopee_item_id']) ? (int)$result['shopee_item_id'] : '0';

                        if ($variants = $this->isVariantProduct($product_id, $profile_info['0']['profile_language'])) {
                            $productToUpload['variations'] = (array) $variants;
                        }
                        
                        $valid = $this->validateProduct($productToUpload, $category);

                        if (isset($valid['success']) && $valid['success']) {
                              $itemCount++;
                            if (count($productToUpload) && (count($validation_error) == 0)) {
                                if (isset($productToUpload['item_id']) && $productToUpload['item_id']) {
                                        unset($productToUpload['images']);
                                       $response = $CedShopeeLibrary->postRequest('item/update', $productToUpload);
                                } else {
                                    $response = $CedShopeeLibrary->postRequest('item/add', $productToUpload);
                                }
                                if (isset($response['item_id']) && $response['item_id']) {
                                    if (isset($response['msg']) && $response['msg']) {
                                        $db->insert(
                                            'cedshopee_uploaded_products',
                                            array(
                                                'product_id' => (int)$product_id,
                                                'shopee_item_id' => (int)$response['item_id'],
                                                'shopee_status' => pSQL($response['item']['status'])
                                                )
                                        );

                                        $variations = $CedShopeeLibrary->postRequest('item/get', array('item_id' => (int)$response['item_id']));

                                        if (isset($variations['item']['variations']) && !empty($variations['item']['variations'])) {
                                            foreach ($variations['item']['variations'] as $variation) {
                                                $name = $variation['name'];
                                                $qty= $variation['stock'];
                                                ;
                                                $price= $variation['price'];
                                                ;
                                                $variation_id= $variation['variation_id'];
                                                ;
                                                $sku= $variation['variation_sku'];
                                                ;

                                                $product_option_value_query = $db->executeS("SELECT id, variation_id FROM `" . _DB_PREFIX_ . "cedshopee_product_variations` WHERE variation_sku = '".$sku."' AND product_id='".$product_id."'");

                                                if (isset($product_option_value_query) && count($product_option_value_query)) {
                                                    $db->update(
                                                        'cedshopee_product_variations',
                                                        array(
                                                            'variation_id' => (int)$variation_id,
                                                            'stock' => (int)$qty,
                                                            'price' => (float)$price,
                                                            'name' => pSQL($name)
                                                            ),
                                                        'variation_sku ="'. pSQL($sku).'" AND product_id="'. (int)$product_id.'"'
                                                    );
                                                } else {
                                                    $db->insert(
                                                        'cedshopee_product_variations',
                                                        array(
                                                            'variation_id' => (int)$variation_id,
                                                            'stock' => (int)$qty,
                                                            'price' => (float)$price,
                                                            'name' => pSQL($name),
                                                            'variation_sku' => pSQL($sku),
                                                            'product_id' => (int)$product_id
                                                            )
                                                    );
                                                }
                                            }
                                        }
                                         return array('success' => true, 'message' => $response['msg']);
                                         $alreadyExist = $db->executeS("SELECT * FROM `".DB_PREFIX."cedshopee_uploaded_products` WHERE product_id = '".(int)$product_id."'");
                                        if (isset($alreadyExist) && count($alreadyExist)) {
                                            $db->update(
                                                'cedshopee_uploaded_products',
                                                array(
                                                'shopee_item_id' => (int)$response['item_id'],
                                                'shopee_status' => pSQL($response['item']['status'])
                                                ),
                                                'product_id="'.(int)$product_id.'" '
                                            );
                                        } else {
                                            $db->insert(
                                                'cedshopee_uploaded_products',
                                                array(
                                                'product_id' => (int)$product_id,
                                                'shopee_item_id' => (int)$response['item_id'],
                                                'shopee_status' => pSQL($response['item']['status'])
                                                ),
                                                'product_id="'.(int)$product_id.'" '
                                            );
                                        }
                                                $variations = $CedShopeeLibrary->postRequest('item/get', array('item_id' => (int)$response['item_id']));

                                        if (isset($variations['item']['variations']) && !empty($variations['item']['variations'])) {
                                            foreach ($variations['item']['variations'] as $variation) {
                                                $name = $variation['name'];
                                                $qty= $variation['stock'];
                                                ;
                                                $price= $variation['price'];
                                                ;
                                                $variation_id= $variation['variation_id'];
                                                ;
                                                $sku= $variation['variation_sku'];
                                                ;

                                                $product_option_value_query = $db->executeS("SELECT id, variation_id FROM `" . _DB_PREFIX_ . "cedshopee_product_variations` where variation_sku = '".$sku."' AND product_id='".$product_id."'");

                                                if (isset($product_option_value_query) && count($product_option_value_query)) {
                                                    $db->update(
                                                        'cedshopee_product_variations',
                                                        array(
                                                        'variation_id' => (int)$variation_id,
                                                        'stock' => (int)$qty,
                                                        'price' => (float)$price,
                                                        'name' => pSQL($name)
                                                        ),
                                                        'variation_sku ="'. pSQL($sku).'" AND product_id="'. (int)$product_id.'"'
                                                    );
                                                } else {
                                                    $db->insert(
                                                        'cedshopee_product_variations',
                                                        array(
                                                        'variation_id' => (int)$variation_id,
                                                        'stock' => (int)$qty,
                                                        'price' => (float)$price,
                                                        'name' => pSQL($name),
                                                        'variation_sku' => pSQL($sku),
                                                        'product_id' => (int)$product_id
                                                        )
                                                    );
                                                }
                                            }
                                        }
                                    }
                                } elseif (isset($response['error']) && isset($response['msg']) && $response['msg']) {
                                    $msg = isset($response['msg']) ? $response['msg'] : $response['error'];
                                    return array('success' => false, 'message' => 'Product Id:' . $product_id . ':' . $msg);
                                } elseif (isset($response['error']) && $response['error']) {
                                        return array('success' => false, 'message' => 'Product Id:' . $product_id . ':' . $response['error']);
                                }
                            } else {
                                return array('success' => false, 'message' => $validation_error);
                            }
                        } else {
                            return array('success' => false, 'message' => 'Required Attribute are Missing : -'.$valid['message']);
                        }
                    } else {
                        continue;
                    }
                }
            }
        }
    }

    public function getProfileByProductId($product_id)
    {
        if ($product_id) {
            $result = Db::getInstance()->executeS("SELECT * FROM `" . _DB_PREFIX_ . "cedshopee_profile` cp LEFT JOIN `" . _DB_PREFIX_ . "cedshopee_profile_products` cpp on (cp.id = cpp.shopee_profile_id) WHERE cpp.product_id='" . $product_id . "'");
            if (isset($result) && count($result)) {
                return $result;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    // public function getProduct($product_id)
    // {
    //  $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
    //     $product = false;
    //     $result = Db::getInstance()->executeS("SELECT DISTINCT * FROM " . _DB_PREFIX_ . "product p LEFT JOIN " . _DB_PREFIX_ . "product_lang pl ON (p.id_product = pl.id_product) WHERE p.id_product = '" . (int)$product_id . "' AND pl.id_lang = '" . (int)$default_lang . "'");
    //     if (isset($result) && count($result)) {
    //      return $result;
    //     }
    // }

    public function getCedShopeeMappedProductData($product_id, $profile_info, $product)
    {
        $profile_info = $profile_info['0'];
        if ($product_id && isset($profile_info['default_mapping']) && $profile_info['default_mapping']) {
            $default_mapping = json_decode($profile_info['default_mapping'], true);
            if (!empty($default_mapping)) {
                $mapped_data = array();
                foreach ($default_mapping as $key => $value) {
                    // $defVal = explode('-', $val);
                    // $value = @$defVal[1];
                    if (isset($product[$value]) && $product[$value] && $key != 'days_to_ship') {
                        $mapped_data[$key] = $product[$value];
                    } elseif ($key == 'days_to_ship') {
                        $mapped_data[$key] = $value;
                    }
                }
                return $mapped_data;
            }
        } else {
            return false;
        }
    }

    public function getCedShopeeCategory($product_id, $profile_info)
    {
        $profile_info = $profile_info['0'];
        if ($product_id) {
            $shopee_category = false;
            if (isset($profile_info['shopee_category']) && $profile_info['shopee_category']) {
                $shopee_category = $profile_info['shopee_category'];
            }
            return json_decode($shopee_category, true);
        } else {
            return false;
        }
    }

    public function getCedShopeePrice($product_id, $product = array())
    {
        // $specialPrice = 0;
        // $res = Product::getPriceStatic(
        //  $product_id,
        //  true,
        //  null,
        //  6,
        //  null,
        //  false,
        //  true,
        //  1,
        //  false,
        //  null,
        //  null,
        //  null,
        //  null,
        //  true,
        //  true,
        //  null,
        //  true
        //  );

        $product_price = 0;
        if (isset($product['price']) && $product['price']) {
            $product_price = $product['price'];
        } else {
            $query_price = Db::getInstance()->executeS("SELECT `price` FROM `" . _DB_PREFIX_ . "product` WHERE `id_product` = '" . (int)$product_id . "'");

            if (isset($query_price) && count($query_price)) {
                $product_price = $query_price['0']['price'];
            }
        }

        $price = (float)$product_price;
        // if (($specialPrice > 0) && ($specialPrice < $price)) {
        //     $price = $specialPrice;
        // }

        $cedshopee_price_choice = trim(Configuration::get(
            'CEDSHOPEE_PRICE_VARIANT_TYPE'
        ));

        switch ($cedshopee_price_choice) {
            case 'default':
                $price = $price;
                break;

            case 'increase_fixed':
                $fixedIncement = trim(Configuration::get('CEDSHOPEE_PRICE_VARIANT_FIXED'));
                $price = $price + $fixedIncement;
                break;

            case 'decrease_fixed':
                $fixedIncement = trim(Configuration::get('CEDSHOPEE_PRICE_VARIANT_FIXED'));
                $price = $price - $fixedIncement;
                break;


            case 'increase_per':
                $percentPrice = trim(Configuration::get('CEDSHOPEE_PRICE_VARIANT_PER'));
                $price = (float)($price + (($price / 100) * $percentPrice));
                break;

            case 'decrease_per':
                $percentPrice = trim(Configuration::get('CEDSHOPEE_PRICE_VARIANT_PER'));
                $price = (float)($price - (($price / 100) * $percentPrice));
                break;

            default:
                return (float)$price;
                break;
        }
        return (float)$price;
    }

    public function getCedShopeeQuantity($product_id, $product = array())
    {
        $quantity = 0;
        if (isset($product['quantity']) && $product['quantity']) {
            $quantity = $product['quantity'];
        } elseif ($product_id) {
            $result = Db::getInstance()->executeS("SELECT `quantity` FROM `" . _DB_PREFIX_ . "product` WHERE `id_product` = '" . $product_id . "'");
            if (isset($result) && count($result)) {
                $quantity = $result['0']['quantity'];
            } else {
                $quantity = 0;
            }
        }
        return $quantity;
    }

    public function getCedShopeeAttribute($product_id, $profile_info, $product)
    {
        $db = Db::getInstance();
        $profile_info = $profile_info['0'];
        if ($product_id && isset($profile_info['profile_attribute_mapping']) && $profile_info['profile_attribute_mapping']) {
            $profile_attribute_mappings = json_decode($profile_info['profile_attribute_mapping'], true);
            $attribute_shopees = array();
            if ($profile_attribute_mappings) {
                foreach ($profile_attribute_mappings as $profile_attribute_mapping) {
                    $attribute_shopee = array();
                    if (isset($profile_attribute_mapping['store_attribute']) && $profile_attribute_mapping['store_attribute']) {
                        $type_array = explode('-', $profile_attribute_mapping['store_attribute']);
                        if (isset($type_array['0']) && ($type_array['0']=='option')) {
                            $options = array();
                            if (isset($profile_attribute_mapping['option'])) {
                                $options = array_filter($profile_attribute_mapping['option']);
                            }
                            $option_value = $this->getProductOptions($product_id, $type_array['1'], $profile_info['profile_language'], $options);
                            $attribute_shopee = array(
                            'attributes_id' =>(int) $profile_attribute_mapping['shopee_attribute'],
                            'value' => $option_value
                            );
                        } elseif (isset($type_array['0']) && ($type_array['0']=='attribute')) {
                            $attribute_value = $this->getProductAttributes($product_id, $type_array['1'], $profile_info['profile_language']);
                            $attribute_shopee = array(
                            'attributes_id' =>(int) $profile_attribute_mapping['shopee_attribute'],
                             'value' => $attribute_value
                             );
                        } elseif (isset($type_array['0']) && ($type_array['0']=='product')) {
                             $attribute_shopee = array(
                            'attributes_id' =>(int) $profile_attribute_mapping['shopee_attribute'],
                            'value' => $product[$type_array['1']]
                            );
                        } else {
                            if (!empty($profile_attribute_mapping['shopee_attribute']) && !empty($profile_attribute_mapping['store_attribute'])) {
                                $attribute_shopee = array(
                                 'attributes_id' =>(int) $profile_attribute_mapping['shopee_attribute'],
                                 'value' => $profile_attribute_mapping['store_attribute']
                                 );
                            }
                        }
                    } elseif (isset($profile_attribute_mapping['default_values']) && $profile_attribute_mapping['default_values']) {
                        $attribute_shopee = array(
                        'attributes_id' =>(int) $profile_attribute_mapping['shopee_attribute'],
                        'value' => $profile_attribute_mapping['default_values']
                        );
                    }
                    if (isset($attribute_shopee['value']) && !$attribute_shopee['value']) {
                        if (isset($profile_attribute_mapping['default_values']) && $profile_attribute_mapping['default_values']) {
                            $attribute_shopee = array(
                            'attributes_id' =>(int) $profile_attribute_mapping['shopee_attribute'],
                            'value' => $profile_attribute_mapping['default_values']);
                        }
                    }
                    $product_d = $db->executeS("SELECT * FROM `". _DB_PREFIX_ ."cedshopee_uploaded_products` WHERE product_id = ".(int)$product_id);
                    if (!empty($product_d) && $product_d['0']['product_attribute']) {
                        $product_attribute_d = json_decode($product_d['0']['product_attribute'], true);
                    }
                    $shoppee_selected_option = false;

                    $attributes_id = isset($attribute_shopee['attributes_id']) ? $attribute_shopee['attributes_id'] : '0';
                    if (isset($product_attribute_d[$attributes_id]) && isset($product_attribute_d[$attributes_id]['shopee_attribute']) && isset($product_attribute_d[$attributes_id]['default_values']) && $product_attribute_d[$attributes_id]['default_values']) {
                        $attribute_shopee['value'] = $product_attribute_d[$attribute_shopee['attributes_id']]['default_values'];
                    }

                    $attribute_shopees[] = $attribute_shopee;
                }
                $attribute_shopees = array_filter($attribute_shopees);
                return $attribute_shopees;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function getProductOptions($product_id, $store_attribute, $language_id, $attribute_shopee)
    {
        $product_option_data = '';

        if ($store_attribute) {
            if (is_numeric($store_attribute) && !empty($store_attribute)) {
                if (isset($attribute_shopee)) {
                    foreach ($attribute_shopee as $option_values) {
                        $product_option_value_query = Db::getInstance()->executeS("SELECT al.`id_attribute` AS option_value_id, al.`name`, a.`position` AS sort_order FROM `"._DB_PREFIX_."attribute` AS a LEFT JOIN `"._DB_PREFIX_."attribute_lang` AS al ON (al.id_attribute = a.id_attribute) WHERE al.`id_attribute` = '". (int)$option_values['store_attribute_id'] ."' AND al.`id_lang` = '". (int)$language_id ."' AND a.`id_attribute_group` = '". (int)$store_attribute ."' ");
                        if (count($product_option_value_query) && isset($option_values['shopee_attribute']) && $option_values['shopee_attribute']) {
                            $product_option_data = $option_values['shopee_attribute'];
                            break;
                        }
                    }
                }
            }
        }
        return $product_option_data;
    }

    public function getProductAttributes($product_id, $store_attribute, $language_id)
    {
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        $product_attribute_data = '';

        if ($product_id && $store_attribute) {
            $product_attribute_query = Feature::getFeature($default_lang, $store_attribute);
            // $product_attribute_query = Db::getInstance()->executeS("SELECT `text` FROM " . _DB_PREFIX_ . "feature_lang WHERE id_product = '" . (int)$product_id . "' AND id_attribute = '" . (int)$store_attribute . "' AND id_lang = '" . (int)$default_lang . "'");
            if (isset($product_attribute_query) && count($product_attribute_query) && isset($product_attribute_query['name']) && $product_attribute_query['name']) {
                    $product_attribute_data = $product_attribute_query['name'];
            }
        }
        return $product_attribute_data;
    }

    public function getLogistics($profile_info, $product_id = null)
    {
        $profile_info = $profile_info['0'];
        $logistics = array();
        $fromProductUploadTable = Db::getInstance()->executeS("SELECT * FROM `". _DB_PREFIX_ ."cedshopee_uploaded_products` WHERE product_id = ".(int)$product_id);

        if (isset($profile_info['logistics']) && !empty($profile_info['logistics'])) {
            $profile_logistics = json_decode($profile_info['logistics'], true);
            if (!empty($profile_logistics) && isset($profile_logistics['logistics'])) {
                foreach ($profile_logistics['logistics'] as $profile_logistic) {
                    $result = Db::getInstance()->executeS("SELECT * FROM `". _DB_PREFIX_ ."cedshopee_logistics` WHERE logistic_id='".trim($profile_logistic)."'");
                    if (isset($result) && count($result) && isset($result['fee_type']) && ($result['fee_type']=='CUSTOM_PRICE')) {
                        $logistics[] = array('logistic_id' => (int)$profile_logistic,'enabled' =>  (bool) $result['enabled'], 'is_free' =>  (bool) $profile_logistics['is_free'],'shipping_fee' => $profile_logistics['shipping_fee']);
                    } elseif ($profile_logistic) {
                        $logistics[] = array('logistic_id' => (int)$profile_logistic, 'is_free' =>  (bool) $profile_logistics['is_free'], 'enabled' =>  (bool) $profile_logistics['is_free']);
                    }
                }
            }
        }
        if (count($fromProductUploadTable)) {
            if (isset($fromProductUploadTable['logistics']) && !empty($fromProductUploadTable['logistics'])) {
                $product_logistics = @json_decode($fromProductUploadTable['logistics'], true);
                if (is_array($product_logistics['logistics']) && !empty($product_logistics['logistics'])) {
                    $logistics = array();
                    foreach ($product_logistics['logistics'] as $product_logistic) {
                        $result = Db::getInstance()->executeS("SELECT * FROM `". _DB_PREFIX_ ."cedshopee_logistics` where logistic_id='".trim($profile_logistic)."'");
                        if (isset($result) && count($result) && isset($result['fee_type']) && ($result['fee_type']=='CUSTOM_PRICE') && $product_logistic) {
                            $logistics[] = array('logistic_id' => (int)$product_logistic,'enabled' =>  (bool) $result['enabled'], 'is_free' =>  (bool) $product_logistics['is_free'],'shipping_fee' => (float)$product_logistics['shipping_fee']);
                        } elseif ($product_logistic) {
                            $logistics[] = array('logistic_id' => (int)$product_logistic, 'is_free' =>  (bool) $product_logistics['is_free'], 'enabled' =>  (bool) $product_logistics['is_free']);
                        }
                    }
                }
            }
        }
        return $logistics;
    }

    public function getWholesales($profile_info, $product_id = null)
    {
        $profile_info = $profile_info['0'];
        $wholesales = array();

        $uploadProductTable = Db::getInstance()->executeS("SELECT * FROM `"._DB_PREFIX_."cedshopee_uploaded_products` WHERE product_id = ".(int)$product_id);

        if (isset($profile_info['wholesale']) && !empty($profile_info['wholesale'])) {
            $profile_wholesale = json_decode($profile_info['wholesale'], true);
            if (!empty($profile_wholesale) && isset($profile_wholesale['wholesale_min'])) {
                $wholesales['min'] =(int) $profile_wholesale['wholesale_min'];
            }
            if (!empty($profile_wholesale) && isset($profile_wholesale['wholesale_max'])) {
                $wholesales['max'] = (int)$profile_wholesale['wholesale_max'];
            }
            if (!empty($profile_wholesale) && isset($profile_wholesale['wholesale_unit_price'])) {
                $wholesales['unit_price'] = (float)$profile_wholesale['wholesale_unit_price'];
            }
        }

        if (count($uploadProductTable)) {
            if (isset($uploadProductTable['wholesale']) && !empty($uploadProductTable['wholesale'])) {
                $product_wholesale = json_decode($uploadProductTable['wholesale'], true);
                $wholesales['min'] = (isset($product_wholesale['wholesale_min']) && !empty($product_wholesale['wholesale_min'])) ? (int)$product_wholesale['wholesale_min'] : 0;
                $wholesales['max'] = (isset($product_wholesale['wholesale_max']) && !empty($product_wholesale['wholesale_max'])) ? (int)$product_wholesale['wholesale_max'] : 0;
                $wholesales['unit_price'] = (isset($product_wholesale['wholesale_unit_price']) && !empty($product_wholesale['wholesale_unit_price'])) ? (int)$product_wholesale['wholesale_unit_price'] : 0;
            }
        }

        return array_filter($wholesales);
    }

    public function isVariantProduct($product_id, $default_lang)
    {
        $db = Db::getInstance();
        $productObject = new Product($product_id, false, $default_lang);
        $variants = $productObject->getAttributeCombinations($default_lang);
        if (isset($variants) && !empty($variants)) {
            return $variants;
        } else {
            return array();
        }
    }

    public function validateProduct($productToUpload, $category)
    {
        if (isset($productToUpload['attributes'])) {
            $required_attribute = array();
            $product_attribute = array();
            $Required_product_attribute = array();
            $result = Db::getInstance()->executeS("SELECT attribute_id,attribute_name FROM `". _DB_PREFIX_ ."cedshopee_attribute` WHERE category_id='".$category."' AND is_mandatory='1'");
               
            if (isset($result) && count($result)) {
                foreach ($result as $row) {
                    $required_attribute[] =  $row['attribute_id'];
                    $Required_product_attribute[$row['attribute_id']] = $row['attribute_name'];
                }
            }

            foreach ($productToUpload['attributes'] as $attribute) {
                  $product_attribute[] =  $attribute['attributes_id'];
            }
            $product_attribute = array_unique($product_attribute);
            $array_not_found = array_diff($required_attribute, $product_attribute);
            if (!empty($array_not_found)) {
                $name='';
                foreach ($array_not_found as $attribute_id) {
                    if (isset($Required_product_attribute[$attribute_id])) {
                        $name .= $Required_product_attribute[$attribute_id] . ',';
                    }
                }
                $name = rtrim($name, ',');
                return array('success' => false, 'message' =>$name);
            }
        }
            return array('success' => true, 'message' =>$productToUpload);
    }

    public function updateInventory($product_id, $data)
    {
        $CedShopeeLibrary = new CedShopeeLibrary;
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        $product = (array)new Product($product_id, true, $default_lang);
        $quantity = $this->getCedShopeeQuantity($product_id, $product);
        $shopee_item_id = $this->getShopeeItemId($product_id);
        $result =false;
        $variants = $this->isVariantProduct($product_id, $default_lang);
        if (!empty($variants)) {
            foreach ($variants as $key => $value) {
                $variation_data = Db::getInstance()->executeS("SELECT variation_id FROM `". _DB_PREFIX_ ."cedshopee_product_variations` WHERE `product_id` = '". (int)$product_id ."' AND `variation_sku` = '". pSQL($value['reference']) ."' ");
                $variation_id = isset($variation_data['0']['variation_id']) ? $variation_data['0']['variation_id'] : '0';
                $stock_data = array(
                    'stock' => (int)$value['quantity'],
                    'variation_id' => (int) $variation_id,
                    'item_id'=>(int)$shopee_item_id,
                    );
                $CedShopeeLibrary->log('items/update_variation_stock');
                $result = $CedShopeeLibrary->postRequest('items/update_variation_stock', $stock_data);
                $CedShopeeLibrary->log(json_encode($result));
            }
        } elseif ($shopee_item_id) {
            $CedShopeeLibrary->log('items/update_stock');
            $result = $CedShopeeLibrary->postRequest('items/update_stock', array('stock'=> (int)$quantity, 'item_id'=>(int)$shopee_item_id));
            $CedShopeeLibrary->log(json_encode($result));
        }
        return $result ;
    }

    public function updatePrice($product_id, $data)
    {
        $CedShopeeLibrary = new CedShopeeLibrary;
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        $product = (array)new Product($product_id, true, $default_lang);
        $price = $this->getCedShopeePrice($product_id, $product);
        $shopee_item_id = $this->getShopeeItemId($product_id);
        $result =false;
        $variants = $this->isVariantProduct($product_id, $default_lang);
        if (!empty($variants)) {
            foreach ($variants as $key => $value) {
                $variation_data = Db::getInstance()->executeS("SELECT variation_id FROM `". _DB_PREFIX_ ."cedshopee_product_variations` WHERE `product_id` = '". (int)$product_id ."' AND `variation_sku` = '". pSQL($value['reference']) ."' ");
                $variation_id = isset($variation_data['0']['variation_id']) ? $variation_data['0']['variation_id'] : '0';
                $price_data = array(
                    'price' => (float)$value['price'],
                    'variation_id' => (int)$variation_id,
                    'item_id'=>(int)$shopee_item_id,
                    );
                $CedShopeeLibrary->log('items/update_variation_price');
                $result = $CedShopeeLibrary->postRequest('items/update_variation_price', $price_data);
                $CedShopeeLibrary->log(json_encode($result));
            }
        } elseif ($shopee_item_id) {
            $CedShopeeLibrary->log('items/update_stock');
            $result = $CedShopeeLibrary->postRequest('items/update_price', array('price'=> (int)$price, 'item_id'=>(int)$shopee_item_id));
            $CedShopeeLibrary->log(json_encode($result));
        }
        return $result ;
    }

    public function getShopeeItemId($product_id = 0)
    {
        $db = Db::getInstance();
        if ($product_id) {
            // $sql = "DELETE FROM `". _DB_PREFIX_ ."cedshopee_uploaded_products` where shopee_item_id = 0";
            $db->delete(
                'cedshopee_uploaded_products',
                'shopee_item_id = 0'
            );
            $shopee_item_id = '';
            $sql = "SELECT `shopee_item_id` FROM `". _DB_PREFIX_ ."cedshopee_uploaded_products` WHERE `product_id`='".$product_id."' AND shopee_item_id > 0";
            $result = $db->executeS($sql);
            if ($result && count($result) && isset($result['0']['shopee_item_id'])) {
                $shopee_item_id = $result['0']['shopee_item_id'];
            }
            return $shopee_item_id;
        }
        return false;
    }
}
