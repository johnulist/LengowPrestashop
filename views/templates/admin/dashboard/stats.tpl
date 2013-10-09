<script type="text/javascript">
;$(document).ready(function() {
	function lengowLoadStats(key) {
		var ctx = $("#lengow-stats").get(0).getContext("2d");
		var data = {
			labels : data_stats[key].evolution_libelle ,
			datasets : [
				{
					fillColor : "rgba(151,187,205,0.5)",
					strokeColor : "rgba(151,187,205,1)",
					pointColor : "rgba(151,187,205,1)",
					pointStrokeColor : "#fff",
					data : data_stats[key].evolution_values
				}
			]
		};
		new Chart(ctx).Line(data);
	}
	// admin dashboard
	$('#table-feeds').hide();
	$('#lengow-info').hide();
	var lengowAPI = 'https://solution.lengow.com/routine/PrestaShop/dashboard_plugin_v2.php?token={$token}&idClient={$id_customer}&idGroup={$id_group}&callback=?' ,
	    table_feeds = '' ,
	    select = '',
	    data_stats = {};
	$.getJSON(lengowAPI, function(json) { 
		if(json.return == 'ok') {
			data_stats = json.stats;
			$('#lengow-load').hide();
    		for(key in json.feeds) {
    			table_feeds += '<tr>'
    			             + '<td>' + json.feeds[key].id + '</td>'
    			             + '<td>' + json.feeds[key].type + '</td>'
    			             + '<td>' + json.feeds[key].diffuseur + '</td>'
    			             + '<td>' + json.feeds[key].nom + '</td>'
    			             + '<td>' + json.feeds[key].nbProduit + '</td>'
    			             + '<td>' + json.feeds[key].nbProduitActif + '</td>'
    			             + '</th>';
    		}
    		select = '<select name="lengow-change" id="lengow-change">';
    		for(key in json.stats) {
    			select += '<option value="' + key + '">' + json.stats[key].name + '</option>';
    		}
    		select += '</select>';
    		$('#table-feeds tbody').html(table_feeds); 
			$('#table-feeds').show();
			$('#lengow-info').show();
			$('#lengow-change-select').html(select);
			$('#lengow-root').html('<canvas id="lengow-stats" width="587" height="400"></canvas>');
			$('#lengow-change').change(function() {
				var selected = $('#lengow-change').val();
				lengowLoadStats(selected);
			});
			lengowLoadStats(0);
		}
	});
});
</script>
<br />
<div id="lengow-load">
	{l s='Loading Lengow dashboard...'}
</div>
<div id="lengow-info">
	<h5>{l s='Dashboard Lengow'} <div id="lengow-change-select"></div></h5>
	<div id="lengow-root"></div>
</div>
<br />
<table id="table-feeds">
	<thead>
		<tr>
			<th><span>{l s='ID'}</span></th>
			<th><span>{l s='Type'}</span></th>
			<th><span>{l s='Supplier'}</span></th>
			<th><span>{l s='Name'}</span></th>
			<th><span>{l s='Products'}</span></th>
			<th><span>{l s='Enable\'s products'}</span></th>
		</tr>
	</thead>
	<tbody></tbody>
</table>