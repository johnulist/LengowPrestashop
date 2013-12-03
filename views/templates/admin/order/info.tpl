<br />
<fieldset>
	<legend><img src="../img/admin/tab-stats.gif" /> {l s='Import Lengow'}</legend>
	<h4>{l s='This order has been imported from Lengow'}</h4>
	<ul>
		<li>{l s='Lengow order ID'} : <strong>{$id_order_lengow}</strong></li>
		<li>{l s='Feed ID'} : <strong>{$id_flux}</strong></li>
		<li>{l s='Marketplace'} : <strong>{$marketplace}</strong></li>
		<li>{l s='Total amount paid on Marketplace'} : <strong>{$total_paid}</strong></li>
		{if $tracking_method}
			<li>{l s='Tracking method'} : <strong>{$tracking_method}</strong></li>
		{/if}
		{if $tracking_carrier}
			<li>{l s='Tracking carrier'} : <strong>{$tracking_carrier}</strong></li>
		{/if}
		<li>{l s='Carrier from marketplace'} : <strong>{$carrier}</strong></li>
		<li>{l s='Message'} : <strong>{$message}</strong></li>
	</ul>
	<!--<a href="{$action_reimport}">{l s='Cancel and re-import order'}</a>-->
</fieldset>