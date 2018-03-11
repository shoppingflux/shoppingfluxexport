<form method="post" action="{$postUri}">
	<div>
		<label>{l s='Activate Multitoken Mode'} :</label>
		<input type="checkbox" id="sfMultitokenActivation" name="SHOPPING_FLUX_MULTITOKEN" value="1" {if $sfMultitokenActivation==1}checked="checked"{/if} {if $sfMultitokenActivation !=1}disabled="DISABLED"{/if} />
		<br /><p></p>
	</div>
	
	<div class="sf_multitokenlist" style="display:{if $sfMultitokenActivation==1}block{else}none{/if};clear: both;">
	{foreach from=$token_tree item=shop name=shop}
		<fieldset>
	    	<legend>{l s='Shop'} : {$shop['name']}</legend>
	    	<label>{l s='General'} : </label>
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
	<p style="margin-top:20px"><input type="submit" value="{l s='Update'}" name="rec_config" class="button"/></p>
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