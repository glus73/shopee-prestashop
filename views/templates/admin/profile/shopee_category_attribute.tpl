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
        <i class="icon icon-tag"></i> {l s='Shopee Category & Attributes' mod='cedshopee'}
    </div>
    <div class="panel-body">
       <!--  -->
                <div class="form-group row">
                    <label class="control-label col-lg-4">
                        <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="" data-original-title="{l s='Shopee Category' mod='cedshopee'}">
                        {l s='Shopee Category' mod='cedshopee'}
                        </span>
                    </label>
                    <div class="col-lg-8">
                        <input type="text" name="shopee_category" id="shopee_category" {if isset($general['shopee_category_name']) && !empty($general['shopee_category_name'])} value="{$general['shopee_category_name']}" {else} value="" {/if} style="width: 96%; padding: 1%; margin-top: 8px; margin-bottom: 8px;" />
                        
                        <input type="hidden" name="shopee_category_id" {if isset($general['shopee_category']) && !empty($general['shopee_category'])} value="{$general['shopee_category']}" {else} {foreach $shopeeCategories as $shopeeCategory} value="{$shopeeCategory['category_id']}" {/foreach} {/if} />
                    </div>
                </div>
            <!--  -->
        <div id="attribute_section">
            <table class="table table-bordered">
                <thead>
                    <th class="col-sm-12 col-md-3 col-lg-3 center">Shopee Attributes</th>
                    <th class="col-sm-12 col-md-3 col-lg-3 center">Set Defult Value</th>
                    <th class="col-sm-12 col-md-3 col-lg-3 center">Map With Store Attributes</th>
                </thead>
                <tbody id="mapping_values">

                </tbody>
            </table>
        </div>
    </div>
</div>
{$token=Tools::getAdminTokenLite('AdminCedShopeeProfile')}
{$profile_id = Tools::getValue('id')}

<div id="overlay">
    <div id="text"> LOADING.....</div>
</div>

