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
});