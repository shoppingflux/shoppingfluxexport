{*
 * 2007-2019 PrestaShop SA and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2019 PrestaShop SA and Contributors
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *
*}
<form method="post" action="{$postUri}">
	<div>
		<label>{l s='Activate Multitoken Mode' mod='shoppingfluxexport'} :</label>
		<input type="checkbox" id="sfMultitokenActivation" name="SHOPPING_FLUX_MULTITOKEN" value="1" {if $sfMultitokenActivation==1}checked="checked"{/if} {if $sfMultitokenActivation !=1}disabled="DISABLED"{/if} />
		<br /><p></p>
	</div>
	
	<div class="sf_multitokenlist" style="display:{if $sfMultitokenActivation==1}block{else}none{/if};clear: both;">
	{foreach from=$token_tree item=shop name=shop}
		<fieldset>
	    	<legend>{l s='Shop' mod='shoppingfluxexport'} : {$shop['name']}</legend>
	    	<label>{l s='General' mod='shoppingfluxexport'} : </label>
			<span>
				<input type="text" size=40 name="token_{$shop['id_shop']}" value="{$shop['token']}" />
			</span>
			
	    	{foreach from=$shop['values'] item=value name=value}
				
				<p style="clear: both">
					<label>{$value['name']} : </label>
					<span>
						<input type="text" size=40 name="token_{$shop['id_shop']}_{$value['id']}" value="{$value['token']}" />
					</span>
				</p>
			{/foreach}
		</fieldset>
	{/foreach}
	</div>
	<p style="margin-top:20px"><input type="submit" value="{l s='Update' mod='shoppingfluxexport'}" name="rec_config" class="button"/></p>
</form>
{literal}
<script>
$(document).ready(function(){
    $("#sfMultitokenActivation").click(function(){
        $(".sf_multitokenlist").slideToggle();
    });
});
</script>
{/literal}