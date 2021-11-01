function replace_html(div_id_to, select_id_from, path)
{
	s_value=$(select_id_from).val();

	$.get( path+'&id='+ s_value, function( data ) {
		$( div_id_to ).html( data );
	});
}

function new_value(path, elem,color_update)
{
	$.get(path, function(data){
		elem.value=data;
		document.getElementById('#'+color_update).style.backgroundColor=data;
	});
}