<div class="row">
<div class="col-lg-12">
	<div class="panel">
		<div class="panel-heading">
			<i class="icon-shopping-cart"></i>
			{l s='This order has been imported from Lengow'}
		</div>
		<div class="well">
			<ul>
				<li>{l s='Lengow order ID'} : <strong>{$id_order_lengow}</strong></li>
				<li>{l s='Feed ID'} : <strong>{$id_flux}</strong></li>
				<li>{l s='Marketplace'} : <strong>{$marketplace}</strong></li>
				<li>{l s='Total amount paid on Marketplace'} : <strong>{$total_paid}</strong></li>
				<li>{l s='Carrier from marketplace'} : <strong>{$carrier}</strong></li>
				<li>{l s='Message'} : <strong>{$message}</strong></li>
			</ul>
		</div>
		<div class="btn-group">
			<button id="reimport-order" class="btn btn-default" data-url="{$action_reimport}" data-orderid="{$order_id}" data-lengoworderid="{$id_order_lengow}" data-feedid="{$id_flux}" data-version='{$version}'>{l s='Cancel and re-import order'}</button>
			<a class="btn btn-default" href="{$action_synchronize}">{l s='Synchronize ID'}</a>
		</div>
	</div>
	{if $add_script == true}
	<script type="text/javascript" src="{$url_script}"></script>
	{/if}
</div>
</div>