<script type="text/javascript">
    $('input[name=\'shopee_category\']').autocomplete({
        delay: 500,
        source: function(request, response) {
            $.ajax({
                url: 'index.php?controller=AdminCedShopeeProfile&method=ajaxProcessAutocomplete&token={$token|escape:'htmlall':'UTF-8'}',
                data: {
                    'action' : 'autocomplete',
                    'ajax' : true,
                    'filter_name' : encodeURIComponent(request.term)
                },
                dataType: 'json',
                success: function(json) {
                   
                    response($.map(json, function(item) {
                         // console.log(item);
                        return {
                            label: item.name,
                            value: item.category_id
                        }
                    }));
                }
            });
        },
        select: function(event, ui) {
            // console.log(ui.item);
            $('input[name=\'shopee_category\']').attr('value', ui.item.label);
            $('input[name=\'shopee_category_id\']').attr('value', ui.item.value);
            fetchShopeeAttributes(ui.item.value);
            return false;
        },
        focus: function(event, ui) {
            return false;
        }
    });

    function fetchShopeeAttributes(category_id) {
        // var category_id = JSON.stringify(category_id);
        // console.log(category_id);
        $.ajax({
            url: 'index.php?controller=AdminCedShopeeProfile&method=ajaxProcessAttributesByCategory&token={$token|escape:'htmlall':'UTF-8'}',
            data: {
                'action' : 'attributesByCategory',
                'ajax': true,
                'category_id': category_id,
                'profile_id': '{$profile_id|escape:'htmlall':'UTF-8'}'
            },
            cache: false,
            beforeSend: function() {
                document.getElementById('overlay').style.display = 'block';
                $('#cancel-plan, #revise-plan').attr('disabled', true);
                $('#cancel-plan').after('<span class="laybuy-loading fa fa-spinner" style="margin-left:2px"></span>');
            },
            complete: function() {
                document.getElementById('overlay').style.display = 'none';
                $('#cancel-plan, #revise-plan').attr('disabled', false);
                $('.laybuy-loading').remove();
            },
            success: function(json) {
                console.log(json);
                document.getElementById('mapping_values').innerHTML=json;
            },
            error: function(xhr, ajaxOptions, thrownError) {
                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    }
    function toggleOptions(panel_id) {
        var panel = document.getElementById('panel'+panel_id);
        var store_attribute_id = $('select[name="profile_attribute_mapping['+panel_id+'][store_attribute]"]').val();
        var shopee_attribute_id = $('select[name="profile_attribute_mapping['+panel_id+'][shopee_attribute]"]').val();

        var show_option_mapping = $('select[name="profile_attribute_mapping['+panel_id+'][store_attribute]"]').find('option:selected');
        if(show_option_mapping)
            show_option_mapping = show_option_mapping.attr("show_option_mapping");
       
        if(store_attribute_id && shopee_attribute_id && parseInt(show_option_mapping)){
            if ($(panel).css('display') == 'none') {
                $(panel).show();
            } else {
                $(panel).hide();
            }
        } else {
            if(!store_attribute_id)
            alert("Please Select Attribute First");
            if(!shopee_attribute_id)
            alert("Please Select Shopee Attribute First");
            if(show_option_mapping){
                alert("Option Mapping not needed.");
            }
        }
    }
    function getStoreOptions(data) {
        if (data.value.length > 0) {
            var id = data.getAttribute('data-id');
        
            $('input[name="profile_attribute_mapping['+id+'][option][store_attribute]"]').autocomplete({
                delay: 500,
                source: function (request, response) {
                    var name = encodeURIComponent(request.term);
                    var attribute_group_id = $('select[name="profile_attribute_mapping['+id+'][store_attribute]"]').val();
                    $.ajax({
                        url: 'index.php?controller=AdminCedShopeeProfile&method=ajaxProcessGetStoreOptions&token={$token|escape:'htmlall':'UTF-8'}',
                        data: {
                            'action': 'getStoreOptions',
                            'ajax': true,
                            'filter_name': name,
                            'catId': $('[name="shopee_category_id"]').val(),
                            'attribute_group_id': attribute_group_id
                        },
                        dataType: 'json',
                        success: function (json) {
                            response($.map(json, function (item) {
                                if(attribute_group_id=='product-manufacturer_id'){
                                    return {
                                        label: item.name,
                                        value: item.attribute_group_id
                                    }
                                } else {
                                    return {
                                        label: item.name,
                                        value: item.option_value_id
                                    }
                                }
                                
                            }));
                        }
                    });
                },
                select: function (event, ui) {
                    $('input[name="profile_attribute_mapping['+id+'][option][store_attribute]"]').attr('value',ui.item.label);
                    $('input[name="profile_attribute_mapping['+id+'][option][store_attribute_id]"]').attr('value',ui.item.value);
                    return false;
                },
                focus: function (event, ui) {
                    return false;
                }
            });
        }
    }
    window.onload = function () {
        if($('[name="shopee_category_id"]') && $('[name="shopee_category_id"]').val()){
            var category_id = $('[name="shopee_category_id"]').val();
            fetchShopeeAttributes(category_id);
        }
    }
    function addAttribute(c_object,attribute_id) {
        alert(attribute_id);
        var attribute_row = $("#option_mapping"+attribute_id+" tr").length-1;
        alert(attribute_row);
        var store_attribute = $('input[name="profile_attribute_mapping['+attribute_id+'][option][store_attribute]"]').val();
        var shopee_attribute = $('input[name="profile_attribute_mapping['+attribute_id+'][option][shopee_attribute]"]').val();
        var store_attribute_id = $('input[name="profile_attribute_mapping['+attribute_id+'][option][store_attribute_id]"]').val();
        html = '<tr id="attribute-row' + attribute_row +'">';
        html += '<td class="col-sm-12 col-md-3 col-lg-3 left"><input type="text" name="profile_attribute_mapping['+attribute_id+'][option]['+attribute_row+'][store_attribute]" value="'+store_attribute+'" /><input type="hidden" name="profile_attribute_mapping['+attribute_id+'][option]['+attribute_row+'][store_attribute_id]" value="'+store_attribute_id+'" /></td>';
        html += '<td class=" col-sm-12 col-md-3 col-lg-3left"><input type="text" name="profile_attribute_mapping['+attribute_id+'][option]['+attribute_row+'][shopee_attribute]" value="'+shopee_attribute+'" /></td>';
        html += '<td class="col-sm-12 col-md-3 col-lg-3 left"><a onclick="$(\'#attribute-row' + attribute_row + '\').remove();" class="button"><?php echo "Remove"; ?></a></td>';
        html += '</tr>';         
        $(c_object).parent().parent().parent().parent().children('thead').after(html)
        attribute_row++;
        $('input[name="profile_attribute_mapping['+attribute_id+'][option][store_attribute]"]').val("");
        $('input[name="profile_attribute_mapping['+attribute_id+'][option][shopee_attribute]"]').val("");
        $('input[name="profile_attribute_mapping['+attribute_id+'][option][store_attribute_id]"]').val("");
    }
    function getBrand(data)
    {
        if (data.value.length > 0) {
            var id = data.getAttribute('data-id');
            $('input[name="profile_attribute_mapping['+id+'][default_values]"]').autocomplete({
                delay: 500,
                source: function (request, response) {
                    var name = encodeURIComponent(request.term);
                    $.ajax({
                        url: 'index.php?controller=AdminCedShopeeProfile&method=ajaxProcessBrandAuto&token={$token|escape:'htmlall':'UTF-8'}',
                        data: {
                            'action': 'brandAuto',
                            'ajax': true,
                            'filter_name': name,
                            'catId': $('[name="shopee_category_id"]').val(),
                            'attribute_id': id
                        },
                        dataType: 'json',
                        success: function (json) {
                            response($.map(json, function (item) {
                                return {
                                    label: item,
                                    value: item
                                }
                            }));
                        }
                    });
                },
                select: function (event, ui) {
                    $('input[name="profile_attribute_mapping['+id+'][default_values]"]').attr('value',ui.item.label);
                    $('input[name="profile_attribute_mapping['+id+'][default_value_id]"]').attr('value',ui.item.label);
                    return false;
                },
                focus: function (event, ui) {
                    return false;
                }
            });
        }
    }

    function getOptions(data)
    {
        if (data.value.length > 0) {
            var id = data.getAttribute('data-id');
          
            $('input[name="profile_attribute_mapping['+id+'][option][shopee_attribute]"]').autocomplete({
                delay: 500,
                source: function (request, response) {
                    var name = encodeURIComponent(request.term);
                    $.ajax({
                        url: 'index.php?controller=AdminCedShopeeProfile&method=ajaxProcessBrandAuto&token={$token|escape:'htmlall':'UTF-8'}',
                        data: {
                            'action': 'brandAuto',
                            'ajax': true,
                            'filter_name': name,
                            'catId': $('[name="shopee_category_id"]').val(),
                            'attribute_id': id
                        },
                        dataType: 'json',
                        success: function (json) {
                            response($.map(json, function (item) {
                                return {
                                    label: item,
                                    value: item
                                }
                            }));
                        }
                    });
                },
                select: function (event, ui) {
                    $('input[name="profile_attribute_mapping['+id+'][option][shopee_attribute]"]').attr('value',ui.item.label);
                    return false;
                },
                focus: function (event, ui) {
                    return false;
                }
            });
        }
    }
</script>