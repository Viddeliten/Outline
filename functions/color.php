<?php

function color_string_to_color($str)
{
	if(is_numeric($str))
		return $str;
		
	//returns int
	$str=strtolower(trim($str));
	switch($str)
	{
		case "red":
			$hex="FF0000";
			break;
		case "green":
			$hex="00FF00";
			break;
		case "blue":
			$hex="0000FF";
			break;
		case "purple":
			$hex="6600CC";
			break;
		case "brown":
			$hex="663300";
			break;
		case "white":
			$hex="FFFFFF";
			break;
		case "black":
			$hex="000000";
			break;
		case "yellow":
			$hex="FFFF00";
			break;
		case "blond":
			$hex="FFF5B2";
			break;
		case "orange":
			$hex="FF9900";
			break;
		case "pink":
			$hex="FF66FF";
			break;
		case "grey":
			$hex="999999";
			break;
		default:
			$hex=$str;
	}
	return hexdec($hex);
}

function color_int_to_colorhex($int)
{
	$red=round($int/(256*256));
	$green=round(($int%(256*256))/256);
	$blue=$int%256;
	if($red<0x10)
		$r_str="0".dechex($red);
	else
		$r_str="$red";
	if($green<0x10)
		$g_str="0".dechex($green);
	else
		$g_str=dechex($green);
	if($blue<0x10)
		$b_str="0".dechex($blue);
	else
		$b_str=dechex($blue);
	return $r_str."-".$g_str."-".$b_str;
}

?>