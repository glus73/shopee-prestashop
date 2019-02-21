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

include_once _PS_MODULE_DIR_ . 'cedshopee/classes/CedShopeeLibrary.php';

class AdminCedShopeeReturnController extends ModuleAdminController
{
    public function __construct()
    {
        $this->db         = Db::getInstance();
        $this->bootstrap  = true;
        $this->table      = 'cedshopee_return';
        $this->identifier = 'id';
        $this->list_no_link = true;
        $this->addRowAction('view');
        $this->context = Context::getContext();

        // $this->bulk_actions = array(
        //  'remove' => array(
        //         'text' => 'Delete Discount',
        //         'icon' => 'icon-trash'
        //     ),
        // );

        $this->fields_list = array(
            'returnsn'       => array(
                'title' => 'Return ID',
                'type'  => 'text',
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ),
            'ordersn'     => array(
                'title' => 'Shopee Order ID',
                'type'  => 'text',
            ),
            'reason'     => array(
                'title' => 'Reason',
                'type'  => 'datetime',
            )
        );

        if (Tools::getIsset('method') &&
            (trim(Tools::getValue('method'))) == 'fetchReturn'
        ) {
            $this->fetchReturn();
        }

        if (Tools::getIsset('method') &&
            (trim(Tools::getValue('method'))) == 'createDispute'
        ) {
            $this->createDispute();
        }

        if (Tools::getIsset('method') &&
            (trim(Tools::getValue('method'))) == 'confirmReturn'
        ) {
            $this->confirmReturn();
        }
       
        parent::__construct();
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['fetch_return'] = array(
                'href' => self::$currentIndex . '&method=fetchReturn&token=' . $this->token,
                'desc' => $this->l('Fetch Return', null, null, false),
                'icon' => 'process-icon-download'
            );
            $this->page_header_toolbar_btn['delete_returns'] = array(
                'href' => self::$currentIndex . '&delete_returns=1&token=' . $this->token,
                'desc' => $this->l('Delete Returns', null, null, false),
                'icon' => 'process-icon-eraser'
            );
        } elseif ($this->display == 'view') {
            $this->page_header_toolbar_btn['create_dispute'] = array(
                'href' => self::$currentIndex . '&method=createDispute&token=' . $this->token,
                'desc' => $this->l('Create Dispute', null, null, false),
                'icon' => 'process-icon-new'
            );

            $this->page_header_toolbar_btn['confirm_return'] = array(
                'href' => self::$currentIndex . '&method=confirmReturn&token=' . $this->token,
                'desc' => $this->l('confirm', null, null, false),
                'icon' => 'process-icon-check'
            );
        }
        parent::initPageHeaderToolbar();
    }

    public function postProcess()
    {
        if (Tools::getIsset('delete_returns') && Tools::getValue('delete_returns')) {
            $res = $this->db->Execute("TRUNCATE TABLE " . _DB_PREFIX_ . "cedshopee_return");
            if ($res) {
                $this->confirmations[] = "Returns Deleted Successfully";
            } else {
                $this->errors[] = "Failed To Delete Returns";
            }
        }
        return parent::postProcess();
    }

    public function fetchReturn()
    {
        $CedshopeeLibrary = new CedshopeeLibrary;
        $params = array('pagination_entries_per_page' => 100, 'pagination_offset' => 0);
        $response = $CedshopeeLibrary->postRequest('returns/get', $params);
        if (!Tools::getIsset($response['error']) && empty($response['error'])) {
            if ($response) {
                $returnResponse = $this->addShopeeReturns($response);
                if (isset($returnResponse) && $returnResponse == 1) {
                    $this->confirmations[] = 'Return fetched successfully';
                } else {
                    $this->errors[] = 'Error while fetching returns';
                }
            } else {
                $this->errors[] = 'No response from Shopee';
            }
        } elseif (!empty($response['error'])) {
            $this->errors[] = $response['error'];
        } elseif (!empty($response['message'])) {
            $this->errors[] = $response['message'];
        } else {
            $this->errors[] = 'No response from Shopee';
        }
    }

    public function addShopeeReturns($return_data = array())
    {
        $this->db->insert(
            'cedshopee_return',
            array(
                    'reason' => pSQL($return_data['reason']),
                    'text_reason' => pSQL($return_data['text_reason']),
                    'returnsn' => pSQL($return_data['returnsn']),
                    'ordersn' => pSQL($return_data['ordersn']),
                    'return_data' => pSQL($return_data),
                    'status' => pSQL($return_data['status']),
                    'dispute_request' => '',
                    'dispute_response' => ''
                )
        );
            
        return true;
    }

    public function renderView()
    {
        $return_id = Tools::getValue('id');
        if (!empty($return_id)) {
            $result = $this->db->executeS("SELECT * FROM `". _DB_PREFIX_ ."cedshopee_return` WHERE `id` = '". $return_id ."' ");
            $returnData = $result[0];
            if (!empty($returnData['return_data'])) {
                $return_data = json_decode($returnData['return_data'], true);
                // $return_data = $return_data['returns'];
                // $images = array();
                // if(!empty($return_data['images'])) {
                //  $images = $return_data['images'];
                // }
                // $user = array();
                // if(Tools::getIsset($return_data['user']) && !empty($return_data['user'])) {
                //  $user = $return_data['user'];
                // }
       //          // echo '<pre>'; print_r($return_data['user']); die;
                // $item = array();
                // if(Tools::getIsset($return_data['item']) && !empty($return_data['item'])) {
                //  $item = $return_data['item'];
                // }
                $returnsn = 0;
                if (Tools::getIsset($return_data['returns']['returnsn']) && !empty($return_data['returns']['returnsn'])) {
                    $returnsn = $return_data['returns']['returnsn'];
                }
                
                $this->context->smarty->assign(array(
                    'images' => $return_data['returns']['images'],
                    'user' => $return_data['returns']['user'],
                    'item' => $return_data['returns']['item'],
                    'returnsn' => $returnsn
                    ));

                unset($return_data['returns']['images']);
                unset($return_data['returns']['user']);
                unset($return_data['returns']['item']);

                $this->context->smarty->assign(array(
                    'return_data'  => $return_data
                    ));
            }
            $this->context->smarty->assign('token', $this->token);

            $returnView = $this->context->smarty->fetch(
                _PS_MODULE_DIR_ .'cedshopee/views/templates/admin/return/return_view.tpl'
            );
            parent::renderView();
            return $returnView;
        }
    }

    public static function getReturnReasons()
    {
        return array(
            'NON_RECEIPT'=> 'NON_RECEIPT',
            'OTHER'=> 'OTHER',
            'NOT_RECEIVED'=> 'NOT_RECEIVED',
            'UNKNOWN'=> 'UNKNOWN',
            );
    }

    public function createDispute()
    {
        $cedshopee = new CedshopeeLibrary;
        $return_id = Tools::getValue('id');
        $request = Tools::getAllValues();
        if (!empty($return_id)) {
            $returnsn = $this->db->getValue("SELECT `returnsn` FROM `". _DB_PREFIX_ ."cedshopee_return` WHERE `id` = '". $return_id ."' ");
            if (!empty($returnsn)) {
                $url = 'returns/get';
                $params = array(
                    'returnsn' => $request['returnsn'],
                    'email' => $request['email'],
                    'dispute_reason' => $request['dispute_reason'],
                    'dispute_text_reason' => $request['dispute_text_reason']
                    );
                $response = $cedshopee->postRequest($url, $params);
                if (!Tools::getIsset($response['error']) && empty($response['error'])) {
                    $returnDispute = $this->saveReturnDispute($return_id, $request, $response);
                    if (isset($returnDispute) && $returnDispute == 1) {
                        $this->confirmations[] = 'Dispute returned successfully';
                    } else {
                        $this->errors[] = 'Error while creating dispute';
                    }
                } elseif (!empty($response['error'])) {
                    $this->errors[] = $response['error'];
                } elseif (!empty($response['message'])) {
                    $this->errors[] = $response['message'];
                } else {
                    $this->errors[] = 'No response from Shopee';
                }
            }
        }
        $this->context->smarty->assign(array(
            'id'  => $return_id,
            'token' => $this->token,
            'controllerUrl' => $this->context->link->getAdminLink('AdminCedShopeeReturn'),
            ));

        $returnDispute = $this->context->smarty->fetch(
            _PS_MODULE_DIR_ .'cedshopee/views/templates/admin/return/return_dispute.tpl'
        );
        return $returnDispute;
    }

    public function saveReturnDispute($id, $request, $response)
    {
        $this->db->update(
            'cedshopee_return',
            array(
                'dispute_request' => pSQL(json_encode($request)),
                'dispute_response' => pSQL(json_encode($response))
                ),
            'id='.(int)$id
        );
        return true;
    }

    public function getDisputeDetails($id)
    {
        $result = $this->db->getValue("SELECT `dispute_request` FROM `". _DB_PREFIX_ ."cedshopee_return` WHERE `id` = '". (int) $id ."' ");
        $dispute_request = json_decode($result['dispute_request'], true);
        if (!empty($dispute_request)) {
            return $dispute_request;
        } else {
            return false;
        }
    }

    public function confirmReturn()
    {
        $returnsn = Tools::getValue('returnsn');
        $return_id = Tools::getValue('id');
        if (!empty($returnsn) && !empty($return_id)) {
            $CedshopeeLibrary = new CedshopeeLibrary;
            $params = array('returnsn' => $returnsn);
            $response = $CedshopeeLibrary->postRequest('returns/get', $params);
            $response = $response['returns'];
            if (!Tools::getIsset($response['error']) && empty($response['error'])) {
                $returnResponse = $this->addShopeeReturns($response);
                if (isset($returnResponse) && $returnResponse == 1) {
                    $this->confirmations[] = 'Return fetched successfully';
                } else {
                    $this->errors[] = 'Error while confirming returns';
                }
            } elseif (!empty($response['error'])) {
                $this->errors[] = $response['error'];
            } elseif (!empty($response['message'])) {
                $this->errors[] = $response['message'];
            } else {
                $this->errors[] = 'No response from Shopee';
            }
        }
    }
}
