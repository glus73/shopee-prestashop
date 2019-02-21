<!--
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
-->

<div class="panel">
    <div class="panel-heading">
        <i class="icon icon-tag"></i> {l s='Logistics' mod='cedshopee'}
    </div>
    <div class="panel-body">
    
        {if isset($shopeeLogistics) && count($shopeeLogistics)}
            {foreach $shopeeLogistics as $logistics}
                <div class="form-group row">
                    <label class="control-label col-lg-4">
                        <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="" data-original-title="{$logistics['label']|escape:'htmlall':'UTF-8'}">
                        {l s= {$logistics['label']|escape:'htmlall':'UTF-8'} mod='cedshopee'}
                        </span>
                    </label>
                    <div class="col-lg-8">
                        {if $logistics['type'] == 'text' && $logistics['name'] == 'logistics'}
                          <div class="well well-sm" style="height:150px; overflow: auto;">
                            {foreach $logistics_list as $logistic }
                              <div class="">
                                {if isset($selectedLogistics) && !empty($selectedLogistics) && isset($selectedLogistics['logistics'])&& is_array($selectedLogistics['logistics']) && in_array($logistic['logistic_id'], $selectedLogistics['logistics']) }
                                <input type="checkbox" name="shopeeLogistics[{$logistics['name']|escape:'htmlall':'UTF-8'}][]" value="{$logistic['logistic_id']}" checked="checked"/>
                                {$logistic['logistic_name']}
                                {else}
                                <input type="checkbox" name="shopeeLogistics[{$logistics['name']|escape:'htmlall':'UTF-8'}][]" value="{$logistic['logistic_id']}"/>
                                {$logistic['logistic_name']}
                                {/if}
                              </div>
                            {/foreach}
                          </div>
                        {/if}
                        
                        {if $logistics['type'] == 'switch'}
                            <span class="switch prestashop-switch fixed-width-lg">
                                <input type="radio" name="shopeeLogistics[{$logistics['name']|escape:'htmlall':'UTF-8'}]" id="free_active_on" value="1" {if isset($selectedLogistics) && count($selectedLogistics) && isset($selectedLogistics[{$logistics['name']}]) && $selectedLogistics[{$logistics['name']}] == '1'} checked="checked" {/if} />
                                <label for="free_active_on">{l s='Enable' mod='cedshopee'}</label>
                                <input type="radio" name="shopeeLogistics[{$logistics['name']|escape:'htmlall':'UTF-8'}]" id="free_active_off" value="0" {if isset($selectedLogistics) && count($selectedLogistics)  && isset($selectedLogistics[{$logistics['name']}]) && $selectedLogistics[{$logistics['name']}] == '1'} {else} checked="checked" {/if} />
                                <label for="free_active_off">{l s='Disable' mod='cedshopee'}</label>
                                <a class="slide-button btn"></a>
                            </span>
                        {/if}
                        {if $logistics['type'] == 'text'  && $logistics['name'] == 'shipping_fee'}
                            <input type="text" name="shopeeLogistics[{$logistics['name']|escape:'htmlall':'UTF-8'}]" class="" {if isset($selectedLogistics) && count($selectedLogistics)} value="{$selectedLogistics[{$logistics['name']}]}" {else} value="" {/if}>
                        {/if}
                        <p class="help-block">
                            {$logistics['description']|escape:'htmlall':'UTF-8'}
                        </p>
                    </div>
                </div>
            {/foreach}
        {/if}
    </div>
</div>

<div class="panel">
    <div class="panel-heading">
        <i class="icon icon-tag"></i> {l s='Wholesale' mod='cedshopee'}
    </div>
    <div class="panel-body">
        {if isset($shopeeWholesale) && count($shopeeWholesale)}
            {foreach $shopeeWholesale as $wholesale}
                <div class="form-group row">
                    <label class="control-label col-lg-4">
                        <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="" data-original-title="{$wholesale['label']|escape:'htmlall':'UTF-8'}">
                        {l s= {$wholesale['label']|escape:'htmlall':'UTF-8'} mod='cedshopee'}
                        </span>
                    </label>
                    <div class="col-lg-8">
                        {if $wholesale['type'] == 'text'}
                            <input type="text" name="shopeeWholesale[{$wholesale['name']|escape:'htmlall':'UTF-8'}]" class="" {if isset($selectedWholesale[{$wholesale['name']}]) && !empty($selectedWholesale[{$wholesale['name']}])} value=" {$selectedWholesale[{$wholesale['name']}]}" {else} value="" {/if}>
                        {/if}
                    </div>
                </div>
            {/foreach}
        {/if}
    </div>
</div>