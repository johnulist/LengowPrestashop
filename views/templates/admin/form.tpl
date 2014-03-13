<script type="text/javascript" src="/modules/lengow/views/js/admin.js"></script>

{if isset($display_error)}
    {if $display_error}
        <div class="error">{l s='An error occured during the form validation' mod='lengow'}</div>
    {else}
        <div class="conf">{l s='Configuration updated' mod='lengow'}</div>
    {/if}
{/if}

<form id="_form" class="defaultForm lengow" action="{$smarty.server.REQUEST_URI|escape:'htmlall':'UTF-8'}" method="post" enctype="multipart/form-data" >
    <fieldset id="fieldset_0">
        <legend>{l s='Check configuration' mod='lengow'}</legend>
        <label>{l s='Checklist' mod='lengow'}</label>							
        <div class="margin-form">
            {$checklist}
        </div>
        <div class="clear"></div>
    </fieldset>
    <br />
    <fieldset id="fieldset_1">
        <legend>{l s='Account' mod='lengow'}</legend>
        <label>{l s='Customer ID' mod='lengow'}</label>							
        <div class="margin-form">
            <input type="text" name="lengow_customer_id" id="lengow_customer_id" value="{$lengow_customer_id}" class="" size="20" /> <sup>*</sup>
        </div>
        <div class="clear"></div>
        <label>{l s='Group ID' mod='lengow'}</label>							
        <div class="margin-form">
            <input type="text" name="lengow_group_id" id="lengow_group_id" value="{$lengow_group_id}" class="" size="20" /> <sup>*</sup> 
        </div>
        <div class="clear"></div>
        <label>{l s='Token API' mod='lengow'}</label>							
        <div class="margin-form">
            <input type="text" name="lengow_token" id="lengow_token" value="{$lengow_token}" class="" size="32" /> <sup>*</sup>
        </div>
        <div class="clear"></div>
        <div class="small"><sup>*</sup> {l s='Required field' mod='lengow'}</div>
    </fieldset>
    <br />
    <fieldset id="fieldset_2">
        <legend>{l s='Security' mod='lengow'}</legend>
        <label>{l s='Authorized IP' mod='lengow'}</label>
        <div class="margin-form">
            <input type="text" name="lengow_authorized_ip" id="lengow_authorized_ip" value="{$lengow_authorized_ip}" class="" size="100" /> 
        </div>
        <div class="clear"></div>
        <div class="small"><sup>*</sup> {l s='Required field' mod='lengow'}</div>
    </fieldset>
    <br />
    <fieldset id="fieldset_3"> 
        <legend>{l s='Tracking' mod='lengow'}</legend>
        <label>{l s='Tracker type choice' mod='lengow'}</label>
        <div class="margin-form">
            <select name="lengow_tracking" class="" id="lengow_tracking">
                {foreach from=$options.trackers item=option}
                    <option value="{$option->id}"{if $option->id == $lengow_tracking} selected="selected"{/if}>{$option->name}</option>
                {/foreach}
            </select>
        </div>
        <div class="clear"></div>
        <div class="small"><sup>*</sup> {l s='Required field' mod='lengow'}</div>
    </fieldset>
    <br />
    <fieldset id="fieldset_4">
        <legend>{l s='Export parameters' mod='lengow'}</legend>
        <label>{l s='Export all products' mod='lengow'}</label>
        <div class="margin-form">
            <input type="radio"	name="lengow_export_all" id="active_on" value="1" {if $lengow_export_all == 1} checked="checked"{/if} />
            <label class="t" for="active_on">
                <img src="../img/admin/enabled.gif" alt="{l s='Enable'}" title="{l s='Enable'}" />
            </label>
            <input type="radio"	name="lengow_export_all" id="active_off" value="0" {if $lengow_export_all == 0} checked="checked"{/if} />
            <label class="t" for="active_off">
                <img src="../img/admin/disabled.gif" alt="{l s='Disable'}" title="{l s='Disable'}" />
            </label>
            <p class="preference_description">{l s='If don\'t want to export all your available products, click "no" and go onto Tab Prestashop to select yours products' mod='lengow'}</p>		
        </div>
        <label>{l s='Export disabled products' mod='lengow'}</label>
        <div class="margin-form">
            <input type="radio"	name="lengow_export_disabled" id="active_on" value="1" {if $lengow_export_disabled == 1} checked="checked"{/if} />
            <label class="t" for="active_on">
                <img src="../img/admin/enabled.gif" alt="{l s='Enable'}" title="{l s='Enable'}" />
            </label>
            <input type="radio"	name="lengow_export_disabled" id="active_off" value="0" {if $lengow_export_disabled == 0} checked="checked"{/if} />
            <label class="t" for="active_off">
                <img src="../img/admin/disabled.gif" alt="{l s='Disable'}" title="{l s='Disable'}" />
            </label>
            <p class="preference_description">{l s='If you want to export disabled products, click "yes".' mod='lengow'}</p>		
        </div>
        <div class="clear"></div>
        <label>{l s='Auto export of new product(s)' mod='lengow'}</label>
        <div class="margin-form">
            <input type="radio"	name="lengow_export_new" id="active_on" value="1" {if $lengow_export_new == 1} checked="checked"{/if} />
            <label class="t" for="active_on">
                <img src="../img/admin/enabled.gif" alt="{l s='Enable'}" title="{l s='Enable'}" />
            </label>
            <input type="radio"	name="lengow_export_new" id="active_off" value="0" {if $lengow_export_new == 0} checked="checked"{/if} />
            <label class="t" for="active_off">
                <img src="../img/admin/disabled.gif" alt="{l s='Disable'}" title="{l s='Disable'}" />
            </label>
            <p class="preference_description">{l s='If you click "yes" your new product(s) will be automatically exported on the next feed'}</p>		
        </div>
        <div class="clear"></div>
        <label>{l s='Export product variations' mod='lengow'}</label>
        <div class="margin-form">
            <input type="radio"	name="lengow_export_all_attributes" id="active_on" value="1" {if $lengow_export_all_attributes == 1} checked="checked"{/if} />
            <label class="t" for="active_on">
                <img src="../img/admin/enabled.gif" alt="{l s='Enable'}" title="{l s='Enable'}" />
            </label>
            <input type="radio"	name="lengow_export_all_attributes" id="active_off" value="0" {if $lengow_export_all_attributes == 0} checked="checked"{/if}/>
            <label class="t" for="active_off">
                <img src="../img/admin/disabled.gif" alt="{l s='Disable'}" title="{l s='Disable'}" />
            </label>
            <p class="preference_description">{l s='If don\'t want to export all your products\' variations, click "no"'}</p>		
        </div>
        <div class="clear"></div>
        <label>{l s='Export product features' mod='lengow'}</label>
        <div class="margin-form">
            <input type="radio"	name="lengow_export_features" id="active_on" value="1" {if $lengow_export_features == 1} checked="checked"{/if} />
            <label class="t" for="active_on">
                <img src="../img/admin/enabled.gif" alt="{l s='Enable'}" title="{l s='Enable'}" />
            </label>
            <input type="radio"	name="lengow_export_features" id="active_off" value="0" {if $lengow_export_features == 0} checked="checked"{/if}/>
            <label class="t" for="active_off">
                <img src="../img/admin/disabled.gif" alt="{l s='Disable'}" title="{l s='Disable'}" />
            </label>
            <p class="preference_description">{l s='If you click "yes", your product(s) will be exported with features.'}</p>		
        </div>
        <div class="clear"></div>
        <label>{l s='Title + attributes + features' mod='lengow'}</label>
        <div class="margin-form">
            <input type="radio"	name="lengow_export_fullname" id="active_on" value="1" {if $lengow_export_fullname == 1} checked="checked"{/if} />
            <label class="t" for="active_on">
                <img src="../img/admin/enabled.gif" alt="{l s='Enable'}" title="{l s='Enable'}" />
            </label>
            <input type="radio"	name="lengow_export_fullname" id="active_off" value="0" {if $lengow_export_fullname == 0} checked="checked"{/if}/>
            <label class="t" for="active_off">
                <img src="../img/admin/disabled.gif" alt="{l s='Disable'}" title="{l s='Disable'}" />
            </label>
            <p class="preference_description">{l s='Select this option if you want a variation product title as title + attributes + feature. By default the title will be the product name'}</p>		
        </div>
        <div class="clear"></div>
        <label>{l s='Number of images to export' mod='lengow'}</label>
        <div class="margin-form">
            <select name="lengow_image_type" class="" id="lengow_image_type">
                {foreach from=$options.images item=option}
                    <option value="{$option.id_image_type}"{if $option.id_image_type == $lengow_image_type} selected="selected"{/if}>{$option.name}</option>
                {/foreach}
            </select>
        </div>
        <div class="clear"></div>
        <label>{l s='Number images to export' mod='lengow'}</label>
        <div class="margin-form">
            <select name="lengow_images_count" class="" id="lengow_images_count">
                {foreach from=$options.images_count item=option}
                    <option value="{$option->id}"{if $option->id == $lengow_images_count} selected="selected"{/if}>{$option->name}</option>
                {/foreach}
            </select>
        </div>
        <div class="clear"></div>
        <label>{l s='Export default format' mod='lengow'}</label>
        <div class="margin-form">
            <select name="lengow_export_format" class="" id="lengow_export_format">
                {foreach from=$options.formats item=option}
                    <option value="{$option->id}"{if $option->id == $lengow_export_format} selected="selected"{/if}>{$option->name}</option>
                {/foreach}
            </select>
        </div>
        <div class="clear"></div>
        <label>{l s='Export in a file' mod='lengow'}</label>
        <div class="margin-form">
            <input type="radio"	name="lengow_export_file"id="active_on" value="1" {if $lengow_export_file}checked="checked"{/if} />
            <label class="t" for="active_on">
                <img src="../img/admin/enabled.gif" alt="{l s='Enable'}" title="{l s='Enable'}" />
            </label>
            <input type="radio"	name="lengow_export_file"id="active_off" value="0" {if $lengow_export_file == 0}checked="checked"{/if}  />
            <label class="t" for="active_off">
                <img src="../img/admin/disabled.gif" alt="{l s='Disable'}" title="{l s='Disable'}" />
            </label> 
            <p class="preference_description">{l s='You should use this option if you have more than 10,000 products' mod='lengow'}{$link_file_export}</p>		
        </div>
        <div class="clear"></div>
        <label>{l s='Fields to export' mod='lengow'}</label>
        <div class="margin-form">
            <select name="lengow_export_fields[]" class="lengow-select" size="15" multiple="multiple">
                {foreach from=$options.export_fields item=field}
                    <option value="{$field->id}"{if $field->id|in_array:$lengow_export_fields} selected="selected"{/if}>{$field->name}</option>
                {/foreach}
            </select>
            <p class="preference_description">{l s='Maintain "control key or command key" to select fields.' mod='lengow'}{$link_file_export}</p>
        </div>
        <div class="clear"></div>
        <label>{l s='Your export script' mod='lengow'}</label>
        <div class="margin-form">
            {$url_feed_export}
        </div>
        <div class="clear"></div>
        <div class="small"><sup>*</sup> {l s='Required field' mod='lengow'}</div>
    </fieldset>
    <br />
    <fieldset id="fieldset_5"> <legend>{l s='Feeds' mod='lengow'}</legend>
        {$lengow_flow}
        <p class="preference_description">{l s='If you use the backoffice of the Lengow module, migrate your feed when you are sure to be ready' mod='lengow'}<br />
            {l s='If you want to use the file export, don\'t use this fonctionality. Please contact Lengow Support Team' mod='lengow'}	
        </p>	
        <div class="clear"></div>
    </fieldset>
    <br />
    <fieldset id="fieldset_6"> <legend>{l s='Import parameters' mod='lengow'}</legend>
        <label>{l s='Status of process orders' mod='lengow'}</label>
        <div class="margin-form">
            <select name="lengow_order_process" class="" id="lengow_order_process">
                {foreach from=$options.states item=option}
                    <option value="{$option.id_order_state}"{if $option.id_order_state == $lengow_order_process} selected="selected"{/if}>{$option.name}</option>
                {/foreach}
            </select>
        </div>
        <div class="clear"></div>
        <label>{l s='Status of shipped orders' mod='lengow'}</label>
        <div class="margin-form">
            <select name="lengow_order_shipped" class="" id="lengow_order_shipped">
                {foreach from=$options.states item=option}
                    <option value="{$option.id_order_state}"{if $option.id_order_state == $lengow_order_shipped} selected="selected"{/if}>{$option.name}</option>
                {/foreach}
            </select>
        </div>
        <div class="clear"></div>
        <label>{l s='Status of cancelled orders' mod='lengow'}</label>
        <div class="margin-form">
            <select name="lengow_order_cancel" class="" id="lengow_order_cancel">
                {foreach from=$options.states item=option}
                    <option value="{$option.id_order_state}"{if $option.id_order_state == $lengow_order_cancel} selected="selected"{/if}>{$option.name}</option>
                {/foreach}
            </select>
        </div>
        <div class="clear"></div>
        <label>{l s='Associated payment method' mod='lengow'}</label>
        <div class="margin-form">
            <select name="lengow_method_name" class="" id="lengow_method_name">
                {foreach from=$options.shippings item=option}
                    <option value="{$option->id}"{if $option->id == $lengow_method_name} selected="selected"{/if}>{$option->name}</option>
                {/foreach}
            </select>
        </div>
        <div class="clear"></div>
        <label>{l s='Default carrier' mod='lengow'}</label>
        <div class="margin-form">
            <select name="lengow_carrier_default" class="" id="lengow_carrier_default">
                {foreach from=$options.carriers item=option}
                    <option value="{$option.id_carrier}"{if $option.id_carrier == $lengow_carrier_default} selected="selected"{/if}>{$option.name}</option>
                {/foreach}
            </select>							
            <p class="preference_description">{l s='Your default carrier' mod='lengow'}</p>		
        </div>
        <div class="clear"></div>
        <label>{l s='Import from x days' mod='lengow'}</label>
        <div class="margin-form">
            <input type="text" name="lengow_import_days" id="lengow_import_days" value="{$lengow_import_days}" class="" size="20" /> <sup>*</sup></div>
        <div class="clear"></div>
        <label>{l s='Forced price'}</label>
        <div class="margin-form">
            <input type="radio"	name="lengow_force_price"id="active_on" value="1" {if $lengow_force_price}checked="checked"{/if} />
            <label class="t" for="active_on">
                <img src="../img/admin/enabled.gif" alt="{l s='Enable'}" title="{l s='Enable'}" />
            </label>
            <input type="radio"	name="lengow_force_price"id="active_off" value="0" {if $lengow_force_price == 0}checked="checked"{/if} />
            <label class="t" for="active_off">
                <img src="../img/admin/disabled.gif" alt="{l s='Disable'}" title="{l s='Disable'}" />
            </label> 
            <p class="preference_description">{l s='This option allows to force the product prices of the marketplace orders during the import' mod='lengow'}</p>		
        </div>
        <div class="clear"></div>
        <label>{l s='Force Products'}</label>
        <div class="margin-form">
            <input type="radio" name="lengow_import_force_product"id="active_on" value="1" {if $lengow_import_force_product}checked="checked"{/if} />
            <label class="t" for="active_on">
                <img src="../img/admin/enabled.gif" alt="{l s='Enable'}" title="{l s='Enable'}" />
            </label>
            <input type="radio" name="lengow_import_force_product"id="active_off" value="0" {if $lengow_import_force_product == 0}checked="checked"{/if} />
            <label class="t" for="active_off">
                <img src="../img/admin/disabled.gif" alt="{l s='Disable'}" title="{l s='Disable'}" />
            </label> 
            <p class="preference_description">{l s='Yes if you want to force import of disabled or out of stock product' mod='lengow'}</p>       
        </div>
        <div class="clear"></div>
        <label>{l s='Import state' mod='lengow'}</label>
        <div class="margin-form">
            {$lengow_is_import}
        </div>
        <div class="clear"></div>
        <label>{l s='Your import script' mod='lengow'}</label>
        <div class="margin-form">
            {$url_feed_import}
        </div>
        <div class="clear"></div>
        <div class="small"><sup>*</sup> {l s='Required field' mod='lengow'}</div>
    </fieldset>
    <br />
    <fieldset id="fieldset_7"> <legend>{l s='Cron' mod='lengow'}</legend>
        {$lengow_cron}
        <div class="clear"></div>
    </fieldset>
    <br />
    <fieldset id="fieldset_7"> <legend>{l s='Developer' mod='lengow'}</legend>
        <label>{l s='Debug mode' mod='lengow'}</label>
        <div class="margin-form">
            <input type="radio"	name="lengow_debug"id="active_on" value="1" {if $lengow_debug}checked="checked"{/if} />
            <label class="t" for="active_on">
                <img src="../img/admin/enabled.gif" alt="{l s='Enable'}" title="{l s='Enable'}" />
            </label>
            <input type="radio"	name="lengow_debug"id="active_off" value="0" {if $lengow_debug == 0}checked="checked"{/if} />
            <label class="t" for="active_off">
                <img src="../img/admin/disabled.gif" alt="{l s='Disable'}" title="{l s='Disable'}" />
            </label> 
        </div>
        <div class=:"clear"></div>
        <label>{l s='Logs' mod='lengow'}</label>
        <div class="margin-form">
            {$log_files}
        </div>
        <div class="margin-form">
            <input type="submit" id="_form_submit_btn" value="{l s='Save' mod='lengow'}" name="submitlengow" class="button" />
        </div>
        <div class="clear"></div>
        <div class="small"><sup>*</sup>{l s='Required field' mod='lengow'}</div>
    </fieldset>
</form>