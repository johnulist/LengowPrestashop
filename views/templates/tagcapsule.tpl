<!-- Tag_Lengow -->
{if $page_type == 'confirmation'}
	
{/if}

{if $page_type == 'confirmation'}
	<!-- Tag_Lengow -->
	<script type="text/javascript">
	var page = 'payment';  // #TYPE DE PAGE#
	var order_amt = '{$order_total}'; // #MONTANT COMMANDE#
	var order_id = '{$id_order}'; // #ID COMMANDE#
	var product_ids = '{$ids_products}'; // #ID PRODUCT#
	var basket_products = '{$ids_products_cart}'; // #LISTING PRODUCTS IN BASKET#
	var ssl = '{$use_ssl}';
	var id_categorie = '{$id_category}'; // #ID CATEGORIE EN COURS#
	</script>
	<script type="text/javascript" src="https://tracking.lengow.com/tagcapsule.js?lengow_id={$id_customer}&idGroup={$id_group}"></script>
	<div id="tagcapsule2"></div>
	<script type="text/javascript">
	setTimeout(
		function() {
			var script = document.createElement('script');
			script.src = "https://tracking.lengow.com/tagcapsule.js?lengow_id={$id_customer}&idGroup={$id_group}";
			page = "payment";
			document.getElementById('tagcapsule2').appendChild(script);
		}, 2000);
	</script>
{else}
	<script type="text/javascript">
	var page = '{$page_type}';  // #TYPE DE PAGE#
	var order_amt = '{$order_total}'; // #MONTANT COMMANDE#
	var order_id = '{$id_order}'; // #ID COMMANDE#
	var product_ids = '{$ids_products}'; // #ID PRODUCT#
	var basket_products = '{$ids_products_cart}'; // #LISTING PRODUCTS IN BASKET#
	var ssl = '{$use_ssl}';
	var id_categorie = '{$id_category}'; // #ID CATEGORIE EN COURS#
	</script>
	<script type="text/javascript" src="https://tracking.lengow.com/tagcapsule.js?lengow_id={$id_customer}&idGroup={$id_group}"></script>
{/if}
<!-- /Tag_Lengow -->