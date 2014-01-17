<br />
<fieldset>
	<legend><img src="../img/admin/tab-stats.gif" /> {l s='Import Lengow'}</legend>
	{if $reimport_message != ''}
		<div class="warn">
			<span style="float:right">
				<a id="hideWarn" href=""><img alt="X" src="../img/admin/close.png"></a>
			</span>
			<ul style="margin-top: 3px">
				<li>{$reimport_message}</li>
			</ul>
		</div>
	{/if}
	<h4>{l s='This order has been imported from Lengow'}</h4>
	<ul>
		<li>{l s='Lengow order ID'} : <strong>{$id_order_lengow}</strong></li>
		<li>{l s='Feed ID'} : <strong>{$id_flux}</strong></li>
		<li>{l s='Marketplace'} : <strong>{$marketplace}</strong></li>
		<li>{l s='Total amount paid on Marketplace'} : <strong>{$total_paid}</strong></li>
		<li>{l s='Carrier from marketplace'} : <strong>{$carrier}</strong></li>
		<li>{l s='Message'} : <strong>{$message}</strong></li>
	</ul>
	<br />
	<div class"button-command-prev-next">
		<button id="reimport-order" class="button" data-url="{$action_reimport}" data-orderid="{$order_id}" data-lengoworderid="{$id_order_lengow}">{l s='Cancel and re-import order'}</button>
		<a class="button" href="{$action_synchronize}">{l s='Synchronize ID'}</a>
	</div>
</fieldset>