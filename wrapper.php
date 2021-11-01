<?php

include("db.php");

if(isset($_GET['tick']))
{
	require_once("functions/tick/func.php");
	if(isset($_GET['type']))
	{
		switch($_GET['type'])
		{
			case "location":
			case "person":
			case "event":
				outline_userop("tick_update", array($_GET['tick'], $_GET['type'], $_GET['id'], TRUE));
				break;
		}
	}
}


?>