<form method="post" action="{$postUri}">
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
	<p style="margin-top:20px"><input type="submit" value="{l s='Update'}" name="rec_config" class="button"/></p>
</form>
