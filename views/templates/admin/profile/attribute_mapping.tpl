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

{if isset($attributes) && !empty($attributes)}
    {foreach $attributes as $attribute}
        <tr>
            <td class="col-sm-12 col-md-3 col-lg-3 left">
                {if isset($attribute['is_mandatory']) && $attribute['is_mandatory']}
                    <span class="required">*</span>
                    <input type="hidden" name="profile_attribute_mapping[{$attribute['attribute_id']}][is_mandatory]"
                           value="1"/>
                {else}
                    <input type="hidden" name="profile_attribute_mapping[{$attribute['attribute_id']}][is_mandatory]"
                           value="0"/>
                {/if}
                <input type="hidden" name="profile_attribute_mapping[{$attribute['attribute_id']}][attribute_type]"
                       value="{$attribute['attribute_type']}"/>
                <input type="hidden" name="profile_attribute_mapping[{$attribute['attribute_id']}][input_type]"
                       value="{$attribute['input_type']}"/>

                <select name="profile_attribute_mapping[{$attribute['attribute_id']}][shopee_attribute]"
                        class="col-sm-12 col-md-8 col-lg-8">
                    {if isset($attribute['is_mandatory']) && !$attribute['is_mandatory']}
                        <option value=""></option>
                    {/if}
                    {foreach $attributes as $attribute_option}
                        <option value="{$attribute_option['attribute_id']}"
                                {if isset($attribute['shoppee_selected_option']) && $attribute_option['attribute_id'] == $attribute['shoppee_selected_option']}
                                    selected="selected"
                                {elseif $attribute['is_mandatory'] && ($attribute_option['attribute_id'] == $attribute['attribute_id'])}
                                    selected="selected"
                                {/if}
                        >{$attribute_option['attribute_name']}</option>
                    {/foreach}

                </select>
            </td>
            <td>
                <input type="text" class="col-sm-12 col-md-8 col-lg-8 text-left"
                       name="profile_attribute_mapping[{$attribute['attribute_id']}][default_values]"
                       onkeyup="getBrand(this)"
                       data-id="{$attribute['attribute_id']}"
                       {if isset($attribute['default_values_selected'])}
                           value="{$attribute['default_values_selected']}"
                           {else}
                           value=""
                       {/if}

                >
                <input type="hidden" class="text-left"
                       name="profile_attribute_mapping[{$attribute['attribute_id']}][default_value_id]"
                        {if isset($attribute['default_values_id_selected'])}
                            value="{$attribute['default_values_id_selected']}"
                        {else}
                            value=""
                        {/if}
                >
            </td>
            <td class="col-sm-12 col-md-3 col-lg-3 left">
                {if (( $attribute['attribute_id'] != 9463) && in_array($attribute['input_type'], array('DROP_DOWN', 'COMBO_BOX')))}
                    <select id="profile_attribute_mapping[{$attribute['attribute_id']}][store_attribute]" name="profile_attribute_mapping[{$attribute['attribute_id']}][store_attribute]" class="col-sm-12 col-md-8 col-lg-8">
                        <option value="">Select Mapping</option>
                        <optgroup label="Store Option">
                            {foreach $options as $option}
                                <option show_option_mapping="1" value="option-{$option['id_attribute_group']}"
                                        {if isset($attribute['store_selected_option']) && "option-{$option['id_attribute_group']}" == $attribute['store_selected_option']}
                                            selected="selected"
                                        {/if}
                                >{$option['name']}</option>
                            {/foreach}
                        </optgroup>
                        <optgroup label="Store Attributes">
                            {foreach $results as $result}
                                <option show_option_mapping="0" value="attribute-{$result['id_feature']}"
                                        {if isset($attribute['store_selected_option']) && "attribute-{$result['id_feature']}" == $attribute['store_selected_option']}
                                            selected="selected"
                                        {/if}
                                >{$result['name']}</option>
                            {/foreach}
                        </optgroup>
                        <optgroup label="Product Fields">
                            {foreach $productFields as $productField}
                                <option show_option_mapping="{$productField['show_option_mapping']}" value="product-{$productField['Field']}"
                                        {if isset($attribute['store_selected_option']) && "product-{$productField['Field']}" == $attribute['store_selected_option']}
                                            selected="selected"
                                        {/if}
                                >{$productField['Field']}</option>
                            {/foreach}
                        </optgroup>
                    </select>
                    <a style="margin-left:1%; font-weight:bold;" class="center button" onclick="toggleOptions('{$attribute['attribute_id']}')">Map Option(s)</a>
                    <div style="display:none;" id="panel{$attribute['attribute_id']}">
                        <table class="table table-bordered" id="option_mapping{$attribute['attribute_id']}">
                            <thead>
                            <tr>
                                <td class="col-sm-12 col-md-4 col-lg-4 center"><strong>Store Option</strong></td>
                                <td class="col-sm-12 col-md-4 col-lg-4 center"><strong>Shopee Option</strong></td>
                            </tr>
                            <tr>
                                <td>
                                    <input type="text" class="col-sm-12 col-md-8 col-lg-8 text-left" name="profile_attribute_mapping[{$attribute['attribute_id']}][option][store_attribute]" value="" onkeyup="getStoreOptions(this)" data-id="{$attribute['attribute_id']}">
                                    <input type="hidden" class="text-left" name="profile_attribute_mapping[{$attribute['attribute_id']}][option][store_attribute_id]"/>
                                </td>
                                <td>
                                    <input type="text" class="col-sm-12 col-md-8 col-lg-8 text-left" name="profile_attribute_mapping[{$attribute['attribute_id']}][option][shopee_attribute]" onkeyup="getOptions(this)" data-id="{$attribute['attribute_id']}">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-primary pull-right" onclick="addAttribute(this,'{$attribute["attribute_id"]}');" >Add Mapping</button>
                                </td>
                            </tr>
                            </thead>
                            {if isset($attribute['mapped_options']) && !empty($attribute['mapped_options'])}
                                {foreach $attribute['mapped_options'] as $key_p => $value}
                                    <tr id="attribute-row{$attribute['attribute_id']}{$key_p}">
                                        <td>
                                            <input type="text" class="col-sm-12 col-md-8 col-lg-8 text-left" name="profile_attribute_mapping[{$attribute["attribute_id"]}][option][{$key_p}][store_attribute]" value="{$value['store_attribute']}"/>
                                            <input type="hidden" class="text-left" name="profile_attribute_mapping[{$attribute["attribute_id"]}][option][{$key_p}][store_attribute_id]" value="{$value['store_attribute_id']}"/>
                                        </td>
                                        <td>
                                            <input type="text" class="col-sm-12 col-md-8 col-lg-8 text-left" name="profile_attribute_mapping[{$attribute["attribute_id"]}][option][{$key_p}][shopee_attribute]" value="{$value['shopee_attribute']}">
                                        </td>
                                        <td>
                                            <a type="button" onclick="$('#attribute-row{$attribute['attribute_id']}{$key_p}').remove();" class="btn btn-danger pull-right"> Remove</a>
                                        </td>
                                    </tr>
                                {/foreach}
                            {/if}
                        </table>
                    </div>
                    {else}
                    {if (isset($mapped_attributes_options[{$attribute['attribute_id']}]['store_attribute']))}
                        <input type="text" value="{$mapped_attributes_options[{$attribute["attribute_id"]}]['store_attribute']}" class="col-sm-12 col-md-8 col-lg-8 text-left" name="profile_attribute_mapping[{$attribute["attribute_id"]}][store_attribute]" onkeyup="getBrand(this)" data-id="{$attribute["attribute_id"]}">
                    {else}
                        <input type="text" class="col-sm-12 col-md-8 col-lg-8 text-left" name="profile_attribute_mapping[{$attribute["attribute_id"]}][store_attribute]" onkeyup="getBrand(this)" data-id="{$attribute["attribute_id"]}">
                    {/if}
                {/if}
            </td>
        </tr>
    {/foreach}
{/if}