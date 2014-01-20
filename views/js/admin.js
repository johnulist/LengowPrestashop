;$(document).ready(function() {
	// Update one flow’
	$('.lengow-migrate-action').each(function(i) {
		$(this).click(function() {
			var id_flow = $(this).data('flow'),
				params = '&idFlow=' + id_flow + 
				         '&format=' + $('#format-' + id_flow).val() + 
				         '&mode=' + $('#mode-' + id_flow).val() + 
				         '&all=' + $('#all-' + id_flow).val() + 
				         '&cur=' + $('#currency-' + id_flow + ' option:selected').attr('id') + 
				         '&shop=' + $('#shop-' + id_flow + ' option:selected').attr('id') + 
				         '&lang=' + $('#lang-' + id_flow + ' option:selected').attr('id');console.log($(this).data('url') + params);
			$.getJSON($(this).data('url') + params, function(json) {
			    	if(json.return) {
			    		$('#lengow-flux-' + id_flow).html(json.flow);
			    	}
			  	});
		    return false;
		});
	});
	// Update all flow
	$('.lengow-migrate-action-all').each(function(i) {
		$(this).click(function() {
			var id_flow = $(this).data('flow'),
				params = '&format=' + $('#format-' + id_flow).val() + 
				         '&mode=' + $('#mode-' + id_flow).val() + 
				         '&all=' + $('#all-' + id_flow).val() + 
				         '&cur=' + $('#currency-' + id_flow + ' option:selected').attr('id') + 
				         '&shop=' + $('#shop-' + id_flow + ' option:selected').attr('id') + 
				         '&lang=' + $('#lang-' + id_flow + ' option:selected').attr('id');
			$.getJSON($(this).data('url') + params, function(json) {
			    	if(json.return) {
			    		$('.lengow-flux').each(function(i) {
			    			$(this).html(json.flow);
			    		});
			    	}
			  	});
		    return false;
		});
	});
	// Reimport Order
	$('#reimport-order').click(function(e){
		var url = $(this).data('url');
		var orderid = $(this).data('orderid');
		var lengoworderid = $(this).data('lengoworderid');
		var version = $(this).data('version');

		var datas = {};
		datas['url'] = url;
		datas['orderid'] = orderid;
		datas['lengoworderid'] = lengoworderid;
		if(version < '1.5')
			datas['action'] = 'reimport_order';

		// Show loading div
		$('#ajax_running').fadeIn(300);
		$.getJSON(url, datas, function(data) {
			$('#ajax_running').fadeOut(0);
			if(data.status == 'success') {
				window.location.replace(data.new_order_url);
			} else {
				alert(data.msg);
			}
			
		});
		return false;
	});
});