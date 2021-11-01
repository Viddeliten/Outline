<?php 
function string_remove_dangerous_ch($str)
{
	$rstr=$str;
	$rstr=str_seplace("'","",$rstr);
	$rstr=str_seplace("\\","",$rstr);
	return $rstr;
}
?>